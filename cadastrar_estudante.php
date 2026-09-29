<?php
// 🗄️ PROCESSADOR DE INSCRIÇÕES AUTOMÁTICAS COM PERMISSÃO CENTRAL - ACADEMIA AURÉLIUS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0); // Termina requisições de pré-envio de segurança do navegador
}

// Credenciais diretas da nuvem da Aiven Cloud
$host = "://aivencloud.com";
$port = "22002";
$user = "avnadmin";
$password = "AVNS_6AyaHMtSplThuvy6uGm";
$dbname = "aurelius_escola";

try {
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
        PDO::MYSQL_ATTR_SSL_CA => true,
        PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false
    ];
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $user, $password, $options);
} catch (PDOException $e) {
    echo json_encode(['sucesso' => false, 'mensagem' => '⚠️ Erro de ligação à nuvem: ' . $e->getMessage()]);
    exit;
}

$resposta = ['sucesso' => false, 'mensagem' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = isset($_POST['nome']) ? trim($_POST['nome']) : '';
    $telefone = isset($_POST['telefone']) ? trim($_POST['telefone']) : '';
    $classe = isset($_POST['classe']) ? trim($_POST['classe']) : '';
    $periodo = isset($_POST['periodo']) ? trim($_POST['periodo']) : '';

    if (empty($nome) || empty($telefone)) {
        $resposta['mensagem'] = '⚠️ Nome e telefone são obrigatórios!';
        echo json_encode($resposta);
        exit;
    }

    try {
        $check = $pdo->prepare("SELECT id_utilizador FROM utilizadores WHERE telefone = ? LIMIT 1");
        $check->execute([$telefone]);
        if ($check->fetch()) {
            $resposta['mensagem'] = '⚠️ Erro: Este número de telefone já se encontra registado!';
            echo json_encode($resposta);
            exit;
        }

        // Gerador do ID solicitado e senha
        $novoID = "AUR-568777"; // Forçando o ID solicitado pelo utilizador para teste imediato
        $codigoValidacao = "123456"; // Senha padrão para teste rápido

        $query = "INSERT INTO utilizadores (nome, telefone, email, senha, id_unico_escolar, periodo, saldo_propina) 
                  VALUES (?, ?, ?, 'estudante', ?, ?, 0.00)";
        
        $stmt = $pdo->prepare($query);
        $resultado = $stmt->execute([$nome, $telefone, $codigoValidacao, $novoID, $periodo]);

        if ($resultado) {
            $resposta['sucesso'] = true;
            $resposta['id_estudante'] = $novoID;
            $resposta['codigo_validacao'] = $codigoValidacao;
            $resposta['mensagem'] = '🎉 Inscrição guardada no banco com sucesso!';
        } else {
            $resposta['mensagem'] = '⚠️ Erro interno ao inserir no banco de dados.';
        }

    } catch (Exception $e) {
        $resposta['mensagem'] = '⚠️ Erro no servidor: ' . $e->getMessage();
    }
}
echo json_encode($resposta);
exit;