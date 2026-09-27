<?php
// 🔌 API DE LIVROS: RECOLHE O PDF E ENVIA PARA A NUVEM AIVEN
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");

require_once "conexao.php";

try {
    $dados = json_decode(file_get_contents("php://input"), true);

    if (!$dados || empty($dados['titulo']) || empty($dados['autor']) || empty($dados['pdf_base64'])) {
        echo json_encode(["sucesso" => false, "mensagem" => "Dados do manual incompletos."]);
        exit;
    }

    $titulo = trim($dados['titulo']);
    $autor = trim($dados['autor']);
    $pdf = $dados['pdf_base64']; // String Base64 do documento

    // Insere na tabela 'livros' do seu DBeaver (id_livro, titulo, autor, dados_ficheiro)
    $stmt = $pdo->prepare("INSERT INTO livros (titulo, autor, dados_ficheiro) VALUES (?, ?, ?)");
    $stmt->execute([$titulo, $autor, $pdf]);

    echo json_encode(["sucesso" => true, "mensagem" => "Manual Académico alocado na nuvem com sucesso!"]);

} catch (Exception $e) {
    echo json_encode(["sucesso" => false, "mensagem" => "Erro na Aiven: " . $e->getMessage()]);
}
?>