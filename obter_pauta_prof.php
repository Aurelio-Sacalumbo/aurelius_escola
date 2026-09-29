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
        // Conta todos os inscritos
        $inscritos = $pdo->query("SELECT COUNT(*) FROM utilizadores")->fetchColumn();
        
        // Conta matriculados reais (Com ID AUR-)
        $matriculados = $pdo->query("SELECT COUNT(*) FROM utilizadores WHERE id_unico_escolar IS NOT NULL AND id_unico_escolar LIKE 'AUR-%'")->fetchColumn();
        
        // Regime Regular (Manhã ou Tarde)
        $regular = $pdo->query("SELECT COUNT(*) FROM utilizadores WHERE periodo LIKE '%Manhã%' OR periodo LIKE '%manhã%' OR periodo LIKE '%Tarde%' OR periodo LIKE '%tarde%'")->fetchColumn();
        
        // Regime Pós-Laboral (Noite)
        $posLaboral = $pdo->query("SELECT COUNT(*) FROM utilizadores WHERE periodo LIKE '%Noite%' OR periodo LIKE '%noite%'")->fetchColumn();
        
        // Faturamento Real baseado nos matriculados efetivos (Ex: 15.000 Kz por aluno)
        $faturamento = $matriculados * 15000;

        // Puxa as turmas do campo periodo ou nivel que contêm dados (ex: '9ª Classe', '10ª Classe')
        $stmtTurmas = $pdo->query("SELECT DISTINCT periodo FROM utilizadores WHERE periodo IS NOT NULL AND periodo != '' AND (periodo LIKE '%Classe%' OR periodo LIKE '%classe%') ORDER BY periodo ASC");
        $turmas = $stmtTurmas->fetchAll(PDO::FETCH_COLUMN);

        // Se não houver turmas no filtro, injeta as padrões para o seletor não sumir
        if (empty($turmas)) {
            $turmas = ["9ª Classe", "10ª Classe (II Ciclo)", "3ª classe"];
        }

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

    // 📚 AÇÃO 2: CARREGA TODOS OS ESTUDANTES PARA A PAUTA
    if ($acao === 'carregar_alunos') {
        $classe = isset($_GET['classe']) ? trim($_GET['classe']) : '';
        
        // Query ultra-segura: Procura correspondência na coluna 'periodo' (onde guardámos a classe nas tabelas anteriores)
        $stmt = $pdo->prepare("SELECT id_utilizador, id_unico_escolar, nome, email as nota_n1, senha as nota_n2, nivel as nota_n3, saldo_propina as faltas, telefone FROM utilizadores WHERE LOWER(periodo) LIKE LOWER(?) ORDER BY nome ASC");
        $stmt->execute(["%$classe%"]);
        
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