<?php
// 📊 GRAVADOR DE NOTAS CENTRALIZADO - ACADEMIA AURÉLIUS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'conexao.php';

$resposta = ['sucesso' => false, 'mensagem' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recebe o JSON com o array de notas enviado pelo JavaScript
    $dadosJson = file_get_contents('php://input');
    $dados = json_decode($dadosJson, true);

    if (empty($dados) || !is_array($dados)) {
        $resposta['mensagem'] = '⚠️ Nenhuma nota foi enviada para processamento.';
        echo json_encode($resposta);
        exit;
    }

    try {
        $pdo->beginTransaction(); // Inicia transação segura

        // Prepara a query de atualização das notas usando o mapeamento das suas colunas
        $stmt = $pdo->prepare("UPDATE utilizadores SET email = ?, senha = ?, periodo = ?, saldo_propina = ? WHERE id_utilizador = ?");

        foreach ($dados as $linha) {
            $id = intval($linha['id_utilizador']);
            $n1 = trim($linha['n1']);
            $n2 = trim($linha['n2']);
            $n3 = trim($linha['n3']);
            $faltas = floatval($linha['faltas']);

            $stmt->execute([$n1, $n2, $n3, $faltas, $id]);
        }

        $pdo->commit(); // Confirma todas as alterações no DBeaver
        $resposta['sucesso'] = true;
        $resposta['mensagem'] = '🎉 Caderneta guardada e sincronizada no MySQL com sucesso!';
    } catch (Exception $e) {
        $pdo->rollBack(); // Cancela se houver erro
        $resposta['mensagem'] = '❌ Erro no banco de dados: ' . $e->getMessage();
    }
} else {
    $resposta['mensagem'] = 'Método inválido.';
}

echo json_encode($resposta);
exit;