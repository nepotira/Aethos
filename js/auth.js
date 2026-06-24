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
                $('#reg-cpf').prop('required', true);
                if ($('input[name="local_existente"]:checked').val() === 'nao') {
                    $('#nome-local, #modalidade-local, #endereco-autocomplete').prop('required', true);
                } else {
                    $('#select-local-existente').prop('required', true);
                }
            } else {
                $('#campos-professor').slideUp(300);
                $('#reg-cpf, #nome-local, #modalidade-local, #endereco-autocomplete, #select-local-existente').prop('required', false);
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

    $(document).on('click', '.toggle-password', function(e) {
        e.preventDefault();
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

    // ==========================================
    // VALIDAÇÃO DE CPF (FRONTEND)
    // ==========================================
    function validarCPF(cpf) {
        cpf = cpf.replace(/[^\d]+/g,'');
        if(cpf == '') return false; 
        if (cpf.length != 11 || /^(\d)\1{10}$/.test(cpf)) return false;       
        let add = 0;    
        for (let i=0; i < 9; i ++)       
            add += parseInt(cpf.charAt(i)) * (10 - i);  
        let rev = 11 - (add % 11);  
        if (rev == 10 || rev == 11) rev = 0;    
        if (rev != parseInt(cpf.charAt(9))) return false;       
        add = 0;    
        for (let i = 0; i < 10; i ++)        
            add += parseInt(cpf.charAt(i)) * (11 - i);  
        rev = 11 - (add % 11);  
        if (rev == 10 || rev == 11) rev = 0;    
        if (rev != parseInt(cpf.charAt(10))) return false;       
        return true;   
    }

    $('#reg-cpf').on('input', function() {
        let v = $(this).val().replace(/\D/g, '');
        if (v.length > 11) v = v.substring(0, 11);
        v = v.replace(/(\d{3})(\d)/, '$1.$2');
        v = v.replace(/(\d{3})(\d)/, '$1.$2');
        v = v.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
        $(this).val(v);

        if (v.length === 14) {
            if (validarCPF(v)) {
                $('#cpf-feedback').text('✔️ Válido').css('color', '#86efac');
            } else {
                $('#cpf-feedback').text('❌ Inválido').css('color', '#fca5a5');
            }
        } else {
            $('#cpf-feedback').text('');
        }
    });

    // ==========================================
    // LÓGICA DO LOCAL ESPORTIVO (PROFESSOR)
    // ==========================================
    $('input[name="local_existente"]').on('change', function() {
        if ($(this).val() === 'sim') {
            $('#campos-novo-local').slideUp();
            $('#campos-buscar-local').slideDown();
            $('#nome-local, #modalidade-local, #endereco-autocomplete').prop('required', false);
            $('#select-local-existente').prop('required', true);

            if ($('#select-local-existente option').length <= 1) {
                $.ajax({
                    url: 'backend/locais_publicos.php',
                    method: 'GET',
                    success: function(res) {
                        if (res.sucesso) {
                            let options = '<option value="" disabled selected>Selecione o local...</option>';
                            res.locais.forEach(loc => {
                                options += `<option value="${loc.id}">${loc.nome} (${loc.modalidade}) - ${loc.endereco}</option>`;
                            });
                            $('#select-local-existente').html(options);
                        } else {
                            $('#select-local-existente').html('<option disabled>Erro ao carregar locais</option>');
                        }
                    }
                });
            }
        } else {
            $('#campos-novo-local').slideDown();
            $('#campos-buscar-local').slideUp();
            $('#nome-local, #modalidade-local, #endereco-autocomplete').prop('required', true);
            $('#select-local-existente').prop('required', false);
        }
    });

    // ==========================================
    // AUTOCOMPLETE NOMINATIM (ENDEREÇOS E POIs)
    // ==========================================
    let timerNominatim;
    $('#endereco-autocomplete').on('input', function() {
        const query = $(this).val();
        const resultsBox = $('#autocomplete-results');

        clearTimeout(timerNominatim);
        
        if (query.length < 3) {
            resultsBox.hide();
            return;
        }

        timerNominatim = setTimeout(() => {
            $.ajax({
                url: `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&countrycodes=br&limit=5`,
                method: 'GET',
                success: function(data) {
                    resultsBox.empty();
                    if (data && data.length > 0) {
                        data.forEach(item => {
                            const div = $('<div></div>').text(item.display_name);
                            div.on('click', function() {
                                $('#endereco-autocomplete').val(item.display_name);
                                $('#local-lat').val(item.lat);
                                $('#local-lon').val(item.lon);
                                resultsBox.hide();
                            });
                            resultsBox.append(div);
                        });
                        resultsBox.show();
                    } else {
                        resultsBox.hide();
                    }
                }
            });
        }, 500);
    });

    $(document).on('click', function(e) {
        if (!$(e.target).closest('#endereco-autocomplete, #autocomplete-results').length) {
            $('#autocomplete-results').hide();
        }
    });

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

        if (dataJson.tipo_usuario === 'professor' && dataJson.cpf && !validarCPF(dataJson.cpf)) {
            showFeedbackMessage('CPF Inválido. Corrija para continuar.', false);
            return;
        }

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
