<?php
// 🗄️ AUTENTICAÇÃO ACADÉMICA - ACADEMIA AURÉLIUS
ini_set('display_errors', 0);
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');

require_once 'conexao.php'; // Usa o motor de conexão dinâmica

$resposta = ['sucesso' => false, 'mensagem' => ''];

try {
    // 🌟 CAPTURA HÍBRIDA (Aceita tanto FormData quanto requisições JSON)
    $identificador = isset($_POST['identificador']) ? trim($_POST['identificador']) : '';
    $senhaInserida = isset($_POST['senha']) ? trim($_POST['senha']) : '';

    if (empty($identificador) || empty($senhaInserida)) {
        // Se não veio por Post tradicional, tenta capturar como JSON puro
        $json = json_decode(file_get_contents("php://input"), true);
        $identificador = isset($json['identificador']) ? trim($json['identificador']) : '';
        $senhaInserida = isset($json['senha']) ? trim($json['senha']) : '';
    }

    if (empty($identificador) || empty($senhaInserida)) {
        $resposta['mensagem'] = '⚠️ Identificador ou senha não fornecidos.';
        echo json_encode($resposta);
        exit;
    }

    // 🌟 ALINHADO COM O SEU PHPMYADMIN: 
    // Procura o aluno validando se a coluna 'senha' é igual a 'estudante'
    // E aceita o ID Único Escolar ou o número de telefone no primeiro campo
    $query = "SELECT id_utilizador, nome, telefone, email as codigo_senha, nivel, periodo FROM utilizadores WHERE (id_unico_escolar = ? OR telefone = ?) AND senha = 'estudante' LIMIT 1";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$identificador, $identificador]);
    $estudante = $stmt->fetch();

    if ($estudante) {
        // No seu banco de dados, o código numérico (ex: 123456) está guardado na coluna 'email'
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

// Retorna obrigatoriamente um JSON limpo para o JavaScript ler sem quebrar
echo json_encode($resposta);
exit;