<?php
header('Content-Type: application/json');
require_once 'conexao.php';

$decoded = json_decode(file_get_contents('php://input'), true);
$data = !empty($decoded) ? $decoded : $_POST;

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($data['email']) && isset($data['senha'])) {
    
    $tipoUsuario = isset($data['tipo_usuario']) ? $data['tipo_usuario'] : 'comum';
    $nome = isset($data['nome']) ? $data['nome'] : '';
    $apelido = isset($data['apelido']) ? $data['apelido'] : null;
    $email = isset($data['email']) ? $data['email'] : '';
    $ddd = isset($data['ddd']) ? $data['ddd'] : '';
    $telefone = isset($data['telefone']) ? $data['telefone'] : '';

    // PATCH BUG-05: Validação de tamanho mínimo de senha (alinhado com trocar_senha.php)
    if (strlen($data['senha']) < 5) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'A senha deve ter no mínimo 5 caracteres.']);
        exit;
    }

    $senha = password_hash($data['senha'], PASSWORD_DEFAULT); // Criptografia segura
    
    $cpf = null;
    $endereco_fixo = null;
    
    // Pegando só se for professor
    if ($tipoUsuario === 'professor') {
        $cpf = isset($data['cpf']) ? $data['cpf'] : null;
        $endereco_fixo = isset($data['endereco']) ? $data['endereco'] : null;
    }

    try {
        $sql = "INSERT INTO usuarios (tipo_usuario, nome, apelido, email, senha, ddd, telefone, cpf, endereco_fixo) 
                VALUES (:tipo, :nome, :apelido, :email, :senha, :ddd, :tel, :cpf, :endereco)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':tipo', $tipoUsuario);
        $stmt->bindParam(':nome', $nome);
        $stmt->bindParam(':apelido', $apelido);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':senha', $senha);
        $stmt->bindParam(':ddd', $ddd);
        $stmt->bindParam(':tel', $telefone);
        $stmt->bindParam(':cpf', $cpf);
        $stmt->bindParam(':endereco', $endereco_fixo);

        if ($stmt->execute()) {
            echo json_encode([
                'sucesso' => true,
                'mensagem' => 'Cadastro realizado com sucesso! Bem-vindo.'
            ]);
        } else {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Falha ao salvar no banco.']);
        }

    } catch (PDOException $e) {
        // Tratar erro de dado duplicado (Ex email ou cpf ja exista)
        if ($e->getCode() == 23000) {
            echo json_encode(['sucesso' => false, 'mensagem' => 'O E-mail ou CPF já está cadastrado em nossa base.']);
        } else {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Erro interno de servidor.']);
        }
    }
} else {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Requisitos não preenchidos.']);
}
?>
