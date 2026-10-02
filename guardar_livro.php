<?php
// 📚 REPOSITÓRIO DIGITAL DE MANUAIS — ACADEMIA AURÉLIUS (MAPPED COLUMNS)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'conexao.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = isset($_POST['titulo']) ? trim($_POST['titulo']) : '';
    $autor  = isset($_POST['autor']) ? trim($_POST['autor']) : 'Editorial Aurélius';
    
    if (empty($titulo) || !isset($_FILES['ficheiro'])) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Título ou arquivo PDF em falta no formulário.']);
        exit;
    }

    $file = $_FILES['ficheiro'];
    $extensao = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if ($extensao !== 'pdf') {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Apenas são permitidos ficheiros em formato PDF.']);
        exit;
    }

    // Cria a estrutura física de diretórios no Render se não existir
    $diretorioSubida = 'uploads/manuais/';
    if (!is_dir($diretorioSubida)) {
        mkdir($diretorioSubida, 0777, true);
    }

    // Normaliza o nome do ficheiro para evitar quebras por caracteres especiais
    $novoNomeFicheiro = md5(time() . $file['name']) . '.pdf';
    $caminhoFinal = $diretorioSubida . $novoNomeFicheiro;

    if (move_uploaded_file($file['tmp_name'], $caminhoFinal)) {
        try {
            // 🌟 CASAMENTO PERFEITO COM O SEU PHPMYADMIN:
            // titulo_livro recebe o título, autor recebe o autor e categoria_curso recebe o caminho final do PDF
            $queryInsert = "INSERT INTO aurelius_escola.livros (titulo_livro, autor, categoria_curso) VALUES (?, ?, ?)";
            
            $stmt = $pdo->prepare($queryInsert);
            $sucesso = $stmt->execute([$titulo, $autor, $caminhoFinal]);

            echo json_encode(['sucesso' => $sucesso, 'mensagem' => '🚀 Manual publicado e alocado com sucesso na prateleira da nuvem!']);
        } catch (Exception $e) {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao salvar no banco central: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Falha física ao mover o PDF para o servidor do Render.']);
    }
    exit;
}
?>