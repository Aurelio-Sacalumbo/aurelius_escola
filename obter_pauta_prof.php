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
    // 🧮 AÇÃO 1: CARREGA OS INDICADORES GLOBAIS DE FATURAMENTO
    if ($acao === 'indicadores') {
        // Conta todos os alunos reais com IDs gerados no MySQL
        $total = $pdo->query("SELECT COUNT(*) FROM utilizadores WHERE id_unico_escolar IS NOT NULL AND id_unico_escolar != ''")->fetchColumn();
        
        // Conta alunos do Ensino Regular (que possuem a palavra 'Classe' no campo curso/nivel)
        $regular = $pdo->query("SELECT COUNT(*) FROM utilizadores WHERE (nivel LIKE '%Classe%' OR nivel LIKE '%classe%') AND id_unico_escolar IS NOT NULL")->fetchColumn();
        
        // O restante entra como ensino superior / cursos livres
        $superior = $total - $regular;
        
        // Faturamento real calculado automaticamente (ex: propina de 15.000 Kz por aluno registado)
        $faturamento = $total * 15000;

        // Puxa as turmas únicas existentes para o dropdown do filtro
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

    // 📚 AÇÃO 2: CARREGA OS ALUNOS REAIS NA CADERNETA PARA LANÇAMENTO DE NOTAS
    if ($acao === 'carregar_alunos') {
        $classe = isset($_GET['classe']) ? trim($_GET['classe']) : '';
        
        // Busca os alunos vinculados a essa classe/curso selecionada
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