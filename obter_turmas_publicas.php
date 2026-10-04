<?php
// 🚀 ENDPOINT DE HORÁRIOS PÚBLICOS REALINHADO - ACADEMIA AURÉLIUS
header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
require_once 'conexao.php';

try {
    // Garante que o Render usa a base correta da escola
    mysqli_select_db($conexao_aurelius, "aurelius_escola");

    // 🔍 1. Agrupa os registos na tabela pública por aluno para evitar nomes repetidos no acordeão
    $queryEstudantes = "SELECT DISTINCT nome_aluno, classe, periodo FROM matriculas_turmas ORDER BY nome_aluno ASC";
    $resultEstudantes = mysqli_query($conexao_aurelius, $queryEstudantes);
    
    $dadosAgrupados = [];
    $idVirtual = 1;

    if ($resultEstudantes) {
        while ($aluno = mysqli_fetch_assoc($resultEstudantes)) {
            $nome = $aluno['nome_aluno'];
            $nomeFiltrado = mysqli_real_escape_string($conexao_aurelius, $nome);

            // 🔍 2. Procura todas as disciplinas, professores e turnos deste aluno específico
            $queryLinhas = "SELECT id_matricula, hora, disciplina, professor, estado FROM matriculas_turmas WHERE nome_aluno = '$nomeFiltrado'";
            $resultLinhas = mysqli_query($conexao_aurelius, $queryLinhas);
            
            $modulos = [];
            while ($linha = mysqli_fetch_assoc($resultLinhas)) {
                $modulos[] = [
                    'disciplina' => $linha['disciplina'],
                    'horario'    => !empty($linha['hora']) ? $linha['hora'] : 'Horário Geral',
                    'professor'  => !empty($linha['professor']) ? $linha['professor'] : 'Docente Responsável',
                    'estatuto'   => !empty($linha['estado']) ? $linha['estado'] : 'pendente'
                ];
            }

            // Adiciona o bloco estruturado ao array de envio
            $dadosAgrupados[] = [
                'id'      => $idVirtual++,
                'nome'    => $nome,
                'classe'  => !empty($aluno['classe']) ? $aluno['classe'] : 'Ensino Regular',
                'periodo' => !empty($aluno['periodo']) ? $aluno['periodo'] : 'Turno Ativo',
                'modulos' => $modulos
            ];
        }
    }

    // Retorna a resposta JSON no formato exato esperado pela Lista.html
    echo json_encode(['sucesso' => true, 'dados' => $dadosAgrupados], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao processar pautas públicas: ' . $e->getMessage()]);
}
exit;