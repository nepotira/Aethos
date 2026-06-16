# system_description.md — Descrição do Sistema Aethos

> [!IMPORTANT]
> **DIRETRIZ CRÍTICA DE EXECUÇÃO (OBRIGATÓRIO PARA A IA)**:
> Todo Agente de IA que acessar esta pasta **DEVE LER ESTE ARQUIVO ANTES DE QUALQUER AÇÃO**, pois ele descreve a visão, escopo, arquitetura e regras de negócio do sistema Aethos. Adicionalmente, **DEVE ATUALIZAR ESTE ARQUIVO AUTOMATICAMENTE** sempre que uma nova funcionalidade for descrita, implementada ou modificada — sem necessidade de solicitação do usuário.

---

## Visão Geral

**Nome do Sistema:** Aethos  
**Tipo:** Aplicação Web Mobile-First  
**Domínio:** Plataforma de encontro e descoberta de locais esportivos  
**Stack Principal:** HTML5 · CSS · Tailwind CSS · JavaScript · jQuery · PHP 5.4.17+ (Compatível / Polyfilled) · MySQL 5.6+ · Leaflet.js  
**Repositório:** [github.com/nepotira/Aethos](https://github.com/nepotira/Aethos)  
**Identidade Visual:** Ver `E:\Desktop\NEPO\PROGRAMACAO\DSI\LaunchPad-Project\aethos_sports_landing_page`

---

## 1. Propósito do Sistema

O **Aethos** é uma plataforma web que conecta pessoas que buscam locais para praticar esportes com professores e estabelecimentos que oferecem esses espaços. Funciona como um **mapa interativo e social**, onde:

- **Atletas e usuários comuns** exploram o mapa para encontrar academias, dojos, campos, quadras e espaços esportivos próximos à sua localização, leem avaliações e entram em contato com professores.
- **Professores e donos de locais** cadastram seus estabelecimentos e aguardam aprovação de um Administrador para que seu ponto apareça visível no mapa público.
- **Administradores** gerenciam aprovações de locais, usuários e comentários do sistema.
- **Desenvolvedores** têm acesso total ao sistema, incluindo painel de logs técnicos e todas as funções administrativas.

A filosofia do sistema é **"sem fricção"**: qualquer pessoa com um navegador moderno pode acessar a plataforma, sem necessidade de instalar aplicativos, em qualquer dispositivo (celular, tablet ou computador), pois a interface é construída em abordagem **mobile-first** com adaptação responsiva para desktop.

---

## 2. Identidade Visual

| Elemento | Especificação |
|---|---|
| **Cor Principal (70%)** | `#0D0F32` — Azul-marinho escuro (fundos e estrutura) |
| **Cor de Acento (20%)** | `#A5B4FC` — Lavanda suave (botões, destaques, links) |
| **Cor de Destaque (10%)** | `#FFFFFF` — Branco puro (textos sobre fundos escuros) |
| **Fonte Primária** | Plus Jakarta Sans (títulos e headings) |
| **Fonte Secundária** | Inter (corpo do texto e UI) |
| **Fonte de Display** | Syafixy (logotipo e elementos de marca) |
| **Estilo Visual** | Glassmorfismo *Liquid Glass* — camadas translúcidas com blur |

A regra 70-20-10 de distribuição de cores garante consistência visual: o azul escuro domina os fundos e estruturas, o lavanda aparece em elementos de ação e foco, e o branco reserva-se a textos de alta legibilidade.

---

## 3. Arquitetura do Sistema

```
┌─────────────────────────────────────────────────────────┐
│                    CLIENTE (Navegador)                   │
│                                                         │
│  ┌─────────────┐  ┌─────────────┐  ┌────────────────┐  │
│  │  index.html │  │ login.html  │  │ nova_senha.html│  │
│  │  (Mapa)     │  │ (Auth UI)   │  │ (Troca Senha)  │  │
│  └──────┬──────┘  └──────┬──────┘  └───────┬────────┘  │
│         │                │                  │           │
│  ┌──────▼──────┐  ┌──────▼──────┐           │           │
│  │   mapa.js   │  │   auth.js   │           │           │
│  │ (Leaflet +  │  │ (jQuery +   │           │           │
│  │  Nominatim) │  │   AJAX)     │           │           │
│  └─────────────┘  └──────┬──────┘           │           │
│                          │  AJAX POST        │           │
└──────────────────────────┼───────────────────┼───────────┘
                           │ HTTP              │
┌──────────────────────────▼───────────────────▼───────────┐
│                  SERVIDOR (PHP + XAMPP)                   │
│                                                          │
│  ┌────────────┐  ┌─────────────┐  ┌──────────────────┐  │
│  │  login.php │  │register.php │  │ trocar_senha.php  │  │
│  │            │  │             │  │                   │  │
│  └──────┬─────┘  └──────┬──────┘  └────────┬──────────┘  │
│         └───────────────┼──────────────────┘            │
│                         │ require_once                   │
│                  ┌──────▼──────┐                         │
│                  │ conexao.php │                         │
│                  │  (PDO)      │                         │
│                  └──────┬──────┘                         │
└─────────────────────────┼────────────────────────────────┘
                          │ MySQL PDO
┌─────────────────────────▼────────────────────────────────┐
│                  BANCO DE DADOS (MySQL)                   │
│                    aethos_db                              │
│                                                          │
│  ┌────────────────────────────────────────────────────┐  │
│  │                    usuarios                         │  │
│  │  id · tipo_usuario · nome · email · senha · ddd    │  │
│  │  telefone · foto_perfil · cpf · endereco_fixo      │  │
│  │  aprovado_admin · primeiro_acesso · criado_em      │  │
│  └────────────────────────────────────────────────────┘  │
│                                                          │
│  (Tabelas futuras: locais_esportivos, avaliacoes, logs)  │
└──────────────────────────────────────────────────────────┘
```

**Padrão arquitetural:** Cliente-Servidor clássico com comunicação via AJAX (JSON), sem framework de SPA. O frontend é multi-page application (MPA) com arquivos HTML independentes por funcionalidade.

---

## 4. Perfis de Usuário

O sistema possui **quatro perfis distintos**, cada um com permissões e fluxo de acesso diferente:

### 4.1 — Atleta / Usuário Comum

**Quem é:** Qualquer pessoa que acessa a plataforma para encontrar locais esportivos.

**Funcionalidades:**
- Explorar o mapa de locais esportivos
- Buscar locais específicos por nome ou endereço
- Visualizar detalhes de locais e perfis de professores
- Avaliar locais e professores (como sistema de estrelas, similar ao Uber)
- Necessita de login para avaliar

**Dados de cadastro exigidos:**
- Foto de perfil
- Nome completo
- Apelido (opcional)
- Número de telefone + DDD (ex: 13 → Baixada Santista)
- E-mail (verificado)
- Senha (com toggle ver/ocultar)

---

### 4.2 — Professor / Usuário-Professor

**Quem é:** Profissional de esportes ou dono de estabelecimento esportivo que quer divulgar seu local na plataforma.

**Funcionalidades:**
- Todas as funcionalidades do Atleta
- Submeter formulário completo de cadastro de local/estabelecimento
- Ter seu local exibido no mapa **após aprovação do Administrador**
- Gerenciar informações do seu local

**Dados de cadastro adicionais (além do Atleta):**
- Endereço fixo do estabelecimento
- CPF (com verificação de autenticidade)

**Regra de negócio:** O local de um Professor só aparece no mapa público após `aprovado_admin = TRUE` no banco de dados.

---

### 4.3 — Administrador

**Quem é:** Responsável pela gestão operacional da plataforma Aethos.

**Funcionalidades:**
- Aprovar ou rejeitar cadastros de locais de Professores
- Excluir usuários e Professores da plataforma
- Remover avaliações e comentários inadequados
- Acessar banco de dados completo para gerenciamento
- Todas as funções do Administrador são acessíveis via painel interno

**Forma de acesso:**
- **Não há cadastro público.** O Administrador acessa via credenciais pré-configuradas no banco de dados.
- No **primeiro login**, o sistema obriga a troca de senha (fluxo `nova_senha.html`).
- Login identificado pelo e-mail + perfil "Admin" selecionado na tela de login.

---

### 4.4 — Desenvolvedor

**Quem é:** Membro da equipe técnica responsável pelo desenvolvimento e manutenção do sistema Aethos.

**Funcionalidades:**
- Todas as funcionalidades do Administrador
- Painel de Desenvolvedor com logs técnicos do sistema
- Visualização de erros e descrições técnicas de falhas
- Acesso completo ao banco de dados

**Forma de acesso:**
- **Não há cadastro público.** Os desenvolvedores são pré-cadastrados no banco de dados via seed SQL.
- Login identificado pelo **nome do desenvolvedor** (não e-mail), conforme a lista pré-definida:
  - Lorrany, Arthur, Nepo, Leo, Joaquim
- **Senha padrão inicial:** `senha123`
- No **primeiro login**, o sistema obriga a troca de senha.

---

## 5. Fluxos Principais do Sistema

### 5.1 — Fluxo de Acesso ao Mapa (Usuário Não Autenticado)

```
1. Usuário acessa index.html
2. Navegador solicita permissão de geolocalização
3. Se aprovada → mapa centraliza na localização do usuário
4. Se negada → mapa exibe localização padrão (Brasília-DF)
5. Usuário pode buscar locais via barra de busca (Nominatim API)
6. Locais cadastrados e aprovados aparecem como marcadores no mapa
```

### 5.2 — Fluxo de Login

```
1. Usuário acessa login.html
2. Seleciona seu perfil (Atleta | Professor | Admin | Desenvolvedor)
3. Preenche credenciais (e-mail ou nome, dependendo do perfil)
4. auth.js envia POST AJAX para backend/login.php
5. login.php valida credenciais via PDO + password_verify()
6. Se válido:
   ├── Admin/Dev + primeiro_acesso = 1 → redireciona para nova_senha.html
   └── Demais casos → redireciona para index.html
7. Se inválido: exibe mensagem de erro via showFeedbackMessage()
```

### 5.3 — Fluxo de Troca de Senha Obrigatória (Admin/Dev)

```
1. Usuário é redirecionado para nova_senha.html após primeiro login
2. Preenche nova senha (mínimo 5 caracteres)
3. Formulário envia POST AJAX para backend/trocar_senha.php
4. trocar_senha.php valida sessão PHP ativa
5. Hash BCRYPT é gerado e salvo; primeiro_acesso é marcado como 0
6. Sistema redireciona para index.html
```

### 5.4 — Fluxo de Cadastro de Usuário/Professor

```
1. Usuário acessa login.html?tab=register (ou clica em "Cadastrar")
2. Seleciona perfil (Atleta ou Professor — Admin/Dev não têm cadastro público)
3. Preenche campos comuns (nome, e-mail, DDD, telefone, senha)
4. Se Professor: campos adicionais de CPF e Endereço Fixo aparecem via slideDown()
5. auth.js envia POST AJAX para backend/register.php
6. register.php criptografa senha (BCRYPT) e insere no banco
7. Para Professor: aprovado_admin = FALSE até aprovação manual
8. Mensagem de sucesso é exibida; sistema redireciona para aba de login
```

---

## 6. Componentes e Arquivos do Sistema

| Arquivo | Tipo | Descrição |
|---|---|---|
| [`index.html`](file:///e:/Desktop/NEPO/PROGRAMACAO/DSI/AETHOS/index.html) | HTML | Página principal com mapa interativo Leaflet + barra de busca |
| [`login.html`](file:///e:/Desktop/NEPO/PROGRAMACAO/DSI/AETHOS/login.html) | HTML | Tela unificada de Login e Cadastro com seletor de perfil |
| [`nova_senha.html`](file:///e:/Desktop/NEPO/PROGRAMACAO/DSI/AETHOS/nova_senha.html) | HTML | Tela de troca de senha obrigatória (1º acesso Admin/Dev) |
| [`css/style.css`](file:///e:/Desktop/NEPO/PROGRAMACAO/DSI/AETHOS/css/style.css) | CSS | Estilos globais base da aplicação |
| [`js/mapa.js`](file:///e:/Desktop/NEPO/PROGRAMACAO/DSI/AETHOS/js/mapa.js) | JavaScript | Geolocalização, renderização do mapa e sistema de busca/autocomplete |
| [`js/auth.js`](file:///e:/Desktop/NEPO/PROGRAMACAO/DSI/AETHOS/js/auth.js) | JavaScript | Controle de abas, seletor de perfil, toggle de senha e AJAX |
| [`backend/conexao.php`](file:///e:/Desktop/NEPO/PROGRAMACAO/DSI/AETHOS/backend/conexao.php) | PHP | Conexão PDO com MySQL — importado por todos os endpoints |
| [`backend/login.php`](file:///e:/Desktop/NEPO/PROGRAMACAO/DSI/AETHOS/backend/login.php) | PHP | Endpoint de autenticação; inicia sessão PHP |
| [`backend/register.php`](file:///e:/Desktop/NEPO/PROGRAMACAO/DSI/AETHOS/backend/register.php) | PHP | Endpoint de cadastro; criptografa senha e insere no banco |
| [`backend/trocar_senha.php`](file:///e:/Desktop/NEPO/PROGRAMACAO/DSI/AETHOS/backend/trocar_senha.php) | PHP | Endpoint de troca de senha; valida sessão e atualiza banco |
| [`backend/database.sql`](file:///e:/Desktop/NEPO/PROGRAMACAO/DSI/AETHOS/backend/database.sql) | SQL | Schema do banco `aethos_db` e seed de usuários iniciais |

### Arquivos de Documentação Técnica

| Arquivo | Descrição |
|---|---|
| [`agent_memory.md`](file:///e:/Desktop/NEPO/PROGRAMACAO/DSI/AETHOS/agent_memory.md) | Diário de bordo do Agente de IA: histórico de prompts e ações |
| [`dev_log.md`](file:///e:/Desktop/NEPO/PROGRAMACAO/DSI/AETHOS/dev_log.md) | Log técnico de desenvolvimento: funções implementadas com código |
| [`system_description.md`](file:///e:/Desktop/NEPO/PROGRAMACAO/DSI/AETHOS/system_description.md) | Este arquivo: descrição completa do sistema |
| [`project_description.md`](file:///e:/Desktop/NEPO/PROGRAMACAO/DSI/AETHOS/project_description.md) | Escopo original do projeto (fonte primária de requisitos) |
| [`projeto_de_software.md`](file:///e:/Desktop/NEPO/PROGRAMACAO/DSI/AETHOS/projeto_de_software.md) | Documentação acadêmica DSI (Seções 5.x) |
| [`projeto_de_software.html`](file:///e:/Desktop/NEPO/PROGRAMACAO/DSI/AETHOS/projeto_de_software.html) | Versão interativa HTML da documentação com diagramas Mermaid |
| [`projeto_de_software_word.html`](file:///e:/Desktop/NEPO/PROGRAMACAO/DSI/AETHOS/projeto_de_software_word.html) | Versão A4 exportável para Word (.doc) |
| [`viabilidade_tecnica_word.html`](file:///e:/Desktop/NEPO/PROGRAMACAO/DSI/AETHOS/viabilidade_tecnica_word.html) | Seção 6.2 – Viabilidade Técnica com tabela de tecnologias candidatas |
| [`cronograma_word.html`](file:///e:/Desktop/NEPO/PROGRAMACAO/DSI/AETHOS/cronograma_word.html) | Seção 6.6 – Cronograma de Atividades com Gráfico de Gantt |

---

## 7. Bibliotecas e Tecnologias Externas

| Tecnologia | Versão | Finalidade | Fonte |
|---|---|---|---|
| Leaflet.js | 1.9.4 | Mapa interativo (renderização e controle) | CDN unpkg.com |
| OpenStreetMap | — | Provedor de tiles do mapa (gratuito) | tile.openstreetmap.org |
| Nominatim API | — | Geocodificação: busca de endereços e autocomplete | nominatim.openstreetmap.org |
| Bootstrap | 5.3.3 | Grid, componentes e utilitários CSS | CDN jsdelivr.net |
| Tailwind CSS | 3.x | Sistema de utilitários CSS na tela de login | CDN tailwindcss.com |
| jQuery | 3.7.1 | Manipulação DOM, AJAX e animações | CDN code.jquery.com |
| FontAwesome | 6.4.0 | Ícones (olho de senha, ícones de UI) | CDN cdnjs.cloudflare.com |
| Mermaid.js | — | Renderização de diagramas UML na documentação | CDN |
| PHP | 5.4.17+ | Linguagem de backend / processamento server-side (com polyfills de compatibilidade) | USBWebserver / XAMPP local |
| MySQL | 5.6.13+ | Sistema de gerenciamento de banco de dados | USBWebserver / XAMPP local |
| PDO | — | Abstração de acesso ao banco de dados em PHP | Nativo PHP |

---

## 8. Segurança

| Mecanismo | Implementação | Arquivo |
|---|---|---|
| Hash de senha | BCRYPT via `password_hash()` e `password_verify()` | `register.php`, `login.php` |
| Prevenção de SQL Injection | Prepared Statements via PDO com `bindParam()` | Todos os arquivos PHP de backend |
| Controle de sessão | `session_start()` + validação de `$_SESSION['usuario_id']` | `login.php`, `trocar_senha.php` |
| Charset seguro | `utf8mb4` na conexão PDO (previne ataques de encoding) | `conexao.php` |
| Separação Admin/Dev | Perfis privilegiados sem cadastro público — apenas via seed SQL | `database.sql` |

---

## 9. Banco de Dados — Estrutura Atual

### Tabela `usuarios`

| Coluna | Tipo | Descrição |
|---|---|---|
| `id` | INT AUTO_INCREMENT PK | Identificador único |
| `tipo_usuario` | ENUM | `comum`, `professor`, `admin`, `desenvolvedor` |
| `nome` | VARCHAR(255) | Nome completo |
| `apelido` | VARCHAR(100) | Apelido (opcional) |
| `email` | VARCHAR(191) UNIQUE | E-mail de login (único - ajustado para evitar limite de index no MySQL 5.6) |
| `senha` | VARCHAR(255) | Hash BCRYPT da senha |
| `ddd` | VARCHAR(3) | DDD do telefone |
| `telefone` | VARCHAR(20) | Número de telefone |
| `foto_perfil` | VARCHAR(255) | Caminho/URL da foto |
| `cpf` | VARCHAR(14) UNIQUE | CPF (exclusivo para Professor) |
| `endereco_fixo` | TEXT | Endereço do estabelecimento (exclusivo para Professor) |
| `aprovado_admin` | BOOLEAN | `FALSE` até o Admin aprovar (Professor) |
| `primeiro_acesso` | BOOLEAN | `TRUE` = obriga troca de senha (Admin/Dev) |
| `criado_em` | TIMESTAMP | Data/hora de criação do registro |

### Tabelas Planejadas (Futuras)

| Tabela | Finalidade |
|---|---|
| `locais_esportivos` | Dados completos dos locais cadastrados por Professores |
| `avaliacoes` | Sistema de avaliações (estrelas + comentário) por Atletas |
| `logs_sistema` | Logs técnicos de ações para o painel do Desenvolvedor |

---

## 10. Requisitos Funcionais (Resumo)

| Código | Descrição | Status |
|---|---|---|
| RF01 | Sistema de mapa interativo com geolocalização | ✅ Implementado |
| RF02 | Busca de locais por nome/endereço com autocomplete | ✅ Implementado |
| RF03 | Sistema de login por e-mail/senha com seletor de perfil | ✅ Implementado |
| RF04 | Cadastro de Atleta com validação de campos | ✅ Implementado |
| RF05 | Cadastro de Professor com CPF e endereço | ✅ Implementado |
| RF06 | Troca de senha obrigatória no primeiro acesso (Admin/Dev) | ✅ Implementado |
| RF07 | Toggle ver/ocultar senha com ícone de olho | ✅ Implementado |
| RF08 | Abertura direta da aba Cadastro via URL `?tab=register` | ✅ Implementado |
| RF09 | Login de Desenvolvedor por nome (não e-mail) | ✅ Implementado |
| RF10 | Fluxo de aprovação de locais (Professor → Admin) | 🔄 Parcial (banco pronto, painel Admin pendente) |
| RF11 | Sistema de avaliações de locais e professores | ❌ Pendente |
| RF12 | Verificação de e-mail no cadastro | ❌ Pendente |
| RF13 | Verificação de CPF real | ❌ Pendente |
| RF14 | Painel do Desenvolvedor com logs técnicos | ❌ Pendente |
| RF15 | Login social (Google) | ❌ Pendente (botão UI criado, lógica não implementada) |

---

## 11. Requisitos Não Funcionais

| Código | Categoria | Descrição |
|---|---|---|
| RNF01 | Usabilidade | Interface mobile-first responsiva para qualquer dispositivo |
| RNF02 | Segurança | Senhas armazenadas exclusivamente em hash BCRYPT |
| RNF03 | Segurança | Proteção contra SQL Injection via Prepared Statements |
| RNF04 | Performance | Autocomplete com debounce para não sobrecarregar a API |
| RNF05 | Compatibilidade | Funciona em navegadores modernos sem instalação |
| RNF06 | Manutenibilidade | Backend modularizado com ponto único de conexão (`conexao.php`) |
| RNF07 | Conformidade | Dados de usuários gerenciáveis (LGPD) |
| RNF08 | Disponibilidade | Sistema funcional sem dependência de lojas de aplicativo |
| RNF09 | Escalabilidade | Arquitetura cliente-servidor permite expansão de funcionalidades |
| RNF10 | Acessibilidade | Labels semânticos e atributos de acessibilidade nos formulários |

---

## 12. Roadmap de Desenvolvimento

### Fase 1 — Concluída ✅
- Estrutura de pastas e arquivos base
- Mapa interativo com geolocalização e busca
- Sistema completo de autenticação (login, cadastro, troca de senha)
- Banco de dados com schema e seed inicial
- Documentação técnica completa (HTML interativo + Word/A4)

### Fase 2 — Em Desenvolvimento 🔄
- Painel Administrativo (aprovação de locais, gestão de usuários)
- Cadastro completo de Locais Esportivos (formulário do Professor)
- Exibição de marcadores de locais aprovados no mapa

### Fase 3 — Planejada 📋
- Sistema de avaliações e comentários
- Verificação de e-mail no cadastro
- Verificação de CPF real
- Login social (Google OAuth)
- Painel do Desenvolvedor com logs

### Fase 4 — Visão de Futuro 🔭
- Sistema de busca avançada por modalidade esportiva, preço e avaliação
- Chat/mensagens entre Atleta e Professor
- Intranet administrativa (localhost)
- Integração com sistema de pagamentos

---

*Este arquivo é mantido automaticamente pelo Agente de IA. Última atualização: 16/06/2026.*
