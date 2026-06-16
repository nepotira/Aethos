# 📋 Patches para Arquivos HTML — Sistema Aethos

Aplicar manualmente nos arquivos `.html` do projeto.
Cada patch indica o **trecho original** e o **trecho correto**.

---

## `login.html` — 3 patches

---

### PATCH 1 — BUG-06 🟠 ALTO: Adicionar campo "Confirmar Senha" no cadastro

**Onde:** Logo após o bloco do campo `id="reg-senha"` no `#form-register`.

**Adicionar este bloco HTML:**

```html
<!-- PATCH BUG-06: Campo de confirmação de senha — INSERIR APÓS O CAMPO reg-senha -->
<div>
    <label class="block text-sm font-medium text-gray-700 mb-1">
        Confirmar Senha
    </label>
    <div class="relative">
        <input
            type="password"
            id="reg-senha-confirm"
            class="w-full border border-gray-300 rounded-lg px-4 py-2 pr-10 focus:ring-2 focus:ring-blue-500 outline-none"
            placeholder="Repita sua senha"
            required
        >
        <i class="fa fa-eye-slash absolute right-3 top-3 text-gray-400 cursor-pointer toggle-password"
           data-target="reg-senha-confirm"></i>
    </div>
</div>
```

**Adicionar no `auth.js`, dentro do submit handler do `#form-register`, ANTES do $.ajax:**

```javascript
// PATCH BUG-06: Validação de confirmação de senha
const s1 = $('#reg-senha').val();
const s2 = $('#reg-senha-confirm').val();
if (s1 !== s2) {
    showFeedbackMessage('As senhas não coincidem!', false);
    btn.text(oldText).prop('disabled', false);
    return;
}
```

---

### PATCH 2 — BUG-04 🟡 MÉDIO: Desabilitar botão "Entrar com Google" até RF15 ser implementado

**Localizar o botão Google no `login.html` (por volta da linha 81) e substituir por:**

```html
<!-- PATCH BUG-04: Botão Google desabilitado até implementação de OAuth (RF15) -->
<button
    type="button"
    disabled
    title="Login com Google — Em breve!"
    class="w-full flex items-center justify-center gap-2 border border-gray-300 rounded-lg py-2 px-4
           text-gray-400 bg-gray-50 cursor-not-allowed opacity-60 select-none"
>
    <img src="https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg"
         class="w-5 h-5 opacity-50" alt="Google">
    Entrar com Google <span class="text-xs">(em breve)</span>
</button>
```

---

### PATCH 3 — BUG-17 🟢 BAIXO: Adicionar placeholder no campo Nome do cadastro

**Localizar o `<input id="reg-nome">` e adicionar o atributo `placeholder`:**

```html
<!-- ANTES: -->
<input type="text" id="reg-nome" class="..." required>

<!-- DEPOIS (PATCH BUG-17): -->
<input type="text" id="reg-nome" class="..." placeholder="Seu nome completo" required>
```

---

## `nova_senha.html` — 2 patches

---

### PATCH 4 — BUG-09 🟡 MÉDIO: Verificar sessão ativa ao carregar a página

**Adicionar no `<script>` de `nova_senha.html`, como PRIMEIRO bloco executado (antes do listener de submit):**

```javascript
// PATCH BUG-09: Verificar sessão ao carregar — redireciona se não autenticado
$(document).ready(function() {

    $.ajax({
        url: 'backend/verificar_sessao.php',
        type: 'GET',
        dataType: 'json',
        success: function(res) {
            if (!res.autenticado) {
                // Sessão inativa: redireciona imediatamente para o login
                window.location.href = 'login.html';
            }
        },
        error: function() {
            // Erro de rede: por segurança, redireciona para login
            window.location.href = 'login.html';
        }
    });

    // ... restante do código de nova_senha.html continua aqui
});
```

> **Dependência:** requer o novo arquivo `backend/verificar_sessao.php`
> (incluído nos arquivos de correção desta sessão).

---

### PATCH 5 — BUG-10 🟡 MÉDIO: Redirecionar automaticamente ao receber "Acesso Negado"

**Localizar o callback `success` do AJAX de troca de senha em `nova_senha.html` e substituir por:**

```javascript
// PATCH BUG-10: Redirecionar para login se sessão expirada durante a troca de senha
success: function(res) {
    if (res.sucesso) {
        msgBox.removeClass('text-red-500').addClass('text-green-600');
        msgBox.text('Senha redefinida com sucesso! Redirecionando...').fadeIn();
        setTimeout(() => window.location.href = 'index.html', 1500);
    } else {
        // PATCH BUG-10: Detecta "Acesso Negado" e redireciona para login
        if (res.mensagem && res.mensagem.toLowerCase().includes('acesso negado')) {
            alert('Sua sessão expirou. Faça login novamente.');
            window.location.href = 'login.html';
            return;
        }
        // Outros erros: exibe a mensagem normalmente
        msgBox.addClass('text-red-500');
        msgBox.text(res.mensagem || 'Erro desconhecido.').fadeIn();
        btn.text('Salvar Senha e Entrar').prop('disabled', false);
    }
},
```

---

## Checklist de Aplicação

Após aplicar todos os patches, testar na seguinte ordem:

```
[ ] mapa.js      → Geolocalização exibe marcador "📍 Você está aqui"
[ ] mapa.js      → Digitar no campo de busca não dispara requisição por letra
[ ] mapa.js      → Pressionar Enter na busca aciona a pesquisa
[ ] login.php    → Login com 'senha123' em qualquer conta retorna erro
[ ] login.php    → Login válido funciona normalmente após a remoção do bypass
[ ] login.html   → Campo "Confirmar Senha" aparece no cadastro
[ ] login.html   → Submeter senhas diferentes exibe "As senhas não coincidem!"
[ ] login.html   → Botão Google aparece desabilitado com tooltip "Em breve"
[ ] nova_senha.html → Acessar diretamente sem login redireciona para login.html
[ ] conexao.php  → Forçar erro de BD: resposta não contém 'erro_tecnico' no JSON
[ ] register.php → Cadastro com senha de 3 chars retorna erro de validação
```
