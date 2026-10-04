<?php
// 🚀 ENDPOINT DE HORÁRIOS E LIVROS REAIS (PADRÃO PDO) — ACADEMIA AURÉLIUS
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

    // 🔍 1. Busca o estudante ativo na tabela utilizadores
    $stmt = $pdo->prepare("SELECT nome, telefone, curso, periodo FROM utilizadores 
                           WHERE (id_unico_escolar = ? OR nome LIKE ? OR telefone = ?) 
                           AND nivel = 'estudante' LIMIT 1");
    
    $stmt->execute([$termo, "%$termo%", $termo]);
    $aluno = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($aluno) {
        $nomeAlunoReal = $aluno['nome'];
        $disciplinasEstruturadas = [];

        // 🔍 2. Lê as disciplinas alocadas para ele na tabela Horario
        $stmtHorario = $pdo->prepare("SELECT classe, periodo, hora, disciplina, professor, estado FROM Horario WHERE nome_aluno = ? ORDER BY id_horario ASC");
        $stmtHorario->execute([$nomeAlunoReal]);
        $linhasHorario = $stmtHorario->fetchAll(PDO::FETCH_ASSOC);

        $classeLimpa = !empty($aluno['curso']) ? explode('[', $aluno['curso'])[0] : "12ª Classe";
        $periodoLimpo = !empty($aluno['periodo']) ? $aluno['periodo'] : 'Noite';

        if (!empty($linhasHorario)) {
            // Prepara a busca do link real encriptado do PDF na tabela 'livros'
            $stmtLivro = $pdo->prepare("SELECT autor, categoria_curso FROM livros WHERE titulo_livro = ? ORDER BY id_libro DESC LIMIT 1");

            foreach ($linhasHorario as $h) {
                $classeLimpa  = !empty($h['classe']) ? trim($h['classe']) : $classeLimpa;
                $periodoLimpo = !empty($h['periodo']) ? trim($h['periodo']) : $periodoLimpo;
                $nomeCadeira  = trim($h['disciplina']);

                // 🔍 3. Procura o PDF na tabela livros
                $stmtLivro->execute([$nomeCadeira]);
                $dadosLivro = $stmtLivro->fetch(PDO::FETCH_ASSOC);

                // Se houver registo no banco usa o PDF encriptado, senão deixa vazio de salvaguarda
                $linkPdfReal = $dadosLivro ? trim($dadosLivro['categoria_curso']) : "";
                $autorReal   = $dadosLivro && !empty($dadosLivro['autor']) ? trim($dadosLivro['autor']) : "Corpo Docente";

                $disciplinasEstruturadas[] = [
                    'disciplina' => $nomeCadeira,
                    'horario'    => !empty($h['hora']) ? $h['hora'] : "19:00 - 20:00",
                    'professor'  => !empty($h['professor']) ? $h['professor'] : "Docente Alocado",
                    'estatuto'   => !empty($h['estado']) ? strtolower(trim($h['estado'])) : 'pendente',
                    'pdf_url'    => $linkPdfReal, // Caminho real: uploads/manuais/xxxx.pdf
                    'autor'      => $autorReal
                ];
            }
        }

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

    } else {
        echo json_encode(['sucesso' => true, 'dados' => []]);
    }

} catch (Exception $e) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro: ' . $e->getMessage()]);
}
exit;
?>