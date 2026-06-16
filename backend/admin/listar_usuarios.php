<?php
// ============================================================
// AETHOS — admin/listar_usuarios.php
// Lista usuários com filtros e suporte a reativação
// ============================================================
session_start();
header('Content-Type: application/json');
require_once '../conexao.php';
require_once '../helpers.php';

exigir_sessao(array('admin', 'desenvolvedor'));

$tipo    = isset($_GET['tipo'])    ? $_GET['tipo']    : null;
$busca   = isset($_GET['busca'])   ? trim($_GET['busca']) : null;
$ativo   = isset($_GET['ativo'])   ? intval($_GET['ativo']) : 1; // Default: ativos
$pagina  = isset($_GET['pagina'])  ? max(1, intval($_GET['pagina'])) : 1;
$por_pag = 20;
$offset  = ($pagina - 1) * $por_pag;

$where = array('1=1');
$params = array();

if ($tipo && in_array($tipo, array('comum', 'professor', 'admin', 'desenvolvedor'))) {
    $where[] = 'u.tipo_usuario = :tipo';
    $params[':tipo'] = $tipo;
}

if ($busca) {
    $where[] = '(u.nome LIKE :busca OR u.email LIKE :busca)';
    $params[':busca'] = '%' . $busca . '%';
}

if ($ativo == 0) {
    $where[] = 'u.ativo = 0';
} elseif ($ativo == 2) {
    // Todos
} else {
    $where[] = 'u.ativo = 1';
}

$whereClause = implode(' AND ', $where);

try {
    // Total
    $stmt_count = $pdo->prepare("SELECT COUNT(*) as total FROM usuarios u WHERE $whereClause");
    foreach ($params as $k => $v) $stmt_count->bindValue($k, $v);
    $stmt_count->execute();
    $total = $stmt_count->fetch()['total'];

    // Dados
    $stmt = $pdo->prepare(
        "SELECT u.id, u.tipo_usuario, u.nome, u.email, u.ddd, u.telefone,
                u.foto_perfil, u.ativo, u.criado_em
         FROM usuarios u
         WHERE $whereClause
         ORDER BY u.criado_em DESC
         LIMIT :limit OFFSET :offset"
    );
    foreach ($params as $k => $v) $stmt->bindValue($k, $v);
    $stmt->bindValue(':limit',  $por_pag, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset,  PDO::PARAM_INT);
    $stmt->execute();

    echo json_encode(array(
        'sucesso'     => true,
        'total'       => intval($total),
        'pagina'      => $pagina,
        'total_pags'  => ceil($total / $por_pag),
        'data'        => $stmt->fetchAll()
    ));
} catch (PDOException $e) {
    error_log('[Aethos] Erro em listar_usuarios: ' . $e->getMessage());
    echo json_encode(array('sucesso' => false, 'mensagem' => 'Erro ao buscar usuários.'));
}
?>
