<?php
// ============================================================
// AETHOS — locais_publicos.php
// Endpoint para buscar locais existentes no cadastro de professor
// ============================================================
header('Content-Type: application/json');
require_once 'conexao.php';

try {
    // Retorna todos os locais aprovados ou pendentes (para evitar cadastro repetido)
    $stmt = $pdo->prepare("SELECT id, nome, modalidade, endereco FROM locais_esportivos ORDER BY nome ASC");
    $stmt->execute();
    $locais = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(array('sucesso' => true, 'locais' => $locais));
} catch (PDOException $e) {
    error_log('[Aethos] Erro em locais_publicos.php: ' . $e->getMessage());
    echo json_encode(array('sucesso' => false, 'mensagem' => 'Erro ao carregar locais.'));
}
?>
