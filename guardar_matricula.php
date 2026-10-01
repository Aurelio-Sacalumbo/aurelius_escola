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
        // 🔍 Puxa todas as colunas de credenciais para garantir que não perde o código original
        $check = $pdo->prepare("SELECT id_utilizador, id_unico_escolar, senha, email FROM utilizadores WHERE telefone = ? AND nivel = 'estudante' LIMIT 1");
        $check->execute([$telefone]);
        $aluno = $check->fetch(PDO::FETCH_ASSOC);

        if ($aluno) {
            // 🔒 PRESERVAÇÃO IMPERIAL: Se existir senha ou código na coluna email, mantém! Senão gera um novo
            $id_unico = !empty($aluno['id_unico_escolar']) ? $aluno['id_unico_escolar'] : "AUR-" . rand(100000, 999999);
            
            // Verifica primeiro a coluna 'senha', depois a coluna 'email' (onde o unitel.php guarda o código)
            if (!empty($aluno['senha'])) {
                $senha_preservada = $aluno['senha'];
            } elseif (!empty($aluno['email'])) {
                $senha_preservada = $aluno['email'];
            } else {
                $senha_preservada = rand(100000, 999999);
            }

            // 🌟 UPDATE SEGURO: Atualiza o curso e mantém as credenciais intocáveis
            $stmt = $pdo->prepare("UPDATE utilizadores SET id_unico_escolar = ?, curso = ?, periodo = ?, senha = ?, email = ?, nome = ? WHERE id_utilizador = ?");
            $sucesso = $stmt->execute([$id_unico, $cursoCompleto, $periodo, $senha_preservada, $senha_preservada, $nome, $aluno['id_utilizador']]);

            echo json_encode([
                'sucesso' => $sucesso,
                'id_unico' => $id_unico,
                'senha_gerada' => $senha_preservada,
                'mensagem' => '🎉 Matrícula sincronizada com sucesso e credenciais preservadas!'
            ]);
        } else {
            // Se o aluno tentar matricular diretamente sem cadastro prévio, insere o registro completo do zero
            $id_novo = "AUR-" . rand(100000, 999999);
            $senha_nova = rand(100000, 999999);

            $stmt = $pdo->prepare("INSERT INTO utilizadores (nome, telefone, senha, email, nivel, id_unico_escolar, curso, periodo, saldo_propina) VALUES (?, ?, ?, ?, 'estudante', ?, ?, ?, 0)");
            $sucesso = $stmt->execute([$nome, $telefone, $senha_nova, $senha_nova, $id_novo, $cursoCompleto, $periodo]);

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
?>