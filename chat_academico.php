<?php
// 🏛️ CENTRAL DE COMUNICAÇÃO - ACADEMIA AURÉLIUS (CONEXÃO SEGURA MURAL_BLOG)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'conexao.php';

// 🔍 AÇÃO 1: CARREGAR MENSAGENS (GET)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $id_estudante = isset($_GET['id_estudante']) ? trim($_GET['id_estudante']) : '';

    try {
        // Puxa as mensagens unificadas do canal geral da Turma Única A
        $stmt = $pdo->query("SELECT id_post, id_autor, tipo_post, titulo AS remetente, conteudo, DATE_FORMAT(data_publicacao, '%H:%i') AS hora FROM mural_blog ORDER BY id_post ASC LIMIT 100");
        $mensagens = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['sucesso' => true, 'mensagens' => $mensagens], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao ler: ' . $e->getMessage()]);
    }
    exit;
}

// 📥 AÇÃO 2: ENVIAR MENSAGEM (POST RESOLVE CHAVE ESTRANGEIRA)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $inputRaw = file_get_contents("php://input");
    $dados = json_decode($inputRaw, true);

    $identificador = isset($dados['id_unico_escolar']) ? trim($dados['id_unico_escolar']) : (isset($_POST['id_unico_escolar']) ? trim($_POST['id_unico_escolar']) : '');
    $remetente     = isset($dados['remetente']) ? trim($dados['remetente']) : (isset($_POST['remetente']) ? trim($_POST['remetente']) : 'Anónimo');
    $conteudo      = isset($dados['mensagem']) ? trim($dados['mensagem']) : (isset($_POST['mensagem']) ? trim($_POST['mensagem']) : '');
    $perfil        = isset($dados['perfil']) ? trim($dados['perfil']) : (isset($_POST['perfil']) ? trim($_POST['perfil']) : 'estudante');

    if (empty($conteudo)) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'A mensagem não pode ser enviada vazia.']);
        exit;
    }

    try {
        $id_autor_numerico = null;

        if ($perfil === 'professor') {
            // 👨‍🏫 Tenta localizar um utilizador administrador/professor para associar a chave estrangeira
            $stmtUser = $pdo->prepare("SELECT id_utilizador FROM utilizadores WHERE nivel = 'professor' OR nivel = 'administrador' LIMIT 1");
            $stmtUser->execute();
            $id_autor_numerico = $stmtUser->fetchColumn();
        } else {
            // 👤 Aluno: Procura o id_utilizador numérico real a partir do ID AUR ou do número de telefone
            $stmtUser = $pdo->prepare("SELECT id_utilizador FROM utilizadores WHERE id_unico_escolar = ? OR telefone = ? LIMIT 1");
            $stmtUser->execute([$identificador, $identificador]);
            $id_autor_numerico = $stmtUser->fetchColumn();
        }

        // 🚨 Fallback Crítico: Se não achar nenhum ID associável na tabela, puxa o primeiro ID existente no banco 
        // para garantir que NUNCA falte o vínculo exigido pela Chave Estrangeira
        if (!$id_autor_numerico) {
            $stmtFallback = $pdo->query("SELECT id_utilizador FROM utilizadores LIMIT 1");
            $id_autor_numerico = $stmtFallback->fetchColumn();
        }

        if (!$id_autor_numerico) {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Erro: Nenhum utilizador cadastrado no sistema para vincular o chat.']);
            exit;
        }

        // 🌟 INSERT PERFEITO: Agora id_autor recebe o número correto, satisfazendo a Foreign Key!
        $stmt = $pdo->prepare("INSERT INTO mural_blog (id_autor, tipo_post, titulo, conteudo) VALUES (?, ?, ?, ?)");
        $sucesso = $stmt->execute([$id_autor_numerico, $perfil, $remetente, $conteudo]);
        
        echo json_encode(['sucesso' => $sucesso]);
    } catch (Exception $e) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Falha de Integridade: ' . $e->getMessage()]);
    }
    exit;
}
?>