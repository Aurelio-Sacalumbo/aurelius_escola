<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'conexao.php';

// 📥 Captura o Payload JSON enviado pela função executarSalvamentoMatricula
$inputRaw = file_get_contents("php://input");
$dados = json_decode($inputRaw, true);

if (!$dados) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Nenhum dado recebido no servidor.']);
    exit;
}

// 📦 Mapeamento das variáveis vindas do formulário do António Sacabi
$nome        = isset($dados['nome']) ? trim($dados['nome']) : '';
$telefone    = isset($dados['telefone']) ? trim($dados['telefone']) : '';
$periodo     = isset($dados['periodo']) ? trim($dados['periodo']) : '';
$classe      = isset($dados['classe']) ? trim($dados['classe']) : '';
$disciplinas = isset($dados['disciplinas']) ? trim($dados['disciplinas']) : '';

// 🌟 CONSTRUTOR CIRÚRGICO: Cria o formato exato esperado pela Pauta (ex: "13ª Classe (Técnico) [Matemática Aplicada, ...]")
$cursoCompleto = $classe . " [" . $disciplinas . "]";

if (empty($telefone) || empty($nome)) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Campos obrigatórios em falta (Nome ou Telefone).']);
    exit;
}

try {
    // 🔍 1. Verifica se o aluno já possui uma linha ativa de inscrição
    $check = $pdo->prepare("SELECT id_utilizador, id_unico_escolar, senha, email FROM utilizadores WHERE telefone = ? AND nivel = 'estudante' LIMIT 1");
    $check->execute([$telefone]);
    $aluno = $check->fetch(PDO::FETCH_ASSOC);

    if ($aluno) {
        // 🔒 PRESERVAÇÃO: Recupera as credenciais geradas no unitel.php para não trancar o login
        $id_unico = !empty($aluno['id_unico_escolar']) ? $aluno['id_unico_escolar'] : "AUR-" . rand(100000, 999999);
        
        if (!empty($aluno['senha'])) {
            $senha_preservada = $aluno['senha'];
        } elseif (!empty($aluno['email'])) {
            $senha_preservada = $aluno['email'];
        } else {
            $senha_preservada = rand(100000, 999999);
        }

        // 🌟 UPDATE SEGURO: Salva a matrícula sem duplicar ou corromper colunas
        $stmt = $pdo->prepare("UPDATE utilizadores SET id_unico_escolar = ?, curso = ?, periodo = ?, senha = ?, email = ?, nome = ?, nivel = 'estudante' WHERE id_utilizador = ?");
        $sucesso = $stmt->execute([$id_unico, $cursoCompleto, $periodo, $senha_preservada, $senha_preservada, $nome, $aluno['id_utilizador']]);

        echo json_encode([
            'sucesso' => $sucesso,
            'id_unico' => $id_unico,
            'senha_gerada' => $senha_preservada,
            'mensagem' => '🎉 Matrícula sincronizada com sucesso e credenciais preservadas!'
        ], JSON_UNESCAPED_UNICODE);
    } else {
        // 🆕 2. FALLBACK: Se o aluno tentar matricular-se diretamente sem inscrição prévia
        $id_novo = "AUR-" . rand(100000, 999999);
        $senha_nova = rand(100000, 999999);

        $stmt = $pdo->prepare("INSERT INTO utilizadores (nome, telefone, senha, email, nivel, id_unico_escolar, curso, periodo, saldo_propina) VALUES (?, ?, ?, ?, 'estudante', ?, ?, ?, 0)");
        $sucesso = $stmt->execute([$nome, $telefone, $senha_nova, $senha_nova, $id_novo, $cursoCompleto, $periodo]);

        echo json_encode([
            'sucesso' => $sucesso,
            'id_unico' => $id_novo,
            'senha_gerada' => $senha_nova,
            'mensagem' => '🎉 Novo aluno inserido e matriculado com sucesso!'
        ], JSON_UNESCAPED_UNICODE);
    }
} catch (Exception $e) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro crítico MySQL: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
exit;
?>