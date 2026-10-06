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

        // 🔍 2. Lê as disciplinas alocadas para ele na tabela Horario (Filtrado estritamente por Nome)
        // 🎯 O GROUP BY garante que mesmo que o professor clique duas vezes, a disciplina só aparece UMA vez no horário!
        $stmtHorario = $pdo->prepare("SELECT classe, periodo, hora, disciplina, professor, estado 
                                      FROM Horario 
                                      WHERE nome_aluno = ? 
                                      GROUP BY disciplina 
                                      ORDER BY id_horario ASC");
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

                $autorReal = $dadosLivro && !empty($dadosLivro['autor']) ? trim($dadosLivro['autor']) : "Corpo Docente";
                $linkPdfReal = "";

                if ($dadosLivro && !empty($dadosLivro['categoria_curso'])) {
                    // 🎯 ALINHAMENTO AUTOMÁTICO PERMANENTE:
                    // Captura o caminho exatamente como o teu sistema de upload grava no banco (ex: uploads/manuais/xxxx.pdf)
                    $caminhoBanco = trim($dadosLivro['categoria_curso']);
                    
                    // Isola o nome encriptado do ficheiro limpo
                    $nomeFicheiroReal = basename(str_replace('\\', '/', $caminhoBanco));
                    
                    // Força a leitura a partir da pasta real de uploads que o teu sistema usa
                    $linkPdfReal = "uploads/manuais/" . $nomeFicheiroReal;
                }

                $disciplinasEstruturadas[] = [
                    'disciplina' => $nomeCadeira,
                    'horario'    => !empty($h['hora']) ? $h['hora'] : "19:00 - 20:00",
                    'professor'  => !empty($h['professor']) ? $h['professor'] : "Docente Alocado",
                    'estatuto'   => !empty($h['estado']) ? strtolower(trim($h['estado'])) : 'pendente',
                    'pdf_url'    => $linkPdfReal, // Devolve a rota automatica 'uploads/manuais/hash.pdf'
                    'autor'      => $autorReal
                ];
            }
        }

        // 🎯 ESTRUTURA ORIGINAL RESTAURADA: Mantém o array dados[0] intacto para o JavaScript ler
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
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro: ' . $e->getMessage()]);
}
exit;
?>
?>