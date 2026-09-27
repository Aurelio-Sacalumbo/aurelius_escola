<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
require_once "conexao.php";

try {
    // Busca o título e o conteúdo usando os nomes exatos do seu DBeaver
    $stmt = $pdo->query("SELECT titulo, conteudo FROM mural_blog WHERE tipo_post = 'dica_estudo' ORDER BY id_post DESC");
    echo json_encode(["sucesso" => true, "dicas" => $stmt->fetchAll()]);
} catch(Exception $e) {
    echo json_encode(["sucesso" => false, "dicas" => []]);
}
?>