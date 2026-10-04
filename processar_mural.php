<?php
// 📰 MOTOR DO MURAL DE DIRETRIZES METODOLÓGICAS — ACADEMIA AURÉLIUS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'conexao.php';

// 🔍 ROTA GET: O ALUNO EXTRATA AS DICAS DIRETAMENTE DA TABELA MURALDICAS
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $stmt = $pdo->query("SELECT titulo, texto FROM MuralDicas ORDER BY id_dica ASC");
        $dicas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['sucesso' => true, 'mural' => $dicas], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        echo json_encode(['sucesso' => false, 'mural' => [], 'mensagem' => $e->getMessage()]);
    }
    exit;
}

// =========================================================================
// 📥 ROTA POST: O PROFESSOR INJETA A DIRETRIZ EM JSON
// =========================================================================
$inputRaw = file_get_contents('php://input');
$dadosJson = json_decode($inputRaw, true);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($dadosJson)) {
    $acao = isset($dadosJson['acao_mural']) ? trim($dadosJson['acao_mural']) : '';

    if ($acao === 'publicar_orientacao') {
        $titulo = isset($dadosJson['titulo']) ? trim($dadosJson['titulo']) : '';
        $texto  = isset($dadosJson['conteudo']) ? trim($dadosJson['conteudo']) : '';

        if (empty($titulo) || empty($texto)) {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Título ou Explicação detalhada em falta.']);
            exit;
        }

        try {
            // Gravação cirúrgica na tabela limpa MuralDicas
            $stmt = $pdo->prepare("INSERT INTO MuralDicas (titulo, texto, autor) VALUES (?, ?, 'Prof. Aurélio')");
            $sucesso = $stmt->execute([$titulo, $texto]);

            echo json_encode(['sucesso' => $sucesso, 'mensagem' => '🎉 Publicado com sucesso!']);
        } catch (Exception $e) {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Erro no MySQL remoto: ' . $e->getMessage()]);
        }
        exit;
    }
}

echo json_encode(['sucesso' => false, 'mensagem' => 'Ação inválida no motor metodológico.']);
exit;
?>