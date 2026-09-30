<?php
// 🗄️ LISTADOR DE ESTUDANTES PARA O CHAT - ACADEMIA AURÉLIUS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'conexao.php';

$resposta = ['sucesso' => false, 'alunos' => []];
$acao = isset($_GET['acao']) ? $_GET['acao'] : 'listar';

try {
    // 🔍 NOVA AÇÃO: VERIFICA SE O NÚMERO DE TELEFONE JÁ EXISTE ANTES DA MATRÍCULA
    if ($acao === 'verificar_telefone') {
        $telefone = isset($_GET['telefone']) ? trim($_GET['telefone']) : '';
        
        if (empty($telefone)) {
            echo json_encode(['sucesso' => true, 'existe' => false]);
            exit;
        }

        // Verifica se há algum registo na tabela utilizadores com o número fornecido
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM utilizadores WHERE telefone = ?");
        $stmt->execute([$telefone]);
        $existe = $stmt->fetchColumn() > 0;

        echo json_encode([
            'sucesso' => true,
            'existe' => $existe
        ]);
        exit;
    }
    if ($acao === 'verificar_telefone') {
        $telefone = isset($_GET['telefone']) ? trim($_GET['telefone']) : '';
        
        if (empty($telefone)) {
            echo json_encode(['sucesso' => true, 'existe' => false]);
            exit;
        }

        // Correção de Regra: Garante que o telefone pertence a um estudante com ID Único ativo
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM utilizadores WHERE telefone = ? AND nivel = 'estudante' AND id_unico_escolar IS NOT NULL AND id_unico_escolar != ''");
        $stmt->execute([$telefone]);
        $existe = $stmt->fetchColumn() > 0;

        echo json_encode([
            'sucesso' => true,
            'existe' => $existe
        ]);
        exit;
    }

    // 🗃️ AÇÃO PADRÃO: LISTAGEM ANTIGA DO CHAT
    // Puxa apenas utilizadores com perfil de estudante que tenham ID escolar gerado
    $query = "SELECT id_unico_escolar, nome FROM utilizadores WHERE id_unico_escolar IS NOT NULL AND id_unico_escolar != '' ORDER BY nome ASC";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    
    $resposta['alunos'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $resposta['sucesso'] = true;

} catch (Exception $e) {
    $resposta['mensagem'] = $e->getMessage();
}

echo json_encode($resposta);
exit;