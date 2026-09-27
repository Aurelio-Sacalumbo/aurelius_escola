<?php
// gerir_pauta.php
header("Content-Type: application/json; charset=UTF-8");
require_once "conexao.php";

$metodo = $_SERVER['REQUEST_METHOD'];
$dados = json_decode(file_get_contents("php://input"), true);

// 1. LISTAR ALUNOS (GET)
if ($metodo === 'GET') {
    try {
        $stmt = $pdo->query("SELECT * FROM matriculas_turmas ORDER BY estado DESC, nome_aluno ASC");
        echo json_encode(["sucesso" => true, "dados" => $stmt->fetchAll()]);
    } catch (Exception $e) {
        echo json_encode(["sucesso" => false, "mensagem" => $e->getMessage()]);
    }
    exit;
}

// 2. PROCESSAR AÇÕES DO PROFESSOR (POST)
if ($metodo === 'POST' && isset($dados['acao'])) {
    $acao = $dados['acao'];

    try {
        if ($acao === 'salvar') {
            // Cria ou edita uma alocação de turma
            if (!empty($dados['id_matricula'])) {
                $stmt = $pdo->prepare("UPDATE matriculas_turmas SET nome_aluno=?, classe=?, turma=?, periodo=?, hora=?, disciplina=?, professor=?, estado=? WHERE id_matricula=?");
                $stmt->execute([$dados['nome_aluno'], $dados['classe'], $dados['turma'], $dados['periodo'], $dados['hora'], $dados['disciplina'], $dados['professor'], $dados['estado'], $dados['id_matricula']]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO matriculas_turmas (nome_aluno, classe, turma, periodo, hora, disciplina, professor, estado) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$dados['nome_aluno'], $dados['classe'], $dados['turma'], $dados['periodo'], $dados['hora'], $dados['disciplina'], $dados['professor'], $dados['estado']]);
            }
            echo json_encode(["sucesso" => true, "mensagem" => "Registo salvo com sucesso!"]);
        } 
        
        elseif ($acao === 'eliminar') {
            $stmt = $pdo->prepare("DELETE FROM matriculas_turmas WHERE id_matricula = ?");
            $stmt->execute([$dados['id_matricula']]);
            echo json_encode(["sucesso" => true, "mensagem" => "Registo eliminado com sucesso!"]);
        }
        
        elseif ($acao === 'alterar_estado') {
            // Aprova ou reprova a matrícula do curso
            $stmt = $pdo->prepare("UPDATE matriculas_turmas SET estado = ? WHERE id_matricula = ?");
            $stmt->execute([$dados['novo_estado'], $dados['id_matricula']]);
            echo json_encode(["sucesso" => true, "mensagem" => "Estado atualizado para " . $dados['novo_estado']]);
        }
    } catch (Exception $e) {
        echo json_encode(["sucesso" => false, "mensagem" => "Erro: " . $e->getMessage()]);
    }
    exit;
}
?>