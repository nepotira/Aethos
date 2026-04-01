<?php
// Configurações do Banco de Dados
$host = 'localhost';
$dbname = 'aethos_db';
$user = 'root'; // Ajuste conforme seu usuário do MySQL, ex: root
$password = ''; // Ajuste conforme sua senha do MySQL

try {
    // Definindo a conexão usando PDO
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $password);
    
    // Mostrando os erros caso haja problema (Útil no ambiente de desenvolvimento)
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Caso dê ruim na conexão, devolve um erro em JSON (Já que vamos chamar por AJAX)
    header('Content-Type: application/json');
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Erro crítico: Falha ao conectar com o banco de dados. Verifique o XAMPP e o script de banco.',
        'erro_tecnico' => $e->getMessage()
    ]);
    exit;
}
?>
