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

// Configurações do Banco de Dados
$host = '127.0.0.1';
$port = '3306';
$dbname = 'aethos_db';
$user = 'root'; 
$password = 'usbw'; 

try {
    // Definindo a conexão usando PDO
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $user, $password);
    
    // Mostrando os erros caso haja problema (Útil no ambiente de desenvolvimento)
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Registra o erro técnico apenas nos logs do servidor (não expõe ao cliente)
    error_log('[Aethos] Erro crítico de conexão PDO: ' . $e->getMessage());

    // Caso dê ruim na conexão, devolve erro genérico em JSON (sem expor detalhes internos)
    header('Content-Type: application/json');
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Erro crítico: Falha ao conectar com o banco de dados. Verifique o XAMPP e o script de banco.'
    ]);
    exit;
}
?>
