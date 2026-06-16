# 🤖 Prompt para Google Antigravity — Teste de Bugs · Sistema Aethos

> Cole este prompt diretamente no Google Antigravity (Manager View ou chat principal) apontando para o repositório do projeto em `github.com/nepotira/Aethos`.

---

## CONTEXTO DO SISTEMA

Você está atuando como **QA Engineer sênior e Bug Hunter** em um projeto web chamado **Aethos** — uma plataforma mobile-first de localização de espaços esportivos.

### Stack Tecnológica
- **Frontend:** HTML5 · Tailwind CSS · jQuery 3.7.1 · Bootstrap 5.3.3 · FontAwesome 6.4.0
- **Mapa:** Leaflet.js 1.9.4 · OpenStreetMap (tiles) · Nominatim API (geocodificação)
- **Backend:** PHP 8.x (sem framework) · PDO MySQL · Sessões nativas PHP
- **Banco:** MySQL (aethos_db) · Schema polimórfico (tabela única `usuarios`)
- **Servidor:** XAMPP local (Apache + MySQL)

### Arquitetura de Arquivos

```
AETHOS/
├── index.html              ← Mapa interativo (Leaflet)
├── login.html              ← Login + Cadastro (unificados)
├── nova_senha.html         ← Troca obrigatória de senha (1º acesso)
├── css/style.css           ← Estilos globais
├── js/
│   ├── mapa.js             ← Geolocalização, busca, autocomplete
│   └── auth.js             ← AJAX, abas, seletor de perfil
└── backend/
    ├── conexao.php         ← Singleton PDO
    ├── login.php           ← Autenticação + sessão PHP
    ├── register.php        ← Cadastro com BCRYPT
    ├── trocar_senha.php    ← Troca de senha com validação de sessão
    └── database.sql        ← Schema + seed de usuários
```

### Perfis de Usuário e Regras de Acesso

| Perfil        | Identificação no Login | Senha Padrão | Observação                                   |
|---------------|------------------------|--------------|----------------------------------------------|
| Atleta        | E-mail                 | Cadastrada   | Cadastro público                             |
| Professor     | E-mail                 | Cadastrada   | Cadastro público; local aprovado pelo Admin  |
| Administrador | E-mail (admin@...)     | Única (DB)   | Sem cadastro público; troca obrigatória 1º acesso |
| Desenvolvedor | **Nome** (não e-mail!) | `senha123`   | Lista: Lorrany, Arthur, Nepo, Leo, Joaquim   |

---

## MISSÃO PRINCIPAL

**Realize uma auditoria completa de bugs e vulnerabilidades no sistema Aethos.** Use agentes paralelos para cobrir todas as áreas do sistema simultaneamente. Para cada bug encontrado, gere um Artifact com: descrição, arquivo afetado, linha de código, severidade (Crítico / Alto / Médio / Baixo) e sugestão de correção.

---

## ÁREAS DE TESTE — DISPATCHE AGENTES PARA CADA BLOCO

### 🗺️ AGENTE 1 — Módulo de Mapa (`js/mapa.js` + `index.html`)

Verifique e teste os seguintes pontos:

1. **Bug suspeito de alta prioridade — Marcador de geolocalização:**
   Analise a função `sucesso(position)`. O código atual faz:
   ```javascript
   map.setView([latitude, longitude]).addTo(map).bindPopup("Você está aqui").openPopup();
   ```
   `map.setView()` retorna o objeto `map`, não um `L.Marker`. Chamar `.addTo(map)` no próprio mapa e depois `.bindPopup()` é semanticamente incorreto — o popup é vinculado ao mapa, não a um marcador. **Confirme se nenhum marcador "Você está aqui" aparece no mapa após a geolocalização.**

2. **Debounce do autocomplete configurado com delay 0ms:**
   ```javascript
   tempoEspera = setTimeout(() => buscarSugestoesAPI(evento.target.value.trim()), 0);
   ```
   Delay zero significa uma requisição HTTP ao Nominatim em cada keystroke, violando os [Termos de Uso da API Nominatim](https://operations.osmfoundation.org/policies/nominatim/) e sobrecarregando a rede. O correto é 300–500ms. **Confirme se ocorre flood de requisições.**

3. **Remoção de marcador anterior:** Verifique se `marcacaoAtual` realmente remove o marcador antigo antes de adicionar o novo. Teste clicando em dois locais distintos na busca e verifique se o marcador anterior persiste.

4. **Fallback de geolocalização negada:** Quando o usuário nega a permissão de localização, o sistema cai para Brasília-DF. Confirme se o alerta `"Não foi possível obter sua localização"` é exibido e o mapa é centralizado corretamente.

5. **Busca sem resultados:** Insira uma string aleatória na busca (ex: `xyzabc123nonexistent`). Verifique se o sistema trata o array vazio de resultados sem quebrar com erro JavaScript.

6. **Tecla Enter na busca:** Confirme se pressionar Enter no campo de busca aciona a pesquisa (além do clique no botão).

---

### 🔐 AGENTE 2 — Sistema de Autenticação (`login.html` + `js/auth.js`)

1. **Teste o fluxo de login para TODOS os 4 perfis:**
   - Atleta: e-mail + senha válidos
   - Professor: e-mail + senha válidos (com `aprovado_admin = TRUE` no banco)
   - Admin: e-mail + senha do seed
   - Desenvolvedor: **nome** (ex: `Nepo`) + `senha123` — o campo de e-mail deve mudar para campo de nome

2. **Toggle de senha:** Clique no ícone de olho em todos os campos de senha. Verifique se o tipo do input alterna entre `password` e `text`, e se o ícone troca entre `fa-eye` e `fa-eye-slash` corretamente.

3. **Duplo envio do formulário:** Clique rápido 2x no botão "Entrar". Confirme se o botão é desabilitado na primeira submissão para evitar chamadas AJAX duplicadas.

4. **Credenciais inválidas:** Tente logar com e-mail inexistente e senha errada. Verifique se a função `showFeedbackMessage()` exibe a mensagem de erro e some após 5 segundos.

5. **Deep link `?tab=register`:** Acesse `login.html?tab=register` diretamente. Confirme se a aba de Cadastro é aberta automaticamente.

6. **Campos condicionais do Professor:** No Cadastro, selecione o perfil "Professor". Verifique se os campos de CPF e Endereço Fixo aparecem via `slideDown()`. Troque para "Atleta" e confirme se somem.

7. **Admin e Dev não aparecem no Cadastro:** Confirme que os botões de perfil "Admin" e "Desenvolvedor" são ocultados (`.login-only`) na aba de Cadastro.

8. **Resposta JSON do backend:** Abra o DevTools (Network), faça login válido e inválido, e inspecione a resposta de `backend/login.php`. Confirme que retorna JSON (`Content-Type: application/json`) em ambos os casos.

---

### 🔑 AGENTE 3 — Fluxo de Primeiro Acesso (`nova_senha.html` + `backend/trocar_senha.php`)

1. **Redirecionamento obrigatório:** Faça login com um Desenvolvedor que tenha `primeiro_acesso = 1` no banco. Confirme se o sistema redireciona para `nova_senha.html` (não para `index.html`).

2. **Proteção de rota:** Acesse `nova_senha.html` diretamente no browser **sem estar logado** (sem sessão PHP ativa). O backend deve retornar `{ "sucesso": false, "mensagem": "Acesso Negado!" }` e impedir o acesso.

3. **Validação de tamanho mínimo:** Tente salvar uma senha com menos de 5 caracteres. O backend valida `strlen($nova_senha) >= 5`. Confirme se a requisição AJAX é rejeitada com mensagem de erro.

4. **Atualização do `primeiro_acesso`:** Após trocar a senha com sucesso, verifique no banco de dados MySQL se `primeiro_acesso` foi atualizado para `0` para o usuário em questão.

5. **Segundo login após troca:** Após trocar a senha, faça logout e login novamente. Confirme que o sistema redireciona para `index.html` e **não** para `nova_senha.html`.

---

### 🛡️ AGENTE 4 — Segurança e Backend PHP

1. **SQL Injection:** Tente injetar no campo de e-mail do login: `' OR '1'='1` e `'; DROP TABLE usuarios;--`. Os Prepared Statements do PDO devem bloquear qualquer manipulação. Confirme que nenhuma resposta anômala ocorre.

2. **Exposição de erros técnicos:** Force um erro de conexão ao banco (ex: modificando temporariamente `database.sql` com nome de banco errado). Verifique se `conexao.php` retorna apenas a mensagem genérica `"Erro crítico: Falha ao conectar com o banco de dados."` sem expor o stack trace para o cliente.

3. **E-mail ou CPF duplicado:** Tente cadastrar dois usuários com o mesmo e-mail. O banco tem constraint `UNIQUE` no campo `email`. Confirme se a resposta retorna `"O E-mail ou CPF já está cadastrado."` (SQLSTATE 23000) sem 500 Internal Server Error.

4. **Content-Type dos endpoints:** Confirme que todos os endpoints PHP (`login.php`, `register.php`, `trocar_senha.php`) retornam `Content-Type: application/json`. Se algum retornar `text/html`, o `$.ajax` com `dataType: 'json'` pode falhar silenciosamente.

5. **Session fixation:** Verifique se um novo ID de sessão PHP é gerado após o login bem-sucedido para prevenir session fixation attacks.

6. **Senhas em texto puro nos logs:** Confirme que nenhum arquivo de log do servidor (Apache/PHP) registra a senha enviada nos requests POST.

---

### 🧩 AGENTE 5 — Funcionalidades Pendentes e Edge Cases

Com base no roadmap documentado, as seguintes funcionalidades estão **pendentes ou parcialmente implementadas**. Confirme seu estado atual e documente o comportamento atual vs. esperado:

| RF   | Funcionalidade                    | Status Documentado | Teste a Realizar                                                  |
|------|-----------------------------------|--------------------|------------------------------------------------------------------|
| RF10 | Aprovação de locais (Admin)       | 🔄 Parcial          | Existe painel Admin? O campo `aprovado_admin` funciona no banco? |
| RF11 | Sistema de avaliações             | ❌ Pendente         | Existe rota ou UI para avaliações? O botão aparece logado?       |
| RF12 | Verificação de e-mail no cadastro | ❌ Pendente         | E-mails inválidos como `abc@notreal.xyz` são aceitos?            |
| RF13 | Verificação de CPF real           | ❌ Pendente         | CPFs inválidos como `111.111.111-11` são aceitos no cadastro?    |
| RF14 | Painel do Desenvolvedor (logs)    | ❌ Pendente         | Existe página de logs? O Dev é redirecionado para algum painel?  |
| RF15 | Login Social Google (OAuth)       | ❌ Pendente         | O botão Google existe na UI? O que acontece ao clicar?           |

---

### 🌐 AGENTE 6 — Browser Agent (Teste Visual e Responsivo)

Use o **Browser Subagent** do Antigravity para executar testes visuais automatizados:

1. **Mobile-first:** Acesse `index.html` e `login.html` em viewport de 375px (iPhone SE). Capture screenshots e confirme que nenhum elemento quebra o layout.

2. **Cross-browser:** Teste nos 4 navegadores alvo (Chrome, Firefox, Safari, Edge). Foque nos elementos de glassmorfismo (`backdrop-filter: blur(12px)`) — Safari Mobile tem suporte limitado a esse CSS.

3. **Acessibilidade básica:** Verifique se todos os campos de formulário têm `<label>` associado ou `aria-label`. Confirme que o foco por teclado (Tab) percorre os campos na ordem correta.

4. **Paleta de cores e contraste:** Confirme que o texto `#FFFFFF` sobre fundo `#0D0F32` passa a WCAG AA (ratio mínimo 4.5:1 para texto normal).

5. **Loading states:** Ao clicar em "Entrar", o botão deve mudar para `"Validando..."` e ficar desabilitado. Meça o tempo de resposta do AJAX e confirme o feedback visual durante a espera.

---

## INSTRUÇÕES FINAIS PARA O ANTIGRAVITY

Ao concluir todos os testes, gere um **Relatório Final consolidado** como Artifact com:

1. **Sumário executivo** — quantos bugs Críticos, Altos, Médios e Baixos foram encontrados
2. **Tabela de bugs** — ID, módulo, descrição, arquivo:linha, severidade
3. **Top 3 bugs mais urgentes** com patch de código sugerido pronto para aplicar
4. **Gaps de funcionalidade** — lista das features pendentes com sugestão de implementação priorizada
5. **Checklist de segurança** — o que passou e o que falhou

> **Nota para o Agente:** O sistema roda localmente via XAMPP. Para testar o backend, inicie o servidor Apache e MySQL antes de executar os testes de endpoint. O banco de dados `aethos_db` deve ser criado executando o arquivo `backend/database.sql`.
