<?php
// 🚀 MOTOR DE MIGRAÇÃO AUTOMÁTICA DE TABELAS - ACADEMIA AURÉLIUS
header('Content-Type: text/html; charset=utf-8');

// Chaves explícitas para conectar diretamente à Aiven Cloud na Nuvem
\$host = "mysql-1a34c184-aureliosacalumbo42-bf60.a.aivencloud.com";
\$port = "22002";
\$user = "avnadmin";
\$password = "AVNS_6AyaHMtSplThuvy6uGm";
\$dbname = "defaultdb";

try {
    \$options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
        PDO::MYSQL_ATTR_SSL_CA => true,
        PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false
    ];

    echo "<h3>🔄 Conectando à Nuvem da Aiven...</h3>";
    \$pdo = new PDO("mysql:host=host;port=port;dbname=dbname;charset=utf8mb4", user, password, options);
    echo "<p style='color:green;'>✔️ Conexão estabelecida com sucesso!</p>";

    // 🏗️ 1. CRIAÇÃO AUTOMÁTICA DA TABELA UTILIZADORES
    echo "<h3>🏗️ Estruturando a Tabela 'utilizadores' no MySQL Remoto...</h3>";
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
    
    pdo->exec(sqlTabela);
    echo "<p style='color:green;'>✔️ Tabela criada ou validada com sucesso!</p>";

    // 👥 2. POPULAÇÃO AUTOMÁTICA COM OS ALUNOS CRÍTICOS (Moma, Sabina, Margarida)
    echo "<h3>👥 Injetando Alunos de Teste Homologados...</h3>";
    
    \$alunosParaMigrar = [
        ['Margarida Cassinda', '925347372', 'aureliosacalumbo42@gmail.com', '123456', 'estudante', 'MAT-2026-01', '8ª Classe (I Ciclo) [Língua Portuguesa, Matemática]', 'Manhã', 0.00],
        ['Moma', '900112233', '900112233@aurelius.com', '123456', 'estudante', 'AUR-916717', NULL, 'Tarde', 36008.00],
        ['Sabina Bongo (Enc: Mário Tatiana)', '925347370', '928524', '928524', 'estudante', 'AUR-799922', NULL, 'Noite (18h - 21h)', 0.00],
        ['Moma (Enc: Martinha)', '900112233', '438252', '438252', 'estudante', 'AUR-566603', NULL, 'Manhã | Longonjo (Goia)', 0.00]
    ];

    stmt = pdo->prepare("INSERT INTO utilizadores (nome, telefone, email, senha, nivel, id_unico_escolar, curso, periodo, saldo_propina) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

    foreach (alunosParaMigrar as aluno) {
        // Verifica se o aluno já está inserido pelo telefone para não duplicar
        check = pdo->prepare("SELECT id_utilizador FROM utilizadores WHERE telefone = ? AND nome = ?");
        \$check->execute([\(aluno[1],\)aluno[0]]);
        if (!\$check->fetch()) {
            stmt->execute(aluno);
            echo "<p>🔹 Aluno <b>{\$aluno[0]}</b> inserido na nuvem.</p>";
        } else {
            echo "<p style='color:orange;'>🔸 Aluno {\$aluno[0]} já existia na Aiven. Pulado.</p>";
        }
    }

    echo "<h2 style='color:green;'>🎉 Processo Concluído! O seu Banco de Dados Remoto está pronto para Operar!</h2>";
    echo "<p>Pode apagar este ficheiro do seu servidor local por questões de segurança.</p>";

} catch (PDOException \$e) {
    die("<h3 style='color:red;'>❌ Erro Fatal de Migração: " . \$e->getMessage() . "</h3>");
}
?>
