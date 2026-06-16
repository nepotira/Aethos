<?php
// ============================================================
// AETHOS — locais.php
// CRUD completo de locais esportivos
// ?acao=listar_aprovados | detalhe | criar | editar | excluir
// ============================================================
session_start();
header('Content-Type: application/json');
require_once 'conexao.php';
require_once 'helpers.php';

$acao = isset($_GET['acao']) ? $_GET['acao'] : '';

switch ($acao) {

    // ─── GET: Lista todos os locais aprovados e ativos ──────────
    case 'listar_aprovados':
        $stmt = $pdo->prepare(
            "SELECT l.id, l.nome, l.modalidade, l.latitude, l.longitude,
                    l.cidade, l.estado, l.foto_capa, l.instagram, l.horarios,
                    u.nome AS professor_nome,
                    ROUND(COALESCE(AVG(a.nota), 0), 1) AS nota_media,
                    COUNT(a.id) AS total_avaliacoes
             FROM locais_esportivos l
             INNER JOIN usuarios u ON l.professor_id = u.id
             LEFT JOIN avaliacoes a ON a.local_id = l.id AND a.ativa = 1
             WHERE l.aprovado = 1 AND l.ativo = 1
             GROUP BY l.id
             ORDER BY nota_media DESC"
        );
        $stmt->execute();
        echo json_encode(array('sucesso' => true, 'data' => $stmt->fetchAll()));
        break;

    // ─── GET: Detalhe de um local (?acao=detalhe&id=X) ─────────
    case 'detalhe':
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if (!$id) {
            echo json_encode(array('sucesso' => false, 'mensagem' => 'ID inválido.'));
            break;
        }

        $stmt = $pdo->prepare(
            "SELECT l.*, u.nome AS professor_nome, u.foto_perfil AS professor_foto,
                    ROUND(COALESCE(AVG(a.nota), 0), 1) AS nota_media,
                    COUNT(a.id) AS total_avaliacoes
             FROM locais_esportivos l
             INNER JOIN usuarios u ON l.professor_id = u.id
             LEFT JOIN avaliacoes a ON a.local_id = l.id AND a.ativa = 1
             WHERE l.id = :id AND l.aprovado = 1 AND l.ativo = 1
             GROUP BY l.id"
        );
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $local = $stmt->fetch();

        if (!$local) {
            echo json_encode(array('sucesso' => false, 'mensagem' => 'Local não encontrado.'));
            break;
        }

        // Busca as últimas 10 avaliações
        $stmt_av = $pdo->prepare(
            "SELECT a.nota, a.comentario, a.criado_em, u.nome AS avaliador_nome, u.foto_perfil AS avaliador_foto
             FROM avaliacoes a
             INNER JOIN usuarios u ON a.usuario_id = u.id
             WHERE a.local_id = :id AND a.ativa = 1
             ORDER BY a.criado_em DESC
             LIMIT 10"
        );
        $stmt_av->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt_av->execute();
        $local['avaliacoes'] = $stmt_av->fetchAll();

        echo json_encode(array('sucesso' => true, 'data' => $local));
        break;

    // ─── POST: Cria novo local (Professor logado) ───────────────
    case 'criar':
        exigir_sessao(array('professor'));
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) $data = $_POST;

        $campos = array('nome', 'modalidade', 'endereco');
        foreach ($campos as $campo) {
            if (empty($data[$campo])) {
                echo json_encode(array('sucesso' => false, 'mensagem' => "Campo '$campo' é obrigatório."));
                exit;
            }
        }

        $professor_id     = $_SESSION['usuario_id'];
        $nome             = trim($data['nome']);
        $descricao        = isset($data['descricao'])        ? trim($data['descricao'])        : null;
        $modalidade       = trim($data['modalidade']);
        $endereco         = trim($data['endereco']);
        $cep              = isset($data['cep'])              ? trim($data['cep'])              : null;
        $cidade           = isset($data['cidade'])           ? trim($data['cidade'])           : null;
        $estado           = isset($data['estado'])           ? trim($data['estado'])           : null;
        $latitude         = isset($data['latitude'])         ? floatval($data['latitude'])     : null;
        $longitude        = isset($data['longitude'])        ? floatval($data['longitude'])    : null;
        $telefone_contato = isset($data['telefone_contato']) ? trim($data['telefone_contato']) : null;
        $instagram        = isset($data['instagram'])        ? trim($data['instagram'])        : null;
        $horarios         = isset($data['horarios'])         ? trim($data['horarios'])         : null;
        $foto_capa        = isset($data['foto_capa'])        ? trim($data['foto_capa'])        : null;

        try {
            $stmt = $pdo->prepare(
                "INSERT INTO locais_esportivos
                 (professor_id, nome, descricao, modalidade, endereco, cep, cidade, estado,
                  latitude, longitude, telefone_contato, instagram, horarios, foto_capa)
                 VALUES (:pid, :nome, :desc, :modal, :end, :cep, :cid, :est,
                         :lat, :lon, :tel, :insta, :hor, :foto)"
            );
            $stmt->bindParam(':pid',   $professor_id, PDO::PARAM_INT);
            $stmt->bindParam(':nome',  $nome);
            $stmt->bindParam(':desc',  $descricao);
            $stmt->bindParam(':modal', $modalidade);
            $stmt->bindParam(':end',   $endereco);
            $stmt->bindParam(':cep',   $cep);
            $stmt->bindParam(':cid',   $cidade);
            $stmt->bindParam(':est',   $estado);
            $stmt->bindParam(':lat',   $latitude);
            $stmt->bindParam(':lon',   $longitude);
            $stmt->bindParam(':tel',   $telefone_contato);
            $stmt->bindParam(':insta', $instagram);
            $stmt->bindParam(':hor',   $horarios);
            $stmt->bindParam(':foto',  $foto_capa);
            $stmt->execute();

            $novo_id = $pdo->lastInsertId();
            registrar_log($pdo, 'INFO', 'locais', 'local_submetido',
                'Professor ' . $_SESSION['nome'] . ' submeteu local: ' . $nome,
                $professor_id);

            echo json_encode(array(
                'sucesso'  => true,
                'id'       => $novo_id,
                'mensagem' => 'Local submetido com sucesso! Aguardando aprovação do Administrador.'
            ));
        } catch (PDOException $e) {
            error_log('[Aethos] Erro em locais.php/criar: ' . $e->getMessage());
            echo json_encode(array('sucesso' => false, 'mensagem' => 'Erro ao salvar o local.'));
        }
        break;

    // ─── POST: Edita local (Professor dono) ────────────────────
    case 'editar':
        exigir_sessao(array('professor', 'admin', 'desenvolvedor'));
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) $data = $_POST;

        $id = isset($data['id']) ? intval($data['id']) : 0;
        if (!$id) {
            echo json_encode(array('sucesso' => false, 'mensagem' => 'ID inválido.'));
            break;
        }

        // Verifica se o professor é dono do local
        if ($_SESSION['tipo_usuario'] === 'professor') {
            $stmt_check = $pdo->prepare("SELECT professor_id FROM locais_esportivos WHERE id = :id");
            $stmt_check->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt_check->execute();
            $local_check = $stmt_check->fetch();
            if (!$local_check || $local_check['professor_id'] != $_SESSION['usuario_id']) {
                echo json_encode(array('sucesso' => false, 'mensagem' => 'Você não tem permissão para editar este local.'));
                exit;
            }
        }

        $nome             = isset($data['nome'])             ? trim($data['nome'])             : null;
        $descricao        = isset($data['descricao'])        ? trim($data['descricao'])        : null;
        $modalidade       = isset($data['modalidade'])       ? trim($data['modalidade'])       : null;
        $telefone_contato = isset($data['telefone_contato']) ? trim($data['telefone_contato']) : null;
        $instagram        = isset($data['instagram'])        ? trim($data['instagram'])        : null;
        $horarios         = isset($data['horarios'])         ? trim($data['horarios'])         : null;

        try {
            $stmt = $pdo->prepare(
                "UPDATE locais_esportivos SET
                 nome = COALESCE(:nome, nome),
                 descricao = :desc,
                 modalidade = COALESCE(:modal, modalidade),
                 telefone_contato = :tel,
                 instagram = :insta,
                 horarios = :hor
                 WHERE id = :id"
            );
            $stmt->bindParam(':nome',  $nome);
            $stmt->bindParam(':desc',  $descricao);
            $stmt->bindParam(':modal', $modalidade);
            $stmt->bindParam(':tel',   $telefone_contato);
            $stmt->bindParam(':insta', $instagram);
            $stmt->bindParam(':hor',   $horarios);
            $stmt->bindParam(':id',    $id, PDO::PARAM_INT);
            $stmt->execute();

            registrar_log($pdo, 'INFO', 'locais', 'local_editado',
                'Local ID ' . $id . ' editado por ' . $_SESSION['nome'],
                $_SESSION['usuario_id']);

            echo json_encode(array('sucesso' => true, 'mensagem' => 'Local atualizado com sucesso.'));
        } catch (PDOException $e) {
            error_log('[Aethos] Erro em locais.php/editar: ' . $e->getMessage());
            echo json_encode(array('sucesso' => false, 'mensagem' => 'Erro ao atualizar o local.'));
        }
        break;

    // ─── POST: Desativa local (soft delete) ─────────────────────
    case 'excluir':
        exigir_sessao(array('professor', 'admin', 'desenvolvedor'));
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) $data = $_POST;

        $id = isset($data['id']) ? intval($data['id']) : 0;
        if (!$id) {
            echo json_encode(array('sucesso' => false, 'mensagem' => 'ID inválido.'));
            break;
        }

        // Professor só pode desativar o próprio local
        if ($_SESSION['tipo_usuario'] === 'professor') {
            $stmt_check = $pdo->prepare("SELECT professor_id FROM locais_esportivos WHERE id = :id");
            $stmt_check->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt_check->execute();
            $local_check = $stmt_check->fetch();
            if (!$local_check || $local_check['professor_id'] != $_SESSION['usuario_id']) {
                echo json_encode(array('sucesso' => false, 'mensagem' => 'Sem permissão para excluir este local.'));
                exit;
            }
        }

        try {
            $stmt = $pdo->prepare("UPDATE locais_esportivos SET ativo = 0 WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            registrar_log($pdo, 'AVISO', 'locais', 'local_desativado',
                'Local ID ' . $id . ' desativado por ' . $_SESSION['nome'],
                $_SESSION['usuario_id']);

            echo json_encode(array('sucesso' => true, 'mensagem' => 'Local removido do mapa.'));
        } catch (PDOException $e) {
            error_log('[Aethos] Erro em locais.php/excluir: ' . $e->getMessage());
            echo json_encode(array('sucesso' => false, 'mensagem' => 'Erro ao desativar o local.'));
        }
        break;

    default:
        echo json_encode(array('sucesso' => false, 'mensagem' => 'Ação inválida ou não informada.'));
        break;
}
?>
