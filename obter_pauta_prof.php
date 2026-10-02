<?php
// 📊 MOTOR DE INDICADORES BLINDADO E CORRIGIDO — ACADEMIA AURÉLIUS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET");
header('Content-Type: application/json; charset=utf-8');

require_once 'conexao.php';

$acao = isset($_GET['acao']) ? $_GET['acao'] : '';

// 🔍 1. PROCESSADOR DE INDICADORES DE FATURAMENTO REAL
if ($acao === 'indicadores') {
    try {
        $stmt = $pdo->query("SELECT * FROM utilizadores");
        $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $totalInscritos = 0;
        $totalMatriculados = 0;
        $regularCount = 0;
        $noiteCount = 0;
        $faturamentoTotal = 0;
        $turmasDetectadas = [];

        $stmtPreco = $pdo->prepare("SELECT preco_base FROM cursos_disciplinas WHERE nome = ? LIMIT 1");

        foreach ($usuarios as $u) {
            $cursoStr = "";
            $periodoStr = "";
            $isEstudante = false;

            // Varredura por conteúdo para suportar colunas deslocadas
            foreach ($u as $chave => $valor) {
                $val = trim((string)$valor);
                if (empty($val)) continue;

                if ($val === 'estudante') {
                    $isEstudante = true;
                }
                if (strpos($val, '[') !== false || stripos($val, 'classe') !== false) {
                    $cursoStr = $val;
                }
                if (stripos($val, 'Manhã') !== false || stripos($val, 'Tarde') !== false || stripos($val, 'Noite') !== false) {
                    $periodoStr = $val;
                }
            }

            if (!$isEstudante && empty($cursoStr)) continue;

            $totalInscritos++;
            $isNoite = (!empty($periodoStr) && stripos($periodoStr, 'Noite') !== false);

            // Verifica se possui disciplinas ativas entre colchetes [ ] (Matriculado)
            if (!empty($cursoStr) && strpos($cursoStr, '[') !== false && strpos($cursoStr, 'Inscrição') === false) {
                $totalMatriculados++;
                if ($isNoite) $noiteCount++; else $regularCount++;

                // 🌟 CORREÇÃO CIRÚRGICA: Isola o primeiro índice do array antes de aplicar o trim!
                $partesClasse = explode('[', $cursoStr);
                $nomeClasseApenas = isset($partesClasse[0]) ? trim($partesClasse[0]) : "9ª classe";
                
                if (!empty($nomeClasseApenas) && !in_array($nomeClasseApenas, $turmasDetectadas)) {
                    $turmasDetectadas[] = $nomeClasseApenas;
                }

                // Calcula os preços reais das disciplinas associadas
                preg_match('/\[(.*?)\]/', $cursoStr, $matches);
                if (isset($matches[1])) {
                    $listaDisc = explode(',', $matches[1]);
                    $subTotalAluno = 0;
                    $cadeirasContadas = 0;

                    foreach ($listaDisc as $d) {
                        $nomeCadeira = trim($d);
                        if (empty($nomeCadeira)) continue;

                        $stmtPreco->execute([$nomeCadeira]);
                        $precoBanc = $stmtPreco->fetchColumn();
                        $subTotalAluno += ($precoBanc !== false) ? floatval($precoBanc) : 1500.00;
                        $cadeirasContadas++;
                    }

                    if ($cadeirasContadas >= 4) {
                        $subTotalAluno *= 0.80; // 20% OFF
                    }
                    $faturamentoTotal += $subTotalAluno;
                }
            } else {
                // Caso seja apenas inscrição básica
                $faturamentoTotal += 1500.00; 
                
                $partesClasse = explode('[', $cursoStr);
                $nomeClasseApenas = isset($partesClasse[0]) ? trim($partesClasse[0]) : "9ª classe";
                if (empty($nomeClasseApenas)) { $nomeClasseApenas = "9ª classe"; }

                if (!in_array($nomeClasseApenas, $turmasDetectadas)) {
                    $turmasDetectadas[] = $nomeClasseApenas;
                }
                
                if ($isNoite) $noiteCount++; else $regularCount++;
            }
        }

        echo json_encode([
            'sucesso' => true,
            'inscritos' => $totalInscritos,
            'matriculados' => $totalMatriculados,
            'regular' => $regularCount,
            'pos_laboral' => $noiteCount,
            'faturamento' => $faturamentoTotal,
            'turmas' => $turmasDetectadas
        ], JSON_UNESCAPED_UNICODE);

    } catch (Exception $e) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Erro nos indicadores: ' . $e->getMessage()]);
    }
    exit;
}

// 🔍 2. LISTAR ESTUDANTES DA TURMA SELECIONADA
if (isset($_GET['classe'])) {
    $classeAlvo = trim($_GET['classe']);
    try {
        // 🌟 BLINDAGEM IMPERIAL: Procura o termo na coluna curso OU na coluna periodo
        // Isso garante que se o professor filtrar por "Manhã", "Noite" ou pelo nome do Curso, os alunos aparecem!
        $stmt = $pdo->prepare("
            SELECT id_utilizador, id_unico_escolar, nome, periodo, curso 
            FROM utilizadores 
            WHERE (curso LIKE ? OR periodo LIKE ? OR id_unico_escolar = ?)
              AND nome IS NOT NULL AND nome != ''
            ORDER BY nome ASC
        ");
        
        $termoBusca = "%" . $classeAlvo . "%";
        $stmt->execute([$termoBusca, $termoBusca, $classeAlvo]);
        $alunos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['sucesso' => true, 'alunos' => $alunos], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Erro na listagem: ' . $e->getMessage()]);
    }
    exit;
}
?>