<?php
// 🚀 ENDPOINT DE HORÁRIOS PÚBLICOS CENTRALIZADO (PADRÃO PDO) — ACADEMIA AURÉLIUS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET");
header('Content-Type: application/json; charset=utf-8');

require_once 'conexao.php';

try {
    $termo = isset($_GET['termo']) ? trim($_GET['termo']) : '';

    if (empty($termo)) {
        echo json_encode(['sucesso' => true, 'dados' => []]);
        exit;
    }

    // 🔍 1. Busca o estudante ativo na tabela utilizadores (Garante que o aluno existe)
    $stmt = $pdo->prepare("SELECT nome, telefone FROM utilizadores 
                           WHERE (id_unico_escolar = ? OR nome LIKE ? OR telefone = ?) 
                           AND nivel = 'estudante' LIMIT 1");
    
    $stmt->execute([$termo, "%$termo%", $termo]);
    $aluno = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($aluno) {
        $nomeAlunoReal = $aluno['nome'];
        $disciplinasEstruturadas = [];

        // 🔍 2. LEITURA EXCLUSIVA: Procura APENAS as linhas gravadas na tabela Horario
        $stmtHorario = $pdo->prepare("SELECT id_matricula, classe, periodo, hora, disciplina, professor, estado FROM Horario WHERE nome_aluno = ? ORDER BY id_horario ASC");
        $stmtHorario->execute([$nomeAlunoReal]);
        $linhasHorario = $stmtHorario->fetchAll(PDO::FETCH_ASSOC);

        // Se o professor eliminou tudo, as variáveis de cabeçalho vêm do cadastro base
        $classeLimpa = "12ª Classe";
        $periodoLimpo = !empty($aluno['periodo']) ? $aluno['periodo'] : 'Noite';

        if (!empty($linhasHorario)) {
            foreach ($linhasHorario as $h) {
                $classeLimpa  = !empty($h['classe']) ? trim($h['classe']) : $classeLimpa;
                $periodoLimpo = !empty($h['periodo']) ? trim($h['periodo']) : $periodoLimpo;

                $disciplinasEstruturadas[] = [
                    'disciplina' => trim($h['disciplina']),
                    'horario'    => !empty($h['hora']) ? $h['hora'] : "19:00 - 20:00",
                    'professor'  => !empty($h['professor']) ? $h['professor'] : "Docente Alocado",
                    'estatuto'   => !empty($h['estado']) ? strtolower(trim($h['estado'])) : 'pendente'
                ];
            }
        }

        // 🌟 RETORNO SEGURO: Só monta o acordeão se existirem disciplinas reais na tabela Horario
        if (!empty($disciplinasEstruturadas)) {
            echo json_encode([
                'sucesso' => true,
                'dados' => [[
                    'id'       => 1,
                    'nome'     => $nomeAlunoReal,
                    'telefone' => !empty($aluno['telefone']) ? trim($aluno['telefone']) : 'Sem Número',
                    'classe'   => $classeLimpa,
                    'periodo'  => $periodoLimpo,
                    'modulos'  => $disciplinasEstruturadas
                ]]
            ], JSON_UNESCAPED_UNICODE);
        } else {
            // Se foi tudo eliminado no banco, retorna vazio para limpar o ecrã público instantaneamente!
            echo json_encode(['sucesso' => true, 'dados' => []]);
        }

    } else {
        echo json_encode(['sucesso' => true, 'dados' => []]);
    }

} catch (Exception $e) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro: ' . $e->getMessage()]);
}
exit;
?>