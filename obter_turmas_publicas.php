<?php
// =========================================================================
// 🚀 ENDPOINT DE HORÁRIOS E LIVROS REAIS — ACADEMIA AURÉLIUS (PRODUÇÃO)
// =========================================================================
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'conexao.php';

try {
    $termo = isset($_GET['termo']) ? trim($_GET['termo']) : '';

    if (empty($termo)) {
        echo json_encode(['sucesso' => true, 'dados' => []]);
        exit;
    }

    // 🔍 1. Busca o estudante ativo de forma flexível usando LIKE nos três parâmetros de login
    $stmt = $pdo->prepare("SELECT nome, telefone, curso, periodo FROM utilizadores 
                           WHERE (id_unico_escolar LIKE ? OR nome LIKE ? OR telefone LIKE ?) 
                           AND nivel = 'estudante' LIMIT 1");
    
    $termoLike = "%" . $termo . "%";
    $stmt->execute([$termoLike, $termoLike, $termoLike]);
    $aluno = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($aluno) {
        $nomeAlunoReal = $aluno['nome'];
        $disciplinasEstruturadas = [];

        // 🔍 2. ALINHAMENTO COM O DBEAVER: Nome da tabela com 'H' maiúsculo ('Horario')
        // 🎯 O GROUP BY disciplina garante que remove as linhas duplicadas do professor.html automaticamente!
        $stmtHorario = $pdo->prepare("SELECT classe, periodo, hora, disciplina, professor, estado 
                                      FROM Horario 
                                      WHERE nome_aluno = ? 
                                      GROUP BY disciplina 
                                      ORDER BY id_horario ASC");
        $stmtHorario->execute([$nomeAlunoReal]);
        $linhasHorario = $stmtHorario->fetchAll(PDO::FETCH_ASSOC);

        // 🎯 CORREÇÃO CRÍTICA: Adicionado [0] para ler a string do array e não quebrar o servidor
        $partesCurso = !empty($aluno['curso']) ? explode('[', $aluno['curso']) : ["3ª Classe"];
        $classeLimpa = trim($partesCurso[0]);
        $periodoLimpo = !empty($aluno['periodo']) ? trim($aluno['periodo']) : 'Manhã';

        if (!empty($linhasHorario)) {
            // Prepara a busca do link do PDF na tabela 'livros'
            $stmtLivro = $pdo->prepare("SELECT autor, categoria_curso FROM livros WHERE titulo_livro = ? ORDER BY id_libro DESC LIMIT 1");

            foreach ($linhasHorario as $h) {
                $classeLimpa  = !empty($h['classe']) ? trim($h['classe']) : $classeLimpa;
                $periodoLimpo = !empty($h['periodo']) ? trim($h['periodo']) : $periodoLimpo;
                $nomeCadeira  = trim($h['disciplina']);

                // 🔍 3. Procura o PDF correspondente à cadeira do aluno
                $stmtLivro->execute([$nomeCadeira]);
                $dadosLivro = $stmtLivro->fetch(PDO::FETCH_ASSOC);

                $autorReal = $dadosLivro && !empty($dadosLivro['autor']) ? trim($dadosLivro['autor']) : "Academia Aurélius";
                $linkPdfReal = "";

                if ($dadosLivro && !empty($dadosLivro['categoria_curso'])) {
                    $caminhoBanco = trim($dadosLivro['categoria_curso']);
                    $nomeFicheiroReal = basename(str_replace('\\', '/', $caminhoBanco));
                    $linkPdfReal = "uploads/manuais/" . $nomeFicheiroReal;
                }

                $disciplinasEstruturadas[] = [
                    'disciplina' => $nomeCadeira,
                    'horario'    => !empty($h['hora']) ? $h['hora'] : "07:00 - 08:00",
                    'professor'  => !empty($h['professor']) ? $h['professor'] : "Professor Alocado",
                    'estatuto'   => !empty($h['estado']) ? strtolower(trim($h['estado'])) : 'aprovado',
                    'pdf_url'    => $linkPdfReal,
                    'autor'      => $autorReal
                ];
            }
        }

        // 🎯 RETORNO MULTIDIMENSIONAL ALINHADO: Mantém os colchetes duplos [[ ]] que o teu JavaScript lê
        echo json_encode([
            'sucesso' => true,
            'dados' => [[
                'id'       => 1,
                'nome'     => $nomeAlunoReal,
                'telefone' => !empty($aluno['telefone']) ? trim($aluno['telefone']) : 'Sem Número',
                'classe'   => trim($classeLimpa),
                'periodo'  => $periodoLimpo,
                'modulos'  => $disciplinasEstruturadas
            ]]
        ], JSON_UNESCAPED_UNICODE);
        exit;

    } else {
        echo json_encode(['sucesso' => true, 'dados' => []]);
    }

} catch (Exception $e) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro interno de servidor: ' . $e->getMessage()]);
}
exit;
?>