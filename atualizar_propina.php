<?php
// 💰 MOTOR FINANCEIRO DINÂMICO - ACADEMIA AURÉLIUS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'conexao.php';

$resposta = ['sucesso' => false];
$metodo = $_SERVER['REQUEST_METHOD'];

// 🔍 SE FOR GET: Faz a busca dinâmica do aluno para carregar na tela
if ($metodo === 'GET') {
    $busca = isset($_GET['busca']) ? trim($_GET['busca']) : '';
    
    if (empty($busca)) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Digite algo para buscar']);
        exit;
    }

    try {
        // Procura por Nome, Telefone ou ID AUR
        $stmt = $pdo->prepare("SELECT id_utilizador, nome, telefone, saldo_propina FROM utilizadores WHERE nome LIKE ? OR telefone LIKE ? OR id_unico_escolar LIKE ? LIMIT 1");
        $stmt->execute(["%$busca%", "%$busca%", "%$busca%"]);
        $aluno = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($aluno) {
            $resposta['sucesso'] = true;
            $resposta['aluno'] = $aluno;
        } else {
            $resposta['mensagem'] = '❌ Aluno não localizado no MySQL remoto.';
        }
    } catch (Exception $e) {
        $resposta['mensagem'] = $e->getMessage();
    }
    echo json_encode($resposta);
    exit;
}

// 📥 SE FOR POST: Liquida a propina dinamicamente somando o valor no banco
if ($metodo === 'POST') {
    $id_utilizador = isset($_POST['id_utilizador']) ? trim($_POST['id_utilizador']) : '';
    $valor_pago = isset($_POST['valor']) ? floatval($_POST['valor']) : 0;
    
    if (empty($id_utilizador) || $valor_pago <= 0) {
        echo json_encode(['sucesso' => false, 'mensagem' => '⚠️ Dados de pagamento inválidos.']);
        exit;
    }

    try {
        // Atualiza a coluna saldo_propina somando o novo valor entregue no caixa
        $stmt = $pdo->prepare("UPDATE utilizadores SET saldo_propina = saldo_propina + ? WHERE id_utilizador = ?");
        $sucesso = $stmt->execute([$valor_pago, $id_utilizador]);

        if ($sucesso) {
            $resposta['sucesso'] = true;
            $resposta['mensagem'] = '🎉 Pagamento processado e saldo atualizado com sucesso no MySQL!';
        } else {
            $resposta['mensagem'] = 'Falha ao atualizar saldo no banco.';
        }
    } catch (Exception $e) {
        $resposta['mensagem'] = $e->getMessage();
    }
    echo json_encode($resposta);
    exit;
}