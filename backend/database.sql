-- ============================================================
-- AETHOS — Schema Completo do Banco de Dados
-- Execute: source backend/database.sql
-- Compatível com MySQL 5.6+ e MariaDB 10+
-- ============================================================

CREATE DATABASE IF NOT EXISTS aethos_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE aethos_db;

-- ============================================================
-- TABELA 1: usuarios (polimórfica para todos os perfis)
-- ============================================================
CREATE TABLE IF NOT EXISTS usuarios (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    tipo_usuario    ENUM('comum','professor','admin','desenvolvedor') NOT NULL DEFAULT 'comum',
    nome            VARCHAR(255) NOT NULL,
    apelido         VARCHAR(100) DEFAULT NULL,
    email           VARCHAR(191) UNIQUE NOT NULL,
    senha           VARCHAR(255) NOT NULL,
    ddd             VARCHAR(3)   DEFAULT NULL,
    telefone        VARCHAR(20)  DEFAULT NULL,
    foto_perfil     VARCHAR(500) DEFAULT NULL,
    cpf             VARCHAR(14)  UNIQUE DEFAULT NULL,
    endereco_fixo   TEXT         DEFAULT NULL,
    aprovado_admin  BOOLEAN      DEFAULT FALSE,
    primeiro_acesso BOOLEAN      DEFAULT TRUE,
    ativo           BOOLEAN      DEFAULT TRUE,
    email_verificado BOOLEAN     DEFAULT FALSE,
    codigo_verificacao VARCHAR(6) DEFAULT NULL,
    criado_em       TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_tipo  (tipo_usuario),
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABELA 2: locais_esportivos
-- ============================================================
CREATE TABLE IF NOT EXISTS locais_esportivos (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    professor_id     INT NOT NULL,
    nome             VARCHAR(255) NOT NULL,
    descricao        TEXT         DEFAULT NULL,
    modalidade       VARCHAR(100) NOT NULL,
    endereco         TEXT         NOT NULL,
    cep              VARCHAR(10)  DEFAULT NULL,
    cidade           VARCHAR(100) DEFAULT NULL,
    estado           VARCHAR(2)   DEFAULT NULL,
    latitude         DECIMAL(10,8) DEFAULT NULL,
    longitude        DECIMAL(11,8) DEFAULT NULL,
    telefone_contato VARCHAR(20)  DEFAULT NULL,
    instagram        VARCHAR(100) DEFAULT NULL,
    horarios         TEXT         DEFAULT NULL,
    foto_capa        VARCHAR(500) DEFAULT NULL,
    aprovado         BOOLEAN      DEFAULT FALSE,
    ativo            BOOLEAN      DEFAULT TRUE,
    aprovado_por     INT          DEFAULT NULL,
    aprovado_em      TIMESTAMP    NULL DEFAULT NULL,
    criado_em        TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (professor_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (aprovado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_aprovado   (aprovado),
    INDEX idx_modalidade (modalidade),
    INDEX idx_professor  (professor_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABELA 3: avaliacoes
-- ============================================================
CREATE TABLE IF NOT EXISTS avaliacoes (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    local_id    INT NOT NULL,
    usuario_id  INT NOT NULL,
    nota        TINYINT NOT NULL,
    comentario  TEXT    DEFAULT NULL,
    ativa       BOOLEAN DEFAULT TRUE,
    criado_em   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (local_id)   REFERENCES locais_esportivos(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)          ON DELETE CASCADE,
    UNIQUE KEY avaliacao_unica (local_id, usuario_id),
    INDEX idx_local   (local_id),
    INDEX idx_usuario (usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABELA 4: logs_sistema
-- ============================================================
CREATE TABLE IF NOT EXISTS logs_sistema (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    nivel      ENUM('INFO','AVISO','ERRO','CRITICO') NOT NULL DEFAULT 'INFO',
    modulo     VARCHAR(100) NOT NULL,
    acao       VARCHAR(255) NOT NULL,
    descricao  TEXT         DEFAULT NULL,
    usuario_id INT          DEFAULT NULL,
    ip         VARCHAR(45)  DEFAULT NULL,
    user_agent TEXT         DEFAULT NULL,
    criado_em  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_nivel     (nivel),
    INDEX idx_modulo    (modulo),
    INDEX idx_criado_em (criado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABELA 5: tokens_redefinicao (reset de senha por e-mail)
-- ============================================================
CREATE TABLE IF NOT EXISTS tokens_redefinicao (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    token      VARCHAR(64) NOT NULL UNIQUE,
    expira_em  TIMESTAMP NOT NULL,
    usado      BOOLEAN DEFAULT FALSE,
    criado_em  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_token   (token),
    INDEX idx_usuario (usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- SEED: Desenvolvedores (senha padrão: 'senha123' em BCRYPT)
-- Hash: $2y$10$06S4p.2rQ6t6Q7J9K1xL$.D24wPon9yYJDJ64CI7rLeAYYSxEvSjG
-- ============================================================
INSERT IGNORE INTO usuarios (tipo_usuario, nome, email, senha, primeiro_acesso, ativo, email_verificado) VALUES
('desenvolvedor', 'Lorrany', 'lorrany@aethos.dev', '$2y$10$06S4p.2rQ6t6Q7J9K1xL$.D24wPon9yYJDJ64CI7rLeAYYSxEvSjG', 1, 1, 1),
('desenvolvedor', 'Arthur',  'arthur@aethos.dev',  '$2y$10$06S4p.2rQ6t6Q7J9K1xL$.D24wPon9yYJDJ64CI7rLeAYYSxEvSjG', 1, 1, 1),
('desenvolvedor', 'Nepo',    'nepo@aethos.dev',    '$2y$10$06S4p.2rQ6t6Q7J9K1xL$.D24wPon9yYJDJ64CI7rLeAYYSxEvSjG', 1, 1, 1),
('desenvolvedor', 'Leo',     'leo@aethos.dev',     '$2y$10$06S4p.2rQ6t6Q7J9K1xL$.D24wPon9yYJDJ64CI7rLeAYYSxEvSjG', 1, 1, 1),
('desenvolvedor', 'Joaquim', 'joaquim@aethos.dev', '$2y$10$06S4p.2rQ6t6Q7J9K1xL$.D24wPon9yYJDJ64CI7rLeAYYSxEvSjG', 1, 1, 1);

-- SEED: Administrador Master
INSERT IGNORE INTO usuarios (tipo_usuario, nome, email, senha, primeiro_acesso, ativo, email_verificado) VALUES
('admin', 'Admin Geral', 'admin@aethos.com', '$2y$10$06S4p.2rQ6t6Q7J9K1xL$.D24wPon9yYJDJ64CI7rLeAYYSxEvSjG', 1, 1, 1);

-- ============================================================
-- TABELA 6: locais_salvos (Favoritos do usuário)
-- ============================================================
CREATE TABLE IF NOT EXISTS locais_salvos (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    local_id   INT NOT NULL,
    criado_em  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (local_id)   REFERENCES locais_esportivos(id) ON DELETE CASCADE,
    UNIQUE KEY salvamento_unico (usuario_id, local_id),
    INDEX idx_usuario (usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
