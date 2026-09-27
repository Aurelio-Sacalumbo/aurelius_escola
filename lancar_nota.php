<?php
// 🔌 API DE NOTAS: LANÇA OU ATUALIZA AS PAUTAS DOS ALUNOS NA AIVEN
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");

require_once "conexao.php";

try {
    $dados = json_decode(file_get_contents("php://input"), true);

    if (!$dados || empty($dados['estudante_id']) || empty($dados['disciplina'])) {
        echo json_encode(["sucesso" => false, "mensagem" => "Parâmetros insuficientes."]);
        exit;
    }

    $id = trim($dados['estudante_id']);
    $disciplina = trim($dados['disciplina']);
    $n1 = floatval($dados['n1']);
    $n2 = floatval($dados['n2']);
    $n3 = floatval($dados['n3']);
    $faltas = intval($dados['faltas']);
    
    // 🛡️ TRAVA DE SEGURANÇA DE NOTAS EXIGIDA (Intervalo estrito de 0 a 20)
    if ($n1 < 0 || $n1 > 20 || $n2 < 0 || $n2 > 20 || $n3 < 0 || $n3 > 20) {
        echo json_encode(["sucesso" => false, "mensagem" => "Erro: As notas académicas devem situar-se estritamente entre 0.0 e 20.0 valores."]);
        exit;
    }

    // Calcula o estatuto de aprovação automaticamente baseado na média aritmética angolana
    $media = ($n1 + $n2 + $n3) / 3;
    $estatuto = ($media >= 10) ? "Aprovado" : "Frequência";

    // Verifica se a pauta já existe para atualizar; caso contrário, insere uma nova
    $check = $pdo->prepare("SELECT id_pauta FROM pautas WHERE estudante_id = ? AND disciplina = ?");
    $check->execute([$id, $disciplina]);
    
    if ($check->fetch()) {
        $stmt = $pdo->prepare("UPDATE pautas SET n1 = ?, n2 = ?, n3 = ?, faltas = ?, estatuto = ? WHERE estudante_id = ? AND disciplina = ?");
        $stmt->execute([$n1, $n2, $n3, $faltas, $estatuto, $id, $disciplina]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO pautas (estudante_id, disciplina, n1, n2, n3, faltas, estatuto) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$id, $disciplina, $n1, $n2, $n3, $faltas, $estatuto]);
    }

    echo json_encode(["sucesso" => true, "mensagem" => "Caderneta de Notas sincronizada na nuvem! Estatuto: $estatuto"]);

} catch (Exception $e) {
    echo json_encode(["sucesso" => false, "mensagem" => "Erro de persistência na Aiven: " . $e->getMessage()]);
}
?>