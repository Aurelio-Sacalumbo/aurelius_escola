<?php
// Adicione estes cabeçalhos no topo do unitel.php para evitar bloqueios de rede (CORS)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// =========================================================================
// 📥 ROTA 1: PROCESSAMENTO POST (REGISTAR PAGAMENTO)
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao_financeira']) && $_POST['acao_financeira'] === 'registar_pagamento') {
    header('Content-Type: application/json; charset=utf-8');
    require_once 'conexao.php';
    
    $telefone = isset($_POST['telefone']) ? trim($_POST['telefone']) : '';
    $valor = isset($_POST['valor_pago']) ? floatval($_POST['valor_pago']) : 0;
    
    try {
        // Atualiza a coluna saldo_propina somando o valor pago na linha do aluno pelo telefone dele
        $stmt = $pdo->prepare("UPDATE utilizadores SET saldo_propina = saldo_propina + ? WHERE telefone = ?");
        $executo = $stmt->execute([$valor, $telefone]);
        
        echo json_encode(['sucesso' => $executo, 'mensagem' => 'Pagamento integrado com sucesso!']);
    } catch (Exception $e) {
        echo json_encode(['sucesso' => false, 'mensagem' => $e->getMessage()]);
    }
    exit; // 🌟 Fecha e corta a execução do POST aqui!
}

// =========================================================================
// 🔍 ROTA 2: PROCESSAMENTO GET (PESQUISA AUTOMÁTICA DO ALUNO)
// =========================================================================
if (isset($_GET['pesquisa_automatica_cliente']) && isset($_GET['termo'])) {
    header('Content-Type: application/json; charset=utf-8');
    require_once 'conexao.php';
    
    $termo = trim($_GET['termo']);
    
    try {
        // Busca ampla por ID Único, Nome ou Telefone
        $stmt = $pdo->prepare("SELECT id_utilizador, nome, telefone, email as codigo_validacao, saldo_propina, nivel, id_unico_escolar FROM utilizadores WHERE id_unico_escolar = ? OR nome LIKE ? OR telefone = ? LIMIT 1");
        $stmt->execute([$termo, "%$termo%", $termo]);
        $aluno = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($aluno) {
            // Se o nível/classe estiver NULL (como o do Moma), define uma classe padrão para não quebrar o JS
            $classeReal = !empty($aluno['nivel']) ? $aluno['nivel'] : "9ª Classe";
            
            // Simulação de histórico financeiro seguro baseado no saldo
            $dividaReal = ($aluno['saldo_propina'] < 0) ? abs($aluno['saldo_propina']) : 0;
            $saldoReal = ($aluno['saldo_propina'] > 0) ? $aluno['saldo_propina'] : 0;

            echo json_encode([
                'status' => 'encontrado',
                'nome' => $aluno['nome'],
                'telefone' => $aluno['telefone'],
                'turma' => 'Turma Única A',
                'classe' => $classeReal,
                'divida' => $dividaReal,
                'saldo_interno' => $saldoReal
            ]);
        } else {
            echo json_encode(['status' => 'nao_encontrado']);
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'erro', 'mensagem' => $e->getMessage()]);
    }
    exit; // 🌟 Fecha e corta a execução do GET aqui!
}
?>
<?php
// Adicione estes cabeçalhos no topo do unitel.php para evitar bloqueios de rede (CORS)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// =========================================================================
// 📥 ROTA 1: PROCESSAMENTO POST (REGISTAR PAGAMENTO)
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');
    require_once 'conexao.php';

    // Captura tanto JSON do Render quanto FormData tradicional
    $inputRaw = file_get_contents("php://input");
    $dadosJson = json_decode($inputRaw, true);

    if ($dadosJson) {
        $acao         = isset($dadosJson['acao_financeira']) ? trim($dadosJson['acao_financeira']) : 'registar_pagamento';
        $nome         = isset($dadosJson['nome']) ? trim($dadosJson['nome']) : '';
        $telefone     = isset($dadosJson['telefone']) ? trim($dadosJson['telefone']) : '';
        $curso        = isset($dadosJson['curso']) ? trim($dadosJson['curso']) : '';
        $periodo      = isset($dadosJson['periodo']) ? trim($dadosJson['periodo']) : '';
        $id_escolar   = isset($dadosJson['id_unico_escolar']) ? trim($dadosJson['id_unico_escolar']) : '';
        $validacao    = isset($dadosJson['codigo_validacao']) ? trim($dadosJson['codigo_validacao']) : '';
        $saldoAbatido = isset($dadosJson['saldo_abatido']) ? floatval($dadosJson['saldo_abatido']) : 0;
    } else {
        $acao         = isset($_POST['acao_financeira']) ? trim($_POST['acao_financeira']) : 'registar_pagamento';
        $nome         = isset($_POST['nome']) ? trim($_POST['nome']) : '';
        $telefone     = isset($_POST['telefone']) ? trim($_POST['telefone']) : '';
        $curso        = isset($_POST['curso']) ? trim($_POST['curso']) : '';
        $periodo      = isset($_POST['periodo']) ? trim($_POST['periodo']) : '';
        $id_escolar   = isset($_POST['id_unico_escolar']) ? trim($_POST['id_unico_escolar']) : '';
        $validacao    = isset($_POST['codigo_validacao']) ? trim($_POST['codigo_validacao']) : '';
        $saldoAbatido = 0;
    }

    if ($acao === 'registar_pagamento') {
        if (empty($telefone)) {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Contacto do aluno em falta.']);
            exit;
        }

        try {
            // 🔍 1. Verifica se o aluno já existe na tabela utilizadores
            $check = $pdo->prepare("SELECT id_utilizador FROM utilizadores WHERE telefone = ?");
            $check->execute([$telefone]);
            $alunoExiste = $check->fetch();

            if ($alunoExiste) {
                // 🔄 Se o aluno já existe (como o Moma), faz o UPDATE regular do saldo
                $stmt = $pdo->prepare("UPDATE utilizadores SET saldo_propina = saldo_propina - ? WHERE telefone = ? AND nivel = 'estudante'");
                $executo = $stmt->execute([$saldoAbatido, $telefone]);
                $msg = "Saldo atualizado com sucesso!";
            } else {
                // 🆕 Se o aluno NÃO existe (Estudante Teste, Áurio, etc.), faz o INSERT real com os códigos gerados
                // Mapeia o código de validação para a coluna email e a senha padrão criptografada
                $senha_padrao_hash = md5($validacao); 
                $nomeCompleto = !empty($nome) ? $nome : "Estudante Inscrito";

                $stmt = $pdo->prepare("INSERT INTO utilizadores (nome, telefone, email, senha, nivel, id_unico_escolar, curso, periodo, saldo_propina) VALUES (?, ?, ?, ?, 'estudante', ?, ?, ?, 0)");
                $executo = $stmt->execute([$nomeCompleto, $telefone, $validacao, $senha_padrao_hash, $id_escolar, $curso, $periodo]);
                $msg = "Novo aluno gravado com sucesso no banco de dados central!";
            }

            echo json_encode(['sucesso' => $executo, 'mensagem' => $msg]);
        } catch (Exception $e) {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Erro crítico MySQL: ' . $e->getMessage()]);
        }
        exit;
    }
}
// =========================================================================
// 🔍 ROTA 2: PROCESSAMENTO GET (PESQUISA AUTOMÁTICA DO ALUNO)
// =========================================================================
if (isset($_GET['pesquisa_automatica_cliente']) && isset($_GET['termo'])) {
    header('Content-Type: application/json; charset=utf-8');
    require_once 'conexao.php';
    
    $termo = trim($_GET['termo']);
    
    try {
        // Correção Cirúrgica: nivel = 'estudante' para a regra de negócio e curso guarda as disciplinas
        $stmt = $pdo->prepare("SELECT id_utilizador, nome, telefone, saldo_propina, curso, id_unico_escolar FROM utilizadores WHERE (id_unico_escolar = ? OR nome LIKE ? OR telefone = ?) AND nivel = 'estudante' LIMIT 1");
        $stmt->execute([$termo, "%$termo%", $termo]);
        $aluno = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($aluno) {
            $classeReal = !empty($aluno['curso']) ? $aluno['curso'] : "9ª Classe";
            $saldoReal = ($aluno['saldo_propina'] > 0) ? floatval($aluno['saldo_propina']) : 0;

            echo json_encode([
                'status' => 'encontrado',
                'nome' => $aluno['nome'],
                'telefone' => $aluno['telefone'],
                'turma' => 'Turma Única A',
                'classe' => $classeReal,
                'divida' => 0,
                'saldo_interno' => $saldoReal
            ]);
        } else {
            echo json_encode(['status' => 'nao_encontrado']);
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'erro', 'mensagem' => $e->getMessage()]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faturamento de Propinas - Academia Aurélius</title>
    <style>
        :root {
            --bg-main: #060b19;
            --bg-surface: #111a2e;
            --brand-gold: #eab308;
            --brand-success: #22c55e;
            --brand-danger: #ef4444;
            --border-slate: #1e293b;
            --text-muted: #94a3b8;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', system-ui, sans-serif; }
        body { background: var(--bg-main); color: #fff; padding: 20px; }
        .checkout-container { max-width: 700px; margin: 20px auto; background: var(--bg-surface); border: 1px solid var(--border-slate); border-radius: 12px; padding: 25px; box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
        
        /* 🏫 LOGO ESTILIZADO ACADEMIA AURÉLIUS */
        .header-logo-escrita { text-align: center; margin-bottom: 25px; border-bottom: 1px solid var(--border-slate); padding-bottom: 20px; }
        .logo-box { display: inline-block; font-size: 26px; font-weight: 900; letter-spacing: 1px; color: #fff; margin-bottom: 5px; text-transform: uppercase; }
        .logo-box span { color: var(--brand-gold); }

        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-size: 12px; color: var(--text-muted); font-weight: bold; margin-bottom: 6px; text-transform: uppercase; }
        .form-input { width: 100%; padding: 12px; background: #070b14; border: 1px solid var(--border-slate); border-radius: 6px; color: #fff; font-size: 14px; outline: none; }
        .form-input:focus { border-color: var(--brand-gold); }
        .grid-dupla { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .alerta-box { padding: 12px; border-radius: 6px; font-size: 13px; margin-bottom: 15px; display: none; text-align: center; font-weight: bold; border: 1px solid transparent; }
        .resumo-fatura { background: #070b14; border: 1px solid var(--border-slate); border-radius: 8px; padding: 15px; margin-top: 20px; }
        .resumo-linha { display: flex; justify-content: space-between; font-size: 13px; padding: 8px 0; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .resumo-linha:last-child { border-bottom: none; font-size: 16px; font-weight: bold; color: var(--brand-gold); }
        .btn-pay { width: 100%; background: var(--brand-gold); color: #000; border: none; padding: 14px; border-radius: 6px; font-size: 15px; font-weight: bold; cursor: pointer; text-transform: uppercase; margin-top: 20px; transition: 0.2s; }
        .btn-pay:hover { background: #f59e0b; }

        /* 📋 ESTILO DA FATURA DE IMPRESSÃO IMPERIAL */
        #bloco_fatura_recibo { display: none; max-width: 450px; margin: 25px auto; background: #fff; color: #000; padding: 25px; border-radius: 4px; box-shadow: 0 4px 15px rgba(0,0,0,0.2); font-family: 'Courier New', Courier, monospace; }
        #bloco_fatura_recibo h3 { text-align: center; border-bottom: 2px dashed #000; padding-bottom: 10px; margin-bottom: 15px; font-size: 18px; font-weight: bold; }
        .recibo-linha { display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 6px; }
        .recibo-total { border-top: 2px dashed #000; padding-top: 8px; margin-top: 10px; font-weight: bold; font-size: 15px; display: flex; justify-content: space-between; }
    </style>
</head>
<body>
<div>
                    <a href="Principal.html" style="color: #cbd5e1; text-decoration: none; font-size: 13px; font-weight: 700; padding: 8px 16px; border-radius: 20px; background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.05); transition: all 0.3s;" onmouseover="this.style.background='rgba(234, 179, 8, 0.15)'; this.style.color='var(--brand-gold)';" onmouseout="this.style.background='rgba(255,255,255,0.02)'; this.style.color='#cbd5e1';">Home</a>
                </div>
    <div class="checkout-container">
        <!-- 🏫 Logótipo Oficial Integrado -->
        <div class="header-logo-escrita">
            <div class="logo-box" ACADEMIA<span>AURELIUS</span></div>
            <p style="color: var(--text-muted); font-size: 13px;">Módulo Financeiro: Emissão de Propinas & Mensalidades</p>
        </div>

        <!-- Painéis Informativos Automatizados (Leitura Direta do Banco) -->
        <div id="msg_status_aluno" class="alerta-box"></div>
        <div id="msg_alerta_stock" class="alerta-box"></div>

        <form id="formFaturamento" onsubmit="gerarFaturaDigital(event)">
        <!-- CAMPO DE PESQUISA INICIAL -->
        <div class="form-group">
            <label>Pesquisar Aluno (Nome, Nº Telefone ou ID AUR):</label>
            <input type="text" id="pesquisa_aluno" class="form-input" placeholder="Digite o nome ou AUR- ..." oninput="buscarAlunoSincronizado(this.value)" required>
        </div>
    
        <!-- BLOCO DE FATURAMENTO (EXIBIDO APÓS SELECIONAR O ESTUDANTE) -->
        <div id="bloco_faturamento_oculto" style="display: none;">
            
            <!-- GRID DUPLA 1: CADEIRAS E MÊS -->
            <div class="grid-dupla">
                <div class="form-group">
                    <label>Disciplinas/Cadeiras Selecionadas:</label>
                    <select id="qtd_disciplinas" class="form-input" onchange="recalcularFaturamentoEscolar()">
                        <option value="1">1 Disciplina</option>
                        <option value="2">2 Disciplinas</option>
                        <option value="3">3 Disciplinas</option>
                        <option value="4">4 Disciplinas (Ganhar 20% OFF)</option>
                        <option value="5">5 Disciplinas (Ganhar 20% OFF)</option>
                        <option value="6">6 Disciplinas (Ganhar 20% OFF)</option>
                    </select>
                </div>
    
                <div class="form-group">
                    <label>Mês de Liquidação:</label>
                    <select id="mes_referencia" class="form-input">
                        <option value="Janeiro">Janeiro</option>
                        <option value="Fevereiro">Fevereiro</option>
                        <option value="Março">Março</option>
                        <option value="Abril">Abril</option>
                        <option value="Maio">Maio</option>
                        <option value="Junho">Junho</option>
                        <option value="Julho">Julho</option>
                        <option value="Agosto">Agosto</option>
                        <option value="Setembro">Setembro</option>
                        <option value="Outubro">Outubro</option>
                        <option value="Novembro">Novembro</option>
                        <option value="Dezembro">Dezembro</option>
                    </select>
                </div>
            </div>
    
            <!-- GRID DUPLA 2: ENTRADA FINANCEIRA E CONTACTO -->
            <div class="grid-dupla">
                <div class="form-group">
                    <label>Valor Entregue / Adiantado (AKZ):</label>
                    <input type="number" id="valor_entregue_input" class="form-input" value="0" min="0" oninput="calcularTrocoECredito(this.value)">
                </div>
                
                <div class="form-group">
                    <label>Contacto Registado:</label>
                    <input type="text" id="telefone_input" class="form-input" readonly>
                </div>
            </div>
    
             <!-- QUADRO RESUMO BASE DAS CONTAS -->
        <div class="resumo-fatura">
        <!-- 🌟 VISUALIZAÇÃO: Mostra o Stock Total que o aluno possui no banco -->
        <div class="resumo-linha" id="linha_saldo_total_banco" style="color: #38bdf8; display: none; font-weight: bold; margin-bottom: 10px; background: rgba(56, 189, 248, 0.05); padding: 8px; border-radius: 6px; border: 1px solid rgba(56, 189, 248, 0.15);">
            <span>Stock Total na Conta:</span>
            <span id="f_saldo_total_banco">0,00 AKZ</span>
        </div>

        <!-- 📚 COMPONENTE: Injeta a listagem detalhada de disciplinas e preços individuais -->
        <div id="detalhe_disciplinas_cliente" style="margin-bottom: 15px; border-bottom: 1px dashed rgba(255,255,255,0.1); padding-bottom: 10px; display: none;"></div>

        <div class="resumo-linha">
            <span>Preço Total das Cadeiras:</span>
            <span id="f_servico">0,00 AKZ</span>
        </div>
        
        <div class="resumo-linha" id="linha_divida" style="color: var(--brand-danger);">
            <span>Dívidas Acumuladas no Banco:</span>
            <span id="f_divida">0,00 AKZ</span>
        </div>
        
        <div class="resumo-linha" id="linha_desconto_vip" style="color: var(--brand-success); display: none;">
            <span>Desconto Cortesia (Apenas 4+ Cadeiras):</span>
            <span id="txt_desc_vip">-0,00 AKZ</span>
        </div>
        
        <div class="resumo-linha" id="linha_saldo_existente" style="color: #38bdf8; display: none;">
            <span>Saldo Abatido Automaticamente:</span>
            <span id="f_saldo_usado">-0,00 AKZ</span>
        </div>
        
        <div class="resumo-linha" id="linha_troco_caixa" style="color: var(--brand-gold); display: none;">
            <span>Troco Físico a Devolver:</span>
            <span id="lbl_troco_caixa">0,00 AKZ</span>
        </div>
        
        <div class="resumo-linha" id="linha_credito_futuro" style="color: #a855f7; display: none;">
            <span>Guardado em Stock (Adiantado):</span>
            <span id="lbl_credito_futuro">0,00 AKZ</span>
        </div>
        
        <div class="resumo-linha" style="border-top: 1px solid rgba(255,255,255,0.1); margin-top: 10px; padding-top: 10px; font-weight: bold;">
            <span>Total Líquido a Pagar no Caixa:</span>
            <span id="txt_total_liquido" style="color: var(--brand-success);">0,00 AKZ</span>
        </div>
    </div>

    <!-- BOTÃO DE CONFIRMAÇÃO DE CAIXA -->
    <button type="submit" class="btn-pay">Emitir Fatura & Confirmar Pagamento ➔</button>
</div>
</form>
</div>

<!-- 📋 BLOCO DA FATURA DE IMPRESSÃO IMPERIAL -->
<div id="bloco_fatura_recibo" style="display: none; background: #fff; color: #000; padding: 20px; font-family: monospace; max-width: 350px; margin: 20px auto; border: 1px solid #000;">
<h3 style="text-align: center; margin-bottom: 5px;">🏫 ACADEMIA AURÉLIUS</h3>
<div style="text-align: center; margin-bottom: 12px; font-size: 11px;">Huambo - São Luís Catimba</div>

<div class="recibo-linha" style="display: flex; justify-content: space-between; font-size: 12px; margin-bottom: 4px;">
    <span>Estudante:</span><b id="rec_nome">-</b>
</div>
<div class="recibo-linha" style="display: flex; justify-content: space-between; font-size: 12px; margin-bottom: 4px;">
    <span>Contacto:</span><span id="rec_tel">-</span>
</div>
<div class="recibo-linha" style="display: flex; justify-content: space-between; font-size: 12px; margin-bottom: 4px;">
    <span>Data de Emissão:</span><span id="rec_data">-</span>
</div>
<div class="recibo-linha" style="display: flex; justify-content: space-between; font-size: 12px; margin-bottom: 4px;">
    <span>Mês Pago:</span><span id="rec_mes">-</span>
</div>
<div class="recibo-linha" style="display: flex; justify-content: space-between; font-size: 12px; margin-bottom: 4px;">
    <span>Turma/Período:</span><span id="rec_turma">-</span>
</div>
<div class="recibo-linha" style="display: flex; justify-content: space-between; font-size: 12px; margin-bottom: 4px;">
    <span>Disciplinas:</span><span id="rec_qtd">-</span>
</div>

<div style="border-top: 1px dashed #000; margin: 8px 0;"></div>

<!-- 📚 DETALHAMENTO DAS DISCIPLINAS NO RECIBO IMPRESSO -->
<div id="rec_detalhe_lista_cadeiras" style="font-size: 11px; margin-bottom: 8px;"></div>

<div style="border-top: 1px dashed #000; margin: 8px 0;"></div>

<div class="recibo-linha" style="display: flex; justify-content: space-between; font-size: 12px; margin-bottom: 4px;">
    <span>Custo Base:</span><span id="rec_custo">-</span>
</div>
<div class="recibo-linha" id="rec_linha_desc" style="display: none; justify-content: space-between; font-size: 12px; margin-bottom: 4px; color: #000;">
    <span>Desconto VIP:</span><span id="rec_desc">-</span>
</div>
<div class="recibo-linha" id="rec_linha_saldo_usado" style="display: none; justify-content: space-between; font-size: 12px; margin-bottom: 4px;">
    <span>Saldo Abatido:</span><span id="rec_saldo_usado">-</span>
</div>
<div class="recibo-linha" id="rec_linha_divida" style="display: none; justify-content: space-between; font-size: 12px; margin-bottom: 4px;">
    <span>Atrasos Pagos:</span><span id="rec_divida">-</span>
</div>
<div class="recibo-linha" id="rec_linha_stock" style="display: none; justify-content: space-between; font-size: 12px; margin-bottom: 4px;">
    <span>Stock Adiantado:</span><span id="rec_stock">-</span>
</div>

<div style="border-top: 1px dashed #000; margin: 8px 0;"></div>

<div class="recibo-total" style="display: flex; justify-content: space-between; font-weight: bold; font-size: 14px;">
    <span>Total Pago:</span><span id="rec_total">-</span>
</div>

<div style="text-align: center; margin-top: 15px; font-size: 10px; border-top: 2px dashed #000; padding-top: 10px;">
    Obrigado pela confiança.<br>Documento processado via Caixa.
</div>
<button onclick="window.print()" style="margin-top: 15px; width: 100%; padding: 8px; font-family: monospace; background: #000; color: #fff; border: none; cursor: pointer; font-size: 12px; font-weight: bold;">Imprimir Fatura 🖨️</button>
</div>






   

<!-- O SCRIPT DEVE COMECAR EXATAMENTE AQUI -->
<script>
// 📦 1. REPOSITÓRIO DE PREÇOS EXPANDIDO E REAL (HUAMBO 2026)
const dbAcademica = {
    regular: [
        { id: '1c', nome: '1ª Classe (Primário)', sala: 'Sala nº 1', disciplinas: [{n: 'Língua Portuguesa', p: 1500}, {n: 'Matemática', p: 1500}] },
        { id: '2c', nome: '2ª Classe (Primário)', sala: 'Sala nº 2', disciplinas: [{n: 'Língua Portuguesa', p: 1500}, {n: 'Matemática', p: 1500}] },
        { id: '3c', nome: '3ª Classe (Primário)', sala: 'Sala nº 3', disciplinas: [{n: 'Língua Portuguesa', p: 2000}, {n: 'Matemática', p: 2000}] },
        { id: '4c', nome: '4ª Classe (Primário)', sala: 'Sala nº 4', disciplinas: [{n: 'Língua Portuguesa', p: 2000}, {n: 'Matemática', p: 1000}] },
        { id: '5c', nome: '5ª Classe (Primário)', sala: 'Sala nº 5', disciplinas: [{n: 'Língua Portuguesa', p: 1000}, {n: 'Matemática', p: 1000}, {n: 'Estudo do Meio', p: 1000}] },
        { id: '6c', nome: '6ª Classe (Primário)', sala: 'Sala nº 6', disciplinas: [{n: 'Língua Portuguesa', p: 1000}, {n: 'Matemática', p: 1000}, {n: 'Ciências da Natureza', p: 1000}] },
        { id: '7c', nome: '7ª Classe (I Ciclo)', sala: 'Sala nº 7', disciplinas: [{n: 'Língua Portuguesa', p: 1500}, {n: 'Matemática', p: 1500}, {n: 'Química', p: 1500}, {n: 'Física', p: 1500}] },
        { id: '8c', nome: '8ª Classe (I Ciclo)', sala: 'Sala nº 8', disciplinas: [{n: 'Língua Portuguesa', p: 1500}, {n: 'Matemática', p: 1500}, {n: 'Química', p: 1500}, {n: 'Física', p: 1500}] },
        { id: '9c', nome: '9ª Classe (I Ciclo)', sala: 'Sala nº 9', disciplinas: [{n: 'Língua Portuguesa', p: 1500}, {n: 'Matemática', p: 1500}, {n: 'História', p: 1500}, {n: 'Geografia', p: 1500}] },
        { id: '10c', nome: '10ª Classe (II Ciclo)', sala: 'Sala nº 10', disciplinas: [{n: 'Língua Portuguesa', p: 2000}, {n: 'Matemática', p: 2000}, {n: 'Física', p: 2000}] },
        { id: '11c', nome: '11ª Classe (II Ciclo)', sala: 'Sala nº 11', disciplinas: [{n: 'Língua Portuguesa', p: 2000}, {n: 'Matemática', p: 2000}, {n: 'Química', p: 2000}] },
        { id: '12c', nome: '12ª Classe (II Ciclo)', sala: 'Sala nº 12', disciplinas: [{n: 'Língua Portuguesa', p: 2000}, {n: 'Matemática', p: 2000}, {n: 'Filosofia', p: 2000}] },
        { id: '13c', nome: '13ª Classe (Técnico)', sala: 'Sala nº 13', disciplinas: [{n: 'Matemática Aplicada', p: 2500}, {n: 'Física Avançada', p: 2500}, {n: 'Estágio Prático', p: 2500}] }
    ],
    superior: {
        Economia: [
            { id: 'eco1', nome: 'Economia - 1º Ano', disciplinas: [{n: 'Introdução à Economia', p: 5000}, {n: 'Análise Matemática I', p: 5500}] }
        ],
        Informatica: [
            { id: 'inf1', nome: 'Informática - 1º Ano', disciplinas: [{n: 'Algorítmo e Lógica de Programação', p: 6000}, {n: 'Matemática Discreta', p: 6500}] }
        ]
    }
};

// 🔒 VARIÁVEIS GLOBAIS EXIGIDAS PELO SEU SISTEMA DE RECIBO
let nomeEstudanteAtivo = "";
let dadosAlunoAtivo = {
    divida: 0, 
    saldo_interno: 0, 
    custo_cadeiras: 0, 
    total_necessario: 0, 
    total_caixa: 0, 
    desconto_ganho: 0,
    classe_real: "",
    turma: "Turma Única A"
};

// 🎯 2. FUNÇÃO DE BUSCA SÍNCRONA CONTRA A ROTA GET DO BANCO DE DADOS
function buscarAlunoSincronizado(valorDigitado) {
    const termo = valorDigitado.trim();
    const blocoOculto = document.getElementById("bloco_faturamento_oculto");

    if (termo.length < 2) {
        if (blocoOculto) blocoOculto.style.display = "none";
        nomeEstudanteAtivo = "";
        return;
    }

    // Consulta em tempo real na Rota 2 do seu próprio PHP (unitel.php)
    fetch(`unitel.php?pesquisa_automatica_cliente=1&termo=${encodeURIComponent(termo)}`)
        .then(res => res.json())
        .then(dados => {
            if (dados.status === 'encontrado') {
                if (blocoOculto) blocoOculto.style.display = "block";
                
                // Mapeia o contacto registado no ecrã
                const telInput = document.getElementById("telefone_input");
                if (telInput) telInput.value = dados.telefone || "";
                
                // Popula os objetos globais mantendo a compatibilidade do sistema
                nomeEstudanteAtivo = dados.nome;
                dadosAlunoAtivo.classe_real = dados.classe; 
                dadosAlunoAtivo.divida = parseFloat(dados.divida) || 0;
                dadosAlunoAtivo.saldo_interno = parseFloat(dados.saldo_interno) || 0;
                dadosAlunoAtivo.turma = dados.turma || "Turma Única A";

                // 🌟 FIX CIRÚRGICO: Injeta e exibe imediatamente o Stock Real na label do topo
                const linhaSaldoTotal = document.getElementById("linha_saldo_total_banco");
                const txtSaldoTotal = document.getElementById("f_saldo_total_banco");
                if (linhaSaldoTotal && txtSaldoTotal) {
                    linhaSaldoTotal.style.display = "flex";
                    txtSaldoTotal.innerText = dadosAlunoAtivo.saldo_interno.toFixed(2).replace(".", ",") + " AKZ";
                }

                console.log("✔️ Aluno reconhecido com Stock de:", dadosAlunoAtivo.saldo_interno);
                
                // Dispara o motor de cálculo reativo
                recalcularFaturamentoEscolar();
            } else {
                if (blocoOculto) blocoOculto.style.display = "none";
            }
        })
        .catch(err => console.error("Erro na rota de busca:", err));
}

function calcularTrocoECredito(valorDigitado) {
    // 🌟 CORREÇÃO CIRÚRGICA: Converte o texto digitado num número real válido
    var valorEntregue = parseFloat(valorDigitado) || 0;

    // Captura os valores reais calculados no motor financeiro
    var precoTotalCadeirasReal = dadosAlunoAtivo.custo_cadeiras || 0;
    var desconto = dadosAlunoAtivo.desconto_ganho || 0;
    var subTotalFatura = precoTotalCadeirasReal - desconto;
    var stockDisponivel = dadosAlunoAtivo.saldo_interno || 0;
    
    // Quanto foi abatido do stock interno existente
    var saldoAbatidoAutomático = stockDisponivel >= subTotalFatura ? subTotalFatura : stockDisponivel;
    var totalLiquidoFinalNoCaixa = subTotalFatura - saldoAbatidoAutomático;

    var lblTroco = document.getElementById("lbl_troco_caixa");
    var lblCredito = document.getElementById("lbl_credito_futuro");
    var linhaTroco = document.getElementById("linha_troco_caixa");
    var linhaCredito = document.getElementById("linha_credito_futuro");

    // 🔄 CASO 1: O saldo interno já cobriu tudo (Total Líquido = 0)
    if (totalLiquidoFinalNoCaixa === 0 && valorEntregue > 0) {
        // Todo o dinheiro físico entregue vira Crédito Futuro (Guardado em Stock)
        if (linhaTroco) linhaTroco.style.display = "none";
        if (linhaCredito) linhaCredito.style.display = "flex";
        
        if (lblTroco) lblTroco.innerText = "0,00 AKZ";
        if (lblCredito) lblCredito.innerText = valorEntregue.toFixed(2).replace(".", ",") + " AKZ";
    } 
    // 🔄 CASO 2: O aluno ainda tinha saldo a pagar no caixa e deu dinheiro a mais
    else if (totalLiquidoFinalNoCaixa > 0 && valorEntregue > totalLiquidoFinalNoCaixa) {
        var diferenca = valorEntregue - totalLiquidoFinalNoCaixa;
        if (linhaTroco) linhaTroco.style.display = "flex";
        if (linhaCredito) linhaCredito.style.display = "none";
        
        if (lblTroco) lblTroco.innerText = diferenca.toFixed(2).replace(".", ",") + " AKZ";
        if (lblCredito) lblCredito.innerText = "0,00 AKZ";
    } 
    // 🔄 CASO 3: O pagamento foi exato ou insuficiente
    else {
        if (linhaTroco) linhaTroco.style.display = "none";
        if (linhaCredito) Richmond; linhaCredito.style.display = "none";
        if (lblTroco) lblTroco.innerText = "0,00 AKZ";
        if (lblCredito) lblCredito.innerText = "0,00 AKZ";
    }
}
function recalcularFaturamentoEscolar() {
    if (!nomeEstudanteAtivo) return;

    const stringCursoCompleto = dadosAlunoAtivo.classe_real || "";
    const matchDisciplinas = stringCursoCompleto.match(/\[(.*?)\]/);
    let listaDisciplinasDoAluno = [];
    
    if (matchDisciplinas && matchDisciplinas[1]) {
        listaDisciplinasDoAluno = matchDisciplinas[1].split(",").map(d => d.trim()).filter(d => d !== "");
    }

    if (listaDisciplinasDoAluno.length === 0) {
        const selectQtd = document.getElementById("qtd_disciplinas");
        const qtdManual = selectQtd ? parseInt(selectQtd.value) : 1;
        for (let i = 1; i <= qtdManual; i++) {
            listaDisciplinasDoAluno.push(`Cadeira Regular ${i}`);
        }
    } else {
        const selectQtd = document.getElementById("qtd_disciplinas");
        if (selectQtd) selectQtd.value = listaDisciplinasDoAluno.length;
    }

    // Mostra o Stock Total do Aluno na linha do topo
    const linhaSaldoTotal = document.getElementById("linha_saldo_total_banco");
    const txtSaldoTotal = document.getElementById("f_saldo_total_banco");
    if (linhaSaldoTotal && txtSaldoTotal) {
        linhaSaldoTotal.style.display = "flex";
        txtSaldoTotal.innerText = dadosAlunoAtivo.saldo_interno.toFixed(2).replace(".", ",") + " AKZ";
    }

    // Calcula os preços reais mapeados
    let precoTotalCadeirasReal = 0;
    let detalheArray = [];
    const nomeNivelLimpo = stringCursoCompleto.split("[")[0].trim();

    const nivelConfig = dbAcademica.regular.find(r => r.nome.toLowerCase() === nomeNivelLimpo.toLowerCase());
    let bancoDisciplinas = nivelConfig ? nivelConfig.disciplinas : [];

    listaDisciplinasDoAluno.forEach(nomeDisc => {
        const configDisc = bancoDisciplinas.find(d => d.n.trim().toLowerCase() === nomeDisc.toLowerCase());
        const precoVerdadeiro = configDisc ? configDisc.p : 1500; // Padrão 1500 se não achar
        precoTotalCadeirasReal += precoVerdadeiro;
        detalheArray.push({ nome: nomeDisc, preco: precoVerdadeiro });
    });

    dadosAlunoAtivo.custo_cadeiras = precoTotalCadeirasReal;

    // Desconto VIP de 20% se houver 4 ou mais disciplinas
    let desconto = listaDisciplinasDoAluno.length >= 4 ? precoTotalCadeirasReal * 0.20 : 0;
    dadosAlunoAtivo.desconto_ganho = desconto;
    
    const linhaDesc = document.getElementById("linha_desconto_vip");
    if (linhaDesc) linhaDesc.style.display = desconto > 0 ? "flex" : "none";

    let subTotalFatura = precoTotalCadeirasReal - desconto;

    // Abatimento automático de stock
    let stockDisponivel = dadosAlunoAtivo.saldo_interno;
    let saldoAbatidoAutomático = stockDisponivel >= subTotalFatura ? subTotalFatura : stockDisponivel;

    const linhaSaldo = document.getElementById("linha_saldo_existente");
    if (linhaSaldo) linhaSaldo.style.display = saldoAbatidoAutomático > 0 ? "flex" : "none";

    let totalLiquidoFinalNoCaixa = (subTotalFatura + dadosAlunoAtivo.divida) - saldoAbatidoAutomático;
    if (totalLiquidoFinalNoCaixa < 0) totalLiquidoFinalNoCaixa = 0;
    dadosAlunoAtivo.total_caixa = totalLiquidoFinalNoCaixa;

    // Injeção de valores na tela
    document.getElementById("f_servico").innerText = precoTotalCadeirasReal.toFixed(2).replace(".", ",") + " AKZ";
    document.getElementById("f_divida").innerText = dadosAlunoAtivo.divida.toFixed(2).replace(".", ",") + " AKZ";
    if (document.getElementById("txt_desc_vip")) document.getElementById("txt_desc_vip").innerText = "-" + desconto.toFixed(2).replace(".", ",") + " AKZ";
    if (document.getElementById("f_saldo_usado")) document.getElementById("f_saldo_usado").innerText = "-" + saldoAbatidoAutomático.toFixed(2).replace(".", ",") + " AKZ";
    document.getElementById("txt_total_liquido").innerText = totalLiquidoFinalNoCaixa.toFixed(2).replace(".", ",") + " AKZ";

    // 📋 EXIBE AS DISCIPLINAS E OS SEUS PREÇOS EXATOS
    renderizarListaDeCadeirasCliente(detalheArray);
    
    const valorAtualInput = parseFloat(document.getElementById("valor_entregue_input").value) || 0;
    calcularTrocoECredito(valorAtualInput);
}

// 👁️ COMPONENTE VISUAL DAS CADEIRAS (Injeta as disciplinas detalhadamente)
function renderizarListaDeCadeirasCliente(disciplinas) {
    var containerDetalhe = document.getElementById("detalhe_disciplinas_cliente");
    if (!containerDetalhe) return;

    if (disciplinas.length === 0) {
        containerDetalhe.style.display = "none";
        return;
    }

    containerDetalhe.style.display = "block";
    
    var htmlGerado = '<div style="font-size: 11px; font-weight: bold; color: var(--brand-gold); text-transform: uppercase; margin-bottom: 6px;">📚 Módulos e Preços Individuais:</div>';
    
    disciplinas.forEach(function(item) {
        var precoFormatado = item.preco.toFixed(2).replace(".", ",");
        htmlGerado += '<div style="display: flex; justify-content: space-between; font-size: 12.5px; color: #cbd5e1; margin-bottom: 4px;">' +
                      '<span>📖 ' + item.nome + '</span>' +
                      '<span style="font-weight: bold; color: #fff;">' + precoFormatado + ' AKZ</span>' +
                      '</div>';
    });

    containerDetalhe.innerHTML = htmlGerado;
}




function gerarFaturaDigital(event) {
    if (event) event.preventDefault();
    
    if (!nomeEstudanteAtivo) {
        alert("⚠️ Erro: Nenhum estudante selecionado para faturamento.");
        return;
    }

    const mes = document.getElementById('mes_referencia').value;
    const entregue = parseFloat(document.getElementById('valor_entregue_input').value) || 0;
    const telefoneAluno = document.getElementById('telefone_input').value;
    const txtTotal = document.getElementById("txt_total_liquido").innerText;
    const totalLiquido = parseFloat(txtTotal.replace(" AKZ", "").replace(".", "").replace(",", ".")) || 0;

    if (entregue < totalLiquido) {
        alert("❌ Erro: O valor entregue é inferior ao total líquido obrigatório.");
        return;
    }

    // 📅 GERAÇÃO DINÂMICA DA DATA EXIGIDA NO RECIBO HTML
    const dataAtual = new Date();
    const dia = String(dataAtual.getDate()).padStart(2, '0');
    const mesesAno = ["Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro"];
    const nomeMes = mesesAno[dataAtual.getMonth()];
    const ano = dataAtual.getFullYear();
    const dataFormatada = `${dia} de ${nomeMes} de ${ano}`;

    // 📋 INJEÇÃO DOS DADOS NO RECIBO VISUAL DO PORTAL
    if (document.getElementById('rec_nome')) document.getElementById('rec_nome').innerText = nomeEstudanteAtivo;
    if (document.getElementById('rec_tel')) document.getElementById('rec_tel').innerText = telefoneAluno;
    if (document.getElementById('rec_mes')) document.getElementById('rec_mes').innerText = mes;
    if (document.getElementById('rec_data')) document.getElementById('rec_data').innerText = dataFormatada;
    if (document.getElementById('rec_qtd')) document.getElementById('rec_qtd').innerText = document.getElementById('qtd_disciplinas').value + " Disciplina(s)";
    if (document.getElementById('rec_turma')) document.getElementById('rec_turma').innerText = dadosAlunoAtivo.turma || "Geral";
    
    // Recupera os valores de preço calculados no ecrã para o recibo
    const fServicoTxt = document.getElementById("f_servico").innerText;
    const fSaldoUsadoTxt = document.getElementById("f_saldo_usado") ? document.getElementById("f_saldo_usado").innerText : "-0,00 AKZ";

    if (document.getElementById('rec_custo')) document.getElementById('rec_custo').innerText = fServicoTxt;

    // Sincroniza o detalhamento das cadeiras do ecrã para o recibo de impressão
    const containerDetalheEcra = document.getElementById("detalhe_disciplinas_cliente");
    const containerDetalheRecibo = document.getElementById("rec_detalhe_lista_cadeiras");
    if (containerDetalheEcra && containerDetalheRecibo) {
        containerDetalheRecibo.innerHTML = containerDetalheEcra.innerHTML.replace("📚 Módulos e Preços Reais:", "<b>Discriminação dos Módulos:</b>");
    }

    // Estrutura visual de Desconto VIP no recibo
    if (dadosAlunoAtivo.desconto_ganho > 0) {
        if (document.getElementById('rec_linha_desc')) document.getElementById('rec_linha_desc').style.display = 'flex';
        if (document.getElementById('rec_desc')) document.getElementById('rec_desc').innerText = document.getElementById("txt_desc_vip").innerText;
    } else { 
        if (document.getElementById('rec_linha_desc')) document.getElementById('rec_linha_desc').style.display = 'none'; 
    }

    // Estrutura visual de Saldo Abatido no recibo
    const saldoUsadoValor = parseFloat(fSaldoUsadoTxt.replace("-", "").replace(" AKZ", "").replace(".", "").replace(",", ".")) || 0;
    if (saldoUsadoValor > 0) {
        if (document.getElementById('rec_linha_saldo_usado')) document.getElementById('rec_linha_saldo_usado').style.display = 'flex';
        if (document.getElementById('rec_saldo_usado')) document.getElementById('rec_saldo_usado').innerText = fSaldoUsadoTxt;
    } else {
        if (document.getElementById('rec_linha_saldo_usado')) document.getElementById('rec_linha_saldo_usado').style.display = 'none';
    }

    // Estrutura visual de Dívidas Acumuladas no recibo
    if (dadosAlunoAtivo.divida > 0) {
        if (document.getElementById('rec_linha_divida')) document.getElementById('rec_linha_divida').style.display = 'flex';
        if (document.getElementById('rec_divida')) document.getElementById('rec_divida').innerText = '+' + dadosAlunoAtivo.divida.toLocaleString('pt-PT') + " AKZ";
    } else { 
        if (document.getElementById('rec_linha_divida')) document.getElementById('rec_linha_divida').style.display = 'none'; 
    }

    // Estrutura de Stock Adiantado (Se o valor entregue superou o líquido final do caixa)
    const sobraStock = entregue - totalLiquido;
    if (sobraStock > 0) {
        if (document.getElementById('rec_linha_stock')) document.getElementById('rec_linha_stock').style.display = 'flex';
        if (document.getElementById('rec_stock')) document.getElementById('rec_stock').innerText = '+' + sobraStock.toLocaleString('pt-PT') + " AKZ";
    } else { 
        if (document.getElementById('rec_linha_stock')) document.getElementById('rec_linha_stock').style.display = 'none'; 
    }

    if (document.getElementById('rec_total')) document.getElementById('rec_total').innerText = entregue.toLocaleString('pt-PT') + " AKZ";

    // 🚀 ENVIO SEGURO EM FORMATO JSON COMPATÍVEL COM O RENDER
    fetch("unitel.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/json; charset=utf-8"
        },
        body: JSON.stringify({
            acao_financeira: 'registar_pagamento',
            telefone: telefoneAluno,
            valor_pago: entregue,
            mes_pago: mes,
            saldo_abatido: saldoUsadoValor
        })
    })
    .then(res => {
        if (!res.ok) throw new Error("A porta de rede do Render rejeitou a resposta.");
        return res.json();
    })
    .then(resposta => {
        if (resposta.sucesso) {
            console.log("🎉 Sincronização concluída no MySQL central via Render.");
            
            // Torna o bloco do recibo imperial visível e faz scroll suave
            const blocoFatura = document.getElementById('bloco_fatura_recibo');
            if (blocoFatura) {
                blocoFatura.style.display = 'block';
                window.scrollTo({ top: document.body.scrollHeight, behavior: 'smooth' });
            }
        } else {
            alert("❌ O Banco rejeitou a transação: " + resposta.mensagem);
        }
    })
    .catch(err => {
        console.error("Erro capturado:", err);
        alert("⚠️ Falha de comunicação: O recibo foi montado na tela mas os dados não puderam ser transmitidos para o servidor Render.");
    });
}
</script>
</body>
</html>