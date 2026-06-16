/**
 * AETHOS — admin.js
 * Lógica completa do Painel Administrativo
 * Depende de: jquery.min.js, admin.html
 */

(function($) {
    'use strict';

    var SESSAO = null;
    var paginaAtualUsr = 1;
    var tabAtiva = 'pendentes';

    // ─── INIT ─────────────────────────────────────────────────
    $(document).ready(function() {
        verificarSessao();
        initNavegacao();
        initFiltros();
    });

    // ─── VERIFICAÇÃO DE SESSÃO ────────────────────────────────
    function verificarSessao() {
        $.getJSON('backend/verificar_sessao.php', function(res) {
            if (!res.autenticado) {
                window.location.href = 'login.html';
                return;
            }
            if (res.tipo_usuario !== 'admin' && res.tipo_usuario !== 'desenvolvedor') {
                alert('Acesso Negado. Esta área é restrita a Administradores.');
                window.location.href = 'index.html';
                return;
            }
            SESSAO = res;
            $('#h-nome').text(res.nome);
            $('#h-role').text(res.tipo_usuario === 'desenvolvedor' ? 'Dev' : 'Admin');
            if (res.foto_perfil) {
                $('#h-foto').html('<img src="' + res.foto_perfil + '" class="user-badge-foto" alt="Foto">');
            }

            // Carrega aba inicial
            carregarPendentes();

        }).fail(function() {
            window.location.href = 'login.html';
        });
    }

    // ─── NAVEGAÇÃO POR ABAS ───────────────────────────────────
    function initNavegacao() {
        $('.sidebar-item').on('click', function() {
            var tab = $(this).data('tab');
            $('.sidebar-item').removeClass('ativo');
            $(this).addClass('ativo');
            $('.tab-panel').removeClass('ativa');
            $('#tab-' + tab).addClass('ativa');
            tabAtiva = tab;

            // Carrega dados da aba ao ativar
            if (tab === 'pendentes') carregarPendentes();
            else if (tab === 'usuarios') carregarUsuarios();
            else if (tab === 'avaliacoes') carregarAvaliacoes();
            else if (tab === 'locais-ativos') carregarLocaisAtivos();
        });

        // Logout
        $('#btn-sair').on('click', function() {
            $.post('backend/logout.php', function() {
                window.location.href = 'login.html';
            });
        });
    }

    // ─── FILTROS ──────────────────────────────────────────────
    function initFiltros() {
        $('#btn-filtrar-usr').on('click', function() {
            paginaAtualUsr = 1;
            carregarUsuarios();
        });
        $('#filtro-busca-usr').on('keydown', function(e) {
            if (e.key === 'Enter') { paginaAtualUsr = 1; carregarUsuarios(); }
        });
        $('#btn-filtrar-av').on('click', carregarAvaliacoes);
    }

    // ─── TOAST NOTIFICATIONS ─────────────────────────────────
    function toast(msg, tipo) {
        tipo = tipo || 'sucesso';
        var icon = tipo === 'sucesso'
            ? '<i class="fa fa-check-circle"></i>'
            : '<i class="fa fa-exclamation-triangle"></i>';
        var $t = $('<div class="toast ' + tipo + '">' + icon + msg + '</div>');
        $('#toast-container').append($t);
        setTimeout(function() { $t.fadeOut(300, function() { $(this).remove(); }); }, 3500);
    }

    // ─── ABA 1: LOCAIS PENDENTES ──────────────────────────────
    function carregarPendentes() {
        $('#body-pendentes').html('<tr class="loading-row"><td colspan="6"><i class="fa fa-circle-notch fa-spin"></i> Carregando...</td></tr>');

        $.getJSON('backend/admin/listar_pendentes.php', function(resp) {
            if (!resp.sucesso) { toast(resp.mensagem, 'erro'); return; }

            var total = resp.total;
            $('#count-pendentes').text(' (' + total + ')');

            // Atualiza badge da sidebar
            if (total > 0) {
                $('#badge-pendentes').text(total).show();
            } else {
                $('#badge-pendentes').hide();
            }

            if (total === 0) {
                $('#body-pendentes').html('<tr class="empty-row"><td colspan="6"><i class="fa fa-check-circle" style="color:#4ade80;"></i> Nenhum local pendente de aprovação.</td></tr>');
                return;
            }

            var rows = resp.data.map(function(l) {
                var data = new Date(l.criado_em).toLocaleDateString('pt-BR');
                return '<tr id="row-local-' + l.id + '">' +
                    '<td><strong>' + h(l.nome) + '</strong></td>' +
                    '<td>' + h(l.modalidade) + '</td>' +
                    '<td>' + (l.cidade ? h(l.cidade) + '/' + h(l.estado) : '—') + '</td>' +
                    '<td>' + h(l.professor_nome) + '<br><small style="color:rgba(255,255,255,0.4);">' + h(l.professor_email) + '</small></td>' +
                    '<td>' + data + '</td>' +
                    '<td>' +
                        '<button class="btn-acao btn-aprovar" data-id="' + l.id + '" data-acao="aprovar"><i class="fa fa-check"></i> Aprovar</button>' +
                        '<button class="btn-acao btn-rejeitar" data-id="' + l.id + '" data-acao="rejeitar"><i class="fa fa-times"></i> Rejeitar</button>' +
                    '</td>' +
                    '</tr>';
            }).join('');

            $('#body-pendentes').html(rows);

            // Eventos de aprovação/rejeição
            $('#body-pendentes').off('click', '.btn-aprovar, .btn-rejeitar');
            $('#body-pendentes').on('click', '.btn-aprovar, .btn-rejeitar', function() {
                var id   = $(this).data('id');
                var acao = $(this).data('acao');
                var $btn = $(this);
                $btn.prop('disabled', true).html('<i class="fa fa-circle-notch fa-spin"></i>');

                var endpoint = acao === 'aprovar'
                    ? 'backend/admin/aprovar_local.php'
                    : 'backend/admin/rejeitar_local.php';

                $.ajax({
                    url:         endpoint,
                    method:      'POST',
                    contentType: 'application/json',
                    data:        JSON.stringify({ id: id }),
                    success: function(resp) {
                        if (resp.sucesso) {
                            $('#row-local-' + id).fadeOut(400, function() { $(this).remove(); });
                            var msg = acao === 'aprovar' ? 'Local aprovado! Agora aparece no mapa.' : 'Local rejeitado.';
                            toast(msg, 'sucesso');
                            // Atualiza contador
                            var novoTotal = parseInt($('#count-pendentes').text().replace(/\D/g, '')) - 1;
                            $('#count-pendentes').text(' (' + novoTotal + ')');
                            if (novoTotal <= 0) $('#badge-pendentes').hide();
                            else $('#badge-pendentes').text(novoTotal);
                        } else {
                            toast(resp.mensagem, 'erro');
                            $btn.prop('disabled', false).html(acao === 'aprovar' ? '<i class="fa fa-check"></i> Aprovar' : '<i class="fa fa-times"></i> Rejeitar');
                        }
                    },
                    error: function() {
                        toast('Falha na comunicação.', 'erro');
                        $btn.prop('disabled', false);
                    }
                });
            });
        }).fail(function() {
            $('#body-pendentes').html('<tr class="empty-row"><td colspan="6" style="color:#f87171;"><i class="fa fa-times-circle"></i> Falha ao carregar pendentes.</td></tr>');
        });
    }

    // ─── ABA 2: USUÁRIOS ─────────────────────────────────────
    function carregarUsuarios(pagina) {
        paginaAtualUsr = pagina || paginaAtualUsr;
        $('#body-usuarios').html('<tr class="loading-row"><td colspan="7"><i class="fa fa-circle-notch fa-spin"></i> Carregando...</td></tr>');

        var params = {
            busca:  $('#filtro-busca-usr').val(),
            tipo:   $('#filtro-tipo-usr').val(),
            ativo:  $('#filtro-ativo-usr').val(),
            pagina: paginaAtualUsr
        };

        $.getJSON('backend/admin/listar_usuarios.php', params, function(resp) {
            if (!resp.sucesso) { toast(resp.mensagem, 'erro'); return; }
            if (!resp.data.length) {
                $('#body-usuarios').html('<tr class="empty-row"><td colspan="7">Nenhum usuário encontrado.</td></tr>');
                $('#paginacao-usr').empty();
                return;
            }

            var tipoMap = { 'comum': 'Atleta', 'professor': 'Professor', 'admin': 'Admin', 'desenvolvedor': 'Dev' };

            var rows = resp.data.map(function(u) {
                var foto = u.foto_perfil
                    ? '<img src="' + u.foto_perfil + '" class="foto-mini">'
                    : '<div class="foto-mini" style="display:inline-flex; align-items:center; justify-content:center;"><i class="fa fa-user" style="color:var(--lavanda); font-size:0.7rem;"></i></div>';
                var status = u.ativo
                    ? '<span class="status-badge status-ativo">Ativo</span>'
                    : '<span class="status-badge status-inativo">Inativo</span>';
                var data = new Date(u.criado_em).toLocaleDateString('pt-BR');
                var btnAcao = u.ativo
                    ? '<button class="btn-acao btn-desativar" data-id="' + u.id + '" data-ativo="0"><i class="fa fa-ban"></i> Desativar</button>'
                    : '<button class="btn-acao btn-reativar"  data-id="' + u.id + '" data-ativo="1"><i class="fa fa-check"></i> Reativar</button>';
                return '<tr id="row-usr-' + u.id + '">' +
                    '<td>' + foto + '</td>' +
                    '<td>' + h(u.nome) + '</td>' +
                    '<td style="color:rgba(255,255,255,0.6);">' + h(u.email) + '</td>' +
                    '<td>' + (tipoMap[u.tipo_usuario] || u.tipo_usuario) + '</td>' +
                    '<td>' + data + '</td>' +
                    '<td>' + status + '</td>' +
                    '<td>' + btnAcao + '</td>' +
                    '</tr>';
            }).join('');

            $('#body-usuarios').html(rows);
            renderPaginacao(resp.total_pags, paginaAtualUsr, '#paginacao-usr', carregarUsuarios);

            // Eventos
            $('#body-usuarios').off('click', '.btn-desativar, .btn-reativar');
            $('#body-usuarios').on('click', '.btn-desativar, .btn-reativar', function() {
                var id    = $(this).data('id');
                var ativo = $(this).data('ativo');
                var $btn  = $(this);
                $btn.prop('disabled', true);

                $.ajax({
                    url:         'backend/admin/excluir_usuario.php',
                    method:      'POST',
                    contentType: 'application/json',
                    data:        JSON.stringify({ id: id, ativo: ativo }),
                    success: function(resp) {
                        if (resp.sucesso) {
                            toast(resp.mensagem, 'sucesso');
                            carregarUsuarios();
                        } else {
                            toast(resp.mensagem, 'erro');
                            $btn.prop('disabled', false);
                        }
                    },
                    error: function() { toast('Erro de comunicação.', 'erro'); $btn.prop('disabled', false); }
                });
            });
        });
    }

    // ─── ABA 3: AVALIAÇÕES ────────────────────────────────────
    function carregarAvaliacoes() {
        $('#body-avaliacoes').html('<tr class="loading-row"><td colspan="7"><i class="fa fa-circle-notch fa-spin"></i> Carregando...</td></tr>');
        var nota = $('#filtro-nota-av').val();

        // Busca via avaliacoes.php — endpoint público listar_por_local não serve aqui
        // Vamos usar uma query direta via admin endpoint customizado
        // Como não temos endpoint dedicado de listar todas avaliações, usamos locais.php
        // para buscar avaliações. Na ausência de endpoint, usamos query direta aqui via locais
        // O backend correto seria backend/admin/listar_avaliacoes.php, mas criaremos a lógica inline.
        $.ajax({
            url:  'backend/admin/listar_avaliacoes.php' + (nota ? '?nota=' + nota : ''),
            method: 'GET',
            success: function(resp) {
                if (!resp || !resp.sucesso) {
                    $('#body-avaliacoes').html('<tr class="empty-row"><td colspan="7">Nenhuma avaliação encontrada.</td></tr>');
                    return;
                }
                if (!resp.data.length) {
                    $('#body-avaliacoes').html('<tr class="empty-row"><td colspan="7">Nenhuma avaliação encontrada.</td></tr>');
                    return;
                }

                var rows = resp.data.map(function(av) {
                    var estrelas = '';
                    for (var i = 1; i <= 5; i++) estrelas += i <= av.nota ? '★' : '☆';
                    var data = new Date(av.criado_em).toLocaleDateString('pt-BR');
                    return '<tr id="row-av-' + av.id + '">' +
                        '<td>#' + av.id + '</td>' +
                        '<td>' + h(av.local_nome || '—') + '</td>' +
                        '<td>' + h(av.avaliador_nome || '—') + '</td>' +
                        '<td style="color:#FBBF24;">' + estrelas + '</td>' +
                        '<td style="color:rgba(255,255,255,0.6);">' + (av.comentario ? h(av.comentario.substring(0,60)) + (av.comentario.length > 60 ? '...' : '') : '—') + '</td>' +
                        '<td>' + data + '</td>' +
                        '<td><button class="btn-acao btn-desativar" data-id="' + av.id + '"><i class="fa fa-trash"></i> Remover</button></td>' +
                        '</tr>';
                }).join('');
                $('#body-avaliacoes').html(rows);

                $('#body-avaliacoes').off('click', '.btn-desativar');
                $('#body-avaliacoes').on('click', '.btn-desativar', function() {
                    var id = $(this).data('id');
                    if (!confirm('Remover esta avaliação?')) return;
                    var $btn = $(this);
                    $.ajax({
                        url:         'backend/admin/remover_avaliacao.php',
                        method:      'POST',
                        contentType: 'application/json',
                        data:        JSON.stringify({ id: id }),
                        success: function(resp) {
                            if (resp.sucesso) {
                                $('#row-av-' + id).fadeOut(300, function() { $(this).remove(); });
                                toast('Avaliação removida.', 'sucesso');
                            } else {
                                toast(resp.mensagem, 'erro');
                            }
                        },
                        error: function() { toast('Erro de comunicação.', 'erro'); }
                    });
                });
            },
            error: function() {
                $('#body-avaliacoes').html('<tr class="empty-row"><td colspan="7" style="color:#f87171;">Falha ao carregar avaliações.</td></tr>');
            }
        });
    }

    // ─── ABA 4: LOCAIS ATIVOS ─────────────────────────────────
    function carregarLocaisAtivos() {
        $('#body-locais-ativos').html('<tr class="loading-row"><td colspan="6"><i class="fa fa-circle-notch fa-spin"></i> Carregando...</td></tr>');

        $.getJSON('backend/locais.php?acao=listar_aprovados', function(resp) {
            if (!resp.sucesso || !resp.data.length) {
                $('#body-locais-ativos').html('<tr class="empty-row"><td colspan="6">Nenhum local ativo no momento.</td></tr>');
                return;
            }

            var rows = resp.data.map(function(l) {
                return '<tr id="row-la-' + l.id + '">' +
                    '<td><strong>' + h(l.nome) + '</strong></td>' +
                    '<td>' + h(l.modalidade) + '</td>' +
                    '<td>' + (l.cidade ? h(l.cidade) + '/' + h(l.estado) : '—') + '</td>' +
                    '<td>' + h(l.professor_nome) + '</td>' +
                    '<td style="color:#FBBF24;">' + parseFloat(l.nota_media).toFixed(1) + ' ★ <small style="color:rgba(255,255,255,0.4);">(' + l.total_avaliacoes + ')</small></td>' +
                    '<td><button class="btn-acao btn-desativar" data-id="' + l.id + '"><i class="fa fa-eye-slash"></i> Desativar</button></td>' +
                    '</tr>';
            }).join('');
            $('#body-locais-ativos').html(rows);

            $('#body-locais-ativos').off('click', '.btn-desativar');
            $('#body-locais-ativos').on('click', '.btn-desativar', function() {
                var id = $(this).data('id');
                if (!confirm('Desativar este local do mapa?')) return;
                var $btn = $(this);
                $.ajax({
                    url:         'backend/locais.php?acao=excluir',
                    method:      'POST',
                    contentType: 'application/json',
                    data:        JSON.stringify({ id: id }),
                    success: function(resp) {
                        if (resp.sucesso) {
                            $('#row-la-' + id).fadeOut(300, function() { $(this).remove(); });
                            toast('Local removido do mapa.', 'sucesso');
                        } else {
                            toast(resp.mensagem, 'erro');
                        }
                    },
                    error: function() { toast('Erro de comunicação.', 'erro'); }
                });
            });
        });
    }

    // ─── PAGINAÇÃO HELPER ─────────────────────────────────────
    function renderPaginacao(totalPags, atual, seletor, callbackFn) {
        var $el = $(seletor);
        $el.empty();
        if (totalPags <= 1) return;

        for (var i = 1; i <= totalPags; i++) {
            var $btn = $('<button style="background:' + (i === atual ? 'rgba(165,180,252,0.2)' : 'rgba(255,255,255,0.05)') +
                '; border:1px solid var(--border); color:' + (i === atual ? 'var(--lavanda)' : 'rgba(255,255,255,0.5)') +
                '; border-radius:6px; padding:0.4rem 0.8rem; cursor:pointer; font-size:0.8rem;">' + i + '</button>');
            (function(page) {
                $btn.on('click', function() { callbackFn(page); });
            })(i);
            $el.append($btn);
        }
    }

    // ─── UTILITÁRIO HTML ESCAPE ───────────────────────────────
    function h(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

})(jQuery);
