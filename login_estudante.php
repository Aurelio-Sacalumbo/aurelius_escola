<?php
// 🗄️ AUTENTICAÇÃO ACADÉMICA - ACADEMIA AURÉLIUS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'conexao.php'; // Motor de conexão dinâmica

$resposta = ['sucesso' => false, 'mensagem' => ''];

try {
    // Captura Híbrida (FormData ou JSON puro)
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

    // Consulta alinhada com as colunas reais do phpMyAdmin
    $query = "SELECT id_utilizador, nome, telefone, email as codigo_senha, periodo FROM utilizadores WHERE (id_unico_escolar = ? OR telefone = ?) LIMIT 1";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$identificador, $identificador]);
    $estudante = $stmt->fetch();

    if ($estudante) {
        // Validação da senha numérica guardada na coluna 'email'
        if ($senhaInserida === $estudante['codigo_senha']) {
            $resposta['sucesso'] = true;
            $resposta['estudante'] = [
                'id' => $identificador,
                'nome' => $estudante['nome'],
                'telefone' => $estudante['telefone'],
                'classe' => '9ª Classe',
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