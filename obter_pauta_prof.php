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
    if ($acao === 'indicadores') {
        // 1. Total Geral de Inscritos na base de dados
        $inscritos = $pdo->query("SELECT COUNT(*) FROM utilizadores")->fetchColumn();
        
        // 2. Matriculados reais (Alunos que possuem código/identificação escolar gerado)
        $matriculados = $pdo->query("SELECT COUNT(*) FROM utilizadores WHERE id_unico_escolar IS NOT NULL AND id_unico_escolar LIKE 'AUR-%'")->fetchColumn();
        
        // 3. Regime Regular (Períodos: Manhã ou Tarde)
        $regular = $pdo->query("SELECT COUNT(*) FROM utilizadores WHERE periodo LIKE '%Manhã%' OR periodo LIKE '%manhã%' OR periodo LIKE '%Tarde%' OR periodo LIKE '%tarde%'")->fetchColumn();
        
        // 4. Regime Pós-Laboral (Período: Noite)
        $posLaboral = $pdo->query("SELECT COUNT(*) FROM utilizadores WHERE periodo LIKE '%Noite%' OR periodo LIKE '%noite%'")->fetchColumn();
        
        // Faturamento Real baseado nos matriculados efetivos (Ex: 15.000 Kz por aluno)
        $faturamento = $matriculados * 15000;

        // Puxa as turmas do campo nivel
        $stmtTurmas = $pdo->query("SELECT DISTINCT nivel FROM utilizadores WHERE nivel IS NOT NULL AND nivel != '' ORDER BY nivel ASC");
        $turmas = $stmtTurmas->fetchAll(PDO::FETCH_COLUMN);

        echo json_encode([
            'sucesso' => true,
            'inscritos' => $inscritos,
            'matriculados' => $matriculados,
            'regular' => $regular,
            'pos_laboral' => $posLaboral,
            'faturamento' => $faturamento,
            'turmas' => $turmas
        ]);
        exit;
    }

    if ($acao === 'carregar_alunos') {
        $classe = isset($_GET['classe']) ? trim($_GET['classe']) : '';
        $stmt = $pdo->prepare("SELECT id_utilizador, id_unico_escolar, nome, email as nota_n1, senha as nota_n2, periodo as nota_n3, saldo_propina as faltas FROM utilizadores WHERE nivel = ? ORDER BY nome ASC");
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