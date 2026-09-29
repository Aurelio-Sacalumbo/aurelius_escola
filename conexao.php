<?php
// 🗄️ CONEXÃO DINÂMICA ALINHADA - ACADEMIA AURÉLIUS

// Deteta se está a correr na sua máquina (Localhost) ou no Render
$isLocal = ($_SERVER['REMOTE_ADDR'] === '127.0.0.1' || $_SERVER['REMOTE_ADDR'] === '::1' || $_SERVER['SERVER_NAME'] === 'localhost');

if ($isLocal) {
    // 🏠 CONFIGURAÇÕES PARA O XAMPP LOCAL
    $host = "127.0.0.1";
    $port = "3306";
    $user = "root";
    $password = "";
    $dbname = "aurelius_escola";
} else {
    // ☁️ CONFIGURAÇÕES PARA A NUVEM DO RENDER (Puxa do painel do Render)
    $host = getenv('DB_HOST');
    $port = getenv('DB_PORT') ?: "22002";
    $user = getenv('DB_USER');
    $password = getenv('DB_PASSWORD');
    $dbname = getenv('DB_NAME') ?: "aurelius_escola";
}

try {
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // 🌟 Corrigido aqui!
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
    ];

    // Só aplica regras rígidas de SSL se estiver fora do Localhost
    if (!$isLocal) {
        $options[PDO::MYSQL_ATTR_SSL_CA] = true;
        $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
    }

    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $user, $password, $options);
} catch (PDOException $e) {
    error_log("Erro de Ligação: " . $e->getMessage());
    header('Content-Type: application/json');
    
    // Devolve uma mensagem clara se falhar local ou na nuvem
    $ambiente = $isLocal ? "servidor local (XAMPP)" : "servidor da nuvem";
    die(json_encode(["sucesso" => false, "mensagem" => "⚠️ Falha de ligação ao $ambiente."]));
}
?>