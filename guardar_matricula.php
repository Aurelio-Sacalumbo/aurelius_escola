<?php
// 💾 GRAVAÇÃO DE MATRÍCULA - ACADEMIA AURÉLIUS (HUAMBO)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST");
header('Content-Type: application/json; charset=utf-8');

require_once 'conexao.php';

$inputRaw = file_get_contents("php://input");
$dados = json_decode($inputRaw, true);

if ($dados) {
    $telefone = trim($dados['telefone']);
    $nome = trim($dados['nome']);
    $cursoCompleto = trim($dados['classe']) . " [" . trim($dados['disciplinas']) . "]";
    $periodo = trim($dados['periodo']);

    try {
        // 🔍 Verifica se o aluno já possui uma linha ativa
        $check = $pdo->prepare("SELECT id_utilizador, id_unico_escolar, senha FROM utilizadores WHERE telefone = ? AND nivel = 'estudante' LIMIT 1");
        $check->execute([$telefone]);
        $aluno = $check->fetch(PDO::FETCH_ASSOC);

        if ($aluno) {
            // Se já tiver credenciais, mantém, senão gera novas de 6 dígitos em texto limpo
            $id_unico = !empty($aluno['id_unico_escolar']) ? $aluno['id_unico_escolar'] : "AUR-" . rand(100000, 999999);
            $senha_limpa = !empty($aluno['senha']) ? $aluno['senha'] : rand(100000, 999999);

            // 🌟 CORREÇÃO CIRÚRGICA: Sem a coluna errada 'id', usa as colunas reais da sua tabela
            $stmt = $pdo->prepare("UPDATE utilizadores SET id_unico_escolar = ?, curso = ?, periodo = ?, senha = ?, nome = ? WHERE id_utilizador = ?");
            $sucesso = $stmt->execute([$id_unico, $cursoCompleto, $periodo, $senha_limpa, $nome, $aluno['id_utilizador']]);

            echo json_encode([
                'sucesso' => $sucesso,
                'id_unico' => $id_unico,
                'senha_gerada' => $senha_limpa,
                'mensagem' => '🎉 Matrícula sincronizada com sucesso!'
            ]);
        } else {
            // Se o aluno tentou matricular diretamente sem cadastro prévio, insere o registro completo do zero
            $id_novo = "AUR-" . rand(100000, 999999);
            $senha_nova = rand(100000, 999999);

            $stmt = $pdo->prepare("INSERT INTO utilizadores (nome, telefone, senha, nivel, id_unico_escolar, curso, periodo, saldo_propina) VALUES (?, ?, ?, 'estudante', ?, ?, ?, 0)");
            $sucesso = $stmt->execute([$nome, $telefone, $senha_nova, $id_novo, $cursoCompleto, $periodo]);

            echo json_encode([
                'sucesso' => $sucesso,
                'id_unico' => $id_novo,
                'senha_gerada' => $senha_nova,
                'mensagem' => '🎉 Novo aluno inserido e matriculado com sucesso!'
            ]);
        }
    } catch (Exception $e) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Erro MySQL: ' . $e->getMessage()]);
    }
    exit;
}
?>