<?php
$host = '127.0.0.1';
$port = '3307';
$user = 'root';
$password = 'usbw';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;charset=utf8mb4", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create DB
    $pdo->exec("CREATE DATABASE IF NOT EXISTS aethos_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE aethos_db");
    
    // Load SQL file
    $sqlFile = __DIR__ . '/database.sql';
    if (!file_exists($sqlFile)) {
        die("Arquivo database.sql não encontrado em " . $sqlFile);
    }
    
    $sql = file_get_contents($sqlFile);
    // Remove comments to avoid parsing issues with some basic PDO exec runs
    // Actually PDO exec can handle multiple statements if emulate prepares is on
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, 1);
    
    $pdo->exec($sql);
    echo "Banco de dados importado com sucesso!";
    
} catch (PDOException $e) {
    echo "Erro PDO: " . $e->getMessage();
} catch (Exception $e) {
    echo "Erro Geral: " . $e->getMessage();
}
?>
