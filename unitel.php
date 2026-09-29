<?php
// 🗄️ ENGINE DE FATURAÇÃO ACADÉMICA - ACADEMIA AURÉLIUS
ini_set('display_errors', 0); 
error_reporting(E_ALL);
header('Content-Type: text/html; charset=utf-8');

require_once 'conexao.php'; // Conexão oficial com o MySQL

// 📱 ENDPOINT DE PESQUISA ASSÍNCRONA (AJAX)
if (isset($_GET['pesquisa_automatica_cliente'])) {
    header('Content-Type: application/json; charset=utf-8');
    $termo = isset($_GET['termo']) ? trim($_GET['termo']) : '';
    
    $resposta = ['status' => 'nao_encontrado'];

    if (!empty($termo)) {
        try {
            // Busca o aluno mapeando a coluna 'senha' como categoria 'estudante'
            $query = "SELECT id_utilizador, nome, telefone, saldo_propina FROM utilizadores WHERE (nome LIKE ? OR telefone = ? OR id_unico_escolar = ?) AND senha = 'estudante' LIMIT 1";
            $stmt = $pdo->prepare($query);
            $stmt->execute(["%$termo%", $termo, $termo]);
            $aluno = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($aluno) {
                $saldoOuDivida = floatval($aluno['saldo_propina']);
                $resposta = [
                    'status' => 'encontrado',
                    'id_utilizador' => $aluno['id_utilizador'],
                    'nome' => $aluno['nome'],
                    'telefone' => $aluno['telefone'],
                    // Se o saldo for negativo na sua tabela, tratamos como dívida, se positivo como stock
                    'divida' => ($saldoOuDivida < 0) ? abs($saldoOuDivida) : 0,
                    'saldo_interno' => ($saldoOuDivida > 0) ? $saldoOuDivida : 0
                ];
            }
        } catch (Exception $e) {
            $resposta = ['status' => 'erro', 'mensagem' => $e->getMessage()];
        }
    }
    echo json_encode($resposta);
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
            <div class="logo-box">🏫 AURE<span>LIUS</span></div>
            <p style="color: var(--text-muted); font-size: 13px;">Módulo Financeiro: Emissão de Propinas & Mensalidades</p>
        </div>

        <!-- Painéis Informativos Automatizados (Leitura Direta do Banco) -->
        <div id="msg_status_aluno" class="alerta-box"></div>
        <div id="msg_alerta_stock" class="alerta-box"></div>

        <form id="formFaturamento" onsubmit="gerarFaturaDigital(event)">
            <div class="form-group">
                <label>Pesquisar Aluno (Nome, Nº Telefone ou ID AUR):</label>
                <input type="text" id="pesquisa_aluno" class="form-input" placeholder="Digite o nome ou AUR- ..." oninput="buscarAlunoSincronizado(this.value)" required>
            </div>

            <div id="bloco_faturamento_oculto" style="display: none;">
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
                            <option value="Janeiro">Janeiro</option> <option value="Fevereiro">Fevereiro</option>
                            <option value="Março">Março</option> <option value="Abril">Abril</option>
                            <option value="Maio">Maio</option> <option value="Junho">Junho</option>
                            <option value="Julho">Julho</option> <option value="Agosto">Agosto</option>
                            <option value="Setembro">Setembro</option> <option value="Outubro">Outubro</option>
                            <option value="Novembro">Novembro</option> <option value="Dezembro">Dezembro</option>
                        </select>
                    </div>
                </div>

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

                <!-- Quadro Resumo Base das Contas -->
                <div class="resumo-fatura">
                    <div class="resumo-linha"><span>Preço Total das Cadeiras:</span><span id="f_servico">0,00 AKZ</span></div>
                    <div class="resumo-linha" id="linha_divida" style="color: var(--brand-danger);"><span>Dívidas Acumuladas no Banco:</span><span id="f_divida">0,00 AKZ</span></div>
                    <div class="resumo-linha" id="linha_desconto_vip" style="color: var(--brand-success); display: none;"><span>Desconto Cortesia (Apenas 4+ Cadeiras):</span><span id="txt_desc_vip">-0,00 AKZ</span></div>
                    <div class="resumo-linha" id="linha_saldo_existente" style="color: #38bdf8; display: none;"><span>Saldo Abatido Automaticamente:</span><span id="f_saldo_usado">-0,00 AKZ</span></div>
                    <div class="resumo-linha" id="linha_troco_caixa" style="color: var(--brand-gold); display: none;"><span>Troco Físico a Devolver:</span><span id="lbl_troco_caixa">0,00 AKZ</span></div>
                    <div class="resumo-linha" id="linha_credito_futuro" style="color: #a855f7; display: none;"><span>Guardado em Stock (Adiantado):</span><span id="lbl_credito_futuro">0,00 AKZ</span></div>
                    <div class="resumo-linha"><span>Total Líquido a Pagar no Caixa:</span><span id="txt_total_liquido">0,00 AKZ</span></div>
                </div>
                <button type="submit" class="btn-pay">Emitir Fatura & Confirmar Pagamento ➔</button>
            </div>
        </form>
    </div>

    <!-- 📋 BLOCO DA FATURA DE IMPRESSÃO IMPERIAL -->
    <div id="bloco_fatura_recibo">
        <h3>🏫 ACADEMIA AURÉLIUS</h3>
        <div style="text-align: center; margin-bottom: 12px; font-size: 11px;">Huambo - São Luís Catimba</div>
        <div class="recibo-linha"><span>Estudante:</span><b id="rec_nome">-</b></div>
        <div class="recibo-linha"><span>Contacto:</span><span id="rec_tel">-</span></div>
        <div class="recibo-linha"><span>Mês Pago:</span><span id="rec_mes">-</span></div>
        <div class="recibo-linha"><span>Disciplinas:</span><span id="rec_qtd">-</span></div>
        <div style="border-top: 1px dashed #000; margin: 8px 0;"></div>
        <div class="recibo-linha"><span>Custo Base:</span><span id="rec_custo">-</span></div>
        <div class="recibo-linha" id="rec_linha_desc" style="display:none;"><span>Desconto (20%):</span><span id="rec_desc">-</span></div>
        <div class="recibo-linha" id="rec_linha_divida" style="display:none;"><span>Atrasos Pagos:</span><span id="rec_divida">-</span></div>
        <div class="recibo-linha" id="rec_linha_stock" style="display:none;"><span>Stock Retido:</span><span id="rec_stock">-</span></div>
        <div class="recibo-total"><span>Total Pago:</span><span id="rec_total">-</span></div>
        <div style="text-align: center; margin-top: 15px; font-size: 10px; border-top: 2px dashed #000; padding-top: 10px;">
            Obrigado pela confiança.<br>Documento processado via Caixa.
        </div>
        <button onclick="window.print()" style="margin-top: 15px; width: 100%; padding: 5px; font-family: sans-serif; background: #000; color: #fff; border: none; cursor: pointer; font-size: 11px;">Imprimir Fatura 🖨️</button>
    </div>

    <!-- 🟢 ENGINE BANCÁRIO REATIVO -->
    <script>
    const PRECO_FIXO_DISCIPLINA = 5000; 
    let nomeEstudanteAtivo = "";
    
    let dadosAlunoAtivo = {
        divida: 0, saldo_interno: 0, custo_cadeiras: 0, total_necessario: 0, total_caixa: 0, desconto_ganho: 0
    };

    function buscarAlunoSincronizado(termo) {
        if (termo.trim().length >= 3) {
            fetch('unitel.php?pesquisa_automatica_cliente=1&termo=' + encodeURIComponent(termo).trim())
            .then(res => res.json())
            .then(data => {
                if (data.status === 'encontrado') {
                    document.getElementById('bloco_faturamento_oculto').style.display = 'block';
                    document.getElementById('telefone_input').value = data.telefone;
                    nomeEstudanteAtivo = data.nome;
                    
                    dadosAlunoAtivo.divida = parseFloat(data.divida) || 0;
                    dadosAlunoAtivo.saldo_interno = parseFloat(data.saldo_interno) || 0;

                    const statusBox = document.getElementById('msg_status_aluno');
                    statusBox.style.display = 'block';
                    
                    if (dadosAlunoAtivo.divida > 0) {
                        statusBox.style.background = 'rgba(239, 68, 68, 0.15)';
                        statusBox.style.color = '#f87171';
                        statusBox.style.borderColor = 'rgba(239, 68, 68, 0.3)';
                        statusBox.innerHTML = `⚠️ DÍVIDA ATIVA NO BANCO: Constam mensalidades em atraso de ${dadosAlunoAtivo.divida.toLocaleString('pt-PT')} AKZ!`;
                    } else {
                        statusBox.style.background = 'rgba(34, 197, 94, 0.15)';
                        statusBox.style.color = '#4ade80';
                        statusBox.style.borderColor = 'rgba(34, 197, 94, 0.3)';
                        statusBox.innerHTML = ` ALUNO REGULARIZADO: Dados de ${data.nome} carregados com sucesso .`;
                    }

                    const stockBox = document.getElementById('msg_alerta_stock');
                    if (dadosAlunoAtivo.saldo_interno > 0) {
                        stockBox.style.display = 'block';
                        stockBox.style.background = 'rgba(56, 189, 248, 0.15)';
                        stockBox.style.color = '#38bdf8';
                        stockBox.style.borderColor = 'rgba(56, 189, 248, 0.3)';
                        stockBox.innerHTML = `💰 STOCK DISPONÍVEL: Existe um valor adiantado de ${dadosAlunoAtivo.saldo_interno.toLocaleString('pt-PT')} AKZ guardado na conta.`;
                    } else {
                        stockBox.style.display = 'none';
                    }

                    recalcularFaturamentoEscolar();
                } else {
                    ocultarTelasFaturamento();
                }
            });
        } else {
            ocultarTelasFaturamento();
        }
    }

    function ocultarTelasFaturamento() {
        document.getElementById('bloco_faturamento_oculto').style.display = 'none';
        document.getElementById('msg_status_aluno').style.display = 'none';
        document.getElementById('msg_alerta_stock').style.display = 'none';
        document.getElementById('bloco_fatura_recibo').style.display = 'none';
    }

    function recalcularFaturamentoEscolar() {
        const qtd = parseInt(document.getElementById('qtd_disciplinas').value) || 1;
        dadosAlunoAtivo.custo_cadeiras = qtd * PRECO_FIXO_DISCIPLINA;
        
        // Desconto de 20% estritamente para 4 ou mais disciplinas
        dadosAlunoAtivo.desconto_ganho = (qtd >= 4) ? (dadosAlunoAtivo.custo_cadeiras * 0.2) : 0;
        
        const subtotalMes = dadosAlunoAtivo.custo_cadeiras - dadosAlunoAtivo.desconto_ganho;
        dadosAlunoAtivo.total_necessario = subtotalMes + dadosAlunoAtivo.divida;

        const saldoUtilizado = Math.min(dadosAlunoAtivo.saldo_interno, dadosAlunoAtivo.total_necessario);
        dadosAlunoAtivo.total_caixa = dadosAlunoAtivo.total_necessario - saldoUtilizado;

        document.getElementById('f_servico').innerText = dadosAlunoAtivo.custo_cadeiras.toLocaleString('pt-PT', {minimumFractionDigits: 2}) + ' AKZ';
        document.getElementById('f_divida').innerText = dadosAlunoAtivo.divida.toLocaleString('pt-PT', {minimumFractionDigits: 2}) + ' AKZ';
        document.getElementById('f_saldo_usado').innerText = '-' + saldoUtilizado.toLocaleString('pt-PT', {minimumFractionDigits: 2}) + ' AKZ';
        document.getElementById('txt_total_liquido').innerText = dadosAlunoAtivo.total_caixa.toLocaleString('pt-PT', {minimumFractionDigits: 2}) + ' AKZ';

        const linhaVip = document.getElementById('linha_desconto_vip');
        if (dadosAlunoAtivo.desconto_ganho > 0) {
            linhaVip.style.display = 'flex';
            document.getElementById('txt_desc_vip').innerText = '-' + dadosAlunoAtivo.desconto_ganho.toLocaleString('pt-PT', {minimumFractionDigits: 2}) + ' AKZ';
        } else {
            linhaVip.style.display = 'none';
        }

        document.getElementById('linha_divida').style.display = (dadosAlunoAtivo.divida > 0) ? 'flex' : 'none';
        document.getElementById('linha_saldo_existente').style.display = (saldoUtilizado > 0) ? 'flex' : 'none';

        const inputPago = document.getElementById('valor_entregue_input');
        inputPago.value = dadosAlunoAtivo.total_caixa;
        
        calcularTrocoECredito(dadosAlunoAtivo.total_caixa);
    }

    function calcularTrocoECredito(valor) {
        const entregue = parseFloat(valor) || 0;
        const diferenca = entregue - dadosAlunoAtivo.total_caixa;

        if (diferenca > 0) {
            document.getElementById('linha_troco_caixa').style.display = 'flex';
            document.getElementById('lbl_troco_caixa').innerText = diferenca.toLocaleString('pt-PT', {minimumFractionDigits: 2}) + ' AKZ';
            
            document.getElementById('linha_credito_futuro').style.display = 'flex';
            document.getElementById('lbl_credito_futuro').innerText = diferenca.toLocaleString('pt-PT', {minimumFractionDigits: 2}) + ' AKZ';
        } else {
            document.getElementById('linha_troco_caixa').style.display = 'none';
            document.getElementById('linha_credito_futuro').style.display = 'none';
        }
    }

    function gerarFaturaDigital(event) {
        event.preventDefault();
        const mes = document.getElementById('mes_referencia').value;
        const qtd = document.getElementById('qtd_disciplinas').value;
        const entregue = parseFloat(document.getElementById('valor_entregue_input').value) || 0;

        document.getElementById('rec_nome').innerText = nomeEstudanteAtivo;
        document.getElementById('rec_tel').innerText = document.getElementById('telefone_input').value;
        document.getElementById('rec_mes').innerText = mes;
        document.getElementById('rec_qtd').innerText = qtd + " Disciplina(s)";
        document.getElementById('rec_custo').innerText = dadosAlunoAtivo.custo_cadeiras.toLocaleString('pt-PT') + " AKZ";

        if(dadosAlunoAtivo.desconto_ganho > 0) {
            document.getElementById('rec_linha_desc').style.display = 'flex';
            document.getElementById('rec_desc').innerText = '-' + dadosAlunoAtivo.desconto_ganho.toLocaleString('pt-PT') + " AKZ";
        } else { document.getElementById('rec_linha_desc').style.display = 'none'; }

        if(dadosAlunoAtivo.divida > 0) {
            document.getElementById('rec_linha_divida').style.display = 'flex';
            document.getElementById('rec_divida').innerText = '+' + dadosAlunoAtivo.divida.toLocaleString('pt-PT') + " AKZ";
        } else { document.getElementById('rec_linha_divida').style.display = 'none'; }

        const sobraStock = entregue - dadosAlunoAtivo.total_caixa;
        if(sobraStock > 0) {
            document.getElementById('rec_linha_stock').style.display = 'flex';
            document.getElementById('rec_stock').innerText = '+' + sobraStock.toLocaleString('pt-PT') + " AKZ Retidos";
        } else { 
            document.getElementById('rec_linha_stock').style.display = 'none'; 
        }

        document.getElementById('rec_total').innerText = entregue.toLocaleString('pt-PT') + " AKZ";

        // Exibe a fatura estruturada no ecrã e faz scroll suave até ela
        document.getElementById('bloco_fatura_recibo').style.display = 'block';
        window.scrollTo({ top: document.body.scrollHeight, behavior: 'smooth' });
    }
    </script>
</body>
</html>