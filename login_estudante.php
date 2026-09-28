<?php
// 🔌 API DE AUTENTICAÇÃO: CONECTA O estudante.html À BASE DE DADOS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");

// 🔒 SEGURANÇA MÁXIMA: Puxa a conexão limpa e dinâmica sem expor senhas no código
require_once "conexao.php";

try {
    // Recebe os dados de login enviados pelo estudante.html
    $dados = json_decode(file_get_contents("php://input"), true);

    if (!$dados || empty($dados['identificador']) || empty($dados['senha'])) {
        echo json_encode(["sucesso" => false, "mensagem" => "Dados de login incompletos."]);
        exit;
    }

    $identificador = trim($dados['identificador']);
    $senhaInserida = trim($dados['senha']);

    // 🔍 ALINHAMENTO DE COLUNAS: Mapeado exatamente com a sua tabela 'utilizadores' do Huambo
    $query = "SELECT id_utilizador, nome, email, telefone, senha, classe FROM utilizadores WHERE (id_utilizador = ? OR email = ? OR telefone = ?) AND tipo_usuario = 'estudante' LIMIT 1";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$identificador, $identificador, $identificador]);
    $estudante = $stmt->fetch();

    // Compara a senha (suporta password_hash e MD5 antigo para não travar os seus testes)
    if ($estudante && (password_verify($senhaInserida, $estudante['senha']) || md5($senhaInserida) === $estudante['senha'])) {
        
        if (session_status() === PHP_SESSION_NONE) { 
            session_start(); 
        }
        $_SESSION['usuario_id'] = $estudante['id_utilizador'];

        // Login com sucesso! Devolve os dados pedagógicos para a interface
        echo json_encode([
            "sucesso" => true,
            "estudante" => [
                "id" => $estudante['id_utilizador'],
                "nome" => $estudante['nome'],
                "telefone" => $estudante['telefone'],
                "classe" => $estudante['classe'] ?? 'Aguardando Matrícula',
                "turma" => 'Ver na Pauta',
                "periodo" => 'Regular'
            ]
        ]);
    } else {
        echo json_encode(["sucesso" => false, "mensagem" => "⚠️ ID/Telefone ou Código de Validação incorretos."]);
    }

} catch (Exception $e) {
    error_log("Erro na API de Login: " . $e->getMessage());
    echo json_encode(["sucesso" => false, "mensagem" => "Falha crítica de ligação ao servidor."]);
}
?>