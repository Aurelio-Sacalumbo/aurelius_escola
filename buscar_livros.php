<?php
// 🔍 BUSCA DE MANUAIS DIGITAIS — ACADEMIA AURÉLIUS
header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json; charset=utf-8');

require_once 'conexao.php';

try {
    // Puxa todos os manuais cadastrados na tabela livros
    $stmt = $pdo->query("SELECT id_libro, titulo_livro, autor, categoria_curso AS caminho_pdf FROM aurelius_escola.livros ORDER BY id_libro DESC");
    $livros = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['sucesso' => true, 'livros' => $livros], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao carregar prateleira: ' . $e->getMessage()]);
}
exit;
?>