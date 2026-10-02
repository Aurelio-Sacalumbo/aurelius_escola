<?php
// 📝 LANÇAMENTO DE NOTAS — ACADEMIA AURÉLIUS (COLUNAS OFICIAIS)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'conexao.php';

$inputRaw = file_get_contents("php://input");
$dados = json_decode($inputRaw, true);

if (!$dados || !isset($dados['id_utilizador']) || !isset($dados['lista_notas'])) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Nenhum dado em lote recebido no servidor.']);
    exit;
}

$id_estudante = intval($dados['id_utilizador']); // id_utilizador vindo do JS mapeia para id_estudante
$lista_notas   = $dados['lista_notas'];

try {
    $pdo->beginTransaction();

    // 🌟 Casamento perfeito com o seu phpMyAdmin: id_estudante, nota_n1, nota_n2, nota_n3, faltas
    $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM pautas WHERE id_estudante = ? AND disciplina = ?");
    $stmtUpdate = $pdo->prepare("UPDATE pautas SET nota_n1 = ?, nota_n2 = ?, nota_n3 = ?, faltas = ? WHERE id_estudante = ? AND disciplina = ?");
    $stmtInsert = $pdo->prepare("INSERT INTO pautas (id_estudante, disciplina, nota_n1, nota_n2, nota_n3, faltas) VALUES (?, ?, ?, ?, ?, ?)");

    foreach ($lista_notas as $item) {
        $disciplina = trim($item['disciplina']);
        $n1 = ($item['n1'] !== "" && $item['n1'] !== null) ? floatval($item['n1']) : null;
        $n2 = ($item['n2'] !== "" && $item['n2'] !== null) ? floatval($item['n2']) : null;
        $n3 = ($item['n3'] !== "" && $item['n3'] !== null) ? floatval($item['n3']) : null;
        $faltas = intval($item['faltas']);

        if (empty($disciplina)) continue;

        $stmtCheck->execute([$id_estudante, $disciplina]);
        $existe = $stmtCheck->fetchColumn() > 0;

        if ($existe) {
            $stmtUpdate->execute([$n1, $n2, $n3, $faltas, $id_estudante, $disciplina]);
        } else {
            $stmtInsert->execute([$id_estudante, $disciplina, $n1, $n2, $n3, $faltas]);
        }
    }

    $pdo->commit();
    echo json_encode(['sucesso' => true, 'mensagem' => '🎉 Caderneta sincronizada com sucesso!']);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro MySQL no lote: ' . $e->getMessage()]);
}
exit;
?>