<?php
// 🏛️ CENTRAL DE COMUNICAÇÃO TOTALMENTE UNIFICADA E BLINDADA - ACADEMIA AURÉLIUS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'conexao.php';

// 🔍 1. LEITURA UNIFICADA DO MURAL (GET)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        // Puxa as últimas 100 mensagens do mural_blog garantindo o escopo absoluto
        $stmt = $pdo->query("
            SELECT id_post, id_autor, tipo_post AS perfil, titulo AS remetente, conteudo, DATE_FORMAT(data_publicacao, '%H:%i') AS hora 
            FROM aurelius_escola.mural_blog 
            ORDER BY id_post ASC 
            LIMIT 100
        ");
        $mensagens = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['sucesso' => true, 'mensagens' => $mensagens], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao carregar chat: ' . $e->getMessage()]);
    }
    exit;
}

// 📥 2. ENVIO SEGURO E HÍBRIDO (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $inputRaw = file_get_contents("php://input");
    $dados = json_decode($inputRaw, true);

    $remetente     = isset($dados['remetente']) ? trim($dados['remetente']) : (isset($_POST['remetente']) ? trim($_POST['remetente']) : 'Anónimo');
    $identificador = isset($dados['id_unico_escolar']) ? trim($dados['id_unico_escolar']) : (isset($_POST['id_estudante']) ? trim($_POST['id_estudante']) : 'SISTEMA');
    
    $conteudo = '';
    if (isset($dados['mensagem'])) $conteudo = trim($dados['mensagem']);
    if (isset($dados['conteudo'])) $conteudo = trim($dados['conteudo']);
    if (isset($_POST['conteudo'])) $conteudo = trim($_POST['conteudo']);
    if (isset($_POST['mensagem'])) $conteudo = trim($_POST['mensagem']);

    $perfil = ($remetente === 'coord_prof' || (isset($dados['perfil']) && $dados['perfil'] === 'professor')) ? 'professor' : 'estudante';
    
    if ($remetente === 'coord_prof') {
        $remetente = "Prof. Aurélio Jamba";
    }

    if (empty($conteudo)) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'A mensagem está vazia.']);
        exit;
    }

    try {
        // Localiza o ID numérico do utilizador associado à chave estrangeira na base certa
        $stmtUser = $pdo->prepare("SELECT id_utilizador FROM aurelius_escola.utilizadores WHERE id_unico_escolar = ? OR telefone = ? LIMIT 1");
        $stmtUser->execute([$identificador, $identificador]);
        $id_autor_numerico = $stmtUser->fetchColumn();

        if (!$id_autor_numerico) {
            $stmtFallback = $pdo->query("SELECT id_utilizador FROM aurelius_escola.utilizadores LIMIT 1");
            $id_autor_numerico = $stmtFallback->fetchColumn();
        }

        // 🌟 CORREÇÃO DEFINITIVA: Força explicitamente a inserção em aurelius_escola.mural_blog!
        $stmt = $pdo->prepare("INSERT INTO aurelius_escola.mural_blog (id_autor, tipo_post, titulo, conteudo) VALUES (?, ?, ?, ?)");
        $sucesso = $stmt->execute([$id_autor_numerico, $perfil, $remetente, $conteudo]);
        
        echo json_encode(['sucesso' => $sucesso]);
    } catch (Exception $e) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Erro SQL no envio: ' . $e->getMessage()]);
    }
    exit;
}
?>