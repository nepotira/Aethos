<?php
// ============================================================
// AETHOS — config.php
// Configurações globais do sistema (SMTP, URLs, etc.)
// ⚠️  NÃO commitar com credenciais reais
// ============================================================

// --- Configurações SMTP (para envio de e-mail) ---
define('SMTP_HOST',      'smtp.gmail.com');
define('SMTP_PORT',      587);
define('SMTP_USER',      'seu_email@gmail.com');   // Altere para o e-mail da conta
define('SMTP_PASS',      'sua_senha_app');         // Use App Password do Google
define('SMTP_FROM_NAME', 'Aethos');

// --- URL base do sistema ---
define('BASE_URL', 'http://localhost:8082/aethos/');

// --- Configurações de upload ---
define('UPLOAD_MAX_SIZE', 2 * 1024 * 1024); // 2MB em bytes
define('UPLOAD_DIR_PERFIS', __DIR__ . '/../uploads/perfis/');
define('UPLOAD_DIR_LOCAIS', __DIR__ . '/../uploads/locais/');

// --- Tempo de expiração do token de redefinição de senha (em horas) ---
define('TOKEN_EXPIRACAO_HORAS', 1);
?>
