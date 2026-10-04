<?php
// 🚀 ENDPOINT AUTOMATIZADO - ACADEMIA AURÉLIUS
header('Content-Type: application/json; charset=utf-8');
require_once "conexao.php";

$totalInscritos    = 0;
$totalMatriculados = 0;
$regimeRegular     = 0; 
$posLaboral        = 0; 
$faturamentoTotal  = 0.00;

try {
    // Leitura direta da tabela utilizadores do Render
    $query = "SELECT id_unico_escolar, periodo, saldo_propina FROM utilizadores WHERE nivel = 'estudante'";
    $result = mysqli_query($conexao_aurelius, $query);

    while ($aluno = mysqli_fetch_assoc($result)) {
        $totalInscritos++; 

        if (!empty($aluno['id_unico_escolar'])) {
            $totalMatriculados++;
            
            $periodo = trim($aluno['periodo'] ?? '');
            
            // Comparação direta conforme está escrito no teu banco de dados
            if ($periodo === 'Noite' || $periodo === 'Pós-Laboral') {
                $posLaboral++;
            } else if ($periodo === 'Manhã' || $periodo === 'Tarde') {
                $regimeRegular++;
            }
        }

        if (isset($aluno['saldo_propina'])) {
            $faturamentoTotal += floatval($aluno['saldo_propina']);
        }
    }
} catch (Exception $e) {
    // Proteção de fluxo
}

// Retorna o JSON com o formato exato que o teu painel precisa
echo json_encode([
    'totalInscritos'    => $totalInscritos,
    'totalMatriculados' => $totalMatriculados,
    'regimeRegular'     => $regimeRegular,
    'posLaboral'        => $posLaboral,
    'faturamento'       => number_format($faturamentoTotal, 2, ',', '.') . ' Kzs'
]);
?>