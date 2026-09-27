<?php
// 🔐 SISTEMA DE RECUPERAÇÃO DE ACESSO - ACADEMIA AURÉLIUS
if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}

// Conecta à base de dados dinâmica (Localhost ou Render)
require_once __DIR__ . "/conexao.php";
$mensagem = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = htmlspecialchars(trim($_POST['email']), ENT_QUOTES, 'UTF-8');
    $id_escolar = htmlspecialchars(trim($_POST['id_unico']), ENT_QUOTES, 'UTF-8');
    $nova_senha = trim($_POST['nova_senha']);
    
    if (!empty($email) && !empty($id_escolar) && !empty($nova_senha)) {
        try {
            // Verifica se o E-mail e o ID Único pertencem ao mesmo estudante na tabela 'utilizadores'
            $stmt = $pdo->prepare("SELECT id_utilizador FROM utilizadores WHERE email = ? AND (id_utilizador = ? OR telefone = ?) LIMIT 1");
            $stmt->execute([$email, $id_escolar, $id_escolar]);
            $user = $stmt->fetch();
            
            if ($user) {
                // Encriptação segura alinhada com os padrões do PHP 8.2 (Substitui o antigo MD5)
                $nova_senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
                
                $update = $pdo->prepare("UPDATE utilizadores SET senha = ? WHERE id_utilizador = ?");
                $update->execute([$nova_senha_hash, $user['id_utilizador']]);
                
                $mensagem = "<div style='background: rgba(74, 222, 128, 0.15); color: #4ade80; padding: 12px; border-radius: 6px; border: 1px solid rgba(74, 222, 128, 0.3); margin-bottom: 15px; text-align: center; font-size: 14px;'>✅ Senha redefinida com sucesso! Já pode fazer login.</div>";
            } else {
                $mensagem = "<div style='background: rgba(239, 68, 68, 0.15); color: #f87171; padding: 12px; border-radius: 6px; border: 1px solid rgba(239, 68, 68, 0.3); margin-bottom: 15px; text-align: center; font-size: 14px;'>❌ Os dados introduzidos não coincidem com nenhum registo.</div>";
            }
        } catch (PDOException $e) {
            $mensagem = "<div style='background: rgba(239, 68, 68, 0.15); color: #f87171; padding: 12px; border-radius: 6px; margin-bottom: 15px; text-align: center;'>Erro: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    } else {
        $mensagem = "<div style='background: rgba(239, 68, 68, 0.15); color: #f87171; padding: 12px; border-radius: 6px; margin-bottom: 15px; text-align: center;'>Por favor, preencha todos os campos.</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#060b19">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Acesso - Academia Aurélius</title>
    <style>
        :root {
            --bg-principal: #060b19;
            --bg-card: #0d1527;
            --texto: #f8fafc;
            --ouro: #eab308;
            --borda: #1e293b;
        }
        body { 
            background: var(--bg-principal); 
            color: var(--texto); 
            font-family: 'Segoe UI', system-ui, sans-serif; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            min-height: 100vh; 
            margin: 0; 
        }
        .card { 
            background: var(--bg-card); 
            padding: 30px; 
            border-radius: 12px; 
            width: 100%; 
            max-width: 380px; 
            box-shadow: 0 8px 24px rgba(0,0,0,0.5); 
            border-top: 4px solid var(--ouro);
            box-sizing: border-box;
        }
        label {
            font-size: 12px; 
            color: #94a3b8; 
            font-weight: 600;
            display: block;
            margin-bottom: 6px;
        }
        input { 
            width: 100%; 
            padding: 12px; 
            margin-bottom: 18px; 
            background: var(--bg-principal); 
            border: 1px solid var(--borda); 
            color: #fff; 
            border-radius: 6px; 
            box-sizing: border-box; 
            font-size: 14px;
        }
        input:focus {
            border-color: var(--ouro);
            outline: none;
        }
        .btn { 
            width: 100%; 
            padding: 13px; 
            background: var(--ouro); 
            border: none; 
            color: #000; 
            font-weight: bold; 
            border-radius: 6px; 
            cursor: pointer; 
            text-transform: uppercase; 
            font-size: 14px;
            transition: background 0.2s;
        }
        .btn:hover { background: #facc15; }
        .link-voltar { color: var(--ouro); text-decoration: none; font-size: 13px; font-weight: 600; }
        .link-voltar:hover { text-decoration: underline; }
    </style>
</head>
<body>

    <div class="card">
        <h2 style="color: var(--ouro); margin-top: 0; font-weight: 900; font-size: 22px;">🔑 Recuperar Senha</h2>
        <p style="font-size: 13px; color: #94a3b8; margin-bottom: 25px;">Confirme os seus dados oficiais para redefinir a sua credencial local.</p>
        
        <!-- Exibe mensagens de sucesso ou erro do PHP -->
        <?= $mensagem ?>
        
        <form method="POST" action="">
            <label>O seu E-mail Registado:</label>
            <input type="email" name="email" required placeholder="exemplo@escola.com">
            
            <label>O seu ID Único ou Nº Telefone:</label>
            <input type="text" name="id_unico" required placeholder="Ex: 925347372">
            
            <label>Nova Senha Secreta:</label>
            <input type="password" name="nova_senha" required placeholder="••••••••">
            
            <button type="submit" class="btn">Atualizar Senha ➔</button>
        </form>
        
        <p style="text-align: center; margin-top: 20px; margin-bottom: 0;">
            <a href="estudante.html" class="link-voltar">← Voltar ao Portal</a>
        </p>
    </div>

    <!-- Registo do Service Worker PWA -->
    <script>
        if ('serviceWorker' in navigator) {
          window.addEventListener('load', () => {
            navigator.serviceWorker.register('sw.js');
          });
        }
    </script>
</body>
</html>