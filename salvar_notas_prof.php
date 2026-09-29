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
// =========================================================================
// 🏢 AÇÃO 2: ATUALIZAR ESTADO / DIRECIONAMENTO DO ALUNO (VIA FORMDATA)
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao_gestao']) && $_POST['acao_gestao'] === 'atualizar_estado_aluno') {
    header('Content-Type: application/json; charset=utf-8');
    require_once 'conexao.php';

    $id = intval($_POST['id_utilizador']);
    $classe = trim($_POST['classe_turma']);
    $periodo = trim($_POST['periodo']);
    
    // Novas colunas dinâmicas enviadas pelo seu formulário avançado
    $horario = isset($_POST['horario']) ? trim($_POST['horario']) : '07:00-08:00';
    $curso = isset($_POST['curso']) ? trim($_POST['curso']) : 'Geral';

    try {
        // Se o professor selecionou "Aprovado (Direto)", vamos gerar o ID Único Escolar definitivo (Ex: AUR-XXXXXX)
        // apenas se o aluno ainda não tiver um, para libertar a pauta de notas automaticamente!
        $verificar = $pdo->prepare("SELECT id_unico_escolar FROM utilizadores WHERE id_utilizador = ?");
        $verificar->execute([$id]);
        $alunoAtual = $verificar->fetch();

        $novoID = $alunoAtual['id_unico_escolar'];
        if (empty($novoID) || !str_contains($novoID, 'AUR-')) {
            $novoID = "AUR-" . rand(100000, 999999);
        }

        // Alinha os dados dinamicamente com as colunas reais da sua tabela utilizadores:
        // nivel = classe do aluno, curso = Disciplina, periodo = Turno, id_unico_escolar = Matrícula ativa
        $stmt = $pdo->prepare("UPDATE utilizadores SET nivel = ?, curso = ?, periodo = ?, id_unico_escolar = ? WHERE id_utilizador = ?");
        $sucesso = $stmt->execute([$classe, $curso, $periodo, $novoID, $id]);

        echo json_encode(['sucesso' => $sucesso, 'mensagem' => '🎉 Aluno aprovado e matriculado com sucesso no MySQL!']);
    } catch (Exception $e) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao atualizar: ' . $e->getMessage()]);
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