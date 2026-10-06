<?php
// Adicione estes cabeçalhos no topo do unitel.php para evitar bloqueios de rede (CORS)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// =========================================================================
// 📥 ROTA 1: PROCESSAMENTO POST (MATRÍCULAS VS CONFIRMAÇÃO DE PAGAMENTO)
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');
    require_once 'conexao.php';

    $inputRaw = file_get_contents("php://input");
    $dadosJson = json_decode($inputRaw, true);

    if ($dadosJson) {
        // Se vier do JavaScript (Fetch JSON)
        $acao         = isset($dadosJson['acao_financeira']) ? trim($dadosJson['acao_financeira']) : 'fazer_matricula';
        $nome         = isset($dadosJson['nome']) ? trim($dadosJson['nome']) : '';
        $telefone     = isset($dadosJson['telefone']) ? trim($dadosJson['telefone']) : '';
        $curso        = isset($dadosJson['curso']) ? trim($dadosJson['curso']) : '';
        $periodo      = isset($dadosJson['periodo']) ? trim($dadosJson['periodo']) : '';
        $id_escolar   = isset($dadosJson['id_unico_escolar']) ? trim($dadosJson['id_unico_escolar']) : '';
        $validacao    = isset($dadosJson['codigo_validacao']) ? trim($dadosJson['codigo_validacao']) : '';
        $valor_pago   = isset($dadosJson['valor_pago']) ? floatval($dadosJson['valor_pago']) : 0;
        $saldoAbatido = isset($dadosJson['saldo_abatido']) ? floatval($dadosJson['saldo_abatido']) : 0;
        $mes_pago     = isset($dadosJson['mes_pago']) ? trim($dadosJson['mes_pago']) : '';
    } else {
        // Se vier de um formulário $_POST tradicional
        $acao         = isset($_POST['acao_financeira']) ? trim($_POST['acao_financeira']) : 'fazer_matricula';
        $nome         = isset($_POST['nome']) ? trim($_POST['nome']) : '';
        $telefone     = isset($_POST['telefone']) ? trim($_POST['telefone']) : '';
        $curso        = isset($_POST['curso']) ? trim($_POST['curso']) : '';
        $periodo      = isset($_POST['periodo']) ? trim($_POST['periodo']) : '';
        $id_escolar   = isset($_POST['id_unico_escolar']) ? trim($_POST['id_unico_escolar']) : '';
        $validacao    = isset($_POST['codigo_validacao']) ? trim($_POST['codigo_validacao']) : '';
        $valor_pago   = isset($_POST['valor_pago']) ? floatval($_POST['valor_pago']) : 0;
        $saldoAbatido = 0;
        $mes_pago     = isset($_POST['mes_pago']) ? trim($_POST['mes_pago']) : '';
    }

    if (empty($telefone)) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Contacto do aluno em falta.']);
        exit;
    }

    // 🌟 SUB-ROTA A: SE FOR LIQUIDAÇÃO DE MENSALIDADE (EMISSÃO DE FATURA DIGITAL)
    if ($acao === 'registar_pagamento') {
        try {
            // 🔍 1. Localiza o aluno na base de dados e obtém o seu saldo atual
            $check = $pdo->prepare("SELECT id_utilizador, saldo_propina, curso FROM utilizadores WHERE telefone = ? AND nivel = 'estudante' LIMIT 1");
            $check->execute([$telefone]);
            $aluno = $check->fetch(PDO::FETCH_ASSOC);

            if ($aluno) {
                $id_user = $aluno['id_utilizador'];
                $saldo_atual_banco = floatval($aluno['saldo_propina']);
// =========================================================================
// 💸 AJUSTE UNIFICADO E DINÂMICO DE MENSALIDADE PARA TODAS AS CLASSES
// =========================================================================
// O sistema assume o preço base dinâmico vindo do cálculo das disciplinas
$precoMensalidadeLiquida = $precoTotalOriginal; 

// Se preferir manter um teto fixo padrão com desconto para qualquer classe com 6 disciplinas:
if ($contagemCadeiras >= 6) {
    $precoMensalidadeLiquida = 8000.00; // Preço padrão promocional aplicado a todas as turmas
} else {
    // Caso queira aplicar uma taxa dinâmica proporcional por cadeira para qualquer classe
    $precoMensalidadeLiquida = $precoTotalOriginal; 
}
                // 🧮 2. Cálculo do Stock Futuro livre de estouro numérico (Overflow)
                $custoEfetivoCaixa = $precoMensalidadeLiquida - $saldoAbatido;
                $sobraStock = $valor_pago - $custoEfetivoCaixa;
                $novo_saldo_stock = $saldo_atual_banco + $sobraStock;
                
                if ($novo_saldo_stock < 0) $novo_saldo_stock = 0.00;

                // 🔄 3. Salva com precisão decimal o novo saldo na nuvem do Render
                $stmtUpdate = $pdo->prepare("UPDATE utilizadores SET saldo_propina = ? WHERE id_utilizador = ?");
                $executo = $stmtUpdate->execute([$novo_saldo_stock, $id_user]);

                echo json_encode([
                    'sucesso' => $executo,
                    'mensagem' => "🎉 Mensalidade de {$mes_pago} confirmada no caixa! Stock adiantado atualizado com sucesso."
                ]);
            } else {
                echo json_encode(['sucesso' => false, 'mensagem' => 'Não pode pagar uma disciplina se não fez a Matrícula.']);
            }
        } catch (Exception $e) {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Erro de faturamento: ' . $e->getMessage()]);
        }
        exit;
    }

    // 📝 SUB-ROTA B: SE FOR APENAS REALIZAR MATRÍCULA/INSCRIÇÃO (LOGICA ANTIGA CORRIGIDA)
    if ($acao === 'fazer_matricula' || $acao === '') {
        try {
            $check = $pdo->prepare("SELECT id_utilizador FROM utilizadores WHERE telefone = ?");
            $check->execute([$telefone]);
            $alunoExiste = $check->fetch(PDO::FETCH_ASSOC);

            $senha_padrao_hash = md5($validacao); 
            $nomeCompleto = !empty($nome) ? $nome : "Estudante Inscrito";

            if ($alunoExiste) {
                $stmt = $pdo->prepare("UPDATE utilizadores SET nome = ?, id_unico_escolar = ?, email = ?, senha = ?, curso = ?, periodo = ?, nivel = 'estudante' WHERE telefone = ?");
                $executo = $stmt->execute([$nomeCompleto, $id_escolar, $validacao, $senha_padrao_hash, $curso, $periodo, $telefone]);
                $msg = "Inscrição e códigos atualizados com sucesso para o aluno existente!";
            } else {
                $stmt = $pdo->prepare("INSERT INTO utilizadores (nome, telefone, email, senha, nivel, id_unico_escolar, curso, periodo, saldo_propina) VALUES (?, ?, ?, ?, 'estudante', ?, ?, ?, 0)");
                $executo = $stmt->execute([$nomeCompleto, $telefone, $validacao, $senha_padrao_hash, $id_escolar, $curso, $periodo]);
                $msg = "Novo aluno gravado com sucesso no banco de dados central!";
            }

            echo json_encode([
                'sucesso' => $executo, 
                'mensagem' => $msg,
                'id_unico' => $id_escolar,
                'codigo_validacao' => $validacao,
                'telefone' => $telefone
            ]);
        } catch (Exception $e) {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Erro crítico MySQL de matrícula: ' . $e->getMessage()]);
        }
        exit;
    }
}

// =========================================================================
// 🔍 ROTA 2: PROCESSAMENTO GET (MÓDULOS REAIS E PREÇOS DINÂMICOS DO BANCO)
// =========================================================================
if (isset($_GET['pesquisa_automatica_cliente']) && isset($_GET['termo'])) {
    header('Content-Type: application/json; charset=utf-8');
    require_once 'conexao.php';
    
    $termo = trim($_GET['termo']);
    
    try {
        $stmt = $pdo->prepare("SELECT id_utilizador, nome, telefone, saldo_propina, curso, id_unico_escolar FROM utilizadores WHERE (id_unico_escolar = ? OR nome LIKE ? OR telefone = ?) AND nivel = 'estudante' LIMIT 1");
        $stmt->execute([$termo, "%$termo%", $termo]);
        $aluno = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($aluno) {
            $cursoBruto = !empty($aluno['curso']) ? $aluno['curso'] : "";
            
            $disciplinasEstruturadas = [];
            $precoTotalOriginal = 0;
            
            if (!empty($cursoBruto)) {
                $limpaCurso = str_ireplace(['[Inscrição]', '[', ']', '[Inscrição Inicial]'], '', $cursoBruto);
                
                // 🎯 Extrai e limpa apenas o número e a palavra Classe (Ex: "3ª Classe" ou "12ª Classe")
                $gavetaClasse = ""; 
                if (preg_match('/(\d+ª\s*Classe)/i', $cursoBruto, $matches)) {
                    $gavetaClasse = trim($matches[1]); 
                }

                $partes = preg_split('/[,;\n\r]+/', $limpaCurso);
                $nomesCadeiras = array_filter(array_map('trim', $partes));
                
                foreach ($nomesCadeiras as $nomeCadeira) {
                    if (empty($nomeCadeira)) continue;

                    $buscaNome = trim($nomeCadeira);
                    if (strpos(strtolower($nomeCadeira), 'portuguesa') !== false) {
                        $buscaNome = 'Língua Portuguesa';
                    }

                    // 🎯 BUSCA CIRÚRGICA: Procura a disciplina combinando o Nome E a Classe do aluno
                    $stmtPreco = $pdo->prepare("SELECT preco_base FROM cursos_disciplinas 
                                               WHERE LOWER(nome) = LOWER(?) 
                                               AND LOWER(nivel_academico) LIKE LOWER(?) 
                                               LIMIT 1");
                    
                    $stmtPreco->execute([$buscaNome, "%" . $gavetaClasse . "%"]);
                    $precoCadeira = $stmtPreco->fetchColumn();
                    
                    // Se não encontrar na gaveta, faz um fallback seguro apenas pelo nome da matéria com menor preço
                    if ($precoCadeira === false) {
                        $stmtPrecoGlobal = $pdo->prepare("SELECT preco_base FROM cursos_disciplinas 
                                                         WHERE LOWER(nome) = LOWER(?) 
                                                         ORDER BY preco_base ASC LIMIT 1");
                        $stmtPrecoGlobal->execute([$buscaNome]);
                        $precoCadeira = $stmtPrecoGlobal->fetchColumn();
                    }
                    
                    $valorFinalCadeira = $precoCadeira ? floatval($precoCadeira) : 0.00;
                    $precoTotalOriginal += $valorFinalCadeira;
                    
                    $disciplinasEstruturadas[] = [
                        'nome' => $nomeCadeira,
                        'preco' => $valorFinalCadeira
                    ];
                } // Fim do foreach
            } // Fim do if (!empty($cursoBruto))


            $contagemCadeiras = count($disciplinasEstruturadas);
            $descontoCortesia = ($contagemCadeiras >= 4) ? ($precoTotalOriginal * 0.20) : 0.00; 
            $totalAPagarFinal = $precoTotalOriginal - $descontoCortesia;
            $saldoReal = ($aluno['saldo_propina'] > 0) ? floatval($aluno['saldo_propina']) : 0;

            echo json_encode([
                'status' => 'encontrado',
                'nome' => $aluno['nome'],
                'telefone' => $aluno['telefone'],
                'turma' => 'Turma Única A',
                'classe' => $cursoBruto,
                'total_disciplinas' => $contagemCadeiras,
                'saldo_interno' => $saldoReal,
                'preco_total' => $precoTotalOriginal,
                'desconto' => $descontoCortesia,
                'total_a_pagar' => $totalAPagarFinal,
                'disciplinas' => $disciplinasEstruturadas
            ], JSON_UNESCAPED_UNICODE);
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
    <link rel="manifest" href="./manifest.json">
    <title>Faturamento de Propinas - Academia Aurélius</title>
    <style>
        :root {
            --bg-main: grey;
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

    <div class="checkout-container">
        <!-- 🏫 Logótipo Oficial Integrado -->
        <div class="header-logo-escrita">
        <div>
                    <a href="Principal.html" style="color: #cbd5e1; text-decoration: none; font-size: 13px; font-weight: 700; padding: 8px 16px; border-radius: 20px; background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.05); transition: all 0.3s;" onmouseover="this.style.background='rgba(234, 179, 8, 0.15)'; this.style.color='var(--brand-gold)';" onmouseout="this.style.background='rgba(255,255,255,0.02)'; this.style.color='#cbd5e1';">Home</a>
              <br> <br> <br> <br>

        <!-- Painéis Informativos Automatizados (Leitura Direta do Banco) -->
        <div id="msg_status_aluno" class="alerta-box"></div>
        <div id="msg_alerta_stock" class="alerta-box"></div>

        <form id="formFaturamento" onsubmit="gerarFaturaDigital(event)">
        <!-- CAMPO DE PESQUISA INICIAL -->
        <div class="form-group">
            <label>Pesquisar Aluno (Nome, Nº Telefone ou ID AUR):</label>
            <input type="text" id="pesquisa_aluno" class="form-input" placeholder="Digite aqui" oninput="buscarAlunoSincronizado(this.value)" required>

            <!-- Coloque isto logo abaixo do <form id="formFaturamento" onsubmit="gerarFaturaDigital(event)"> -->
<input type="hidden" id="id_utilizador_hidden" name="id_utilizador">
<input type="hidden" id="saldo_propina_atual_hidden" name="saldo_propina_atual">
        </div>
    
        <!-- BLOCO DE FATURAMENTO (EXIBIDO APÓS SELECIONAR O ESTUDANTE) -->
        <div id="bloco_faturamento_oculto" style="display: none;">
            
            <!-- GRID DUPLA 1: CADEIRAS E MÊS -->
            <div class="grid-dupla">
                <div class="form-group">
                    <label>Disciplinas Selecionadas:</label>
                    <select id="qtd_disciplinas" class="form-input" onchange="recalcularFaturamentoEscolar()">
                        <option value="1">1 Disciplina</option>
                        <option value="2">2 Disciplinas</option>
                        <option value="3">3 Disciplinas</option>
                        <option value="4">4 Disciplinas (Ganha 20% de Desconto)</option>
                        <option value="5">5 Disciplinas (Ganha 20% de desconto)</option>
                        <option value="6">6 Disciplinas (Ganha 20% de desconto )</option>
                    </select>
                </div>
    
                <div class="form-group">
                    <label>Mês de Liquidação:</label>
                    <select id="mes_referencia" class="form-input">
                    <option value="Janeiro">Selecione o Mês</option>
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
                    <label>Valor Entregue / Adiantar (AKZ):</label>
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
            <span>Preço Total:</span>
            <span id="f_servico">0,00 AKZ</span>
        </div>
        
        <div class="resumo-linha" id="linha_divida" style="color: var(--brand-danger);">
            <span>Dinheiro Acumulado no Stock:</span>
            <span id="f_divida">0,00 AKZ</span>
        </div>
        
        <div class="resumo-linha" id="linha_desconto_vip" style="color: var(--brand-success); display: none;">
            <span>Desconto de 20% (4 ou + Discipl..):</span>
            <span id="txt_desc_vip">-0,00 AKZ</span>
        </div>
        
        <div class="resumo-linha" id="linha_saldo_existente" style="color: #38bdf8; display: none;">
            <span>Saldo Abatido :</span>
            <span id="f_saldo_usado">-0,00 AKZ</span>
        </div>
        
        <div class="resumo-linha" id="linha_troco_caixa" style="color: var(--brand-gold); display: none;">
            <span>Troco a Devolver:</span>
            <span id="lbl_troco_caixa">0,00 AKZ</span>
        </div>
        
        <div class="resumo-linha" id="linha_credito_futuro" style="color: #a855f7; display: none;">
            <span>Guardado em Stock:</span>
            <span id="lbl_credito_futuro">0,00 AKZ</span>
        </div>
        
        <div class="resumo-linha" style="border-top: 1px solid rgba(255,255,255,0.1); margin-top: 10px; padding-top: 10px; font-weight: bold;">
            <span>Total de Saldo a Pagar:</span>
            <span id="txt_total_liquido" style="color: var(--brand-success);">0,00 AKZ</span>
        </div>
    </div>

    <!-- BOTÃO DE CONFIRMAÇÃO DE CAIXA -->
    <button type="submit" class="btn-pay">Emitir Fatura & Confirmar Pagamento</button>
</div>
</form>
</div>

<!-- 📋 BLOCO DA FATURA DE IMPRESSÃO IMPERIAL -->
<div id="bloco_fatura_recibo" style="display: none; background: #fff; color: #000; padding: 20px; font-family: monospace; max-width: 350px; margin: 20px auto; border: 1px solid #000;">
<h3 style="text-align: center; margin-bottom: 5px;"> ACADEMIA AURÉLIUS</h3>
<div style="text-align: center; margin-bottom: 12px; font-size: 11px;">Escritório: Huambo - São Luís Catimba</div>

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






   
<script>
// 🔒 VARIÁVEIS GLOBAIS DO SISTEMA ACADEMIA AURÉLIUS
let nomeEstudanteAtivo = "";
let dadosAlunoAtivo = {
    id_utilizador: 0,
    divida: 0, 
    saldo_interno: 0, 
    custo_cadeiras: 0, 
    total_necessario: 0, 
    desconto_ganho: 0,
    classe_real: "",
    turma: "Turma Única A",
    disciplinas_reais: []
};

// 🎯 1. MOTOR DE BUSCA SÍNCRONA CONTRA O BANCO DE DADOS
function buscarAlunoSincronizado(valorDigitado) {
    const termo = valorDigitado.trim();
    const blocoOculto = document.getElementById("bloco_faturamento_oculto");

    if (termo.length < 2) {
        if (blocoOculto) blocoOculto.style.display = "none";
        nomeEstudanteAtivo = "";
        return;
    }

    fetch(`unitel.php?pesquisa_automatica_cliente=1&termo=${encodeURIComponent(termo)}`)
        .then(res => res.json())
        .then(dados => {
            if (dados.status === 'encontrado') {
                if (blocoOculto) blocoOculto.style.display = "block";
                
                // Popula inputs básicos escondidos e visíveis
                if (document.getElementById("telefone_input")) document.getElementById("telefone_input").value = dados.telefone || "";
                if (document.getElementById("id_utilizador_hidden")) document.getElementById("id_utilizador_hidden").value = dados.id_utilizador || "";
                if (document.getElementById("saldo_propina_atual_hidden")) document.getElementById("saldo_propina_atual_hidden").value = dados.saldo_interno || 0;
                
                // Guarda os dados no estado global da aplicação
                nomeEstudanteAtivo = dados.nome;
                dadosAlunoAtivo.id_utilizador = dados.id_utilizador;
                dadosAlunoAtivo.classe_real = dados.classe; 
                dadosAlunoAtivo.saldo_interno = parseFloat(dados.saldo_interno) || 0;
                dadosAlunoAtivo.custo_cadeiras = parseFloat(dados.preco_total) || 0;
                dadosAlunoAtivo.desconto_ganho = parseFloat(dados.desconto) || 0;
                dadosAlunoAtivo.total_necessario = parseFloat(dados.total_a_pagar) || 0;
                dadosAlunoAtivo.disciplinas_reais = dados.disciplinas || [];

                // Exibe o Stock Total na conta
                const linhaSaldoTotal = document.getElementById("linha_saldo_total_banco");
                const txtSaldoTotal = document.getElementById("f_saldo_total_banco");
                if (linhaSaldoTotal && txtSaldoTotal) {
                    linhaSaldoTotal.style.display = "flex";
                    txtSaldoTotal.innerText = dadosAlunoAtivo.saldo_interno.toFixed(2).replace(".", ",") + " AKZ";
                }

                // 🔄 SINCRONIZAÇÃO AUTOMÁTICA DO SELECT DE QUANTIDADE
                const selectQtd = document.getElementById("qtd_disciplinas");
                if (selectQtd) {
                    selectQtd.value = dados.total_disciplinas >= 1 && dados.total_disciplinas <= 6 ? dados.total_disciplinas : "1";
                }

                // 📚 INJEÇÃO AUTOMÁTICA DAS DISCIPLINAS NO COMPONENTE
                const containerDetalhe = document.getElementById("detalhe_disciplinas_cliente");
                if (containerDetalhe && dados.disciplinas) {
                    containerDetalhe.style.display = "block";
                    containerDetalhe.innerHTML = `<label style="font-weight:bold; display:block; margin-bottom:5px; color:#94a3b8;"> Módulos e Preços :</label>`;
                    
                    dados.disciplinas.forEach(disc => {
                        const precoFmt = parseFloat(disc.preco).toFixed(2).replace(".", ",") + " AKZ";
                        containerDetalhe.innerHTML += `
                            <div style="display:flex; justify-content:space-between; padding:3px 5px; font-size:13px; border-bottom:1px solid rgba(255,255,255,0.05);">
                                <span>${disc.nome}</span>
                                <span style="color:#38bdf8;">${precoFmt}</span>
                            </div>
                        `;
                    });
                }

                // Executa o reprocessamento visual dos valores
                recalcularFaturamentoEscolar();
            } else {
                if (blocoOculto) blocoOculto.style.display = "none";
            }
        })
        .catch(err => console.error("Erro no motor de faturamento dinâmico:", err));
}

// 🔄 2. MOTOR DE CÁLCULO E RENDERIZAÇÃO DA INTERFACE
function recalcularFaturamentoEscolar() {
    const custoBase = dadosAlunoAtivo.custo_cadeiras;
    const desconto = dadosAlunoAtivo.desconto_ganho;
    const totalLiquido = dadosAlunoAtivo.total_necessario;
    const stockExistente = dadosAlunoAtivo.saldo_interno;

    // Injeta os valores base formatados nas labels nativas
    if (document.getElementById("f_servico")) document.getElementById("f_servico").innerText = custoBase.toFixed(2).replace(".", ",") + " AKZ";
    
    // Controla a exibição da linha do desconto VIP
    const linhaDesc = document.getElementById("linha_desconto_vip");
    const txtDesc = document.getElementById("txt_desc_vip");
    if (linhaDesc && txtDesc) {
        if (desconto > 0) {
            linhaDesc.style.display = "flex";
            txtDesc.innerText = "-" + desconto.toFixed(2).replace(".", ",") + " AKZ";
        } else {
            linhaDesc.style.display = "none";
        }
    }

    // Calcula e desconta automaticamente se o aluno já tiver Stock guardado na conta
    const linhaSaldoUsado = document.getElementById("linha_saldo_existing") || document.getElementById("linha_saldo_existente");
    const labelSaldoUsado = document.getElementById("f_saldo_usado");
    let saldoAbatido = 0;

    if (stockExistente > 0) {
        saldoAbatido = Math.min(stockExistente, totalLiquido);
        if (linhaSaldoUsado && labelSaldoUsado) {
            linhaSaldoUsado.style.display = "flex";
            labelSaldoUsado.innerText = "-" + saldoAbatido.toFixed(2).replace(".", ",") + " AKZ";
        }
    } else {
        if (linhaSaldoUsado) linhaSaldoUsado.style.display = "none";
    }

    // Define o valor final que deve ser pago em caixa na label
    const totalFinalCaixa = totalLiquido - saldoAbatido;
    if (document.getElementById("txt_total_liquido")) {
        document.getElementById("txt_total_liquido").innerText = totalFinalCaixa.toFixed(2).replace(".", ",") + " AKZ";
    }

    // Atualiza a matemática do troco baseado no input digitado
    const inputEntregue = document.getElementById("valor_entregue_input");
    if (inputEntregue) {
        calcularTrocoECredito(parseFloat(inputEntregue.value) || 0);
    }
}

// 💵 3. GERENCIADOR DE TROCO E CRÉDITO FUTURO (Protegido contra o Bug dos Triliões)
function calcularTrocoECredito(valorDigitado) {
    const entregue = parseFloat(valorDigitado) || 0;
    
    // Obtém o valor líquido correto subtraindo o stock já consumido
    const totalLiquido = dadosAlunoAtivo.total_necessario;
    const saldoAbatido = Math.min(dadosAlunoAtivo.saldo_interno, totalLiquido);
    const totalFinalCaixa = totalLiquido - saldoAbatido;

    let troco = 0;
    let creditoFuturo = 0;

    if (entregue > totalFinalCaixa) {
        // Se o operador preferir guardar o excedente em Stock para os meses seguintes
        creditoFuturo = entregue - totalFinalCaixa;
    }

    // Renderiza a linha de "Guardado em Stock"
    const linhaCredito = document.getElementById("linha_credito_futuro");
    const lblCredito = document.getElementById("lbl_credito_futuro");
    if (linhaCredito && lblCredito) {
        if (creditoFuturo > 0) {
            linhaCredito.style.display = "flex";
            lblCredito.innerText = "+" + creditoFuturo.toFixed(2).replace(".", ",") + " AKZ";
        } else {
            linhaCredito.style.display = "none";
        }
    }

    // Mantém o controle do campo Troco a Devolver zerado (já que o excedente acumula como Stock)
    const linhaTroco = document.getElementById("linha_troco_caixa");
    const lblTroco = document.getElementById("lbl_troco_caixa");
    if (linhaTroco && lblTroco) {
        linhaTroco.style.display = "none";
    }
}

function gerarFaturaDigital(event) {
    if (event) event.preventDefault();
    
    if (!nomeEstudanteAtivo) {
        alert("⚠️ Erro: Nenhum estudante selecionado para faturamento.");
        return;
    }

    // Captura segura de dados do ecrã
    const mesSelect = document.getElementById('mes_referencia');
    const mes = mesSelect ? mesSelect.value : "Janeiro";
    const telefoneAluno = document.getElementById('telefone_input')?.value || "";
    const entregue = parseFloat(document.getElementById('valor_entregue_input')?.value) || 0;

    // 🌟 MATEMÁTICA PURA PROTEGIDA: Puxa os dados calculados diretamente do banco (Fim total dos triliões)
    const custoBase = dadosAlunoAtivo.custo_cadeiras;
    const totalDesconto = dadosAlunoAtivo.desconto_ganho;
    const totalLiquido = dadosAlunoAtivo.total_necessario;
    const stockExistente = dadosAlunoAtivo.saldo_interno;

    // Calcula se existia saldo para abater e o valor final que era exigido em caixa
    const saldoAbatido = Math.min(stockExistente, totalLiquido);
    const totalFinalCaixa = totalLiquido - saldoAbatido;

    if (entregue < totalFinalCaixa) {
        alert("Calma: Mantenha calma meu Amigo/a, o seu valor Monetário é inferior ao valor estipulado pelas Disciplinas/Cursos..., por Favor Pague o Valor certo, e.., aproveitando a situação: Minha Dica é: Se pretendes fazer um Pagamento adiantado de ( 1, 2, 3, ou mais Meses), para que os próximo meses não tenhas que pagar novamente, podes ir em frente pois o sistema desconta e lhe mostra na tela todo dinheiro adiantado, para que tenhas o controle de suas saídas de cada Pagamento mensal, SAUDAÇÕES .");
        return;
    }

    // 📅 GERAÇÃO DA DATA DE EMISSÃO DO RECIBO
    const dataAtual = new Date();
    const dia = String(dataAtual.getDate()).padStart(2, '0');
    const mesesAno = ["Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro"];
    const nomeMes = mesesAno[dataAtual.getMonth()];
    const ano = dataAtual.getFullYear();
    const dataFormatada = `${dia} de ${nomeMes} de ${ano}`;

    // 📋 INJEÇÃO DE DADOS NO BLOCO IMPERIAL DO PORTAL
    if (document.getElementById('rec_nome')) document.getElementById('rec_nome').innerText = nomeEstudanteAtivo;
    if (document.getElementById('rec_tel')) document.getElementById('rec_tel').innerText = telefoneAluno;
    if (document.getElementById('rec_mes')) document.getElementById('rec_mes').innerText = mes;
    if (document.getElementById('rec_data')) document.getElementById('rec_data').innerText = dataFormatada;
    if (document.getElementById('rec_turma')) document.getElementById('rec_turma').innerText = dadosAlunoAtivo.turma || "Turma Única A";

    // Mostra a contagem exata e real de disciplinas
    const qtdRealDisciplinas = dadosAlunoAtivo.disciplinas_reais ? dadosAlunoAtivo.disciplinas_reais.length : 0;
    if (document.getElementById('rec_qtd')) document.getElementById('rec_qtd').innerText = `${qtdRealDisciplinas} Disciplina(s)`;
    if (document.getElementById('rec_custo')) document.getElementById('rec_custo').innerText = custoBase.toFixed(2).replace(".", ",") + " AKZ";

    // 📚 SINCRONIZAÇÃO DAS DISCIPLINAS NO CORPO DO RECIBO DE IMPRESSÃO
    const containerDetalheRecibo = document.getElementById("rec_detalhe_lista_cadeiras");
    if (containerDetalheRecibo && dadosAlunoAtivo.disciplinas_reais) {
        containerDetalheRecibo.innerHTML = "<b>Discriminação dos Módulos:</b><br>";
        dadosAlunoAtivo.disciplinas_reais.forEach(disc => {
            const precoFmt = parseFloat(disc.preco).toFixed(2).replace(".", ",") + " AKZ";
            containerDetalheRecibo.innerHTML += `
                <div style="display:flex; justify-content:space-between; font-size:12px; padding:2px 0; border-bottom:1px dashed rgba(0,0,0,0.1); color:#000;">
                    <span>• ${disc.nome}</span>
                    <span>${precoFmt}</span>
                </div>
            `;
        });
    }

    // Linha visual do Desconto
    if (totalDesconto > 0) {
        if (document.getElementById('rec_linha_desc')) document.getElementById('rec_linha_desc').style.display = 'flex';
        if (document.getElementById('rec_desc')) document.getElementById('rec_desc').innerText = "-" + totalDesconto.toFixed(2).replace(".", ",") + " AKZ";
    } else { 
        if (document.getElementById('rec_linha_desc')) document.getElementById('rec_linha_desc').style.display = 'none'; 
    }

    // Linha visual de Saldo Abatido (Stock consumido)
    if (saldoAbatido > 0) {
        if (document.getElementById('rec_linha_saldo_usado')) document.getElementById('rec_linha_saldo_usado').style.display = 'flex';
        if (document.getElementById('rec_saldo_usado')) document.getElementById('rec_saldo_usado').innerText = "-" + saldoAbatido.toFixed(2).replace(".", ",") + " AKZ";
    } else {
        if (document.getElementById('rec_linha_saldo_usado')) document.getElementById('rec_linha_saldo_usado').style.display = 'none';
    }

    // Linha de Crédito Adiantado / Sobra de Stock
    const sobraStock = entregue - totalFinalCaixa;
    if (sobraStock > 0) {
        if (document.getElementById('rec_linha_stock')) document.getElementById('rec_linha_stock').style.display = 'flex';
        if (document.getElementById('rec_stock')) document.getElementById('rec_stock').innerText = '+' + sobraStock.toFixed(2).replace(".", ",") + " AKZ";
    } else { 
        if (document.getElementById('rec_linha_stock')) document.getElementById('rec_linha_stock').style.display = 'none'; 
    }

    if (document.getElementById('rec_total')) document.getElementById('rec_total').innerText = entregue.toFixed(2).replace(".", ",") + " AKZ";

    // 🚀 ENVIO SEGURO CENTRALIZADO PARA O BACKEND PHP
    fetch("unitel.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/json; charset=utf-8"
        },
        body: JSON.stringify({
            acao_financeira: 'registar_pagamento',
            id_utilizador: dadosAlunoAtivo.id_utilizador,
            telefone: telefoneAluno,
            valor_pago: entregue,
            mes_pago: mes,
            saldo_abatido: saldoAbatido,
            credito_guardado: sobraStock > 0 ? sobraStock : 0
        })
    })
    .then(res => {
        if (!res.ok) throw new Error("A porta de rede do Render rejeitou a resposta.");
        return res.json();
    })
    .then(resposta => {
        if (resposta.sucesso) {
            console.log("🎉 Sincronização concluída no MySQL central via Render.");
            
            // Ativa visualmente o bloco da fatura e desce com scroll suave
            const blocoFatura = document.getElementById('bloco_fatura_recibo');
            if (blocoFatura) {
                blocoFatura.style.display = 'block';
                window.scrollTo({ top: document.body.scrollHeight, behavior: 'smooth' });
            }
        } else {
            alert("Informação do Caixa: " + resposta.mensagem);
        }
    })
    .catch(err => {
        console.error("Erro capturado:", err);
        // Exibe o recibo mesmo com erro de rede local para não travar o operador
        const blocoFatura = document.getElementById('bloco_fatura_recibo');
        if (blocoFatura) blocoFatura.style.display = 'block';
        alert("⚠️ O recibo foi montado no ecrã com sucesso, mas os dados não puderam ser transmitidos de imediato para o servidor central.");
    });
}
</script>
</body>
</html>