<?php
// 🗄️ LISTADOR DE ESTUDANTES PARA O CHAT E INDICADORES - ACADEMIA AURÉLIUS
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
    // 🔍 VERIFICA SE O NÚMERO DE TELEFONE JÁ EXISTE ANTES DA MATRÍCULA
    if ($acao === 'verificar_telefone') {
        $telefone = isset($_GET['telefone']) ? trim($_GET['telefone']) : '';
        
        if (empty($telefone)) {
            echo json_encode(['sucesso' => true, 'existe' => false]);
            exit;
        }

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM utilizadores WHERE telefone = ? AND nivel = 'estudante'");
        $stmt->execute([$telefone]);
        $existe = $stmt->fetchColumn() > 0;

        echo json_encode(['sucesso' => true, 'existe' => $existe]);
        exit;
    }

    // 🗃️ AÇÃO PADRÃO: LISTAGEM PARA O CHAT E INDICADORES DO PROFESSOR
    // 🌟 ADICIONADAS AS COLUNAS: nivel, curso, periodo e saldo_propina para alimentar os contadores visuais
    $query = "SELECT IFNULL(id_unico_escolar, telefone) AS id_unico_escolar, nome, nivel, curso, periodo, saldo_propina 
              FROM utilizadores 
              WHERE nome IS NOT NULL AND nome != '' AND nivel = 'estudante' 
              ORDER BY nome ASC";
              
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    
    $resposta['alunos'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $resposta['sucesso'] = true;

} catch (Exception $e) {
    $resposta['mensagem'] = $e->getMessage();
}

echo json_encode($resposta);
exit;
?>