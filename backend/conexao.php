<?php
// Polyfill para compatibilidade com PHP < 5.5 (como o USBWebserver rodando PHP 5.4.17)
if (!function_exists('password_hash')) {
    if (!defined('PASSWORD_DEFAULT')) {
        define('PASSWORD_DEFAULT', 1);
    }
    function password_hash($password, $algo, array $options = array()) {
        $cost = isset($options['cost']) ? $options['cost'] : 10;
        // Gera um sal de 22 caracteres válidos para o bcrypt
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

// Configurações do Banco de Dados - Testando as possibilidades (XAMPP e USBWebserver)
$possibilidades = [
    // 1. Tentar padrão XAMPP
    ['host' => '127.0.0.1', 'port' => '3306', 'user' => 'root', 'password' => ''],
    // 2. Tentar padrão USBWebserver (caso o usuário use este)
    ['host' => '127.0.0.1', 'port' => '3307', 'user' => 'root', 'password' => 'usbw'],
    // 3. Tentar porta MariaDB alternativa XAMPP
    ['host' => '127.0.0.1', 'port' => '3308', 'user' => 'root', 'password' => '']
];

$dbname = 'aethos_db';
$pdo = null;
$last_error = '';

foreach ($possibilidades as $cfg) {
    try {
        $dsn = "mysql:host={$cfg['host']};port={$cfg['port']};dbname={$dbname};charset=utf8mb4";
        $pdo = new PDO($dsn, $cfg['user'], $cfg['password']);
        
        // Mostrando os erros caso haja problema
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        
        // Conexão bem sucedida, sai do loop
        break;
    } catch (PDOException $e) {
        $last_error = $e->getMessage();
        $pdo = null;
        // Continua tentando o próximo
    }
}

if (!$pdo) {
    // Registra o erro técnico apenas nos logs do servidor
    error_log('[Aethos] Erro crítico de conexão PDO (todas as portas falharam): ' . $last_error);

    // Caso dê ruim na conexão, devolve erro genérico em JSON
    header('Content-Type: application/json');
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Erro crítico: Falha ao conectar com o banco de dados. Verifique se o XAMPP está ligado (MySQL/MariaDB).'
    ]);
    exit;
}
?>
