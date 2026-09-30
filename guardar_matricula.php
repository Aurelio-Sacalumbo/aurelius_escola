<?php
require_once 'conexao.php';

// Recebe os dados do fetch JSON
$inputRaw = file_get_contents("php://input");
$dados = json_decode($inputRaw, true);

if ($dados) {
    $telefone = trim($dados['telefone']);
    $nome = trim($dados['nome']);
    $cursoCompleto = trim($dados['classe']) . " [" . trim($dados['disciplinas']) . "]";
    $periodo = trim($dados['periodo']);
    
    // 🎲 GERAÇÃO DINÂMICA DO ID ÚNICO ESCOLAR (Se não existir)
    $id_unico_escolar = "AUR-" . rand(100000, 999999);
    $senha_padrao = rand(100000, 999999); // Gera uma senha numérica de 6 dígitos para o primeiro acesso
    $senha_hash = md5($senha_padrao); // Se o seu sistema usa MD5 como vimos na tabela utilizadores

    try {
        // 1. Primeiro, verificamos se o utilizador com esse telefone realmente existe
        $checkStmt = $pdo->prepare("SELECT id FROM utilizadores WHERE telefone = ? AND nivel = 'estudante'");
        $checkStmt->execute([$telefone]);
        
        if ($checkStmt->rowCount() === 0) {
            // Se não existir, podemos optar por dar erro ou fazer um INSERT. Vamos devolver erro:
            echo json_encode([
                'sucesso' => false,
                'mensagem' => 'Erro: Este número de telefone não foi pré-registado no sistema!'
            ]);
            exit;
        }

        // 2. Se existe, atualiza com os códigos imperiais de acesso
        $stmt = $pdo->prepare("UPDATE utilizadores SET id_unico_escolar = ?, curso = ?, periodo = ?, senha = IF(senha='' OR senha IS NULL, ?, senha) WHERE telefone = ? AND nivel = 'estudante'");
        $stmt->execute([$id_unico_escolar, $cursoCompleto, $periodo, $senha_hash, $telefone]);

        // 3. Retorna os códigos INCLUINDO o telefone para o preenchimento da fatura no HTML
        echo json_encode([
            'sucesso' => true, 
            'id_unico' => $id_unico_escolar, 
            'senha_gerada' => $senha_padrao,
            'telefone' => $telefone, // <-- Adicionado para o seu estudante.html ler na fatura
            'mensagem' => 'Matrícula guardada e códigos de acesso gerados com sucesso!'
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['sucesso' => false, 'mensagem' => $e->getMessage()]);
    }
    exit;
}
?>