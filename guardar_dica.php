<?php
// 🔌 API DO MURAL: GRAVA AS DICAS DO PROFESSOR USANDO AS COLUNAS REAIS DA AIVEN
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");

require_once "conexao.php";

try {
    $dados = json_decode(file_get_contents("php://input"), true);

    if (!$dados || empty($dados['titulo']) || empty($dados['texto'])) {
        echo json_encode(["sucesso" => false, "mensagem" => "Por favor, preencha todos os campos."]);
        exit;
    }

    $titulo = trim($dados['titulo']);
    $conteudo = trim($dados['texto']);
    $id_autor = 1; // ID padrão do professor administrador
    $tipo_post = "dica_estudo"; // Categoria do post para o filtro pedagógico

    // 🎯 INSERÇÃO EDITADA COM AS SUAS COLUNAS EXATAS DA BASE DE DADOS
    $stmt = $pdo->prepare("INSERT INTO mural_blog (id_autor, tipo_post, titulo, conteudo) VALUES (?, ?, ?, ?)");
    $stmt->execute([$id_autor, $tipo_post, $titulo, $conteudo]);

    echo json_encode([
        "sucesso" => true, 
        "mensagem" => "Dica pedagógica publicada com sucesso ..!"
    ]);

} catch (Exception $e) {
    echo json_encode(["sucesso" => false, "mensagem" => "Erro na Aiven: " . $e->getMessage()]);
}
?>