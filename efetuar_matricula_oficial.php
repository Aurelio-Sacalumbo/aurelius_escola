<?php
// 🏢 MOTOR DE LIBERAÇÃO DE DISCIPLINAS E LIVROS — ACADEMIA AURÉLIUS (FLUXO MULTI-ALUNOS PDO)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'conexao.php';

$nome     = isset($_POST['nome']) ? trim($_POST['nome']) : '';
$telefone = isset($_POST['telefone']) ? trim($_POST['telefone']) : '';
$periodo  = isset($_POST['periodo']) ? trim($_POST['periodo']) : 'Manhã';
$curso    = isset($_POST['curso']) ? trim($_POST['curso']) : '';

if (empty($telefone) || empty($nome)) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Nome e Telefone do aluno necessários para ativar a matrícula.']);
    exit;
}

$classeLimpa = trim(str_replace(['[Inscrição]', '[', ']', '[Inscrição Inicial]'], '', $curso));
$cursoCompletoParaTabela = $classeLimpa . " [Língua Portuguesa, Matemática, História, Geografia, Inglês, Biologia]";

try {
    // 🔍 Localiza o filho exato que realizou a inscrição inicial combinando Nome + Telefone
    $check = $pdo->prepare("SELECT id_utilizador, id_unico_escolar, senha FROM utilizadores WHERE nome = ? AND telefone = ? AND nivel = 'estudante' LIMIT 1");
    $check->execute([$nome, $telefone]);
    $aluno = $check->fetch(PDO::FETCH_ASSOC);

    if (!$aluno) {
        echo json_encode(['sucesso' => false, 'mensagem' => '❌ Erro: Este estudante precisa fazer primeiro a Inscrição inicial (garantir a vaga) antes de se Matricular.']);
        exit;
    }

    $id_user = $aluno['id_utilizador'];
    $idEscolar = trim($aluno['id_unico_escolar']);
    $senhaExistente = trim($aluno['senha']);

    // 1. Atualiza o perfil para o estado Matriculado com a grade de disciplinas aberta
    $stmt = $pdo->prepare("UPDATE utilizadores SET curso = ?, periodo = ? WHERE id_utilizador = ?");
    $sucesso = $stmt->execute([$cursoCompletoParaTabela, $periodo, $id_user]);

    // 2. Transmite a grade para a tabela Horario para libertar os livros em formato de pastas
    $disciplinasPadrao = ['Língua Portuguesa', 'Matemática', 'História', 'Geografia', 'Inglês', 'Biologia'];
    $stmtHorario = $pdo->prepare("INSERT INTO Horario (id_matricula, nome_aluno, classe, periodo, hora, disciplina, professor, estado) 
                                  VALUES (?, ?, ?, ?, ?, ?, 'Corpo Docente', 'aprovado')
                                  ON DUPLICATE KEY UPDATE id_matricula = ?, classe = ?, estado = 'aprovado'");

    $contadorHora = 0;
    foreach ($disciplinasPadrao as $disc) {
        $horaReal = ($periodo === 'Manhã') ? (($contadorHora % 2 === 0) ? "09:00 - 10:00" : "10:00 - 11:00") : (($contadorHora % 2 === 0) ? "19:00 - 20:00" : "20:00 - 21:00");
        $stmtHorario->execute([$idEscolar, $nome, $classeLimpa, $periodo, $horaReal, $disc, $idEscolar, $classeLimpa]);

        // 3. Cria a caderneta de avaliações na tabela pautas
        $stmtPautaCheck = $pdo->prepare("SELECT COUNT(*) FROM pautas WHERE id_estudante = ? AND disciplina = ?");
        $stmtPautaCheck->execute([$id_user, $disc]);
        
        if ($stmtPautaCheck->fetchColumn() == 0) {
            $stmtPautaIns = $pdo->prepare("INSERT INTO pautas (id_estudante, disciplina, nota_n1, nota_n2, nota_n3, faltas) VALUES (?, ?, NULL, NULL, NULL, 0)");
            $stmtPautaIns->execute([$id_user, $disc]);
        }
        $contadorHora++;
    }

    echo json_encode([
        'sucesso' => true,
        'status' => 'sucesso',
        'mensagem' => '🎉 Matrícula oficializada com sucesso! Disciplinas e manuais didáticos libertados.',
        'id_unico_escolar' => $idEscolar,
        'senha_gerada' => $senhaExistente
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao processar ativação: ' . $e->getMessage()]);
}
exit;
?>