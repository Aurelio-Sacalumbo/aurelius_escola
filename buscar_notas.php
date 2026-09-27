<?php
// 🔌 API DE NOTAS: TRAZ AS PAUTAS PARA O estudante.html
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");

require_once "conexao.php";

try {
    $dados = json_decode(file_get_contents("php://input"), true);

    if (!$dados || empty($dados['estudante_id'])) {
        echo json_encode(["sucesso" => false, "mensagem" => "ID do estudante em falta."]);
        exit;
    }

    // Limpa o ID recebido (Garante compatibilidade com id_estudante INT)
    $estudante_id = (int)trim($dados['estudante_id']);

    // 🔍 CORREÇÃO AQUI: Mapeado exatamente com as colunas reais da sua tabela 'pautas'
    $query = "SELECT disciplina, nota_n1, nota_n2, nota_n3, faltas FROM pautas WHERE id_estudante = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$estudante_id]);
    $pautas = $stmt->fetchAll();

    echo json_encode([
        "sucesso" => true,
        "disciplinas" => $pautas
    ]);

} catch (Exception $e) {
    echo json_encode([
        "sucesso" => false, 
        "mensagem" => "Erro ao processar pautas: " . $e->getMessage()
    ]);
}
?>