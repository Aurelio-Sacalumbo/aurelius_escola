<?php
// 🗄️ AUTENTICAÇÃO ACADÉMICA - ACADEMIA AURÉLIUS (CORRIGIDO)
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

    // 🌟 FIX CIRÚRGICO: Busca expandida trazendo email, senha, curso e id_unico com a trava nivel = 'estudante'
    $query = "SELECT id_utilizador, nome, telefone, email, senha, id_unico_escolar, curso, periodo FROM utilizadores WHERE (id_unico_escolar = ? OR telefone = ?) LIMIT 1";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$identificador, $identificador]);
    $estudante = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($estudante) {
        // 🌟 DUPLA VALIDAÇÃO: Aceita o código quer esteja guardado na coluna 'senha' ou na coluna 'email'
        if ($senhaInserida === $estudante['senha'] || $senhaInserida === $estudante['email'] || md5($senhaInserida) === $estudante['senha']) {
            
            $classeFinal = !empty($estudante['curso']) ? $estudante['curso'] : '9ª Classe';
            
            $resposta['sucesso'] = true;
            $resposta['estudante'] = [
                'id_utilizador' => $estudante['id_utilizador'],
                'nome' => $estudante['nome'],
                'telefone' => $estudante['telefone'],
                'id_unico_escolar' => $estudante['id_unico_escolar'],
                'classe' => $classeFinal,
                'periodo' => $estudante['periodo'] ?: 'Tarde',
                'turma' => 'Turma Única A'
            ];
        } else {
            $resposta['mensagem'] = '❌ Código de validação ou senha incorreta.';
        }
    } else {
        $resposta['mensagem'] = '❌ Aluno não localizado na base académica do Huambo.';
    }

} catch (Exception $e) {
    $resposta['mensagem'] = '⚠️ Erro interno no servidor: ' . $e->getMessage();
}

echo json_encode($resposta);
exit;
?>