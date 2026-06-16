<?php
// ============================================================
// AETHOS — dev/db_stats.php
// Retorna contagem de registros de cada tabela principal
// ============================================================
session_start();
header('Content-Type: application/json');
require_once '../conexao.php';
require_once '../helpers.php';

exigir_sessao(array('desenvolvedor'));

try {
    $tabelas = array(
        array('tabela' => 'usuarios',          'descricao' => 'Atletas, professores, admins e devs'),
        array('tabela' => 'locais_esportivos', 'descricao' => 'Espaços cadastrados pelos professores'),
        array('tabela' => 'avaliacoes',        'descricao' => 'Avaliações dos locais pelos atletas'),
        array('tabela' => 'logs_sistema',      'descricao' => 'Registro de todas as ações do sistema'),
    );

    foreach ($tabelas as &$t) {
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM `" . $t['tabela'] . "`");
        $stmt->execute();
        $t['total'] = intval($stmt->fetch()['total']);
    }

    echo json_encode(array('sucesso' => true, 'data' => $tabelas));

} catch (PDOException $e) {
    error_log('[Aethos] Erro em db_stats: ' . $e->getMessage());
    echo json_encode(array('sucesso' => false, 'mensagem' => 'Erro ao consultar banco.'));
}
?>
