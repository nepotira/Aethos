<?php
// ============================================================
// AETHOS — dev/limpar_logs.php
// Remove logs com mais de 30 dias
// ============================================================
session_start();
header('Content-Type: application/json');
require_once '../conexao.php';
require_once '../helpers.php';

exigir_sessao(array('desenvolvedor'));

try {
    $stmt = $pdo->prepare(
        "DELETE FROM logs_sistema WHERE criado_em < DATE_SUB(NOW(), INTERVAL 30 DAY)"
    );
    $stmt->execute();
    $removidos = $stmt->rowCount();

    registrar_log($pdo, 'INFO', 'dev', 'limpar_logs',
        'Dev ' . $_SESSION['nome'] . ' limpou ' . $removidos . ' log(s) com mais de 30 dias.',
        $_SESSION['usuario_id']);

    echo json_encode(array(
        'sucesso'   => true,
        'removidos' => $removidos,
        'mensagem'  => $removidos . ' log(s) removidos com sucesso.'
    ));
} catch (PDOException $e) {
    error_log('[Aethos] Erro em limpar_logs: ' . $e->getMessage());
    echo json_encode(array('sucesso' => false, 'mensagem' => 'Erro ao limpar logs.'));
}
?>
