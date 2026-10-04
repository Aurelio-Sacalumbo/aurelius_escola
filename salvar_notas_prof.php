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
// 📥 1. PROCESSAMENTO DE REQUISIÇÕES EM JSONPayLoad (professor.html / Fetch)
// =========================================================================
$inputRaw = file_get_contents('php://input');
$dadosJson = json_decode($inputRaw, true);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($dadosJson)) {
    
    $acao = isset($dadosJson['acao_professor']) ? trim($dadosJson['acao_professor']) : '';
    if (empty($acao) && isset($dadosJson['acao_gestao'])) {
        $acao = trim($dadosJson['acao_gestao']);
    }

    // 🌟 ROTA A: SALVAR DIRECIONAMENTO DE TURMA NA TABELA HORARIO
    if ($acao === 'salvar_turma_direcionamento') {
        try {
            $idMatricula = isset($dadosJson['id_matricula']) ? trim($dadosJson['id_matricula']) : 'MAT-' . rand(100000, 999999);
            $nome_aluno  = trim($dadosJson['nome_aluno']);
            $classe      = trim($dadosJson['classe']);
            $periodo     = trim($dadosJson['periodo']);
            $hora        = trim($dadosJson['hora']);
            $disciplina  = trim($dadosJson['disciplina']);
            $professor   = trim($dadosJson['professor']);
            $estado      = trim($dadosJson['estado']);

            if (empty($nome_aluno) || empty($disciplina)) {
                echo json_encode(['sucesso' => false, 'mensagem' => 'Nome do aluno ou disciplina ausentes.']);
                exit;
            }

            $stmt = $pdo->prepare("INSERT INTO Horario (id_matricula, nome_aluno, classe, periodo, hora, disciplina, professor, estado) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                                   ON DUPLICATE KEY UPDATE hora = ?, professor = ?, estado = ?");
            $sucesso = $stmt->execute([
                $idMatricula, $nome_aluno, $classe, $periodo, $hora, $disciplina, $professor, $estado,
                $hora, $professor, $estado
            ]);

            echo json_encode(['sucesso' => $sucesso, 'mensagem' => '🎉 Alocação guardada com sucesso na tabela Horario!']);
        } catch (Exception $e) {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao salvar horário: ' . $e->getMessage()]);
        }
        exit;
    }

    // 🌟 ROTA B: ELIMINAR DIRECIONAMENTO ESPECÍFICO DA TABELA HORARIO
    if ($acao === 'eliminar_disciplina_pauta') {
        try {
            $nome_aluno = trim($dadosJson['nome_aluno']);
            $disciplina = trim($dadosJson['disciplina']);

            $stmt = $pdo->prepare("DELETE FROM Horario WHERE nome_aluno = ? AND disciplina = ?");
            $sucesso = $stmt->execute([$nome_aluno, $disciplina]);

            echo json_encode(['sucesso' => $sucesso, 'mensagem' => 'Registo removido com sucesso da tabela Horario.']);
        } catch (Exception $e) {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao eliminar: ' . $e->getMessage()]);
        }
        exit;
    }

    // 📊 ROTA C: SALVAMENTO EM LOTE / BATCH UPDATE DE NOTAS REAIS (CADERNETA)
    if (is_array($dadosJson) && empty($acao)) {
        try {
            $pdo->beginTransaction(); 

            // 🎯 CORREÇÃO DO BANCO: Atualiza a tabela pautas com os nomes nota_n1, nota_n2 e nota_n3
            $stmt = $pdo->prepare("UPDATE pautas SET nota_n1 = ?, nota_n2 = ?, nota_n3 = ?, faltas = ? WHERE id_estudante = ? AND disciplina = ?");

            foreach ($dadosJson as $linha) {
                $id     = intval($linha['id_utilizador']);
                $disc   = trim($linha['disciplina']);
                $n1     = ($linha['n1'] !== "" && $linha['n1'] !== null) ? floatval($linha['n1']) : null;
                $n2     = ($linha['n2'] !== "" && $linha['n2'] !== null) ? floatval($linha['n2']) : null;
                $n3     = ($linha['n3'] !== "" && $linha['n3'] !== null) ? floatval($linha['n3']) : null;
                $faltas = intval($linha['faltas']);

                $stmt->execute([$n1, $n2, $n3, $faltas, $id, $disc]);
            }

            $pdo->commit(); 
            echo json_encode(['sucesso' => true, 'mensagem' => '🎉 Perfeito! A pauta de todas as disciplinas foi guardada e sincronizada com sucesso!']);
        } catch (Exception $e) {
            $pdo->rollBack(); 
            echo json_encode(['sucesso' => false, 'mensagem' => '❌ Erro no banco de dados: ' . $e->getMessage()]);
        }
        exit;
    }
}

// =========================================================================
// 📥 2. PROCESSAMENTO DE REQUISIÇÕES VIA FORMDATA TRADICIONAL ($_POST)
// =========================================================================

// ❌ AÇÃO 1 ORIGINAL: ELIMINAR ESTUDANTE DA BASE DE DADOS
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

// 🏢 AÇÃO 2 ORIGINAL: ATUALIZAR ESTADO / DIRECIONAMENTO DO ALUNO (MATRÍCULA)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao_gestao']) && $_POST['acao_gestao'] === 'atualizar_estado_aluno') {
    $id = intval($_POST['id_utilizador']);
    $classe = trim($_POST['classe_turma']);
    $periodo = trim($_POST['periodo']);
    $horario = isset($_POST['horario']) ? trim($_POST['horario']) : '07:00-08:00';
    $curso = isset($_POST['curso']) ? trim($_POST['curso']) : 'Geral';

    try {
        $verificar = $pdo->prepare("SELECT id_unico_escolar FROM utilizadores WHERE id_utilizador = ?");
        $verificar->execute([$id]);
        $alunoAtual = $verificar->fetch();

        $novoID = $alunoAtual['id_unico_escolar'];
        if (empty($novoID) || !str_contains($novoID, 'AUR-')) {
            $novoID = "AUR-" . rand(100000, 999999);
        }

        $stmt = $pdo->prepare("UPDATE utilizadores SET nivel = ?, curso = ?, periodo = ?, id_unico_escolar = ? WHERE id_utilizador = ?");
        $sucesso = $stmt->execute([$classe, $curso, $periodo, $novoID, $id]);

        echo json_encode(['sucesso' => $sucesso, 'mensagem' => '🎉 Aluno aprovado e matriculado com sucesso no MySQL!']);
    } catch (Exception $e) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao atualizar: ' . $e->getMessage()]);
    }
    exit;
}

echo json_encode(['sucesso' => false, 'mensagem' => 'Método ou ação inválida recebida no servidor central.']);
exit;
?>