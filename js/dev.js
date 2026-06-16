/**
 * AETHOS — dev.js
 * Lógica do Painel do Desenvolvedor
 * Inclui tudo do admin.js + logs + banco de dados
 */

(function($) {
    'use strict';

    var SESSAO = null;
    var paginaLogs = 1;
    var paginaUsr  = 1;

    $(document).ready(function() {
        verificarSessao();
        initNavegacao();
        initFiltros();
    });

    // ─── VERIFICAÇÃO DE SESSÃO ────────────────────────────────
    function verificarSessao() {
        $.getJSON('backend/verificar_sessao.php', function(res) {
            if (!res.autenticado) { window.location.href = 'login.html'; return; }
            if (res.tipo_usuario !== 'desenvolvedor') {
                alert('Acesso Negado. Esta área é exclusiva para Desenvolvedores.');
                window.location.href = 'index.html';
                return;
            }
            SESSAO = res;
            $('#h-nome').text(res.nome);
            carregarPendentes();
        }).fail(function() { window.location.href = 'login.html'; });
    }

    // ─── NAVEGAÇÃO ────────────────────────────────────────────
    function initNavegacao() {
        $('.sidebar-item').on('click', function() {
            var tab = $(this).data('tab');
            $('.sidebar-item').removeClass('ativo');
            $(this).addClass('ativo');
            $('.tab-panel').removeClass('ativa');
            $('#tab-' + tab).addClass('ativa');

            var loaders = {
                'pendentes':    carregarPendentes,
                'usuarios':     function() { paginaUsr = 1; carregarUsuarios(); },
                'avaliacoes':   carregarAvaliacoes,
                'locais-ativos': carregarLocaisAtivos,
                'logs':         function() { paginaLogs = 1; carregarLogs(); },
                'database':     carregarDatabase
            };
            if (loaders[tab]) loaders[tab]();
        });

        $('#btn-sair').on('click', function() {
            $.post('backend/logout.php', function() { window.location.href = 'login.html'; });
        });
    }

    // ─── FILTROS ──────────────────────────────────────────────
    function initFiltros() {
        $('#btn-filtrar-usr').on('click', function() { paginaUsr = 1; carregarUsuarios(); });
        $('#filtro-busca-usr').on('keydown', function(e) { if (e.key === 'Enter') { paginaUsr = 1; carregarUsuarios(); } });
        $('#btn-filtrar-av').on('click', carregarAvaliacoes);
        $('#btn-filtrar-log').on('click', function() { paginaLogs = 1; carregarLogs(); });

        $('#btn-limpar-logs').on('click', function() {
            if (!confirm('Tem certeza? Isso apagará permanentemente todos os logs com mais de 30 dias.')) return;
            var $btn = $(this).prop('disabled', true).text('Limpando...');
            $.ajax({
                url:    'backend/dev/limpar_logs.php',
                method: 'POST',
                success: function(resp) {
                    if (resp.sucesso) {
                        toast(resp.mensagem, 'sucesso');
                        paginaLogs = 1; carregarLogs();
                    } else {
                        toast(resp.mensagem, 'erro');
                    }
                },
                error: function() { toast('Erro de comunicação.', 'erro'); },
                complete: function() { $btn.prop('disabled', false).html('<i class="fa fa-trash"></i> Limpar &gt;30 dias'); }
            });
        });
    }

    // ─── TOAST ────────────────────────────────────────────────
    function toast(msg, tipo) {
        tipo = tipo || 'sucesso';
        var icon = tipo === 'sucesso' ? '<i class="fa fa-check-circle"></i>' : '<i class="fa fa-exclamation-triangle"></i>';
        var $t = $('<div class="toast ' + tipo + '">' + icon + msg + '</div>');
        $('#toast-container').append($t);
        setTimeout(function() { $t.fadeOut(300, function() { $(this).remove(); }); }, 3500);
    }

    // ─── ABA: PENDENTES ──────────────────────────────────────
    function carregarPendentes() {
        $('#body-pendentes').html('<tr class="loading-row"><td colspan="6"><i class="fa fa-circle-notch fa-spin"></i></td></tr>');
        $.getJSON('backend/admin/listar_pendentes.php', function(resp) {
            if (!resp.sucesso) { toast(resp.mensagem, 'erro'); return; }
            var total = resp.total;
            $('#count-pendentes').text(' (' + total + ')');
            if (total > 0) $('#badge-pendentes').text(total).show();
            else $('#badge-pendentes').hide();

            if (!total) {
                $('#body-pendentes').html('<tr class="empty-row"><td colspan="6"><i class="fa fa-check-circle" style="color:#4ade80;"></i> Sem pendências.</td></tr>');
                return;
            }

            var rows = resp.data.map(function(l) {
                var data = new Date(l.criado_em).toLocaleDateString('pt-BR');
                return '<tr id="row-local-' + l.id + '">' +
                    '<td><strong>' + h(l.nome) + '</strong></td>' +
                    '<td>' + h(l.modalidade) + '</td>' +
                    '<td>' + (l.cidade ? h(l.cidade) + '/' + h(l.estado) : '—') + '</td>' +
                    '<td>' + h(l.professor_nome) + '</td>' +
                    '<td>' + data + '</td>' +
                    '<td>' +
                    '<button class="btn-acao btn-aprovar" data-id="' + l.id + '" data-acao="aprovar"><i class="fa fa-check"></i> Aprovar</button>' +
                    '<button class="btn-acao btn-rejeitar" data-id="' + l.id + '" data-acao="rejeitar"><i class="fa fa-times"></i> Rejeitar</button>' +
                    '</td></tr>';
            }).join('');
            $('#body-pendentes').html(rows);

            $('#body-pendentes').off('click', '.btn-aprovar, .btn-rejeitar');
            $('#body-pendentes').on('click', '.btn-aprovar, .btn-rejeitar', function() {
                var id   = $(this).data('id');
                var acao = $(this).data('acao');
                var $btn = $(this).prop('disabled', true);
                var endpoint = acao === 'aprovar' ? 'backend/admin/aprovar_local.php' : 'backend/admin/rejeitar_local.php';
                $.ajax({
                    url: endpoint, method: 'POST', contentType: 'application/json',
                    data: JSON.stringify({ id: id }),
                    success: function(resp) {
                        if (resp.sucesso) {
                            $('#row-local-' + id).fadeOut(300, function() { $(this).remove(); });
                            toast(resp.mensagem, 'sucesso');
                        } else { toast(resp.mensagem, 'erro'); $btn.prop('disabled', false); }
                    },
                    error: function() { toast('Falha.', 'erro'); $btn.prop('disabled', false); }
                });
            });
        });
    }

    // ─── ABA: USUÁRIOS ────────────────────────────────────────
    function carregarUsuarios() {
        $('#body-usuarios').html('<tr class="loading-row"><td colspan="7"><i class="fa fa-circle-notch fa-spin"></i></td></tr>');
        var params = { busca: $('#filtro-busca-usr').val(), tipo: $('#filtro-tipo-usr').val(), ativo: $('#filtro-ativo-usr').val(), pagina: paginaUsr };
        var tipoMap = { 'comum': 'Atleta', 'professor': 'Professor', 'admin': 'Admin', 'desenvolvedor': 'Dev' };

        $.getJSON('backend/admin/listar_usuarios.php', params, function(resp) {
            if (!resp.sucesso || !resp.data.length) {
                $('#body-usuarios').html('<tr class="empty-row"><td colspan="7">Nenhum usuário encontrado.</td></tr>');
                $('#paginacao-usr').empty();
                return;
            }
            var rows = resp.data.map(function(u) {
                var foto = u.foto_perfil
                    ? '<img src="' + u.foto_perfil + '" class="foto-mini">'
                    : '<div class="foto-mini" style="display:inline-flex;align-items:center;justify-content:center;"><i class="fa fa-user" style="color:var(--lavanda);font-size:0.65rem;"></i></div>';
                var status = u.ativo
                    ? '<span class="status-badge status-ativo">Ativo</span>'
                    : '<span class="status-badge status-inativo">Inativo</span>';
                var btn = u.ativo
                    ? '<button class="btn-acao btn-desativar" data-id="' + u.id + '" data-ativo="0"><i class="fa fa-ban"></i></button>'
                    : '<button class="btn-acao btn-reativar"  data-id="' + u.id + '" data-ativo="1"><i class="fa fa-check"></i></button>';
                return '<tr id="row-usr-' + u.id + '"><td>' + foto + '</td><td>' + h(u.nome) + '</td><td style="color:rgba(255,255,255,0.55);">' + h(u.email) + '</td><td>' +
                    (tipoMap[u.tipo_usuario] || u.tipo_usuario) + '</td><td>' + new Date(u.criado_em).toLocaleDateString('pt-BR') + '</td><td>' + status + '</td><td>' + btn + '</td></tr>';
            }).join('');
            $('#body-usuarios').html(rows);
            renderPaginacao(resp.total_pags, paginaUsr, '#paginacao-usr', function(p) { paginaUsr = p; carregarUsuarios(); });

            $('#body-usuarios').off('click', '.btn-desativar, .btn-reativar');
            $('#body-usuarios').on('click', '.btn-desativar, .btn-reativar', function() {
                var id = $(this).data('id'); var ativo = $(this).data('ativo'); var $btn = $(this).prop('disabled', true);
                $.ajax({
                    url: 'backend/admin/excluir_usuario.php', method: 'POST', contentType: 'application/json',
                    data: JSON.stringify({ id: id, ativo: ativo }),
                    success: function(resp) {
                        if (resp.sucesso) { toast(resp.mensagem, 'sucesso'); carregarUsuarios(); }
                        else { toast(resp.mensagem, 'erro'); $btn.prop('disabled', false); }
                    },
                    error: function() { toast('Erro.', 'erro'); $btn.prop('disabled', false); }
                });
            });
        });
    }

    // ─── ABA: AVALIAÇÕES ─────────────────────────────────────
    function carregarAvaliacoes() {
        $('#body-avaliacoes').html('<tr class="loading-row"><td colspan="7"><i class="fa fa-circle-notch fa-spin"></i></td></tr>');
        var nota = $('#filtro-nota-av').val();
        $.ajax({
            url: 'backend/admin/listar_avaliacoes.php' + (nota ? '?nota=' + nota : ''),
            method: 'GET',
            success: function(resp) {
                if (!resp || !resp.sucesso || !resp.data.length) {
                    $('#body-avaliacoes').html('<tr class="empty-row"><td colspan="7">Nenhuma avaliação.</td></tr>');
                    return;
                }
                var rows = resp.data.map(function(av) {
                    var stars = ''; for (var i = 1; i <= 5; i++) stars += i <= av.nota ? '★' : '☆';
                    return '<tr id="row-av-' + av.id + '"><td>#' + av.id + '</td><td>' + h(av.local_nome || '—') + '</td><td>' + h(av.avaliador_nome) + '</td>' +
                        '<td style="color:#FBBF24;">' + stars + '</td>' +
                        '<td style="color:rgba(255,255,255,0.55);">' + (av.comentario ? h(av.comentario.substring(0,50)) + (av.comentario.length > 50 ? '...' : '') : '—') + '</td>' +
                        '<td>' + new Date(av.criado_em).toLocaleDateString('pt-BR') + '</td>' +
                        '<td><button class="btn-acao btn-desativar" data-id="' + av.id + '"><i class="fa fa-trash"></i></button></td></tr>';
                }).join('');
                $('#body-avaliacoes').html(rows);
                $('#body-avaliacoes').off('click', '.btn-desativar');
                $('#body-avaliacoes').on('click', '.btn-desativar', function() {
                    var id = $(this).data('id');
                    if (!confirm('Remover avaliação #' + id + '?')) return;
                    $.ajax({
                        url: 'backend/admin/remover_avaliacao.php', method: 'POST', contentType: 'application/json',
                        data: JSON.stringify({ id: id }),
                        success: function(r) {
                            if (r.sucesso) { $('#row-av-' + id).fadeOut(300, function() { $(this).remove(); }); toast('Removida.', 'sucesso'); }
                            else toast(r.mensagem, 'erro');
                        },
                        error: function() { toast('Erro.', 'erro'); }
                    });
                });
            },
            error: function() { $('#body-avaliacoes').html('<tr class="empty-row"><td colspan="7" style="color:#f87171;">Erro.</td></tr>'); }
        });
    }

    // ─── ABA: LOCAIS ATIVOS ───────────────────────────────────
    function carregarLocaisAtivos() {
        $('#body-locais-ativos').html('<tr class="loading-row"><td colspan="6"><i class="fa fa-circle-notch fa-spin"></i></td></tr>');
        $.getJSON('backend/locais.php?acao=listar_aprovados', function(resp) {
            if (!resp.sucesso || !resp.data.length) {
                $('#body-locais-ativos').html('<tr class="empty-row"><td colspan="6">Nenhum local ativo.</td></tr>');
                return;
            }
            var rows = resp.data.map(function(l) {
                return '<tr id="row-la-' + l.id + '"><td><strong>' + h(l.nome) + '</strong></td><td>' + h(l.modalidade) + '</td>' +
                    '<td>' + (l.cidade ? h(l.cidade) + '/' + h(l.estado) : '—') + '</td>' +
                    '<td>' + h(l.professor_nome) + '</td>' +
                    '<td style="color:#FBBF24;">' + parseFloat(l.nota_media).toFixed(1) + ' ★</td>' +
                    '<td><button class="btn-acao btn-desativar" data-id="' + l.id + '"><i class="fa fa-eye-slash"></i></button></td></tr>';
            }).join('');
            $('#body-locais-ativos').html(rows);
            $('#body-locais-ativos').off('click', '.btn-desativar');
            $('#body-locais-ativos').on('click', '.btn-desativar', function() {
                var id = $(this).data('id');
                if (!confirm('Desativar local #' + id + '?')) return;
                $.ajax({
                    url: 'backend/locais.php?acao=excluir', method: 'POST', contentType: 'application/json',
                    data: JSON.stringify({ id: id }),
                    success: function(r) {
                        if (r.sucesso) { $('#row-la-' + id).fadeOut(300, function() { $(this).remove(); }); toast('Desativado.', 'sucesso'); }
                        else toast(r.mensagem, 'erro');
                    },
                    error: function() { toast('Erro.', 'erro'); }
                });
            });
        });
    }

    // ─── ABA: LOGS ────────────────────────────────────────────
    function carregarLogs() {
        $('#body-logs').html('<tr class="loading-row"><td colspan="7"><i class="fa fa-circle-notch fa-spin"></i> Carregando logs...</td></tr>');
        var params = {
            nivel:  $('#filtro-nivel-log').val(),
            modulo: $('#filtro-modulo-log').val(),
            de:     $('#filtro-de-log').val(),
            ate:    $('#filtro-ate-log').val(),
            pagina: paginaLogs
        };

        $.getJSON('backend/dev/listar_logs.php', params, function(resp) {
            if (!resp.sucesso) { toast(resp.mensagem, 'erro'); return; }

            // Atualiza contadores
            var cnt = resp.contadores || {};
            $('#cnt-info').text(cnt['INFO'] || 0);
            $('#cnt-aviso').text(cnt['AVISO'] || 0);
            $('#cnt-erro').text(cnt['ERRO'] || 0);
            $('#cnt-critico').text(cnt['CRITICO'] || 0);

            if (!resp.data.length) {
                $('#body-logs').html('<tr class="empty-row"><td colspan="7">Nenhum log encontrado com os filtros selecionados.</td></tr>');
                $('#paginacao-logs').empty();
                return;
            }

            var rows = resp.data.map(function(l) {
                var badgeClass = 'nivel-' + (l.nivel === 'CRITICO' ? 'CRITICO' : l.nivel);
                var dt = new Date(l.criado_em);
                var dataStr = dt.toLocaleDateString('pt-BR') + ' ' + dt.toLocaleTimeString('pt-BR');
                var desc = l.descricao ? h(l.descricao.substring(0, 60)) + (l.descricao.length > 60 ? '...' : '') : '—';
                return '<tr>' +
                    '<td><span class="badge-nivel ' + badgeClass + '">' + h(l.nivel) + '</span></td>' +
                    '<td><code style="font-size:0.78rem; color:rgba(165,180,252,0.8);">' + h(l.modulo) + '</code></td>' +
                    '<td style="font-weight:500;">' + h(l.acao) + '</td>' +
                    '<td style="color:rgba(255,255,255,0.55); font-size:0.8rem;">' + desc + '</td>' +
                    '<td style="color:rgba(255,255,255,0.5);">' + (l.usuario_nome ? h(l.usuario_nome) : '—') + '</td>' +
                    '<td style="font-family:\'JetBrains Mono\',monospace; font-size:0.75rem; color:rgba(255,255,255,0.4);">' + h(l.ip || '—') + '</td>' +
                    '<td style="font-size:0.78rem; color:rgba(255,255,255,0.4);">' + dataStr + '</td>' +
                    '</tr>';
            }).join('');
            $('#body-logs').html(rows);
            renderPaginacao(resp.total_pags, paginaLogs, '#paginacao-logs', function(p) { paginaLogs = p; carregarLogs(); });
        }).fail(function() {
            $('#body-logs').html('<tr class="empty-row"><td colspan="7" style="color:#f87171;">Falha ao carregar logs.</td></tr>');
        });
    }

    // ─── ABA: BANCO DE DADOS ──────────────────────────────────
    function carregarDatabase() {
        $('#db-cards').html('<div style="text-align:center; padding:2rem; color:rgba(255,255,255,0.3);"><i class="fa fa-circle-notch fa-spin"></i> Carregando...</div>');

        $.getJSON('backend/dev/db_stats.php', function(resp) {
            if (!resp.sucesso) {
                $('#db-cards').html('<p style="color:#f87171;">' + resp.mensagem + '</p>');
                return;
            }
            var html = resp.data.map(function(t) {
                return '<div class="db-card">' +
                    '<div>' +
                        '<div class="db-card-nome"><i class="fa fa-table" style="margin-right:6px; opacity:0.5;"></i>' + h(t.tabela) + '</div>' +
                        '<div class="db-card-label">' + t.descricao + '</div>' +
                    '</div>' +
                    '<div style="text-align:right;">' +
                        '<div class="db-card-count">' + t.total.toLocaleString('pt-BR') + '</div>' +
                        '<div class="db-card-label">registros</div>' +
                    '</div>' +
                    '<div>' +
                        '<a href="backend/dev/exportar_csv.php?tabela=' + h(t.tabela) + '" class="btn-csv" target="_blank">' +
                        '<i class="fa fa-download"></i> CSV</a>' +
                    '</div>' +
                '</div>';
            }).join('');
            $('#db-cards').html(html);
        }).fail(function() {
            // Fallback: cria cards manuais se o endpoint db_stats não existir ainda
            var tabelas = [
                { tabela: 'usuarios',          descricao: 'Atletas, professores, admins e devs', total: '?' },
                { tabela: 'locais_esportivos', descricao: 'Espaços cadastrados pelos professores',  total: '?' },
                { tabela: 'avaliacoes',        descricao: 'Avaliações de locais pelos atletas',      total: '?' },
                { tabela: 'logs_sistema',      descricao: 'Registro de todas as ações do sistema',   total: '?' }
            ];
            var html = tabelas.map(function(t) {
                return '<div class="db-card">' +
                    '<div><div class="db-card-nome"><i class="fa fa-table" style="margin-right:6px; opacity:0.5;"></i>' + t.tabela + '</div><div class="db-card-label">' + t.descricao + '</div></div>' +
                    '<div style="text-align:right;"><div class="db-card-count">—</div><div class="db-card-label">registros</div></div>' +
                    '<div><a href="backend/dev/exportar_csv.php?tabela=' + t.tabela + '" class="btn-csv" target="_blank"><i class="fa fa-download"></i> CSV</a></div>' +
                    '</div>';
            }).join('');
            $('#db-cards').html(html);
        });
    }

    // ─── HELPERS ─────────────────────────────────────────────
    function renderPaginacao(totalPags, atual, seletor, fn) {
        var $el = $(seletor).empty();
        if (totalPags <= 1) return;
        for (var i = 1; i <= totalPags; i++) {
            var $btn = $('<button style="background:' + (i === atual ? 'rgba(165,180,252,0.2)' : 'rgba(255,255,255,0.05)') +
                '; border:1px solid var(--border); color:' + (i === atual ? 'var(--lavanda)' : 'rgba(255,255,255,0.5)') +
                '; border-radius:6px; padding:0.4rem 0.8rem; cursor:pointer; font-size:0.8rem;">' + i + '</button>');
            (function(page) { $btn.on('click', function() { fn(page); }); })(i);
            $el.append($btn);
        }
    }

    function h(str) {
        if (!str) return '';
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

})(jQuery);
