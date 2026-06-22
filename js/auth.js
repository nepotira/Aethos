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

    // ==========================================
    // SISTEMA LEMBRAR-ME (localStorage)
    // ==========================================
    
    // Ao carregar a página, verificar se há dados salvos do "Lembrar-me"
    const savedEmail = localStorage.getItem('aethos_lembrar_email');
    const savedTipo  = localStorage.getItem('aethos_lembrar_tipo');
    if (savedEmail) {
        $('#login-email').val(savedEmail);
        $('#chk-lembrar-me').prop('checked', true);
        if (savedTipo) {
            // Clica no botão de perfil correspondente
            $(`.perfil-btn[data-type="${savedTipo}"]`).click();
        }
    }

    // Função de alterar entre tela Entrar e Cadastrar
    window.switchMainTab = function(tab) {
        if(tab === 'login') {
            isLoginTab = true;
            $('#btn-tab-login').addClass('active-tab-btn');
            $('#btn-tab-register').removeClass('active-tab-btn');
            
            $('#form-login').fadeIn(300).css('display', 'block');
            $('#form-register').hide().addClass('hidden-tab');
            
            // Administradores e Dev não se "cadastram"
            $('.login-only').fadeIn();
            
            // Voltar para aba de Usuário comum caso esteja num admin
            $('.perfil-btn[data-type="comum"]').click();
        } else {
            isLoginTab = false;
            $('#btn-tab-register').addClass('active-tab-btn');
            $('#btn-tab-login').removeClass('active-tab-btn');
            
            $('#form-register').fadeIn(300).removeClass('hidden-tab').css('display', 'block');
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
        $('.perfil-btn').removeClass('active');
        
        // Adiciona ativo no clicado
        $(this).addClass('active');
        
        const tipoSelecionado = $(this).data('type'); // comum, professor, admin, desenvolvedor
        
        // Atualiza campos ocultos nos dois forms
        $('input[name="tipo_usuario"]').val(tipoSelecionado);

        // ------- Ações baseadas no Tipo -------

        // 1. Mostrar campos de CPF e ENDEREÇO se for *Professor no Cadastro*
        if (!isLoginTab) {
            if (tipoSelecionado === 'professor') {
                $('#campos-professor').slideDown(300);
                $('input[name="cpf"], input[name="endereco"]').prop('required', true);
            } else {
                $('#campos-professor').slideUp(300);
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

            // Social login wrapper (Google) — esconder para admin/dev/professor
            if (tipoSelecionado === 'comum') {
                $('.social-login-wrapper').slideDown(300);
            } else {
                $('.social-login-wrapper').slideUp(300);
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
            $(this).removeClass('fa-eye-slash').addClass('fa-eye').css('color', '#A5B4FC');
        } else {
            inputField.attr('type', 'password');
            $(this).removeClass('fa-eye').addClass('fa-eye-slash').css('color', '');
        }
    });


    // ==========================================
    // REQUISIÇÕES AJAX (BACKEND)
    // ==========================================

    function showFeedbackMessage(msg, isSuccess) {
        const alertBox = $('#mensagem-alerta');
        alertBox.removeClass('sucesso erro').hide();
        
        if(isSuccess) {
            alertBox.addClass('sucesso').text(msg).fadeIn();
        } else {
            alertBox.addClass('erro').text(msg).fadeIn();
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
                    // Redireciona para verificação de e-mail (Jornada Completa)
                    setTimeout(() => { 
                        window.location.href = 'verificar-email.html?email=' + encodeURIComponent(res.email || dataJson.email); 
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

        // Salvar ou limpar "Lembrar-me"
        if ($('#chk-lembrar-me').is(':checked')) {
            localStorage.setItem('aethos_lembrar_email', emailOuNome);
            localStorage.setItem('aethos_lembrar_tipo', tipo);
        } else {
            localStorage.removeItem('aethos_lembrar_email');
            localStorage.removeItem('aethos_lembrar_tipo');
        }

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
                    if (res.url_redirecionamento) {
                        setTimeout(() => { 
                            window.location.href = res.url_redirecionamento; 
                        }, 2000);
                    }
                }
            },
            error: function(xhr, status, error) {
                // More descriptive error messages
                if (xhr.status === 0) {
                    showFeedbackMessage('Não foi possível conectar ao servidor. Verifique se o XAMPP está ligado e rodando na porta correta.', false);
                } else if (xhr.status === 404) {
                    showFeedbackMessage('Endpoint de login não encontrado. Verifique a configuração do servidor.', false);
                } else if (xhr.status === 500) {
                    showFeedbackMessage('Erro interno no servidor. Verifique os logs do PHP.', false);
                } else {
                    showFeedbackMessage('Erro ao comunicar com o backend. Status: ' + xhr.status, false);
                }
            },
            complete: function() {
                btn.text(oldText).prop('disabled', false);
            }
        });
    });
});
