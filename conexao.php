<?php
// 🗄️ CONEXÃO DINÂMICA SEGURA E ALINHADA - ACADEMIA AURÉLIUS

$isLocal = (getenv('DB_HOST') === false);

if ($isLocal) {
    // 🏠 CONFIGURAÇÕES PARA O XAMPP LOCAL
    $host = "127.0.0.1";
    $port = "3306";
    $user = "root";
    $password = "";
    $dbname = "aurelius_escola"; 
} else {
    // ☁️ CONFIGURAÇÕES FORÇADAS PARA A NUVEM DO RENDER (Aiven Cloud)
    $host = getenv('DB_HOST');
    $port = getenv('DB_PORT') ?: "22002";
    $user = getenv('DB_USER');
    $password = getenv('DB_PASSWORD');
    
    // 🌟 FORÇADO: Garante que o Render abre sempre a 'aurelius_escola' e ignora o 'defaultdb'
    $dbname = "aurelius_escola"; 
}

try {
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
    ];

    if (!$isLocal) {
        $options[PDO::MYSQL_ATTR_SSL_CA] = true;
        $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
    }

    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $user, $password, $options);

    // Gatilho de expansão remota
    if (!$isLocal) {
        $pdo->exec("ALTER TABLE utilizadores MODIFY COLUMN curso TEXT NULL");
    }

} catch (PDOException $e) {
    error_log("Erro de Ligação: " . $e->getMessage());
    header('Content-Type: application/json; charset=utf-8');
    
    $ambiente = $isLocal ? "servidor local (XAMPP)" : "servidor da nuvem (Aiven)";
    die(json_encode(["sucesso" => false, "mensagem" => "⚠️ Falha de ligação ao $ambiente. Detalhe: " . $e->getMessage()]));
}
?>