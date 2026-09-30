<?php
// 🗄️ REPOSITÓRIO DE NOTAS DO ESTUDANTE - ACADEMIA AURÉLIUS (CORRIGIDO)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'conexao.php';

$resposta = ['sucesso' => false];

// Recebe o ID do aluno logado via parâmetro GET
$id_estudante = isset($_GET['id_estudante']) ? trim($_GET['id_estudante']) : '';

if (empty($id_estudante)) {
    echo json_encode(['sucesso' => false, 'mensagem' => '⚠️ Identificador escolar ausente.']);
    exit;
}

try {
    // 🔍 FIX CIRÚRGICO: Puxa as colunas reais do phpMyAdmin e define fallbacks pedagógicos para as notas
    $stmt = $pdo->prepare("SELECT id_utilizador, nome, telefone, id_unico_escolar, curso, periodo FROM utilizadores WHERE id_unico_escolar = ? AND nivel = 'estudante' LIMIT 1");
    $stmt->execute([$id_estudante]);
    $aluno = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($aluno) {
        $resposta['sucesso'] = true;
        
        // Dados do cabeçalho que o JavaScript precisa para preencher os traços (---)
        $resposta['estudante'] = [
            'nome' => $aluno['nome'],
            'id_unico_escolar' => $aluno['id_unico_escolar'],
            'classe' => !empty($aluno['curso']) ? $aluno['curso'] : '9ª Classe',
            'curso' => $aluno['curso'],
            'periodo' => !empty($aluno['periodo']) ? $aluno['periodo'] : 'Manhã',
            'turma' => 'Turma Única A'
        ];

        // 📊 Valores pedagógicos padrão (Serão atualizados quando fizermos o professor.html)
        $resposta['notas'] = [
            'n1' => 0,
            'n2' => 0,
            'n3' => 0,
            'faltas' => 0
        ];
        
    } else {
        $resposta['mensagem'] = 'Estudante não localizado na base académica do Huambo.';
    }
} catch (Exception $e) {
    $resposta['mensagem'] = 'Erro no servidor: ' . $e->getMessage();
}

echo json_encode($resposta);
exit;
?>