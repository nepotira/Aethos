<?php
try {
    $pdo = new PDO('mysql:host=127.0.0.1;port=3307;dbname=aethos_db;charset=utf8mb4', 'root', 'usbw');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("ALTER TABLE usuarios ADD COLUMN email_verificado BOOLEAN DEFAULT FALSE AFTER ativo");
    $pdo->exec("ALTER TABLE usuarios ADD COLUMN codigo_verificacao VARCHAR(6) DEFAULT NULL AFTER email_verificado");
    $pdo->exec("UPDATE usuarios SET email_verificado = 1 WHERE tipo_usuario IN ('admin', 'desenvolvedor')");
    echo "Tabela usuarios atualizada com sucesso com as novas colunas.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
