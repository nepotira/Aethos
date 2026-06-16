<?php
// ============================================================
// AETHOS — dev/exportar_csv.php
// Exporta tabela como arquivo CSV sem bibliotecas externas
// ?tabela=usuarios | locais_esportivos | avaliacoes | logs_sistema
// ============================================================
session_start();
require_once '../conexao.php';
require_once '../helpers.php';

exigir_sessao(array('desenvolvedor'));

$tabelas_permitidas = array(
    'usuarios'          => 'SELECT id, tipo_usuario, nome, email, ddd, telefone, ativo, criado_em FROM usuarios',
    'locais_esportivos' => 'SELECT id, nome, modalidade, cidade, estado, aprovado, ativo, criado_em FROM locais_esportivos',
    'avaliacoes'        => 'SELECT id, local_id, usuario_id, nota, comentario, ativa, criado_em FROM avaliacoes',
    'logs_sistema'      => 'SELECT id, nivel, modulo, acao, descricao, usuario_id, ip, criado_em FROM logs_sistema',
);

$tabela = isset($_GET['tabela']) ? $_GET['tabela'] : '';

if (!isset($tabelas_permitidas[$tabela])) {
    header('Content-Type: application/json');
    echo json_encode(array('sucesso' => false, 'mensagem' => 'Tabela inválida. Use: ' . implode(', ', array_keys($tabelas_permitidas))));
    exit;
}

try {
    $stmt = $pdo->prepare($tabelas_permitidas[$tabela]);
    $stmt->execute();
    $rows = $stmt->fetchAll();

    $nome_arquivo = 'aethos_' . $tabela . '_' . date('Ymd_His') . '.csv';

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $nome_arquivo . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $output = fopen('php://output', 'w');

    // BOM para Excel interpretar UTF-8 corretamente
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    if (!empty($rows)) {
        // Cabeçalho
        fputcsv($output, array_keys($rows[0]), ';');

        // Dados
        foreach ($rows as $row) {
            fputcsv($output, $row, ';');
        }
    }

    fclose($output);

    // Log da exportação
    registrar_log($pdo, 'INFO', 'dev', 'exportar_csv',
        'Dev ' . $_SESSION['nome'] . ' exportou tabela "' . $tabela . '" como CSV.',
        $_SESSION['usuario_id']);

} catch (PDOException $e) {
    error_log('[Aethos] Erro em exportar_csv: ' . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(array('sucesso' => false, 'mensagem' => 'Erro ao exportar dados.'));
}
?>
