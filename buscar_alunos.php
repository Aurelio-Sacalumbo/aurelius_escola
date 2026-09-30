<?php
// Configuração da ligação ao Banco de Dados
$host = "localhost";
$user = "SEU_UTILIZADOR";
$pass = "SUA_SENHA";
$dbname = "SEU_BANCO_DE_DADOS";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(["erro" => "Falha na ligação"]);
    exit;
}

// Obtém o parâmetro enviado pelo JavaScript (pode ser o ID, curso ou período)
$classe = isset($_GET['classe']) ? trim($_GET['classe']) : '';

if (empty($classe)) {
    echo json_encode([]);
    exit;
}

// SQL adaptada para a sua estrutura: busca quem tem nível 'estudante' e corresponde à classe
// NOTA: Ajuste o 'periodo' ou 'curso' conforme o valor que o seu <select> envia.
$query = "SELECT nome FROM utilizadores WHERE nivel = 'estudante' AND (periodo = :classe OR curso = :classe) ORDER BY nome ASC";
$stmt = $pdo->prepare($query);
$stmt->bindParam(':classe', $classe, PDO::PARAM_STR);
$stmt->execute();

$alunos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Define o cabeçalho como JSON para o JavaScript ler corretamente
header('Content-Type: application/json');
echo json_encode($alunos);
?>