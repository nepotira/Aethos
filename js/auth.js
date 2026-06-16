$(document).ready(function() {
    
    // ==========================================
    // INICIALIZAÇÃO E CONTROLE DE ABAS PRINCIPAIS
    // ==========================================

    let isLoginTab = true; // Por padrão na página 'Entrar'
    
    // Ler se veio da URL pra abrir direto o Cadastro (ex: href="login.html?tab=register")
    const urlParams = new URLSearchParams(window.location.search);
    if(urlParams.get('tab') === 'register') {
        window.switchMainTab('register');
    }

    // Função de alterar entre tela Entrar e Cadastrar
    window.switchMainTab = function(tab) {
        if(tab === 'login') {
            isLoginTab = true;
            $('#btn-tab-login').addClass('active-tab-btn text-gray-700').removeClass('hover:text-blue-500');
            $('#btn-tab-register').removeClass('active-tab-btn text-gray-700').addClass('hover:text-blue-500');
            
            $('#form-login').fadeIn(300);
            $('#form-register').hide();
            
            // Administradores e Dev não se "cadastram"
            $('.login-only').fadeIn();
            
            // Voltar para aba de Usuário comum caso esteja num admin
            $('.perfil-btn[data-type="comum"]').click();
        } else {
            isLoginTab = false;
            $('#btn-tab-register').addClass('active-tab-btn text-gray-700').removeClass('hover:text-blue-500');
            $('#btn-tab-login').removeClass('active-tab-btn text-gray-700').addClass('hover:text-blue-500');
            
            $('#form-register').fadeIn(300);
            $('#form-login').hide();
            
            // Remove botão de admin e dev do cadastro
            $('.login-only').hide();

            // Reseta se tiver vindo de aba Dev/Admin pro comum
            $('.perfil-btn[data-type="comum"]').click();
        }
    }


    // ==========================================
    // SISTEMA DE SELEÇÃO DE PERFIL
    // ==========================================

    $('.perfil-btn').on('click', function() {
        // Remover estilos ativos de todos
        $('.perfil-btn').removeClass('active bg-blue-500 text-white shadow-sm').addClass('bg-gray-100 text-gray-600');
        
        // Adiciona cor e ativa no clicado
        $(this).removeClass('bg-gray-100 text-gray-600').addClass('active bg-blue-500 text-white shadow-sm');
        
        const tipoSelecionado = $(this).data('type'); // comum, professor, admin, desenvolvedor
        
        // Atualiza campos ocultos nos dois force
        $('input[name="tipo_usuario"]').val(tipoSelecionado);

        // ------- Ações baseadas no Tipo -------

        // 1. Mostrar campos de CPF e ENDEREÇO se for *Professor no Cadastro*
        if (!isLoginTab) {
            if (tipoSelecionado === 'professor') {
                $('#campos-professor').removeClass('hidden').hide().slideDown();
                $('input[name="cpf"], input[name="endereco"]').prop('required', true);
            } else {
                $('#campos-professor').slideUp(function(){ $(this).addClass('hidden'); });
                $('input[name="cpf"], input[name="endereco"]').prop('required', false);
            }
        }

        // 2. Mudar o Label de "E-mail" para "Nome do DEV" para login de desenvolvedores especiais
        if (isLoginTab) {
            if (tipoSelecionado === 'desenvolvedor') {
                $('#label-login-email').text('Nome do Desenvolvedor (Lorrany, Arthur, etc)');
                $('#login-email').attr('placeholder', 'Seu Nome');
                $('#login-email').attr('type', 'text');
            } else {
                $('#label-login-email').text('E-mail');
                $('#login-email').attr('placeholder', 'seu@email.com');
                $('#login-email').attr('type', 'email');
            }
        }
    });

    // ==========================================
    // UTILITÁRIOS (Ver / Esconder Senha)
    // ==========================================

    $('.toggle-password').on('click', function() {
        const inputId = $(this).data('target');
        const inputField = $('#' + inputId);
        
        if (inputField.attr('type') === 'password') {
            inputField.attr('type', 'text');
            $(this).removeClass('fa-eye-slash').addClass('fa-eye text-blue-500');
        } else {
            inputField.attr('type', 'password');
            $(this).removeClass('fa-eye text-blue-500').addClass('fa-eye-slash');
        }
    });


    // ==========================================
    // REQUISIÇÕES FAKE / REAIS AJAX (BACKEND)
    // ==========================================

    function showFeedbackMessage(msg, isSuccess) {
        const alertBox = $('#mensagem-alerta');
        alertBox.removeClass('hidden bg-red-100 text-red-700 bg-green-100 text-green-700');
        
        if(isSuccess) {
            alertBox.addClass('bg-green-100 text-green-700').text(msg).fadeIn();
        } else {
            alertBox.addClass('bg-red-100 text-red-700').text(msg).fadeIn();
        }

        setTimeout(() => alertBox.fadeOut(), 5000);
    }

    // POST NO CADASTRO
    $('#form-register').on('submit', function(e) {
        e.preventDefault();
        
        // PATCH BUG-06: Validação de confirmação de senha
        const s1 = $('#reg-senha').val();
        const s2 = $('#reg-senha-confirm').val();
        if (s1 !== s2) {
            showFeedbackMessage('As senhas não coincidem!', false);
            return;
        }

        // Aqui enviamos via POST/Ajax para o PHP
        const rawArray = $(this).serializeArray();
        const dataJson = {};
        rawArray.forEach(item => { dataJson[item.name] = item.value; });

        // Muda visual do botão
        const btn = $(this).find('button[type="submit"]');
        const oldText = btn.text();
        btn.text('Cadastrando...').prop('disabled', true);


        $.ajax({
            url: 'backend/register.php',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(dataJson),
            success: function(res) {
                if(res.sucesso) {
                    showFeedbackMessage(res.mensagem, true);
                    // Opcional: redicionar pro login depois ou acessar
                   setTimeout(() => { 
                       if (res.email) {
                           window.location.href = 'verificar-email.html?email=' + encodeURIComponent(res.email);
                       } else {
                           window.switchMainTab('login'); 
                       }
                   }, 2000);
                } else {
                    showFeedbackMessage(res.mensagem, false);
                }
            },
            error: function() {
                showFeedbackMessage('Erro de conexão com o banco local. (XAMPP ligado?)', false);
            },
            complete: function() {
                btn.text(oldText).prop('disabled', false);
            }
        });
    });

    // POST NO LOGIN
    $('#form-login').on('submit', function(e) {
        e.preventDefault();
        
        const emailOuNome = $('#login-email').val();
        const senha = $('#login-senha').val();
        const tipo = $('input[name="tipo_usuario"]').val();

        const dataJson = {
            email: emailOuNome,
            senha: senha,
            tipo_login: tipo
        };

        const btn = $(this).find('button[type="submit"]');
        const oldText = btn.text();
        btn.text('Validando...').prop('disabled', true);

        $.ajax({
            url: 'backend/login.php',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(dataJson),
            success: function(res) {
                if(res.sucesso) {
                    showFeedbackMessage(res.mensagem, true);
                    
                    setTimeout(() => { 
                        // Realiza redirecionamento dinâmico (Para index ou para trocar_senha.php)
                        window.location.href = res.url_redirecionamento; 
                    }, 1500);

                } else {
                    showFeedbackMessage(res.mensagem, false);
                }
            },
            error: function() {
                showFeedbackMessage('Erro ao comunicar com backend... (Banco não existe?)', false);
            },
            complete: function() {
                btn.text(oldText).prop('disabled', false);
            }
        });
    });
});
