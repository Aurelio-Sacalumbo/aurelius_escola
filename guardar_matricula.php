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
        // Atualiza o aluno que já estava inscrito, injetando o curso, o ID único e a senha de acesso
        $stmt = $pdo->prepare("UPDATE utilizadores SET id_unico_escolar = ?, curso = ?, periodo = ?, senha = IF(senha='' OR senha IS NULL, ?, senha) WHERE telefone = ? AND nivel = 'estudante'");
        $sucesso = $stmt->execute([$id_unico_escolar, $cursoCompleto, $periodo, $senha_hash, $telefone]);

        // Retorna os códigos gerados para o JavaScript exibir na fatura/recibo imperial
        echo json_encode([
            'sucesso' => $sucesso, 
            'id_unico' => $id_unico_escolar, 
            'senha_gerada' => $senha_padrao,
            'mensagem' => 'Matrícula guardada e códigos de acesso gerados com sucesso!'
        ]);
    } catch (Exception $e) {
        echo json_encode(['sucesso' => false, 'mensagem' => $e->getMessage()]);
    }
    exit;
}
?>