<?php
// 🗄️ PROCESSADOR DE INSCRIÇÕES - ACADEMIA AURÉLIUS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'conexao.php'; // Ligação dinâmica ao MySQL (XAMPP ou Render)

$resposta = ['sucesso' => false, 'mensagem' => ''];

// 🛑 AQUI: Falta esta linha no seu código para abrir o bloco POST corretamente!
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
        // 🔍 NOVA VALIDAÇÃO: Bloqueia apenas se o MESMO FILHO (Nome) já estiver associado a este Telefone
        $check = $pdo->prepare("SELECT id_utilizador FROM utilizadores WHERE nome = ? AND telefone = ? LIMIT 1");
        $check->execute([$nome, $telefone]);
        if ($check->fetch()) {
            $resposta['mensagem'] = '⚠️ Erro: Este estudante já se encontra matriculado com este número de telefone!';
            echo json_encode($resposta);
            exit;
        }

        // Gera o ID Único Escolar padrão (Ex: AUR-123456) e a senha padrão temporária (Ex: 123456)
        $numAleatorio = rand(100000, 900000);
        $novoID = "AUR-" . $numAleatorio;
        $codigoValidacao = rand(100000, 999999); // Senha numérica simples de 6 dígitos

        // 🌟 ALINHAMENTO COM O SEU PHPMYADMIN: 
        // Ordem dos ?: 1.nome, 2.telefone, 3.email, 4.id_unico_escolar, 5.nivel, 6.periodo
        $query = "INSERT INTO utilizadores (nome, telefone, email, senha, id_unico_escolar, nivel, periodo, saldo_propina) 
                  VALUES (?, ?, ?, 'estudante', ?, ?, ?, 0.00)";
        
        $stmt = $pdo->prepare($query);
        
        // A ordem exata das variáveis adaptada para os pontos de interrogação:
        $resultado = $stmt->execute([
            $nome,            // 1º ? -> nome
            $telefone,        // 2º ? -> telefone
            $codigoValidacao, // 3º ? -> email (onde guarda o código)
            $novoID,          // 4º ? -> id_unico_escolar
            $classe,          // 5º ? -> nivel
            $periodo          // 6º ? -> periodo
        ]);

        if ($resultado) {
            $resposta['sucesso'] = true;
            $resposta['id_estudante'] = $novoID;
            $resposta['codigo_validacao'] = $codigoValidacao;
            $resposta['mensagem'] = '🎉 Inscrição guardada no banco com sucesso!';
        } else {
            $resposta['mensagem'] = '⚠️ Erro interno ao inserir no banco de dados.';
        }

    } catch (Exception $e) {
        $resposta['mensagem'] = '⚠️ Erro no servidor: ' . $e->getMessage();
    }
} else {
    $resposta['mensagem'] = 'Método de requisição inválido.';
}

echo json_encode($resposta);
exit;