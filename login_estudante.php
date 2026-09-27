<?php
// 🔌 API DE AUTENTICAÇÃO: CONECTA O estudante.html À NUVEM DA AIVEN
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");

// Credenciais de Conexão da sua Consola Aiven
$host = "SEU_HOST_DA_://aivencloud.com"; 
$port = "SUA_PORTA_DA_AIVEN"; 
$user = "avnadmin"; 
$pass = "SUA_SENHA_LONGA_DA_AIVEN"; 
$db   = "aurelius_escola";

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ATTR_ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::ATTR_FETCH_ASSOC
    ]);

    // Recebe os dados de login enviados pelo estudante.html
    $dados = json_decode(file_get_contents("php://input"), true);

    if (!$dados || empty($dados['identificador']) || empty($dados['senha'])) {
        echo json_encode(["sucesso" => false, "mensagem" => "Dados de login incompletos."]);
        exit;
    }

    $identificador = trim($dados['identificador']);
    $senhaInserida = trim($dados['senha']);

    // Procura na tabela 'utilizadores' se o ID ou Telefone correspondem ao código de validação
    // Nota: Ajuste os nomes das colunas conforme o que definiu no DBeaver
    $stmt = $pdo->prepare("SELECT * FROM utilizadores WHERE (id = ? OR telefone = ?) AND codigo_validacao = ?");
    $stmt->execute([$identificador, $identificador, $senhaInserida]);
    $estudante = $stmt->fetch();

    if ($estudante) {
        // Login com sucesso! Devolve os dados pedagógicos do estudante
        echo json_encode([
            "sucesso" => true,
            "estudante" => [
                "id" => $estudante['id'],
                "nome" => $estudante['nome'],
                "telefone" => $estudante['telefone'],
                "codigoValidacao" => $estudante['codigo_validacao'],
                "classe" => $estudante['classe'] ?? 'Aguardando Matrícula',
                "turma" => $estudante['turma'] ?? 'Sem Turma',
                "periodo" => $estudante['periodo'] ?? 'Regular'
            ]
        ]);
    } else {
        echo json_encode(["sucesso" => false, "mensagem" => "ID/Telefone ou Código de Validação incorretos."]);
    }

} catch (Exception $e) {
    echo json_encode(["sucesso" => false, "mensagem" => "Erro de rede na nuvem: " . $e->getMessage()]);
}
?>