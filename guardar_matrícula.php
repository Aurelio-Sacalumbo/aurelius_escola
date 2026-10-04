<?php
// 📝 CENTRAL DE INSCRIÇÕES INICIAIS — ACADEMIA AURÉLIUS (REGRA DE MÚLTIPLOS FILHOS POR PAI)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'conexao.php';

// 📥 Captura os dados do seu FormData original enviado pelo front-end ($_POST)
$nome             = isset($_POST['nome']) ? trim($_POST['nome']) : '';
$telefone         = isset($_POST['telefone']) ? trim($_POST['telefone']) : '';
$periodo          = isset($_POST['periodo']) ? trim($_POST['periodo']) : '';
$cursoBruto       = isset($_POST['curso']) ? trim($_POST['curso']) : '';

if (empty($telefone) || empty($nome)) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Campos obrigatórios em falta (Nome ou Telefone).']);
    exit;
}

// Formata o curso preservando a restrição de Inscrição Inicial (Sem disciplinas libertadas)
$classeLimpa = trim(str_replace(['[Inscrição]', '[', ']', '[Inscrição Inicial]'], '', $cursoBruto));
$cursoBloqueado = $classeLimpa . " [Inscrição Inicial]";

try {
    // 🔍 REGRA IMPERIAL: Verifica se JÁ EXISTE este FILHO específico (Nome + Telefone idênticos)
    $check = $pdo->prepare("SELECT id_utilizador, id_unico_escolar, senha FROM utilizadores WHERE nome = ? AND telefone = ? AND nivel = 'estudante' LIMIT 1");
    $check->execute([$nome, $telefone]);
    $alunoExistente = $check->fetch(PDO::FETCH_ASSOC);

    if ($alunoExistente) {
        // 🔄 O FILHO JÁ EXISTE: Mantém rigidamente as credenciais dele intocáveis e apenas atualiza o período/classe
        $id_user = $alunoExistente['id_utilizador'];
        $idFinal = trim($alunoExistente['id_unico_escolar']);
        $senhaFinal = trim($alunoExistente['senha']);

        $stmt = $pdo->prepare("UPDATE utilizadores SET curso = ?, periodo = ? WHERE id_utilizador = ?");
        $sucesso = $stmt->execute([$cursoBloqueado, $periodo, $id_user]);
        $msg = "🎉 Inscrição inicial do estudante atualizada! Mantenha o seu ID e Senha originais.";
    } else {
        // 🆕 FILHO NOVO COM O MESMO TELEFONE DO PAI: Cria um novo registro com ID e Senha 100% exclusivos!
        $idFinal = "AUR-" . random_int(100000, 999999);
        $senhaFinal = strval(random_int(100000, 999999));

        $stmt = $pdo->prepare("INSERT INTO utilizadores (nome, telefone, senha, email, nivel, id_unico_escolar, curso, periodo, saldo_propina) 
                               VALUES (?, ?, ?, ?, 'estudante', ?, ?, ?, 0)");
        $sucesso = $stmt->execute([$nome, $telefone, $senhaFinal, $senhaFinal, $idFinal, $cursoBloqueado, $periodo]);
        $msg = "🎉 Inscrição inicial concluída com sucesso! Credenciais únicas geradas para o estudante.";
    }

    // Retorna as credenciais reais para a injeção automática no formulário de login
    echo json_encode([
        'sucesso' => $sucesso,
        'status' => 'sucesso',
        'mensagem' => $msg,
        'id_unico_escolar' => $idFinal,
        'senha_gerada' => $senhaFinal
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro crítico MySQL local: ' . $e->getMessage()]);
}
exit;
?>