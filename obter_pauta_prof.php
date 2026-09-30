<?php
// 📊 CENTRAL DE PROCESSAMENTO DOCENTE - ACADEMIA AURÉLIUS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'conexao.php';

$resposta = ['sucesso' => false];
$acao = isset($_GET['acao']) ? $_GET['acao'] : 'indicadores';

try {
    // 🧮 AÇÃO 1: INDICADORES E LISTAGEM DE TURMAS REAIS DO BANCO
    if ($acao === 'indicadores') {
        $inscritos = $pdo->query("SELECT COUNT(*) FROM utilizadores")->fetchColumn();
        $matriculados = $pdo->query("SELECT COUNT(*) FROM utilizadores WHERE id_unico_escolar IS NOT NULL AND id_unico_escolar LIKE 'AUR-%'")->fetchColumn();
        
        $regular = $pdo->query("SELECT COUNT(*) FROM utilizadores WHERE periodo LIKE '%Manhã%' OR periodo LIKE '%manhã%' OR periodo LIKE '%Tarde%' OR periodo LIKE '%tarde%'")->fetchColumn();
        $posLaboral = $pdo->query("SELECT COUNT(*) FROM utilizadores WHERE periodo LIKE '%Noite%' OR periodo LIKE '%noite%'")->fetchColumn();
        
        $faturamento = $matriculados * 15000;

        // 🌟 DINÂMICO: Agrupa e extrai os valores reais diretamente da coluna 'periodo'
        $stmtTurmas = $pdo->query("SELECT DISTINCT periodo FROM utilizadores WHERE periodo IS NOT NULL AND periodo != '' ORDER BY periodo ASC");
        $turmas = $stmtTurmas->fetchAll(PDO::FETCH_COLUMN);

        echo json_encode([
            'sucesso' => true,
            'inscritos' => intval($inscritos),
            'matriculados' => intval($matriculados),
            'regular' => intval($regular),
            'pos_laboral' => intval($posLaboral),
            'faturamento' => floatval($faturamento),
            'turmas' => $turmas
        ]);
        exit;
    }

    // 📚 AÇÃO 2: CARREGA OS ESTUDANTES FILTRADOS PELA TURMA (COLUNA PERIODO)
    if ($acao === 'carregar_alunos') {
        $classe = isset($_GET['classe']) ? trim($_GET['classe']) : '';
        
        $stmt = $pdo->prepare("SELECT id_utilizador, id_unico_escolar, nome, email as nota_n1, senha as nota_n2, nivel as nota_n3, saldo_propina as faltas, telefone FROM utilizadores WHERE periodo = ? ORDER BY nome ASC");
        $stmt->execute([$classe]);
        
        echo json_encode([
            'sucesso' => true,
            'alunos' => $stmt->fetchAll(PDO::FETCH_ASSOC)
        ]);
        exit;
    }
} catch (Exception $e) {
    echo json_encode(['sucesso' => false, 'mensagem' => $e->getMessage()]);
    exit;
}