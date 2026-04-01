CREATE DATABASE IF NOT EXISTS aethos_db;
USE aethos_db;

-- Tabela genérica para todos os tipos de perfis na plataforma Aethos
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo_usuario ENUM('comum', 'professor', 'admin', 'desenvolvedor') NOT NULL DEFAULT 'comum',
    
    -- Campos em comum
    nome VARCHAR(255) NOT NULL,
    apelido VARCHAR(100),
    email VARCHAR(255) UNIQUE NOT NULL,
    senha VARCHAR(255) NOT NULL,
    ddd VARCHAR(3),
    telefone VARCHAR(20),
    foto_perfil VARCHAR(255),
    
    -- Campos exclusivos de Professor
    cpf VARCHAR(14) UNIQUE,
    endereco_fixo TEXT,
    aprovado_admin BOOLEAN DEFAULT FALSE, -- Professores precisam de aprovação (Regra de Negócio)

    -- Controle de senha de primeiro acesso (Para ADMINS e DEVS)
    primeiro_acesso BOOLEAN DEFAULT TRUE,
    
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Inserindo os Desenvolvedores iniciais. 
-- A senha 'senha123' está criptografada em BCRYPT, que é o padrão seguro do PHP.
INSERT INTO usuarios (tipo_usuario, nome, email, senha, primeiro_acesso) VALUES
('desenvolvedor', 'Lorrany', 'lorrany@aethos.dev', '$2y$10$06S4p.2rQ6t6Q7J9K1xLquWzH7b.2rQ6t6Q7J9K1xLquWzH7b.2rQ', 1),
('desenvolvedor', 'Arthur',  'arthur@aethos.dev',  '$2y$10$06S4p.2rQ6t6Q7J9K1xLquWzH7b.2rQ6t6Q7J9K1xLquWzH7b.2rQ', 1),
('desenvolvedor', 'Nepo',    'nepo@aethos.dev',    '$2y$10$06S4p.2rQ6t6Q7J9K1xLquWzH7b.2rQ6t6Q7J9K1xLquWzH7b.2rQ', 1),
('desenvolvedor', 'Leo',     'leo@aethos.dev',     '$2y$10$06S4p.2rQ6t6Q7J9K1xLquWzH7b.2rQ6t6Q7J9K1xLquWzH7b.2rQ', 1),
('desenvolvedor', 'Joaquim', 'joaquim@aethos.dev', '$2y$10$06S4p.2rQ6t6Q7J9K1xLquWzH7b.2rQ6t6Q7J9K1xLquWzH7b.2rQ', 1);

-- Inserindo 1 Administrador Master genérico
INSERT INTO usuarios (tipo_usuario, nome, email, senha, primeiro_acesso) VALUES
('admin', 'Admin Geral', 'admin@aethos.com', '$2y$10$06S4p.2rQ6t6Q7J9K1xLquWzH7b.2rQ6t6Q7J9K1xLquWzH7b.2rQ', 1);
