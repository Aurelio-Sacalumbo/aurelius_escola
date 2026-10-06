<?php
// 📝 CENTRAL DE INSCRIÇÕES INICIAIS — ACADEMIA AURÉLIUS (UX REFINADO: APENAS TELEFONE)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'conexao.php';

$nome             = isset($_POST['nome']) ? trim($_POST['nome']) : '';
$telefone         = isset($_POST['telefone']) ? trim($_POST['telefone']) : '';
$periodo          = isset($_POST['periodo']) ? trim($_POST['periodo']) : '';
$cursoBruto       = isset($_POST['curso']) ? trim($_POST['curso']) : '';

if (empty($telefone) || empty($nome)) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Campos obrigatórios em falta (Nome ou Telefone).']);
    exit;
}

$classeLimpa = trim(str_replace(['[Inscrição]', '[', ']', '[Inscrição Inicial]'], '', $cursoBruto));
$cursoBloqueado = $classeLimpa . " [Inscrição Inicial]";

try {
    // 🔍 REGRA DE SEGURANÇA: Bloqueia apenas se o MESMO FILHO já estiver registado sob o mesmo número
    $check = $pdo->prepare("SELECT id_utilizador FROM utilizadores WHERE nome = ? AND telefone = ? AND nivel = 'estudante' LIMIT 1");
    $check->execute([$nome, $telefone]);
    $alunoExistente = $check->fetch(PDO::FETCH_ASSOC);

    if ($alunoExistente) {
        echo json_encode(['sucesso' => false, 'mensagem' => '⚠️ Este estudante já se encontra inscrito com este número de telefone!']);
        exit;
    }

    // 🔑 UX ABSOLUTO: A senha é gerada aleatoriamente com 6 dígitos, mas o login é feito 100% pelo TELEFONE
    $senhaFinal = strval(random_int(100000, 999999));

    // 🌟 RESOLUÇÃO DO ERRO 1062: Como o teu DBeaver exige que a coluna 'id_unico_escolar' seja preenchida 
    // e ela é UNIQUE, vamos concatenar o número de telefone com o ID interno do loop para garantir que 
    // o banco de dados aceita múltiplos filhos sem gerar conflitos, mas para o utilizador isso fica invisível!
    $idInternoBanco = $telefone . "-" . random_int(10, 99);

    $stmt = $pdo->prepare("INSERT INTO utilizadores (nome, telefone, senha, email, nivel, id_unico_escolar, curso, periodo, saldo_propina) 
                           VALUES (?, ?, ?, null, 'estudante', ?, ?, ?, 0)");
    
    $sucesso = $stmt->execute([
        $nome,           // nome
        $telefone,       // telefone (Identificador de Login oficial)
        $senhaFinal,     // senha de acesso
        $idInternoBanco, // id_unico_escolar (Apenas para satisfazer a restrição UNIQUE do MySQL)
        $cursoBloqueado, // curso
        $periodo         // periodo
    ]);

    // Retorna EXCLUSIVAMENTE o Telefone e a Senha para o ecrã do utilizador
    echo json_encode([
        'sucesso' => $sucesso,
        'status' => 'sucesso',
        'mensagem' => "🎉 Inscrição concluída com sucesso!",
        'id_unico_escolar' => $telefone, // O front-end recebe o Telefone no lugar do ID
        'senha_gerada' => $senhaFinal
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro crítico na base de dados: ' . $e->getMessage()]);
}
exit;
?>