# REGIMENTO DE PROJETO — SISTEMA AETHOS
## Missão de Construção Completa · Prompt para Google Antigravity

---

> **ATENÇÃO AO AGENTE:** Leia este documento integralmente antes de iniciar qualquer ação.  
> Este é o documento-lei do projeto Aethos. Cada regra aqui descrita é inviolável.  
> Qualquer funcionalidade descrita neste documento **deve ser implementada de forma real e funcional.**  
> **Nenhuma simulação, placeholder, `alert('em breve')`, função vazia ou mock será tolerado.**

---

## 1. CONTEXTO DO PROJETO

**Nome:** Aethos  
**Repositório:** `github.com/nepotira/Aethos`  
**Tipo:** Aplicação Web Mobile-First de localização de espaços esportivos  
**Stack:** HTML5 · Tailwind CSS · jQuery 3.7.1 · Bootstrap 5.3.3 · FontAwesome 6.4.0 · Leaflet.js 1.9.4 · PHP 8.x (PDO) · MySQL · XAMPP local  

O Aethos é uma plataforma que conecta atletas a locais e professores de esportes via mapa interativo. Professores cadastram seus espaços, administradores aprovam, e atletas encontram, avaliam e entram em contato.

---

## 2. REGIMENTO — REGRAS INVIOLÁVEIS

### 2.1 Lei de Funcionalidade Real
**PROIBIDO** produzir qualquer um dos itens a seguir:
- Funções JavaScript que só exibem `console.log` ou `alert`
- Botões que não fazem nada ao ser clicados
- Formulários que não enviam dados reais para o backend
- Páginas que carregam sem verificar a sessão PHP do usuário
- Endpoints PHP que retornam dados fictícios hardcoded
- Tabelas MySQL referenciadas no código mas não criadas no `database.sql`
- Redirecionamentos que levam para páginas que não existem
- Qualquer funcionalidade marcada como "em breve" ou "a implementar"

### 2.2 Lei da Stack
Não será utilizado nenhum framework de SPA (React, Vue, Angular). O sistema é **Multi-Page Application (MPA)** com arquivos HTML independentes por funcionalidade, comunicação AJAX via jQuery e backend PHP puro. Não trocar essa stack por nenhum motivo.

### 2.3 Lei do Banco de Dados
**Toda tabela referenciada em qualquer código PHP ou JavaScript deve existir no `backend/database.sql`.** O schema deve ser completo, com FKs, INDEXes e seeds de dados iniciais prontos para rodar com um único `source database.sql` no MySQL.

### 2.4 Lei da Identidade Visual
Toda página do sistema deve seguir a identidade visual abaixo. Nenhuma página pode ter fundo branco, fonte Times New Roman ou cores fora da paleta definida.

**Paleta (Regra 70-20-10):**
- `#0D0F32` — Azul-marinho escuro · 70% · Fundos e estrutura
- `#A5B4FC` — Lavanda suave · 20% · Botões, destaques, links
- `#FFFFFF` — Branco puro · 10% · Textos sobre fundos escuros

**Tipografia:**
- Títulos e headings: `Plus Jakarta Sans` (Google Fonts)
- Corpo e UI: `Inter` (Google Fonts)
- Logotipo/marca: `Syafixy` (fonte display)

**Estilo visual:** Glassmorfismo "Liquid Glass" — cards com `backdrop-filter: blur(12px)`, `background: rgba(13,15,50,0.6)`, `border: 1px solid rgba(165,180,252,0.2)`, `border-radius: 16px`.

### 2.5 Lei de Segurança
- Todas as senhas: `password_hash()` BCRYPT, nunca texto puro
- Todas as queries: PDO Prepared Statements com `bindParam()`, sem concatenação de string SQL
- Todo endpoint PHP que exige sessão: deve checar `$_SESSION['usuario_id']` e retornar `403` se ausente
- `session_regenerate_id(true)` deve ser chamado após todo login bem-sucedido
- Erros técnicos do PDO: apenas `error_log()`, nunca expor ao cliente
- Proteção CSRF: token gerado em session e verificado em todos os POSTs de formulário

### 2.6 Lei de Redirecionamento por Perfil
Após login bem-sucedido, cada perfil vai para uma página diferente:

| Perfil | Destino (sem ser 1º acesso) | Destino (1º acesso) |
|---|---|---|
| Atleta | `index.html` | `index.html` |
| Professor | `index.html` | `index.html` |
| Administrador | `admin.html` | `nova_senha.html` |
| Desenvolvedor | `dev.html` | `nova_senha.html` |

### 2.7 Lei da Jornada Completa (UX, Estado e Reflexos da UI)
**Nenhuma funcionalidade deve ser entregue isoladamente (apenas o backend ou apenas o botão).** Toda feature (como Login, Cadastro, Upload, CRUDs) deve ser projetada como uma **Jornada de Usuário Completa**, cumprindo rigorosamente os seguintes critérios de aceite:
1. **Tratamento de Estado Visual (Loading):** Durante requisições AJAX, o botão deve entrar em estado de carregamento (ex: desabilitado, alterando texto para "Carregando..." ou exibindo um spinner CSS/FontAwesome). O usuário nunca pode ficar sem feedback visual enquanto o servidor processa.
2. **Feedback e Tratamento de Erros Amigável:** O servidor sempre retornará JSON com `sucesso` e `mensagem`. O frontend (jQuery) deve capturar isso e renderizar mensagens amigáveis na tela (em `divs` de alerta estilizadas com Tailwind), diferenciando visualmente Sucesso (Verde) e Erro (Vermelho).
3. **Reflexo Visual de Sessão Global (Header/Navbar):** Quando uma ação muda o estado global (como Login ou Logout), a interface deve refletir isso. Por exemplo: se o usuário estiver logado, a Navbar deve **ocultar** os botões "Entrar / Cadastrar" e **exibir** "Meu Perfil", "Sair" e a foto do usuário. Isso exige que toda página faça uma checagem rápida (`backend/verificar_sessao.php`) no carregamento para adaptar o header.
4. **Redirecionamentos e Navegação Limpa:** Após o sucesso de fluxos críticos (Login, Cadastro, Atualização de Senha), o usuário deve ser redirecionado automaticamente para a rota pertinente, sem ficar preso em uma tela de sucesso estática.
5. **Completude de Funcionalidade:** Features "comuns" não podem ser entregues pela metade. (ex: "Listagem" exige paginação/scroll e busca; "Exclusão" exige Modal de Confirmação; "Upload" exige preview da imagem na tela antes de enviar).

---

## 3. ESTADO ATUAL DO SISTEMA (O que já existe no repositório)

### 3.1 Arquivos existentes com bugs conhecidos

| Arquivo | Estado | Bugs a Corrigir |
|---|---|---|
| `js/mapa.js` | ✅ Existe, com bugs | BUG-01: marcador de geolocalização; BUG-02: debounce 0ms; BUG-03: Enter não aciona busca |
| `login.html` | ✅ Existe, incompleto | BUG-06: sem campo "Confirmar Senha"; BUG-04: botão Google sem handler |
| `nova_senha.html` | ✅ Existe, incompleto | BUG-09: carrega sem checar sessão; BUG-10: sem redirecionamento ao receber "Acesso Negado" |
| `backend/login.php` | ✅ Existe, com falhas críticas | BUG-07: `$senha == 'senha123'` hardcoded; BUG-08: sem `session_regenerate_id()` |
| `backend/conexao.php` | ✅ Existe, com falha | BUG-11: expõe `$e->getMessage()` ao cliente |
| `backend/register.php` | ✅ Existe, incompleto | BUG-05: sem validação de tamanho mínimo de senha; sem validação de CPF; sem validação de e-mail |
| `backend/trocar_senha.php` | ✅ Existe, funcional | Nenhum bug crítico |
| `backend/database.sql` | ✅ Existe, incompleto | Apenas tabela `usuarios`; faltam 3 tabelas |
| `index.html` | ✅ Existe | Nenhum bug no HTML, apenas no JS |
| `css/style.css` | ✅ Existe | Nenhum bug |
| `js/auth.js` | ✅ Existe | Requer adição de validação de confirmação de senha |

### 3.2 Arquivos que NÃO existem e devem ser criados do zero

**Páginas:**
- `admin.html` — Painel Administrativo (completo)
- `dev.html` — Painel do Desenvolvedor (completo)
- `perfil.html` — Perfil do usuário logado
- `local.html` — Detalhe de um local esportivo (recebe `?id=X`)
- `professor/cadastrar-local.html` — Formulário do Professor para submeter local

**Backend:**
- `backend/verificar_sessao.php` — Retorna sessão ativa (JSON)
- `backend/logout.php` — Destroi sessão e redireciona
- `backend/locais.php` — CRUD completo de locais esportivos
- `backend/avaliacoes.php` — CRUD de avaliações
- `backend/upload.php` — Upload de foto de perfil
- `backend/redefinir_senha.php` — Envio de código por e-mail + redefinição
- `backend/admin/listar_pendentes.php` — Lista locais aguardando aprovação
- `backend/admin/aprovar_local.php` — Aprova local de Professor
- `backend/admin/rejeitar_local.php` — Rejeita local de Professor
- `backend/admin/listar_usuarios.php` — Lista todos os usuários
- `backend/admin/excluir_usuario.php` — Exclui usuário
- `backend/admin/remover_avaliacao.php` — Remove avaliação
- `backend/dev/listar_logs.php` — Lista logs técnicos
- `backend/dev/limpar_logs.php` — Limpa logs antigos

**JavaScript:**
- `js/mapa-locais.js` — Carrega marcadores de locais aprovados no mapa via AJAX
- `js/admin.js` — Lógica do painel Admin (aprovações, exclusões, filtros)
- `js/dev.js` — Lógica do painel Dev (logs, filtros, ações técnicas)
- `js/avaliacao.js` — Sistema de estrelas interativo + envio de avaliação

---

## 4. BANCO DE DADOS — SCHEMA COMPLETO

O arquivo `backend/database.sql` deve ser **completamente reescrito** contendo as 4 tabelas abaixo, com todas as FKs, INDEXes, constraints e seeds.

### 4.1 Tabela `usuarios` (atualizar a existente)

```sql
CREATE TABLE IF NOT EXISTS usuarios (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    tipo_usuario   ENUM('comum','professor','admin','desenvolvedor') NOT NULL DEFAULT 'comum',
    nome           VARCHAR(255) NOT NULL,
    apelido        VARCHAR(100) DEFAULT NULL,
    email          VARCHAR(255) UNIQUE NOT NULL,
    senha          VARCHAR(255) NOT NULL,
    ddd            VARCHAR(3)   DEFAULT NULL,
    telefone       VARCHAR(20)  DEFAULT NULL,
    foto_perfil    VARCHAR(500) DEFAULT NULL,
    cpf            VARCHAR(14)  UNIQUE DEFAULT NULL,
    endereco_fixo  TEXT         DEFAULT NULL,
    aprovado_admin BOOLEAN      DEFAULT FALSE,
    primeiro_acesso BOOLEAN     DEFAULT TRUE,
    ativo          BOOLEAN      DEFAULT TRUE,
    criado_em      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_tipo (tipo_usuario),
    INDEX idx_email (email)
);
```

**Seed obrigatório** — Desenvolvedores com senha `senha123` em BCRYPT real (gerar os hashes com `password_hash('senha123', PASSWORD_DEFAULT)` antes de inserir):

```sql
INSERT INTO usuarios (tipo_usuario, nome, email, senha, primeiro_acesso, ativo) VALUES
('desenvolvedor', 'Lorrany', 'lorrany@aethos.dev', '[BCRYPT_DE_senha123]', 1, 1),
('desenvolvedor', 'Arthur',  'arthur@aethos.dev',  '[BCRYPT_DE_senha123]', 1, 1),
('desenvolvedor', 'Nepo',    'nepo@aethos.dev',    '[BCRYPT_DE_senha123]', 1, 1),
('desenvolvedor', 'Leo',     'leo@aethos.dev',     '[BCRYPT_DE_senha123]', 1, 1),
('desenvolvedor', 'Joaquim', 'joaquim@aethos.dev', '[BCRYPT_DE_senha123]', 1, 1),
('admin',        'Admin Geral', 'admin@aethos.com', '[BCRYPT_DE_senha123]', 1, 1);
```

> **Instrução ao agente:** Gere os hashes BCRYPT reais com PHP (`echo password_hash('senha123', PASSWORD_DEFAULT);`) antes de inserir no SQL. Não use strings hardcoded nem deixe o campo vazio.

### 4.2 Tabela `locais_esportivos` (nova)

```sql
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
    aprovado_em      TIMESTAMP    DEFAULT NULL,
    criado_em        TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (professor_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (aprovado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_aprovado (aprovado),
    INDEX idx_modalidade (modalidade),
    INDEX idx_professor (professor_id)
);
```

### 4.3 Tabela `avaliacoes` (nova)

```sql
CREATE TABLE IF NOT EXISTS avaliacoes (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    local_id    INT NOT NULL,
    usuario_id  INT NOT NULL,
    nota        TINYINT NOT NULL CHECK (nota BETWEEN 1 AND 5),
    comentario  TEXT    DEFAULT NULL,
    ativa       BOOLEAN DEFAULT TRUE,
    criado_em   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (local_id)   REFERENCES locais_esportivos(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)          ON DELETE CASCADE,
    UNIQUE KEY avaliacao_unica (local_id, usuario_id),
    INDEX idx_local (local_id),
    INDEX idx_usuario (usuario_id)
);
```

### 4.4 Tabela `logs_sistema` (nova)

```sql
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
    INDEX idx_nivel (nivel),
    INDEX idx_modulo (modulo),
    INDEX idx_criado_em (criado_em)
);
```

### 4.5 Tabela `tokens_redefinicao` (nova — para reset de senha por e-mail)

```sql
CREATE TABLE IF NOT EXISTS tokens_redefinicao (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    token      VARCHAR(64) NOT NULL UNIQUE,
    expira_em  TIMESTAMP NOT NULL,
    usado      BOOLEAN DEFAULT FALSE,
    criado_em  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_usuario (usuario_id)
);
```

---

## 5. MÓDULOS A IMPLEMENTAR — ESPECIFICAÇÕES COMPLETAS

---

### MÓDULO 1 — Correção de Bugs (Prioridade Máxima)

Aplicar ANTES de qualquer novo desenvolvimento:

**`js/mapa.js`:**
- BUG-01: Separar `map.setView()` de `L.marker().addTo(map)` — são objetos distintos
- BUG-02: Alterar debounce de `0ms` para `350ms`
- BUG-03: Adicionar listener `keydown Enter` no campo de busca que aciona `searchBtn.click()`

**`backend/login.php`:**
- BUG-07: Remover completamente `|| $senha == 'senha123'` do if de autenticação
- BUG-08: Adicionar `session_regenerate_id(true)` imediatamente antes de gravar em `$_SESSION`

**`backend/conexao.php`:**
- BUG-11: Substituir `'erro_tecnico' => $e->getMessage()` por `error_log()` — nunca ao cliente

**`login.html` + `js/auth.js`:**
- BUG-06: Adicionar campo `#reg-senha-confirm` no form de cadastro; validar `s1 !== s2` antes do AJAX
- BUG-04: Substituir botão Google por versão `disabled` com tooltip "Login com Google — Em breve"

**`nova_senha.html`:**
- BUG-09: Ao carregar a página, fazer GET em `backend/verificar_sessao.php`; redirecionar para `login.html` se `!res.autenticado`
- BUG-10: No callback `success` do AJAX, detectar `mensagem.includes('Acesso Negado')` e redirecionar para `login.html`

---

### MÓDULO 2 — Endpoints de Sessão e Logout

**`backend/verificar_sessao.php`:**
```
- Inicia session_start()
- Retorna JSON: { autenticado: bool, tipo_usuario: string|null, nome: string|null, id: int|null }
- Não requer autenticação — apenas verifica se existe sessão ativa
```

**`backend/logout.php`:**
```
- Inicia session_start()
- Registra log de logout em logs_sistema
- Executa session_destroy() e session_unset()
- Retorna JSON: { sucesso: true }
- O JS redireciona para login.html
```

Um botão de "Sair" deve estar visível em TODAS as páginas do sistema para usuários logados (no `index.html`, `perfil.html`, `admin.html`, `dev.html`).

---

### MÓDULO 3 — Mapa com Locais Esportivos Reais

#### 3.1 `js/mapa-locais.js` (novo arquivo, carregado junto ao `index.html`)

- Ao carregar o mapa, fazer `$.getJSON('backend/locais.php?acao=listar_aprovados')` 
- Para cada local retornado (com `latitude` e `longitude` preenchidos), adicionar `L.marker()` no mapa com ícone personalizado diferente do marcador de geolocalização do usuário
- Ao clicar no marcador, abrir popup com: nome do local, modalidade, nota média (estrelas), botão "Ver mais" que leva para `local.html?id=X`
- Marcadores de locais aprovados devem usar ícone na cor `#A5B4FC` (lavanda)
- Marcador do usuário deve usar ícone diferente (azul escuro ou pin padrão)

#### 3.2 `backend/locais.php`

Endpoint único com parâmetro `?acao=`:

| ação | método | autenticação | descrição |
|---|---|---|---|
| `listar_aprovados` | GET | pública | Retorna todos com `aprovado=1` e `ativo=1`, incluindo `lat`, `lon`, `nome`, `modalidade`, `nota_media` |
| `detalhe` | GET + `?id=X` | pública | Retorna todos os campos de um local + avaliações + nome do professor |
| `criar` | POST | Professor logado | Insere novo local com `aprovado=0` |
| `editar` | POST + `id` | Professor dono | Atualiza campos do próprio local |
| `excluir` | POST + `id` | Professor dono ou Admin | Soft delete (`ativo=0`) |

Toda ação que modifica dados deve registrar em `logs_sistema`.

---

### MÓDULO 4 — Formulário do Professor (`professor/cadastrar-local.html`)

Página protegida (verificar sessão: apenas `tipo_usuario = 'professor'` pode acessar).

**Campos do formulário:**
- Nome do local / academia / dojo / espaço
- Modalidade esportiva (select: Futebol, Jiu-Jitsu, Muay Thai, Natação, Crossfit, Yoga, Tênis, Basquete, Vôlei, Outro)
- Descrição livre (textarea)
- Endereço completo (com campo de CEP que preenche cidade/estado via ViaCEP API: `viacep.com.br/ws/{CEP}/json/`)
- Telefone de contato
- Instagram (opcional)
- Horários de funcionamento (textarea)
- Foto de capa (upload de imagem — via `backend/upload.php`)

**Após o envio:**
- POST AJAX para `backend/locais.php?acao=criar`
- Exibir mensagem: "Cadastro enviado! Aguardando aprovação do Administrador para aparecer no mapa."
- Registrar entrada em `logs_sistema`

---

### MÓDULO 5 — Página de Detalhe do Local (`local.html?id=X`)

**Carregamento:**
- Ao abrir, ler `?id=X` da URL e fazer `GET backend/locais.php?acao=detalhe&id=X`
- Se local não encontrado: redirecionar para `index.html`

**Conteúdo da página:**
- Foto de capa do local (ou placeholder com ícone de esporte)
- Nome, modalidade, descrição
- Mapa Leaflet pequeno (400px) centralizado nas coordenadas do local com marcador
- Endereço, telefone, Instagram, horários
- Nome e foto do Professor (com link para perfil)
- Média de avaliações com estrelas visuais (1 a 5)
- Lista das últimas 10 avaliações (nome do avaliador, estrelas, comentário, data)
- Se usuário logado como Atleta: formulário de avaliação (estrelas clicáveis + comentário + botão "Avaliar")
- Se usuário já avaliou: exibir sua avaliação existente com botão "Editar avaliação"

---

### MÓDULO 6 — Sistema de Avaliações Completo

#### 6.1 `backend/avaliacoes.php`

| ação | método | autenticação | descrição |
|---|---|---|---|
| `criar` | POST | Atleta logado | Insere avaliação; impede duplicata via UNIQUE KEY |
| `editar` | POST + `id` | Dono da avaliação | Atualiza nota e comentário |
| `listar_por_local` | GET + `?local_id=X` | pública | Retorna avaliações com nome do usuário e nota |
| `remover` | POST + `id` | Admin ou dono | Soft delete (`ativa=0`) |

#### 6.2 `js/avaliacao.js`

- Componente de estrelas interativo: 5 estrelas SVG que mudam de cor ao hover e ao clicar
- Ao submeter: POST AJAX, atualiza a média visível instantaneamente sem recarregar a página
- Tratamento de erro "já avaliado": exibir avaliação existente no lugar do formulário

---

### MÓDULO 7 — Painel do Administrador (`admin.html`)

Página protegida. Verificar sessão ao carregar: apenas `tipo_usuario = 'admin'` ou `'desenvolvedor'`.

**Estrutura com abas (jQuery `.tab-panel`):**

#### Aba 1 — "Locais Pendentes"
- Tabela com locais onde `aprovado=0` e `ativo=1`
- Colunas: Nome, Modalidade, Cidade, Professor, Data de envio
- Ações por linha:
  - Botão "Aprovar" → POST `backend/admin/aprovar_local.php` → atualiza tabela sem reload
  - Botão "Rejeitar" → POST `backend/admin/rejeitar_local.php` → remove da lista + soft delete + registra log
- Contador de pendências no título da aba ("Locais Pendentes (3)")

#### Aba 2 — "Usuários"
- Tabela paginada com todos os usuários (`ativo=1`)
- Filtros: por tipo (Atleta / Professor / todos), por nome/e-mail
- Colunas: Foto, Nome, E-mail, DDD, Tipo, Data de cadastro
- Ações:
  - Botão "Desativar" → soft delete (`ativo=0`)
  - Botão "Reativar" (se `ativo=0`) → reativa usuário

#### Aba 3 — "Avaliações"
- Tabela com todas as avaliações `ativa=1`
- Filtros: por nota (1 a 5), por local, por usuário
- Ações: Botão "Remover" → `backend/admin/remover_avaliacao.php` → soft delete

#### Aba 4 — "Locais Ativos"
- Tabela com todos os locais `aprovado=1` e `ativo=1`
- Ações: Botão "Desativar" → remove do mapa público sem excluir o registro

**Header do painel:**
- Nome do admin logado + foto de perfil
- Botão "Sair" visível

---

### MÓDULO 8 — Painel do Desenvolvedor (`dev.html`)

Página protegida. Verificar sessão: apenas `tipo_usuario = 'desenvolvedor'`.

**Herança:** O Desenvolvedor tem acesso a TUDO que o Admin tem. O painel Dev deve incluir as 4 abas do Admin MAIS:

#### Aba "Logs do Sistema"
- Tabela de `logs_sistema` ordenada por `criado_em DESC`
- Filtros: por `nivel` (INFO / AVISO / ERRO / CRÍTICO), por `modulo`, por período (data de/até)
- Colunas: Nível (com badge colorido), Módulo, Ação, Descrição, Usuário, IP, Data/Hora
- Badges de nível:
  - `INFO` → badge azul lavanda
  - `AVISO` → badge amarelo
  - `ERRO` → badge laranja
  - `CRÍTICO` → badge vermelho pulsante
- Botão "Limpar logs com mais de 30 dias" → POST `backend/dev/limpar_logs.php`
- Contador de logs por nível no topo: "📊 INFO: 42 | ⚠️ AVISO: 5 | 🔴 ERRO: 1"

#### Aba "Banco de Dados"
- Tabela resumo com contagem de registros por tabela: `usuarios`, `locais_esportivos`, `avaliacoes`, `logs_sistema`
- Botões "Exportar CSV" para cada tabela (gerado pelo backend via PHP, sem bibliotecas externas)

---

### MÓDULO 9 — Redefinição de Senha por E-mail

**Fluxo completo (RF — Mencionado em `project_description.md`):**

#### Tela 1: `esqueci-senha.html`
- Campo: E-mail cadastrado
- Ao submeter: POST `backend/redefinir_senha.php?acao=solicitar`
- Backend:
  1. Busca usuário por e-mail
  2. Gera token aleatório de 64 chars: `bin2hex(random_bytes(32))`
  3. Salva em `tokens_redefinicao` com `expira_em = NOW() + 1 hora`
  4. Envia e-mail com link `redefinir-senha.html?token=XXXXXX` via PHPMailer (SMTP Gmail configurável via `backend/config.php`)
  5. Retorna JSON de sucesso (não revelar se o e-mail existe ou não — sempre "Se o e-mail estiver cadastrado, você receberá as instruções")

#### Tela 2: `redefinir-senha.html?token=XXXXXX`
- Ao carregar: GET `backend/redefinir_senha.php?acao=verificar_token&token=XXXXXX`
- Se token inválido ou expirado: exibir erro e link para `esqueci-senha.html`
- Campos: Nova senha + Confirmar nova senha
- Ao submeter: POST `backend/redefinir_senha.php?acao=confirmar` com `{ token, nova_senha }`
- Backend:
  1. Verifica token válido e não expirado e não usado
  2. Atualiza senha com `password_hash()`
  3. Marca token como `usado=1`
  4. Redireciona para `login.html` com mensagem de sucesso

**Link "Esqueci minha senha"** deve estar visível no formulário de login em `login.html`.

**`backend/config.php`** (novo arquivo — configurações sensíveis do sistema):
```php
// SMTP Configuration
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'seu_email@gmail.com');
define('SMTP_PASS', 'sua_senha_app');
define('SMTP_FROM_NAME', 'Aethos');
// PHPMailer: instalar via Composer ou incluir manualmente na pasta vendor/
```

---

### MÓDULO 10 — Validação de CPF Real (`backend/register.php`)

Implementar verificação do dígito verificador do CPF em PHP puro, sem API externa:

```
Algoritmo de validação (obrigatório implementar):
1. Remover máscara: apenas dígitos
2. Rejeitar se tem menos de 11 dígitos
3. Rejeitar sequências inválidas: "00000000000", "11111111111", etc.
4. Calcular 1º dígito verificador: soma ponderada dos 9 primeiros × (10 a 2), resto por 11
5. Calcular 2º dígito verificador: soma ponderada dos 10 primeiros × (11 a 2), resto por 11
6. Comparar com os 2 últimos dígitos do CPF informado
```

Retornar erro `"CPF inválido."` antes de qualquer INSERT se a validação falhar.

---

### MÓDULO 11 — Verificação de E-mail (DNS MX Lookup)

No `backend/register.php`, antes do INSERT, verificar se o domínio do e-mail informado possui registro MX ativo:

```php
$dominio = substr(strrchr($email, "@"), 1);
if (!checkdnsrr($dominio, 'MX')) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'O e-mail informado não parece válido. Verifique o domínio.']);
    exit;
}
```

---

### MÓDULO 12 — Upload de Foto de Perfil (`backend/upload.php`)

- Aceita: JPEG, PNG, WebP — tamanho máximo: 2MB
- Valida MIME type real com `finfo_file()`, não apenas extensão
- Gera nome único: `uniqid('foto_', true) . '.' . $extensao`
- Salva em `uploads/perfis/` (criar a pasta com `.htaccess` bloqueando execução de scripts)
- Retorna JSON: `{ sucesso: true, caminho: "uploads/perfis/foto_xxx.jpg" }`
- Em caso de erro: retorna mensagem clara ("Arquivo muito grande", "Formato não suportado", etc.)

---

### MÓDULO 13 — Página de Perfil (`perfil.html`)

Página protegida (qualquer usuário logado pode acessar o próprio perfil).

**Seções:**
- Foto de perfil (com botão de alterar → abre seletor de arquivo → upload via AJAX para `backend/upload.php`)
- Nome completo, apelido, DDD, telefone, e-mail (somente leitura)
- Botão "Editar perfil" → abre modal com campos editáveis (nome, apelido, telefone, DDD)
- Botão "Alterar senha" → abre modal com campos: senha atual, nova senha, confirmar nova senha
- Se perfil for Professor: seção "Meus Locais" com lista de locais cadastrados e status (Pendente / Aprovado / Desativado)
- Se perfil for Atleta: seção "Minhas Avaliações" com lista de avaliações feitas

---

### MÓDULO 14 — Logging Automático

Toda ação significativa do sistema deve registrar uma entrada em `logs_sistema`. Criar função PHP centralizada `registrar_log($pdo, $nivel, $modulo, $acao, $descricao = null, $usuario_id = null)` em `backend/helpers.php`:

| Evento | Nível | Módulo |
|---|---|---|
| Login bem-sucedido | INFO | auth |
| Falha de login | AVISO | auth |
| Novo cadastro | INFO | auth |
| Troca de senha | INFO | auth |
| Local enviado pelo Professor | INFO | locais |
| Local aprovado pelo Admin | INFO | admin |
| Local rejeitado pelo Admin | AVISO | admin |
| Usuário desativado | AVISO | admin |
| Avaliação removida | AVISO | admin |
| Erro de PDO | ERRO | database |
| Token de redefinição gerado | INFO | auth |
| Login com IP suspeito (5+ falhas) | CRÍTICO | segurança |

---

## 6. ESTRUTURA COMPLETA DE ARQUIVOS (RESULTADO FINAL)

```
AETHOS/
├── index.html                    ← Mapa + locais aprovados
├── login.html                    ← Login + Cadastro
├── nova_senha.html               ← Troca obrigatória (1º acesso)
├── perfil.html                   ← Perfil do usuário logado
├── local.html                    ← Detalhe de local esportivo (?id=X)
├── esqueci-senha.html            ← Solicitar redefinição de senha
├── redefinir-senha.html          ← Confirmar nova senha via token
├── admin.html                    ← Painel Administrativo
├── dev.html                      ← Painel do Desenvolvedor
│
├── professor/
│   └── cadastrar-local.html      ← Formulário do Professor
│
├── css/
│   └── style.css                 ← Estilos globais
│
├── js/
│   ├── mapa.js                   ← Geolocalização + busca (corrigido)
│   ├── mapa-locais.js            ← Marcadores de locais aprovados no mapa
│   ├── auth.js                   ← Login/cadastro/AJAX (corrigido)
│   ├── avaliacao.js              ← Componente de estrelas + envio
│   ├── admin.js                  ← Lógica do painel Admin
│   └── dev.js                    ← Lógica do painel Dev + logs
│
├── uploads/
│   ├── perfis/                   ← Fotos de perfil
│   │   └── .htaccess             ← Bloqueia execução de scripts
│   └── locais/                   ← Fotos de capa dos locais
│       └── .htaccess
│
└── backend/
    ├── config.php                ← Configurações (SMTP, etc.)
    ├── conexao.php               ← PDO singleton (corrigido)
    ├── helpers.php               ← Funções utilitárias (registrar_log, etc.)
    ├── database.sql              ← Schema completo (5 tabelas + seeds)
    ├── login.php                 ← Autenticação (corrigido)
    ├── register.php              ← Cadastro (corrigido + CPF + MX)
    ├── trocar_senha.php          ← Troca obrigatória (existente, funcional)
    ├── verificar_sessao.php      ← Verificação de sessão (novo)
    ├── logout.php                ← Logout (novo)
    ├── upload.php                ← Upload de fotos (novo)
    ├── redefinir_senha.php       ← Reset de senha por e-mail (novo)
    ├── locais.php                ← CRUD de locais (novo)
    ├── avaliacoes.php            ← CRUD de avaliações (novo)
    │
    ├── admin/
    │   ├── listar_pendentes.php  ← Locais aguardando aprovação
    │   ├── aprovar_local.php     ← Aprovar local
    │   ├── rejeitar_local.php    ← Rejeitar local
    │   ├── listar_usuarios.php   ← Listar todos usuários
    │   ├── excluir_usuario.php   ← Soft delete usuário
    │   └── remover_avaliacao.php ← Soft delete avaliação
    │
    └── dev/
        ├── listar_logs.php       ← Logs paginados com filtros
        ├── limpar_logs.php       ← Limpar logs antigos
        └── exportar_csv.php      ← Exportar tabelas como CSV
```

---

## 7. FLUXOS COMPLETOS DE USUÁRIO

### Fluxo A — Atleta encontra um local e avalia

```
1. Acessa index.html
2. Navegador solicita localização → mapa centraliza
3. Vê marcadores de locais aprovados no mapa
4. Clica no marcador → popup com nome, nota e botão "Ver mais"
5. Clica "Ver mais" → local.html?id=X
6. Lê detalhes, vê avaliações existentes
7. Clica "Avaliar este local" → sistema redireciona para login.html (se não logado)
8. Faz login como Atleta → retorna para local.html?id=X
9. Seleciona 1 a 5 estrelas → digita comentário → clica "Enviar avaliação"
10. Avaliação aparece na lista em tempo real (sem reload)
```

### Fluxo B — Professor cadastra um local

```
1. Faz login como Professor em login.html
2. Redirecionado para index.html
3. Clica no próprio avatar/perfil → vai para perfil.html
4. Clica "Cadastrar novo local" → professor/cadastrar-local.html
5. Preenche formulário completo (endereço, modalidade, fotos, etc.)
6. Sistema geocodifica o endereço via Nominatim → preenche lat/lon automaticamente
7. Submete → mensagem "Aguardando aprovação"
```

### Fluxo C — Admin aprova o local

```
1. Faz login como Admin → redirecionado para admin.html
2. Aba "Locais Pendentes" mostra o novo local com badge "Novo"
3. Admin clica "Aprovar"
4. Local aparece imediatamente no mapa público (index.html)
5. Log registrado: "Local 'X' aprovado pelo Admin Y"
```

### Fluxo D — Developer monitora o sistema

```
1. Faz login como Desenvolvedor (pelo nome, não e-mail) → nova_senha.html (1º acesso)
2. Troca senha → redirecionado para dev.html
3. Visualiza todas as abas do Admin
4. Aba "Logs do Sistema": filtra por "CRÍTICO" → vê tentativas de acesso suspeitas
5. Clica em um log CRÍTICO → expande detalhes (IP, user_agent, ação)
6. Exporta logs como CSV para análise externa
```

---

## 8. INSTRUÇÕES PARA DISPATCH DE AGENTES — GOOGLE ANTIGRAVITY

### Configuração inicial (fazer ANTES de despachar agentes)
1. Clonar o repositório `github.com/nepotira/Aethos`
2. Configurar XAMPP: Apache + MySQL ativos
3. Executar o novo `backend/database.sql` completo no MySQL
4. Verificar que todos os arquivos existentes estão na estrutura correta

### Despacho de Agentes (executar em paralelo)

#### 🔧 AGENTE ALPHA — Bug Fixes + Segurança
Responsabilidade: Aplicar TODOS os patches do Módulo 1 nos arquivos existentes.
Entregável: Arquivos corrigidos sem regressão de funcionalidade existente.
Validação: Login com `senha123` direto deve falhar; marcador de localização deve aparecer no mapa; busca com Enter deve funcionar.

#### 🗄️ AGENTE BETA — Banco de Dados + Backend Core
Responsabilidade: Reescrever `database.sql` com as 5 tabelas completas + seeds. Criar `helpers.php`, `conexao.php` atualizado, `verificar_sessao.php`, `logout.php`.
Entregável: `database.sql` executável que cria tudo do zero. Endpoints de sessão funcionais.
Validação: Executar SQL do zero → zero erros; GET `verificar_sessao.php` sem sessão retorna `{ autenticado: false }`.

#### 📍 AGENTE GAMMA — Módulo de Locais
Responsabilidade: `backend/locais.php` (CRUD completo) + `backend/upload.php` + `js/mapa-locais.js` + `professor/cadastrar-local.html` + `local.html`.
Entregável: Professor consegue submeter um local; Admin aprova; local aparece no mapa como marcador; página de detalhe carrega dados reais do banco.
Validação: Criar local via formulário → aprovar via backend direto → verificar marcador no mapa; abrir `local.html?id=1` → ver dados reais.

#### ⭐ AGENTE DELTA — Sistema de Avaliações
Responsabilidade: `backend/avaliacoes.php` + `js/avaliacao.js` + integração na `local.html`.
Entregável: Componente de estrelas interativo; avaliação salva no banco e exibida em tempo real; impede avaliação duplicada; Admin pode remover avaliação.
Validação: Logar como Atleta → avaliar local → nota aparece na `local.html`; tentar avaliar novamente → sistema bloqueia.

#### 🛡️ AGENTE EPSILON — Painel Admin + Dev
Responsabilidade: `admin.html` + `dev.html` + todos os endpoints em `backend/admin/` e `backend/dev/`.
Entregável: Todas as abas funcionais com dados reais do banco. Logs exibindo entradas reais. Exportação CSV funcionando.
Validação: Logar como Admin → ver locais pendentes reais; logar como Dev → ver logs de login; filtrar por nível "ERRO" → retornar apenas erros.

#### 👤 AGENTE ZETA — Perfil + Redefinição de Senha
Responsabilidade: `perfil.html` + `esqueci-senha.html` + `redefinir-senha.html` + `backend/redefinir_senha.php` + PHPMailer integration.
Entregável: Usuário edita próprio perfil; altera foto via upload real; fluxo de redefinição por e-mail funciona end-to-end.
Validação: Alterar nome no perfil → ver nome atualizado no header; solicitar redefinição → receber e-mail com link válido; usar link → senha alterada com sucesso.

#### 🔎 AGENTE ETA — Validações + Segurança
Responsabilidade: CPF validation em `register.php`; DNS MX check em `register.php`; tokens CSRF em todos os formulários; rate limiting básico (máx 5 tentativas de login por 15 min por IP via tabela `logs_sistema`).
Entregável: CPF `111.111.111-11` rejeitado; domínio de e-mail inexistente rejeitado; 6ª tentativa de login bloqueada por 15 minutos.
Validação: Tentar cadastrar com CPF inválido → erro; tentar cadastrar com `test@dominioinexistente123xyz.com` → erro; 6 tentativas de login erradas → mensagem "Muitas tentativas, aguarde 15 minutos".

---

## 9. RELATÓRIO FINAL ESPERADO

Ao concluir todos os módulos, o Antigravity deve gerar um Artifact final contendo:

1. **Checklist de funcionalidades** — cada RF de RF01 a RF15 marcado como ✅ implementado ou explicado se algum não for possível
2. **Mapa de arquivos criados/modificados** — lista de todos os arquivos com data de modificação
3. **Instruções de setup** — passo a passo para rodar o sistema do zero (XAMPP + database.sql + configuração SMTP)
4. **Credenciais de teste** — logins prontos para testar cada perfil
5. **Pendências se houver** — qualquer item que precise de configuração manual (ex: Gmail App Password para SMTP)

---

*Documento gerado para o projeto Aethos — `github.com/nepotira/Aethos` · Versão de regimento: 1.0.0*
