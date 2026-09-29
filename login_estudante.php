<?php
// 🗄️ AUTENTICAÇÃO ACADÉMICA - ACADEMIA AURÉLIUS
ini_set('display_errors', 0);
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');

require_once 'conexao.php'; // Motor de conexão dinâmica

$resposta = ['sucesso' => false, 'mensagem' => ''];

try {
    // Captura Híbrida (Aceita FormData e JSON puro)
    $identificador = isset($_POST['identificador']) ? trim($_POST['identificador']) : '';
    $senhaInserida = isset($_POST['senha']) ? trim($_POST['senha']) : '';

    if (empty($identificador) || empty($senhaInserida)) {
        $json = json_decode(file_get_contents("php://input"), true);
        $identificador = isset($json['identificador']) ? trim($json['identificador']) : '';
        $senhaInserida = isset($json['senha']) ? trim($json['senha']) : '';
    }

    if (empty($identificador) || empty($senhaInserida)) {
        $resposta['mensagem'] = '⚠️ Identificador ou senha não fornecidos.';
        echo json_encode($resposta);
        exit;
    }

    // Consulta 100% alinhada com as colunas reais do seu phpMyAdmin da Aiven
    $query = "SELECT id_utilizador, nome, telefone, email as codigo_senha, periodo, nivel FROM utilizadores WHERE (id_unico_escolar = ? OR telefone = ?) AND senha = 'estudante' LIMIT 1";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$identificador, $identificador]);
    $estudante = $stmt->fetch();

    if ($estudante) {
        if ($senhaInserida === $estudante['codigo_senha']) {
            $resposta['sucesso'] = true;
            $resposta['estudante'] = [
                'id' => $identificador,
                'nome' => $estudante['nome'],
                'telefone' => $estudante['telefone'],
                'classe' => $estudante['nivel'] ?: '9ª Classe', 
                'periodo' => $estudante['periodo'] ?: 'Tarde',
                'turma' => 'Turma Única A',
                'disciplinas' => [],
                'livros' => []
            ];
        } else {
            $resposta['mensagem'] = '❌ Código de validação incorreto.';
        }
    } else {
        $resposta['mensagem'] = '❌ Aluno não localizado na base académica.';
    }

} catch (Exception $e) {
    $resposta['mensagem'] = '⚠️ Erro interno no servidor: ' . $e->getMessage();
}

echo json_encode($resposta);
exit;