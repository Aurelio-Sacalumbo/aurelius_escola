<?php
// 🔍 BUSCA DE NOTAS CORRIGIDA — ACADEMIA AURÉLIUS
header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json; charset=utf-8');

require_once 'conexao.php';

$id_utilizador = isset($_GET['id_utilizador']) ? intval($_GET['id_utilizador']) : 0;

try {
    $notasAgrupadas = [];

    if ($id_utilizador > 0) {
        // Alinhado com as colunas reais do phpMyAdmin: nota_n1, nota_n2, nota_n3, faltas
        $stmt = $pdo->prepare("SELECT disciplina, nota_n1 AS n1, nota_n2 AS n2, nota_n3 AS n3, faltas FROM pautas WHERE id_estudante = ?");
        $stmt->execute([$id_utilizador]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as $r) {
            $notasAgrupadas[$r['disciplina']] = [
                'n1' => $r['n1'],
                'n2' => $r['n2'],
                'n3' => $r['n3'],
                'faltas' => $r['faltas']
            ];
        }
    }

    echo json_encode(['sucesso' => true, 'notas' => $notasAgrupadas], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    echo json_encode(['sucesso' => false, 'mensagem' => $e->getMessage()]);
}
exit;
?>