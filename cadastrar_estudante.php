<?php
// 🗄️ PROCESSADOR AUTOMÁTICO DE MATRÍCULAS - ACADEMIA AURÉLIUS
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

    if (empty($nome) || empty($telefone)) {
        $resposta['mensagem'] = '⚠️ Nome e telefone são obrigatórios!';
        echo json_encode($resposta);
        exit;
    }

    try {
        // 🔍 Evita duplicação automática verificando se o telefone já existe
        $check = $pdo->prepare("SELECT id_utilizador FROM utilizadores WHERE telefone = ? LIMIT 1");
        $check->execute([$telefone]);
        if ($check->fetch()) {
            $resposta['mensagem'] = '⚠️ Este número de telefone já se encontra matriculado!';
            echo json_encode($resposta);
            exit;
        }

        // 🧠 GERADORES DINÂMICOS AUTOMÁTICOS:
        $numAleatorio = rand(100000, 999999);
        $novoID = "AUR-" . $numAleatorio;       // Gera ID único reativo ex: AUR-482934
        $codigoValidacao = rand(100000, 999999); // Gera Senha numérica aleatória

        // 🌟 GRAVAÇÃO AUTOMÁTICA NO PHPMYADMIN/DBEAVER
        $query = "INSERT INTO utilizadores (nome, telefone, email, senha, id_unico_escolar, periodo, saldo_propina) 
                  VALUES (?, ?, ?, 'estudante', ?, ?, 0.00)";
        
        $stmt = $pdo->prepare($query);
        $resultado = $stmt->execute([$nome, $telefone, $codigoValidacao, $novoID, $periodo]);

        if ($resultado) {
            $resposta['sucesso'] = true;
            $resposta['id_estudante'] = $novoID;
            $resposta['codigo_validacao'] = $codigoValidacao;
            $resposta['mensagem'] = '🎉 Inscrição gerada e gravada no banco de dados!';
        } else {
            $resposta['mensagem'] = '⚠️ Falha interna ao inserir dados no MySQL.';
        }

    } catch (Exception $e) {
        $resposta['mensagem'] = '⚠️ Erro no servidor: ' . $e->getMessage();
    }
} else {
    $resposta['mensagem'] = 'Método inválido.';
}

echo json_encode($resposta);
exit;