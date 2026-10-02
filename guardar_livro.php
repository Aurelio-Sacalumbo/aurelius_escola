<?php
// 📚 REPOSITÓRIO DIGITAL DE MANUAIS - ACADEMIA AURÉLIUS
header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json; charset=utf-8');

require_once 'conexao.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = isset($_POST['titulo']) ? trim($_POST['titulo']) : '';
    $autor  = isset($_POST['autor']) ? trim($_POST['autor']) : 'Editorial Aurélius';
    
    if (empty($titulo) || !isset($_FILES['ficheiro'])) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Título ou arquivo PDF em falta.']);
        exit;
    }

    $file = $_FILES['ficheiro'];
    $extensao = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if ($extensao !== 'pdf') {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Apenas são permitidos ficheiros em formato PDF.']);
        exit;
    }

    $diretorioSubida = 'uploads/manuais/';
    if (!is_dir($diretorioSubida)) {
        mkdir($diretorioSubida, 0777, true);
    }

    // Nome único encriptado para evitar conflitos no Render
    $novoNomeFicheiro = md5(time() . $file['name']) . '.pdf';
    $caminhoFinal = $diretorioSubida . $novoNomeFicheiro;

    if (move_uploaded_file($file['tmp_name'], $caminhoFinal)) {
        try {
            // Guarda o livro associando o título como a disciplina-alvo
            $stmt = $pdo->prepare("INSERT INTO livros (titulo, autor, caminho_pdf, disciplinas_associadas) VALUES (?, ?, ?, ?)");
            $sucesso = $stmt->execute([$titulo, $autor, $caminhoFinal, $titulo]);

            echo json_encode(['sucesso' => $sucesso, 'mensagem' => '🚀 Manual publicado e alocado com sucesso na prateleira!']);
        } catch (Exception $e) {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao salvar no banco: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Falha física ao mover o PDF para o servidor do Render.']);
    }
    exit;
}
?>