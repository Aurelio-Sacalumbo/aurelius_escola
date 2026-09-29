<?php
// 🗄️ MOTOR DE SINCRONIZAÇÃO DO CHAT ACADÉMICO - ACADEMIA AURÉLIUS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'conexao.php';

$resposta = ['sucesso' => false];
$metodo = $_SERVER['REQUEST_METHOD'];

// 📥 SE FOR POST: Grava a nova mensagem enviada
if ($metodo === 'POST') {
    $remetente = isset($_POST['remetente']) ? trim($_POST['remetente']) : '';
    $id_estudante = isset($_POST['id_estudante']) ? trim($_POST['id_estudante']) : '';
    $conteudo = isset($_POST['conteudo']) ? trim($_POST['conteudo']) : '';

    if (empty($id_estudante) || empty($conteudo) || empty($remetente)) {
        $resposta['mensagem'] = '⚠️ Campos incompletos para envio.';
        echo json_encode($resposta);
        exit;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO mensagens_chat (remetente, id_estudante, conteudo) VALUES (?, ?, ?)");
        $sucesso = $stmt->execute([$remetente, $id_estudante, $conteudo]);
        $resposta['sucesso'] = $sucesso;
    } catch (Exception $e) {
        $resposta['mensagem'] = $e->getMessage();
    }
    echo json_encode($resposta);
    exit;
}

// 📤 SE FOR GET: Lista as mensagens daquele estudante específico para sincronização
if ($metodo === 'GET') {
    $id_estudante = isset($_GET['id_estudante']) ? trim($_GET['id_estudante']) : '';

    if (empty($id_estudante)) {
        $resposta['mensagem'] = '⚠️ ID Estudante em falta.';
        echo json_encode($resposta);
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT remetente, conteudo, DATE_FORMAT(data_envio, '%H:%i') as hora FROM mensagens_chat WHERE id_estudante = ? ORDER BY id_mensagem ASC");
        $stmt->execute([$id_estudante]);
        $resposta['mensagens'] = $stmt->fetchAll();
        $resposta['sucesso'] = true;
    } catch (Exception $e) {
        $resposta['mensagem'] = $e->getMessage();
    }
    echo json_encode($resposta);
    exit;
}
