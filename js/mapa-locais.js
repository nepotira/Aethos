/**
 * AETHOS — mapa-locais.js
 * Carrega locais esportivos aprovados do banco e os plota
 * no mapa Leaflet com marcadores personalizados em lavanda.
 * Depende de: leaflet.js, jquery.min.js, mapa.js (inicializa #map)
 */

(function($) {
    'use strict';

    // Ícone personalizado em lavanda (#A5B4FC) para locais
    var iconeLavanda = L.divIcon({
        className: '',
        html: '<div class="marcador-local">' +
              '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="40" viewBox="0 0 32 40">' +
              '<path d="M16 0C7.163 0 0 7.163 0 16c0 10.5 16 24 16 24S32 26.5 32 16C32 7.163 24.837 0 16 0z" fill="#A5B4FC"/>' +
              '<circle cx="16" cy="16" r="7" fill="#fff"/>' +
              '<path d="M16 10 L13 19 L16 17 L19 19 Z" fill="#6366F1"/>' +
              '</svg>' +
              '</div>',
        iconSize:   [32, 40],
        iconAnchor: [16, 40],
        popupAnchor: [0, -40]
    });

    /**
     * Formata a nota média em estrelas simples.
     */
    function renderEstrelas(nota) {
        var cheias  = Math.round(nota);
        var html    = '';
        for (var i = 1; i <= 5; i++) {
            html += i <= cheias ? '★' : '☆';
        }
        return '<span class="estrelas-popup">' + html + '</span> ' +
               '<span class="nota-popup">' + nota.toFixed(1) + '</span>';
    }

    /**
     * Carrega os locais via AJAX e adiciona ao mapa global.
     */
    function carregarLocaisNoMapa() {
        $.ajax({
            url:      'backend/locais.php?acao=listar_aprovados',
            method:   'GET',
            dataType: 'json',
            success: function(resp) {
                if (!resp.sucesso || !resp.data.length) return;

                resp.data.forEach(function(local) {
                    if (!local.latitude || !local.longitude) return;

                    var lat = parseFloat(local.latitude);
                    var lon = parseFloat(local.longitude);

                    var popupConteudo =
                        '<div class="popup-local">' +
                          (local.foto_capa
                            ? '<img src="' + local.foto_capa + '" alt="' + local.nome + '" class="popup-foto"/>'
                            : '') +
                          '<h3 class="popup-nome">' + local.nome + '</h3>' +
                          '<span class="popup-modalidade">' + local.modalidade + '</span>' +
                          '<div class="popup-nota">' +
                            renderEstrelas(parseFloat(local.nota_media)) +
                            ' <small>(' + local.total_avaliacoes + ' avaliações)</small>' +
                          '</div>' +
                          '<p class="popup-professor">📍 ' + local.professor_nome + '</p>' +
                          '<a href="local.html?id=' + local.id + '" class="btn-popup">Ver Detalhes</a>' +
                        '</div>';

                    // Armazenar os marcadores em array global para podermos filtrar depois
                    if (!window.marcadoresAethos) window.marcadoresAethos = [];
                    
                    var marker = L.marker([lat, lon], { icon: iconeLavanda })
                        .bindPopup(popupConteudo, {
                            maxWidth: 260,
                            className: 'popup-aethos'
                        })
                        .addTo(window.mapaAethos || window.mapa);

                    // Registrar clique no marcador como "local recente"
                    (function(localData) {
                        marker.on('click', function() {
                            if (typeof window.registrarLocalRecente === 'function') {
                                window.registrarLocalRecente({
                                    id: localData.id,
                                    nome: localData.nome,
                                    modalidade: localData.modalidade
                                });
                            }
                        });
                    })(local);

                    // Anexar meta-dados para o filtro local
                    marker.aethosData = {
                        nome: local.nome.toLowerCase(),
                        modalidade: local.modalidade.toLowerCase()
                    };
                    
                    window.marcadoresAethos.push(marker);
                });
            },
            error: function() {
                console.warn('[Aethos] Falha ao carregar locais esportivos no mapa.');
            }
        });
    }

    // Aguarda o mapa Leaflet estar disponível
    function aguardarMapaECarregar() {
        if (window.mapaAethos || window.mapa) {
            carregarLocaisNoMapa();
        } else {
            setTimeout(aguardarMapaECarregar, 300);
        }
    }

    $(document).ready(function() {
        aguardarMapaECarregar();
    });

})(jQuery);
