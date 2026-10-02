<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'conexao.php';

$acao = isset($_GET['acao']) ? $_GET['acao'] : '';

// 📥 GUARDAR NOVA MENSAGEM (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $inputRaw = file_get_contents("php://input");
    $dados = json_decode($inputRaw, true);

    if ($dados) {
        $id_escolar = trim($dados['id_unico_escolar']);
        $remetente  = trim($dados['remetente']);
        $mensagem   = trim($dados['mensagem']);
        $perfil     = isset($dados['perfil']) ? trim($dados['perfil']) : 'estudante';

        if (!empty($mensagem)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO chat_mensagens (id_unico_escolar, remetente, mensagem, perfil) VALUES (?, ?, ?, ?)");
                $sucesso = $stmt->execute([$id_escolar, $remetente, $mensagem, $perfil]);
                echo json_encode(['sucesso' => $sucesso]);
            } catch (Exception $e) {
                echo json_encode(['sucesso' => false, 'mensagem' => $e->getMessage()]);
            }
            exit;
        }
    }
}

// 🔍 CARREGAR MENSAGENS (GET)
if ($acao === 'carregar') {
    try {
        $stmt = $pdo->query("SELECT * FROM chat_mensagens ORDER BY id ASC LIMIT 100");
        $mensagens = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['sucesso' => true, 'mensagens' => $mensagens], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        echo json_encode(['sucesso' => false, 'mensagem' => $e->getMessage()]);
    }
    exit;
}
?>