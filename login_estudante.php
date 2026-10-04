<?php
// 🔑 SESSÃO E AUTENTICAÇÃO DO ESTUDANTE — ACADEMIA AURÉLIUS (PADRÃO PDO BLINDADO)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'conexao.php';

$inputRaw = file_get_contents('php://input');
$dadosJson = json_decode($inputRaw, true);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($dadosJson)) {
    $identificador = isset($dadosJson['identificador']) ? trim($dadosJson['identificador']) : '';
    $senhaInserida = isset($dadosJson['senha']) ? trim($dadosJson['senha']) : '';

    if (empty($identificador) || empty($senhaInserida)) {
        echo json_encode(['sucesso' => false, 'mensagem' => '⚠️ Preencha o ID/Telefone e a senha de acesso.']);
        exit;
    }

    try {
        // 🔍 1. Procura o estudante na base utilizando o $pdo correto (Suporta ID AUR ou Telefone)
        $stmt = $pdo->prepare("SELECT id_utilizador, nome, telefone, curso, periodo, id_unico_escolar, senha, email 
                               FROM utilizadores 
                               WHERE (id_unico_escolar = ? OR telefone = ? OR email = ?) 
                               AND nivel = 'estudante' LIMIT 1");
        $stmt->execute([$identificador, $identificador, $identificador]);
        $aluno = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($aluno) {
            $senhaBanco = trim($aluno['senha']);
            $emailBanco = trim($aluno['email']);
            $senhaValida = false;

            // 🔐 2. COMPARAÇÃO FLEXÍVEL: Valida texto limpo ou MD5 histórico do banco
            if ($senhaBanco === $senhaInserida || $senhaBanco === md5($senhaInserida) || $emailBanco === $senhaInserida) {
                $senhaValida = true;
            }

            if ($senhaValida) {
                $classeFormatada = !empty($aluno['curso']) ? $aluno['curso'] : '12ª Classe [Inscrição]';

                echo json_encode([
                    'sucesso' => true,
                    'mensagem' => '🎉 Acesso autorizado com sucesso!',
                    'estudante' => [
                        'id_utilizador' => $aluno['id_utilizador'],
                        'nome' => $aluno['nome'],
                        'id_unico_escolar' => !empty($aluno['id_unico_escolar']) ? $aluno['id_unico_escolar'] : $identificador,
                        'classe' => $classeFormatada,
                        'periodo' => !empty($aluno['periodo']) ? $aluno['periodo'] : 'Noite',
                        'turma' => 'Turma Única A'
                    ]
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
        }

        echo json_encode(['sucesso' => false, 'mensagem' => '❌ Aluno não localizado na base académica do Huambo. Verifique as credenciais.']);
        
    } catch (Exception $e) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Erro crítico no servidor de acessos: ' . $e->getMessage()]);
    }
    exit;
}

echo json_encode(['sucesso' => false, 'mensagem' => 'Método de requisição inválido.']);
exit;
?>