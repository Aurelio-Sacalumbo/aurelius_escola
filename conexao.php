<?php
// 🗄️ CONEXÃO DINÂMICA - PORTAL AURÉLIUS ESCOLA

// Deteta se está a correr localmente ou no Render
$isLocal = ($_SERVER['REMOTE_ADDR'] === '127.0.0.1' || $_SERVER['REMOTE_ADDR'] === '::1');

if ($isLocal) {
    // 🏠 CONFIGURAÇÕES LOCALHOST (XAMPP / WAMP)
    $host = "127.0.0.1";
    $port = "3306";
    $user = "root";
    $password = "";
    $dbname = "aurelius_escola";
} else {
    // ☁️ CONFIGURAÇÕES DA NUVEM (RENDER)
    // Configure estas Variáveis de Ambiente (Environment Variables) no painel do Render
    $host = getenv('DB_HOST') ?: "O_HOST_DA_SUA_NUVEM"; 
    $port = getenv('DB_PORT') ?: "3306";
    $user = getenv('DB_USER') ?: "O_UTILIZADOR_DA_NUVEM";
    $password = getenv('DB_PASSWORD') ?: "A_SENHA_DA_NUVEM";
    $dbname = getenv('DB_NAME') ?: "A_BASE_DE_DADOS_DA_NUVEM";
}

try {
    $options = [
        PDO::ATTR_ERRMODE => PDO::ATTR_ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::ATTR_FETCH_ASSOC,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
    ];

    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $user, $password, $options);
} catch (PDOException $e) {
    error_log("Erro de Conexão: " . $e->getMessage());
    header('Content-Type: application/json');
    die(json_encode([
        "sucesso" => false, 
        "mensagem" => "⚠️ Falha de ligação ao servidor de base de dados."
    ]));
}
?>