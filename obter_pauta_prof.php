<?php
// 📊 MOTOR DE NOTAS, INDICADORES E TURMAS - ACADEMIA AURÉLIUS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'conexao.php';

$resposta = ['sucesso' => false];
$acao = isset($_GET['acao']) ? $_GET['acao'] : 'indicadores';

try {
    // 🧮 1. CARREGA OS INDICADORES GLOBAIS DE FATURAMENTO
    if ($acao === 'indicadores') {
        // Conta Total de alunos
        $total = $pdo->query("SELECT COUNT(*) FROM utilizadores WHERE id_unico_escolar IS NOT NULL")->fetchColumn();
        
        // Conta alunos do Ensino Regular (ex: 7ª Classe, 8ª, 9ª)
        $regular = $pdo->query("SELECT COUNT(*) FROM utilizadores WHERE nivel LIKE '%Classe%' OR nivel LIKE '%classe%'")->fetchColumn();
        
        // Conta Superior ou Cursos Livres/Técnicos
        $superior = $total - $regular;
        
        // Faturamento Real Estimado (Ex: Propinas acumuladas ou simuladas de 15.000 Kz por aluno)
        $faturamento = $total * 15000;

        // Puxa as turmas únicas para o select
        $stmtTurmas = $pdo->query("SELECT DISTINCT nivel FROM utilizadores WHERE nivel IS NOT NULL AND nivel != '' ORDER BY nivel ASC");
        $turmas = $stmtTurmas->fetchAll(PDO::FETCH_COLUMN);

        echo json_encode([
            'sucesso' => true,
            'total' => $total,
            'regular' => $regular,
            'superior' => $superior,
            'faturamento' => $faturamento,
            'turmas' => $turmas
        ]);
        exit;
    }

    // 📚 2. CARREGA OS ALUNOS NA CADERNETA PARA LANÇAMENTO DE NOTAS
    if ($acao === 'carregar_alunos') {
        $classe = isset($_GET['classe']) ? trim($_GET['classe']) : '';
        
        // Puxa os dados reais da pauta incluindo as colunas mac1(N1), npp1(N2), npt1(N3), faltas do phpMyAdmin
        $stmt = $pdo->prepare("SELECT id_utilizador, id_unico_escolar, nome, email as nota_n1, senha as nota_n2, periodo as nota_n3, saldo_propina as faltas, nivel FROM utilizadores WHERE nivel = ?");
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