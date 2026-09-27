<?php
// 🔌 API INVISÍVEL: CONECTA O HTML (TELA 3) À INSTÂNCIA AIVEN
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// Dados de Conexão da sua Consola Aiven
$host = "SEU_HOST_://aivencloud.com"; 
$port = "SUA_PORTA"; 
$user = "avnadmin"; 
$pass = "SUA_SENHA"; 
$db   = "aurelius_escola";

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ATTR_ERRMODE_EXCEPTION
    ]);

    // Recebe a matrícula enviada pelo botão "Concluir Matrícula" do HTML
    $dados = json_decode(file_get_contents("php://input"), true);

    if ($dados) {
        // Insere na tabela 'utilizadores' de forma segura
        $stmt = $pdo->prepare("INSERT INTO utilizadores (id, nome, telefone, codigo_validacao, classe, periodo, municipio, bairro, valor_pago) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $dados['id'], $dados['nome'], $dados['telefone'], $dados['codigoValidacao'],
            $dados['classe'], $dados['periodo'], $dados['municipio'], $dados['bairro'], $dados['valorPagoReal']
        ]);
        
        echo json_encode(["sucesso" => true, "mensagem" => "Sincronizado na Aiven!"]);
    }
} catch (Exception $e) {
    echo json_encode(["sucesso" => false, "erro" => $e->getMessage()]);
}
?>