<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);
require_once 'conexao.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = isset($_POST['titulo']) ? trim($_POST['titulo']) : '';
    $autor  = isset($_POST['autor']) ? trim($_POST['autor']) : 'Editorial Aurélius';
    
    if (empty($titulo) || !isset($_FILES['ficheiro'])) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Campos em falta.']);
        exit;
    }

    $file = $_FILES['ficheiro'];
    $caminhoFinal = 'uploads/manuais/' . md5(time() . $file['name']) . '.pdf';

    if (!is_dir('uploads/manuais/')) mkdir('uploads/manuais/', 0777, true);

    if (move_uploaded_file($file['tmp_name'], $caminhoFinal)) {
        try {
            // 🌟 Uso limpo da tabela livros
            $stmt = $pdo->prepare("INSERT INTO livros (titulo_livro, autor, categoria_curso) VALUES (?, ?, ?)");
            $sucesso = $stmt->execute([$titulo, $autor, $caminhoFinal]);
            echo json_encode(['sucesso' => $sucesso, 'mensagem' => '🚀 Manual publicado com sucesso na nuvem!']);
        } catch (Exception $e) {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Erro banco: ' . $e->getMessage()]);
        }
    }
    exit;
}
?>