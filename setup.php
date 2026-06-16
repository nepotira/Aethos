<?php
// Polyfill para compatibilidade com PHP < 5.5
if (!function_exists('password_hash')) {
    if (!defined('PASSWORD_DEFAULT')) {
        define('PASSWORD_DEFAULT', 1);
    }
    function password_hash($password, $algo, array $options = array()) {
        $cost = isset($options['cost']) ? $options['cost'] : 10;
        $salt = "";
        $chars = "./ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
        for ($i = 0; $i < 22; $i++) {
            $salt .= $chars[mt_rand(0, 63)];
        }
        $formattedSalt = sprintf("$2y$%02d$%s", $cost, $salt);
        return crypt($password, $formattedSalt);
    }
}

if (!function_exists('password_verify')) {
    function password_verify($password, $hash) {
        if (strlen($hash) < 60) {
            return false;
        }
        $testHash = crypt($password, $hash);
        $status = 0;
        $len = min(strlen($testHash), strlen($hash));
        for ($i = 0; $i < $len; $i++) {
            $status |= (ord($testHash[$i]) ^ ord($hash[$i]));
        }
        $status |= (strlen($testHash) ^ strlen($hash));
        return $status === 0;
    }
}

// ============================================================
// AETHOS — SETUP / INSTALADOR DO BANCO DE DADOS
// Acesse: http://localhost:8082/aethos/setup.php
//
// ⚠️  SEGURANÇA: Apague este arquivo após a instalação!
// ============================================================

$host     = '127.0.0.1';
$port     = '3307';
$dbname   = 'aethos_db';
$user     = 'root';
$password = 'usbw';

// Senha 'senha123' pré-hashada em BCRYPT
define('HASH_PADRAO', '$2y$10$06S4p.2rQ6t6Q7J9K1xL$.D24wPon9yYJDJ64CI7rLeAYYSxEvSjG');

$steps   = [];
$success = true;
$already = false;

// ── Executar apenas se o botão foi acionado ──────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_setup'])) {

    // ── PASSO 1: Conectar ao MySQL (sem banco ainda) ──────────
    try {
        $pdo = new PDO(
            "mysql:host=$host;port=$port;charset=utf8mb4",
            $user, $password,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        $steps[] = ['ok', 'Conexão com MySQL (porta ' . $port . ') estabelecida com sucesso.'];
    } catch (PDOException $e) {
        $steps[]  = ['err', 'Falha ao conectar ao MySQL: ' . $e->getMessage()];
        $success  = false;
        goto render;
    }

    // ── PASSO 2: Criar o banco de dados ───────────────────────
    try {
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname`
                    CHARACTER SET utf8mb4
                    COLLATE utf8mb4_unicode_ci");
        $steps[] = ['ok', "Banco de dados <strong>$dbname</strong> criado (ou já existia)."];
        $pdo->exec("USE `$dbname`");
    } catch (PDOException $e) {
        $steps[]  = ['err', 'Falha ao criar banco: ' . $e->getMessage()];
        $success  = false;
        goto render;
    }

    // ── PASSO 3 removido: O database.sql já usa IF NOT EXISTS, o que torna seguro re-executar sem pular as outras tabelas.

    // ── PASSO 4: Criar Todas as Tabelas (via database.sql) ────────
    try {
        $sql = file_get_contents(__DIR__ . '/backend/database.sql');
        // Remover comandos de CREATE DATABASE e USE, pois já os fizemos no Passo 2
        $sql = preg_replace('/CREATE DATABASE[^;]+;/i', '', $sql);
        $sql = preg_replace('/USE [^;]+;/i', '', $sql);
        
        $pdo->exec($sql);
        $steps[] = ['ok', 'Todas as 5 tabelas (usuarios, locais_esportivos, avaliacoes, logs_sistema, tokens) criadas com sucesso a partir de database.sql!'];
    } catch (PDOException $e) {
        $steps[]  = ['err', 'Falha ao criar as tabelas: ' . $e->getMessage()];
        $success  = false;
        goto render;
    }

    // ── PASSO 5: Seed — Desenvolvedores ──────────────────────
    $devs = [
        ['Lorrany', 'lorrany@aethos.dev'],
        ['Arthur',  'arthur@aethos.dev'],
        ['Nepo',    'nepo@aethos.dev'],
        ['Leo',     'leo@aethos.dev'],
        ['Joaquim', 'joaquim@aethos.dev'],
    ];
    try {
        $stmt = $pdo->prepare("
            INSERT INTO usuarios (tipo_usuario, nome, email, senha, primeiro_acesso)
            VALUES ('desenvolvedor', :nome, :email, :senha, 1)
        ");
        foreach ($devs as $dev) {
            $nome = $dev[0];
            $email = $dev[1];
            $stmt->execute([':nome' => $nome, ':email' => $email, ':senha' => HASH_PADRAO]);
        }
        $devNames = [];
        foreach ($devs as $dev) {
            $devNames[] = $dev[0];
        }
        $nomes = implode(', ', $devNames);
        $steps[] = ['ok', "Desenvolvedores inseridos: <strong>$nomes</strong> (senha padrão: <code>senha123</code>)."];
    } catch (PDOException $e) {
        $steps[]  = ['err', 'Falha ao inserir desenvolvedores: ' . $e->getMessage()];
        $success  = false;
        goto render;
    }

    // ── PASSO 6: Seed — Administrador ────────────────────────
    try {
        $pdo->prepare("
            INSERT INTO usuarios (tipo_usuario, nome, email, senha, primeiro_acesso)
            VALUES ('admin', 'Admin Geral', 'admin@aethos.com', :senha, 1)
        ")->execute([':senha' => HASH_PADRAO]);
        $steps[] = ['ok', 'Administrador inserido: <strong>admin@aethos.com</strong> (senha padrão: <code>senha123</code>).'];
    } catch (PDOException $e) {
        $steps[]  = ['err', 'Falha ao inserir administrador: ' . $e->getMessage()];
        $success  = false;
        goto render;
    }

    $steps[] = ['ok', '🎉 <strong>Instalação concluída!</strong> O sistema Aethos está pronto para uso.'];
}

render:
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aethos — Setup do Banco de Dados</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --navy:   #0D0F32;
            --lavender: #A5B4FC;
            --white:  #FFFFFF;
            --green:  #4ADE80;
            --red:    #F87171;
            --yellow: #FBBF24;
            --card-bg: rgba(255,255,255,0.05);
            --border:  rgba(165,180,252,0.15);
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--navy);
            color: var(--white);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
            background-image:
                radial-gradient(ellipse 80% 60% at 50% -20%, rgba(165,180,252,0.18) 0%, transparent 70%),
                radial-gradient(ellipse 50% 40% at 90% 110%, rgba(99,102,241,0.12) 0%, transparent 60%);
        }

        .container {
            width: 100%;
            max-width: 640px;
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        /* ── Header ────────────────────────────────── */
        .header {
            text-align: center;
        }
        .logo-row {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            margin-bottom: 0.5rem;
        }
        .logo-badge {
            width: 44px; height: 44px;
            border-radius: 12px;
            background: linear-gradient(135deg, #6366f1, #a5b4fc);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.4rem; font-weight: 800;
            box-shadow: 0 0 24px rgba(165,180,252,0.4);
        }
        .logo-text {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 1.7rem;
            font-weight: 800;
            letter-spacing: -0.03em;
            background: linear-gradient(90deg, #fff 30%, #a5b4fc);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .header p {
            color: rgba(255,255,255,0.5);
            font-size: 0.9rem;
            margin-top: 0.25rem;
        }

        /* ── Card ───────────────────────────────────── */
        .card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 2rem;
            backdrop-filter: blur(16px);
        }

        .card h2 {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--lavender);
            margin-bottom: 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* ── Info grid ──────────────────────────────── */
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
        }
        .info-item {
            background: rgba(255,255,255,0.04);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 0.75rem 1rem;
        }
        .info-item .label {
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: rgba(255,255,255,0.4);
            margin-bottom: 0.25rem;
        }
        .info-item .value {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-weight: 600;
            font-size: 0.95rem;
            color: var(--lavender);
        }

        /* ── Button ─────────────────────────────────── */
        .btn {
            width: 100%;
            padding: 0.9rem 1.5rem;
            border-radius: 12px;
            border: none;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        .btn-primary {
            background: linear-gradient(135deg, #6366f1, #818cf8);
            color: #fff;
            box-shadow: 0 4px 20px rgba(99,102,241,0.35);
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 28px rgba(99,102,241,0.5);
        }
        .btn-primary:active { transform: translateY(0); }
        .btn-primary:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }

        /* ── Steps log ──────────────────────────────── */
        .steps {
            display: flex;
            flex-direction: column;
            gap: 0.6rem;
        }
        .step {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            padding: 0.8rem 1rem;
            border-radius: 10px;
            font-size: 0.88rem;
            line-height: 1.5;
            animation: fadeSlide 0.35s ease both;
        }
        .step.ok   { background: rgba(74,222,128,0.1);  border: 1px solid rgba(74,222,128,0.25);  color: #d1fae5; }
        .step.err  { background: rgba(248,113,113,0.1); border: 1px solid rgba(248,113,113,0.25); color: #fee2e2; }
        .step.warn { background: rgba(251,191,36,0.1);  border: 1px solid rgba(251,191,36,0.25);  color: #fef3c7; }
        .step-icon { font-size: 1.1rem; flex-shrink: 0; margin-top: 0.05rem; }
        .step code {
            background: rgba(255,255,255,0.12);
            border-radius: 4px;
            padding: 0 5px;
            font-family: monospace;
            font-size: 0.85em;
        }

        @keyframes fadeSlide {
            from { opacity:0; transform: translateY(6px); }
            to   { opacity:1; transform: translateY(0); }
        }

        /* ── Final banner ───────────────────────────── */
        .banner {
            border-radius: 16px;
            padding: 1.25rem 1.5rem;
            text-align: center;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-weight: 700;
            font-size: 1rem;
        }
        .banner.success {
            background: linear-gradient(135deg, rgba(74,222,128,0.15), rgba(52,211,153,0.1));
            border: 1px solid rgba(74,222,128,0.35);
            color: #6ee7b7;
        }
        .banner.fail {
            background: rgba(248,113,113,0.1);
            border: 1px solid rgba(248,113,113,0.35);
            color: #fca5a5;
        }
        .banner.already {
            background: rgba(251,191,36,0.1);
            border: 1px solid rgba(251,191,36,0.3);
            color: #fde68a;
        }

        /* ── Action links ───────────────────────────── */
        .actions {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }
        .link-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.8rem 1.5rem;
            border-radius: 12px;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-weight: 600;
            font-size: 0.95rem;
            text-decoration: none;
            transition: all 0.2s;
            border: 1px solid var(--border);
            color: var(--white);
            background: rgba(255,255,255,0.06);
        }
        .link-btn:hover {
            background: rgba(165,180,252,0.15);
            border-color: var(--lavender);
            transform: translateY(-1px);
        }
        .link-btn.main {
            background: linear-gradient(135deg, rgba(99,102,241,0.3), rgba(129,140,248,0.2));
            border-color: rgba(165,180,252,0.4);
        }

        /* ── Warning footer ─────────────────────────── */
        .warn-footer {
            background: rgba(251,191,36,0.08);
            border: 1px solid rgba(251,191,36,0.25);
            border-radius: 12px;
            padding: 1rem 1.25rem;
            font-size: 0.82rem;
            color: #fde68a;
            display: flex;
            gap: 0.5rem;
            align-items: flex-start;
        }
    </style>
</head>
<body>
<div class="container">

    <!-- Header -->
    <div class="header">
        <div class="logo-row">
            <div class="logo-badge">æ</div>
            <span class="logo-text">Aethos</span>
        </div>
        <p>Setup · Instalação do Banco de Dados</p>
    </div>

    <?php if (empty($steps)): ?>
    <!-- ── TELA INICIAL (formulário) ───────────────── -->
    <div class="card">
        <h2>⚙️ Configuração de Conexão</h2>
        <div class="info-grid">
            <div class="info-item">
                <div class="label">Host</div>
                <div class="value"><?= htmlspecialchars($host) ?></div>
            </div>
            <div class="info-item">
                <div class="label">Porta MySQL</div>
                <div class="value"><?= htmlspecialchars($port) ?></div>
            </div>
            <div class="info-item">
                <div class="label">Banco de Dados</div>
                <div class="value"><?= htmlspecialchars($dbname) ?></div>
            </div>
            <div class="info-item">
                <div class="label">Usuário</div>
                <div class="value"><?= htmlspecialchars($user) ?></div>
            </div>
        </div>

        <form method="POST">
            <button type="submit" name="run_setup" class="btn btn-primary" id="runBtn"
                    onclick="this.disabled=true; this.innerHTML='⏳ Instalando...'; this.form.submit();">
                🚀 Criar Banco e Tabelas Agora
            </button>
        </form>
    </div>

    <div class="card">
        <h2>📋 O que será instalado</h2>
        <div class="steps">
            <div class="step ok"><span class="step-icon">1</span> Criar banco de dados <strong>aethos_db</strong> (utf8mb4)</div>
            <div class="step ok"><span class="step-icon">2</span> Criar tabela <strong>usuarios</strong> com todos os campos (polimórfica)</div>
            <div class="step ok"><span class="step-icon">3</span> Inserir 5 desenvolvedores: Lorrany, Arthur, Nepo, Leo, Joaquim</div>
            <div class="step ok"><span class="step-icon">4</span> Inserir administrador padrão (admin@aethos.com)</div>
        </div>
    </div>

    <?php else: ?>
    <!-- ── TELA DE RESULTADO ────────────────────────── -->
    <div class="card">
        <h2>📋 Log de Instalação</h2>
        <div class="steps">
            <?php foreach ($steps as $step): 
                $type = $step[0];
                $msg  = $step[1];
            ?>
                <div class="step <?= $type ?>">
                    <span class="step-icon">
                        <?= $type === 'ok' ? '✅' : ($type === 'warn' ? '⚠️' : '❌') ?>
                    </span>
                    <span><?= $msg ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if ($already): ?>
        <div class="banner already">
            ⚠️ O banco já estava configurado. Nenhuma alteração foi feita.
        </div>
    <?php elseif ($success): ?>
        <div class="banner success">
            ✅ Banco de dados instalado com sucesso! O Aethos está pronto.
        </div>
    <?php else: ?>
        <div class="banner fail">
            ❌ A instalação encontrou erros. Verifique o log acima e tente novamente.
        </div>
    <?php endif; ?>

    <!-- Links de navegação -->
    <?php if ($success || $already): ?>
    <div class="card actions">
        <h2>🔗 Próximos Passos</h2>
        <a href="index.html" class="link-btn main">
            🗺️ Abrir o Sistema Aethos
        </a>
        <a href="login.html" class="link-btn">
            🔐 Ir para o Login
        </a>
        <a href="backend/database.sql" class="link-btn" download>
            💾 Baixar database.sql
        </a>
    </div>
    <?php endif; ?>

    <!-- Se não houve erro, opção de reexecutar -->
    <?php if (!$success): ?>
    <form method="POST">
        <button type="submit" name="run_setup" class="btn btn-primary">
            🔁 Tentar Novamente
        </button>
    </form>
    <?php endif; ?>
    <?php endif; ?>

    <!-- Aviso de segurança (sempre visível) -->
    <div class="warn-footer">
        <span>⚠️</span>
        <span><strong>Segurança:</strong> Apague o arquivo <code>setup.php</code> após a instalação para não deixar uma página de administração exposta.</span>
    </div>

</div>
</body>
</html>
