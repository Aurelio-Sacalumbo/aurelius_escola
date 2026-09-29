<?php
// 📊 CENTRAL DE GRAVAÇÃO E GESTÃO DOCENTE - ACADEMIA AURÉLIUS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'conexao.php';

// =========================================================================
// ❌ AÇÃO 1: ELIMINAR ESTUDANTE DA BASE DE DADOS (VIA FORMDATA)
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao_gestao']) && $_POST['acao_gestao'] === 'eliminar_aluno') {
    $id = intval($_POST['id_utilizador']);
    try {
        $stmt = $pdo->prepare("DELETE FROM utilizadores WHERE id_utilizador = ?");
        $sucesso = $stmt->execute([$id]);
        echo json_encode(['sucesso' => $sucesso, 'mensagem' => 'Registo removido com sucesso do MySQL.']);
    } catch (Exception $e) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao eliminar: ' . $e->getMessage()]);
    }
    exit;
}

// =========================================================================
// 🏢 AÇÃO 2: ATUALIZAR ESTADO / DIRECIONAMENTO DO ALUNO (VIA FORMDATA)
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao_gestao']) && $_POST['acao_gestao'] === 'atualizar_estado_aluno') {
    $id = intval($_POST['id_utilizador']);
    $classe = trim($_POST['classe_turma']);
    $periodo = trim($_POST['periodo']);

    try {
        // Atualiza a turma (nivel) e o período na tabela utilizadores
        $stmt = $pdo->prepare("UPDATE utilizadores SET nivel = ?, periodo = ? WHERE id_utilizador = ?");
        $sucesso = $stmt->execute([$classe, $periodo, $id]);
        echo json_encode(['sucesso' => $sucesso, 'mensagem' => 'Estado e direcionamento atualizados com sucesso!']);
    } catch (Exception $e) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao atualizar estado: ' . $e->getMessage()]);
    }
    exit;
}

// =========================================================================
// 📊 AÇÃO 3: SALVAMENTO EM LOTE / BATCH UPDATE DE NOTAS (VIA JSON PAYLOAD)
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dadosJson = file_get_contents('php://input');
    $dados = json_decode($dadosJson, true);

    if (empty($dados) || !is_array($dados)) {
        echo json_encode(['sucesso' => false, 'mensagem' => '⚠️ Nenhuma nota ou ação válida foi enviada para processamento.']);
        exit;
    }

    try {
        $pdo->beginTransaction(); 

        // Query vinculando os inputs às colunas: email(N1), senha(N2), periodo(N3), saldo_propina(Faltas)
        $stmt = $pdo->prepare("UPDATE utilizadores SET email = ?, senha = ?, periodo = ?, saldo_propina = ? WHERE id_utilizador = ?");

        foreach ($dados as $linha) {
            $id = intval($linha['id_utilizador']);
            $n1 = trim($linha['n1']);
            $n2 = trim($linha['n2']);
            $n3 = trim($linha['n3']);
            $faltas = floatval($linha['faltas']);

            $stmt->execute([$n1, $n2, $n3, $faltas, $id]);
        }

        $pdo->commit(); 
        echo json_encode(['sucesso' => true, 'mensagem' => '🎉 Caderneta guardada e sincronizada no MySQL com sucesso!']);
    } catch (Exception $e) {
        $pdo->rollBack(); 
        echo json_encode(['sucesso' => false, 'mensagem' => '❌ Erro no banco de dados: ' . $e->getMessage()]);
    }
    exit;
} else {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Método inválido.']);
    exit;
}