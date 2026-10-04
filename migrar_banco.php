<?php
// 🚀 MOTOR DE MIGRAÇÃO INTELIGENTE DE ALUNOS (LOCAL -> NUVEM) - ACADEMIA AURÉLIUS
header('Content-Type: text/html; charset=utf-8');
set_time_limit(0); // Impede que o script vá abaixo se tiver muitos alunos

// 1️⃣ Configurações do Banco de Dados LOCAL (Origem)
\$host_local = "127.0.0.1";
\$user_local = "root";
\$pass_local = ""; // Deixe a sua senha do XAMPP aqui
\$db_local   = "aurelius_escola";

// 2️⃣ Configurações do Banco de Dados na NUVEM (Destino - Aiven)
\$host_nuvem = "mysql-1a34c184-aureliosacalumbo42-bf60.a.aivencloud.com";
\$port_nuvem = "22002";
\$user_nuvem = "avnadmin";
\$pass_nuvem = "AVNS_6AyaHMtSplThuvy6uGm";
\$db_nuvem   = "aurelius_escola"; // 🌟 Correção: Nome unificado da base de dados

try {
    // Conexão com o Banco Local
    \$pdo_local = new PDO("mysql:host=\$host_local;dbname=\$db_local;charset=utf8mb4", \$user_local, \$pass_local, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo "<h3>🔌 Conectado ao Localhost (Origem)...</h3>";

    // Conexão com a Nuvem (Aiven)
    \$options_nuvem = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
        PDO::MYSQL_ATTR_SSL_CA => true,
        PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false
    ];
    
    // 🌟 Correção Crítica: Adicionados os modificadores '\$' nas variáveis de conexão
    \$pdo_nuvem = new PDO("mysql:host=\$host_nuvem;port=\$port_nuvem;dbname=\$db_nuvem;charset=utf8mb4", \$user_nuvem, \$pass_nuvem, \$options_nuvem);
    echo "<h3 style='color:green;'>☁️ Conectado à Aiven Cloud (Destino)...</h3>";

    // 🏗️ Garantir que a tabela existe na nuvem
    \$sqlTabela = "CREATE TABLE IF NOT EXISTS `utilizadores` (
        `id_utilizador` INT AUTO_INCREMENT PRIMARY KEY,
        `nome` VARCHAR(255) NOT NULL,
        `telefone` VARCHAR(50) NOT NULL,
        `email` VARCHAR(255) NULL,
        `senha` VARCHAR(255) NOT NULL,
        `nivel` VARCHAR(50) NOT NULL DEFAULT 'estudante',
        `id_unico_escolar` VARCHAR(100) NULL,
        `curso` TEXT NULL,
        `periodo` VARCHAR(100) NULL,
        `saldo_propina` DECIMAL(10,2) NOT NULL DEFAULT 0.00
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    \$pdo_nuvem->exec(\$sqlTabela);

    // 3️⃣ Buscar TODOS os alunos que estão guardados no seu computador
    \$stmt_local = \$pdo_local->query("SELECT * FROM utilizadores");
    \$alunos_locais = \$stmt_local->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Encontrados <b>" . count(\$alunos_locais) . "</b> registos locais para processar.</p>";

    // Preparar a query de inserção na nuvem de forma segura (Previne duplicados com a Madalena)
    \$stmt_nuvem_ins = \$pdo_nuvem->prepare("INSERT INTO utilizadores 
        (nome, telefone, email, senha, nivel, id_unico_escolar, curso, periodo, saldo_propina) 
        VALUES (:nome, :telefone, :email, :senha, :nivel, :id_unico_escolar, :curso, :periodo, :saldo_propina)
        ON DUPLICATE KEY UPDATE 
            nome = VALUES(nome), 
            senha = VALUES(senha),
            id_unico_escolar = IFNULL(id_unico_escolar, VALUES(id_unico_escolar))");

    \$inseridos = 0;
    \$atualizados = 0;

    foreach (\$alunos_locais as \$aluno) {
        // Verifica se o aluno já existe na Nuvem pelo telefone ou pelo número de inscrição escolar
        \$check = \$pdo_nuvem->prepare("SELECT id_utilizador FROM utilizadores WHERE telefone = ? OR (id_unico_escolar = ? AND id_unico_escolar IS NOT NULL)");
        \$check->execute([\$aluno['telefone'], \$aluno['id_unico_escolar'] ?? '']);
        
        if (!\$check->fetch()) {
            // Se não existir, insere um novo aluno
            \$stmt_nuvem_ins->execute([
                ':nome'             => \$aluno['nome'],
                ':telefone'         => \$aluno['telefone'],
                ':email'            => \$aluno['email'] ?? null,
                ':senha'            => \$aluno['senha'],
                ':nivel'            => \$aluno['nivel'] ?? 'estudante',
                ':id_unico_escolar' => \$aluno['id_unico_escolar'] ?? null,
                ':curso'            => \$aluno['curso'] ?? null,
                ':periodo'          => \$aluno['periodo'] ?? null,
                ':saldo_propina'    => \$aluno['saldo_propina'] ?? 0.00
            ]);
            echo "<p style='color:blue;'>🔹 Aluno <b>{\$aluno['nome']}</b> migrado com sucesso para a Nuvem.</p>";
            \$inseridos++;
        } else {
            echo "<p style='color:orange;'>🔸 Aluno <b>{\$aluno['nome']}</b> já existe no Render. Dados mantidos e protegidos.</p>";
            \$atualizados++;
        }
    }

    echo "<h2 style='color:green;'>🎉 Sincronização Concluída com Sucesso!</h2>";
    echo "<p><b>Novos alunos na nuvem:</b> \$inseridos | <b>Alunos protegidos/já existentes:</b> \$atualizados</p>";

} catch (PDOException \$e) {
    die("<h3 style='color:red;'>❌ Erro de Migração: " . \$e->getMessage() . "</h3>");
}
?>