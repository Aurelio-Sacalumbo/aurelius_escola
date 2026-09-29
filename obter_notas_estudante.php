<?php
// 🗄️ REPOSITÓRIO DE NOTAS DO ESTUDANTE - ACADEMIA AURÉLIUS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'conexao.php';

$resposta = ['sucesso' => false];

// Recebe o ID do aluno logado via parâmetro GET
$id_estudante = isset($_GET['id_estudante']) ? trim($_GET['id_estudante']) : '';

if (empty($id_estudante)) {
    echo json_encode(['sucesso' => false, 'mensagem' => '⚠️ Identificador escolar ausente.']);
    exit;
}

try {
    // Procura o registo do aluno pelo ID Único AUR-
    $stmt = $pdo->prepare("SELECT email as n1, senha as n2, nivel as n3, saldo_propina as faltas, periodo as classe_turma FROM utilizadores WHERE id_unico_escolar = ? LIMIT 1");
    $stmt->execute([$id_estudante]);
    $aluno = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($aluno) {
        $resposta['sucesso'] = true;
        $resposta['notas'] = [
            'n1' => $aluno['n1'],
            'n2' => $aluno['n2'],
            'n3' => $aluno['n3'],
            'faltas' => $aluno['faltas'],
            'classe_turma' => $aluno['classe_turma']
        ];
    } else {
        $resposta['mensagem'] = 'Estudante não localizado.';
    }
} catch (Exception $e) {
    $resposta['mensagem'] = $e->getMessage();
}

echo json_encode($resposta);
exit;