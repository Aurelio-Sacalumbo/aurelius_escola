<?php
// 🗄️ PROCESSADOR DE INSCRIÇÕES - ACADEMIA AURÉLIUS (UX SIMPLIFICADO)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'conexao.php'; // Ligação dinâmica ao MySQL (XAMPP ou Render)

$resposta = ['sucesso' => false, 'mensagem' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = isset($_POST['nome']) ? trim($_POST['nome']) : '';
    $telefone = isset($_POST['telefone']) ? trim($_POST['telefone']) : '';
    $classe = isset($_POST['classe']) ? trim($_POST['classe']) : '';
    $periodo = isset($_POST['periodo']) ? trim($_POST['periodo']) : '';
    
    // Captura a senha definida pelo utilizador no formulário
    $senhaInserida = isset($_POST['senha']) ? trim($_POST['senha']) : '';

    // Se o teu formulário não tiver o campo de senha ainda, geramos uma baseada no telefone para não quebrar o UX
    if (empty($senhaInserida)) {
        $senhaInserida = substr($telefone, -6); // Usa os últimos 6 dígitos do telefone como senha padrão
    }

    if (empty($nome) || empty($telefone)) {
        $resposta['mensagem'] = '⚠️ Nome e telefone são obrigatórios!';
        echo json_encode($resposta);
        exit;
    }

    try {
        // 🔍 REGRA EXCLUSIVA: Permite o mesmo telefone para vários filhos, mas bloqueia se o NOME for idêntico sob o mesmo número
        $check = $pdo->prepare("SELECT id_utilizador FROM utilizadores WHERE nome = ? AND telefone = ? LIMIT 1");
        $check->execute([$nome, $telefone]);
        
        if ($check->fetch()) {
            echo json_encode(['sucesso' => false, 'mensagem' => '⚠️ Este filho(a) já se encontra inscrito com este número de telefone!']);
            exit;
        }
    
        // Cada filho terá uma senha definida (ou gerada), permitindo diferenciar o acesso
        $query = "INSERT INTO utilizadores (nome, telefone, email, senha, id_unico_escolar, nivel, periodo, saldo_propina) 
                  VALUES (?, ?, null, ?, ?, ?, ?, 0.00)";
        
        $stmt = $pdo->prepare($query);
        $resultado = $stmt->execute([
            $nome,
            $telefone,
            $senhaInserida, // A senha será a chave para abrir a conta de cada filho individualmente
            $telefone,      // Armazenado como referência estrutural
            $classe,
            $periodo
        ]);
    
        if ($resultado) {
            echo json_encode([
                'sucesso' => true,
                'id_estudante' => $telefone,
                'codigo_validacao' => $senhaInserida,
                'mensagem' => "🎉 Inscrição de " . $nome . " realizada com sucesso sob o contacto do encarregado!"
            ]);
        }
        exit;
    } catch (Exception $e) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Erro: ' . $e->getMessage()]);
        exit;
    }