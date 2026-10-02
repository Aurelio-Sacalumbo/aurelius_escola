<?php
// 🔍 BUSCA DE NOTAS INTELIGENTE — ACADEMIA AURÉLIUS
header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json; charset=utf-8');

require_once 'conexao.php';

// Captura o parâmetro de forma tolerante (ID de texto ou ID numérico)
$identificadorRaw = isset($_GET['id_utilizador']) ? trim($_GET['id_utilizador']) : '';

try {
    $notasAgrupadas = [];
    $id_estudante_num = null;

    if (!empty($identificadorRaw)) {
        // Se o front-end enviou o ID em texto (ex: AUR-542534), descobre o ID numérico real na tabela utilizadores
        if (!is_numeric($identificadorRaw)) {
            $stmtUser = $pdo->prepare("SELECT id_utilizador FROM utilizadores WHERE id_unico_escolar = ? LIMIT 1");
            $stmtUser->execute([$identificadorRaw]);
            $id_estudante_num = $stmtUser->fetchColumn();
        } else {
            $id_estudante_num = intval($identificadorRaw);
        }

        if ($id_estudante_num) {
            // Puxa as notas oficiais cruzando com as colunas reais da tabela pautas
            $stmt = $pdo->prepare("SELECT disciplina, nota_n1 AS n1, nota_n2 AS n2, nota_n3 AS n3, faltas FROM pautas WHERE id_estudante = ?");
            $stmt->execute([$id_estudante_num]);
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
    }

    echo json_encode(['sucesso' => true, 'notas' => $notasAgrupadas], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao buscar pauta: ' . $e->getMessage()]);
}
exit;
?>