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
    // 📱 CORREÇÃO: Captura dinamicamente tanto 'telefone' (novo) como 'identificador' (antigo) para não quebrar
    $identificador = isset($dadosJson['telefone']) ? trim($dadosJson['telefone']) : (isset($dadosJson['identificador']) ? trim($dadosJson['identificador']) : '');
    $senhaInserida = isset($dadosJson['senha']) ? trim($dadosJson['senha']) : '';

    if (empty($identificador) || empty($senhaInserida)) {
        echo json_encode(['sucesso' => false, 'mensagem' => '⚠️ Preencha o número de telefone e a palavra-passe de acesso.']);
        exit;
    }

    try {
        // 🔍 REGRA DE MÚLTIPLOS FILHOS: Procuramos todos os registos com este contacto (Sem LIMIT 1)
        $stmt = $pdo->prepare("SELECT id_utilizador, nome, telefone, curso, periodo, id_unico_escolar, senha, email 
                               FROM utilizadores 
                               WHERE (telefone = ? OR id_unico_escolar = ? OR email = ?) 
                               AND nivel = 'estudante'");
        $stmt->execute([$identificador, $identificador, $identificador]);
        $alunosEncontrados = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $alunoValido = null;

        // 🔐 Varre a lista de filhos para encontrar aquele que bate com a senha digitada
        foreach ($alunosEncontrados as $aluno) {
            $senhaBanco = isset($aluno['senha']) ? trim($aluno['senha']) : '';
            $emailBanco = isset($aluno['email']) ? trim($aluno['email']) : ''; // Plano B para senhas na coluna email

            if (
                (!empty($senhaBanco) && ($senhaBanco === $senhaInserida || $senhaBanco === md5($senhaInserida))) ||
                (!empty($emailBanco) && ($emailBanco === $senhaInserida || $emailBanco === md5($senhaInserida)))
            ) {
                $alunoValido = $aluno;
                break; // Encontrou o filho correto, interrompe o loop
            }
        }

        if ($alunoValido) {
            $classeFormatada = !empty($alunoValido['curso']) ? $alunoValido['curso'] : '12ª Classe [Inscrição]';

            echo json_encode([
                'sucesso' => true,
                'mensagem' => '🎉 Acesso autorizado com sucesso!',
                'estudante' => [
                    'id_utilizador' => $alunoValido['id_utilizador'],
                    'nome' => $alunoValido['nome'],
                    'id_unico_escolar' => !empty($alunoValido['id_unico_escolar']) ? $alunoValido['id_unico_escolar'] : $identificador,
                    'classe' => $classeFormatada,
                    'periodo' => !empty($alunoValido['periodo']) ? $alunoValido['periodo'] : 'Noite',
                    'turma' => 'Turma Única A'
                ]
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Se passar pelo loop e não achar a senha correspondente de nenhum filho
        echo json_encode(['sucesso' => false, 'mensagem' => '❌ O número de telefone ou a senha de acesso está incorreta. Verifique as credenciais.']);
        
    } catch (Exception $e) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'O número de telefone ou a senha de acesso está incorreta.']);
    }
    exit;
}

echo json_encode(['sucesso' => false, 'mensagem' => 'Método de requisição inválido.']);
exit;
?>