/**
 * AETHOS — avaliacao.js
 * Componente de 5 estrelas SVG interativo para avaliação de locais.
 * Uso: AethosAvaliacaoStars('#container-id')
 * O container receberá data('nota') com o valor selecionado.
 */

(function($) {
    'use strict';

    /**
     * Inicializa o componente de estrelas em um seletor.
     * @param {string} seletor - Seletor jQuery do container
     */
    window.AethosAvaliacaoStars = function(seletor) {
        var $container = $(seletor);
        var notaSelecionada = 0;

        var html = '<div class="aethos-stars" style="display:flex; gap:8px; align-items:center;">';
        for (var i = 1; i <= 5; i++) {
            html += '<button type="button" class="star-btn" data-nota="' + i + '" ' +
                    'aria-label="' + i + ' estrela' + (i > 1 ? 's' : '') + '" ' +
                    'style="background:none; border:none; cursor:pointer; padding:0; outline:none; line-height:1;">' +
                    '<svg data-nota="' + i + '" width="32" height="32" viewBox="0 0 24 24" fill="none" ' +
                    'xmlns="http://www.w3.org/2000/svg" class="star-svg" ' +
                    'style="transition: transform 0.15s, fill 0.15s;">' +
                    '<path d="M12 2l2.9 6.26L22 9.27l-5.46 5.14 1.29 7.27L12 18.2l-5.83 3.48 1.29-7.27L2 9.27l7.1-1.01L12 2z" ' +
                    'fill="rgba(165,180,252,0.2)" stroke="rgba(165,180,252,0.5)" stroke-width="1.5"/>' +
                    '</svg>' +
                    '</button>';
        }
        html += '</div>' +
                '<p class="stars-label" style="font-size:0.85rem; color:rgba(255,255,255,0.5); margin-top:0.5rem; min-height:1.2em;"></p>';

        $container.html(html);

        var labels = ['', 'Horrível', 'Ruim', 'Regular', 'Bom', 'Excelente!'];
        var cores  = ['', '#ef4444', '#f97316', '#facc15', '#84cc16', '#22c55e'];

        function atualizarEstrelas(ate) {
            $container.find('.star-svg').each(function() {
                var n = parseInt($(this).data('nota'), 10);
                if (n <= ate) {
                    $(this).find('path').attr('fill', cores[ate] || '#FBBF24');
                    $(this).css('transform', 'scale(1.15)');
                } else {
                    $(this).find('path').attr('fill', 'rgba(165,180,252,0.2)');
                    $(this).css('transform', 'scale(1)');
                }
            });

            if (ate > 0) {
                $container.find('.stars-label')
                    .text(labels[ate])
                    .css('color', cores[ate]);
            } else {
                $container.find('.stars-label').text('Selecione uma nota').css('color', 'rgba(255,255,255,0.4)');
            }
        }

        // Hover
        $container.on('mouseenter', '.star-btn', function() {
            atualizarEstrelas(parseInt($(this).data('nota'), 10));
        });

        $container.on('mouseleave', '.aethos-stars', function() {
            atualizarEstrelas(notaSelecionada);
        });

        // Click
        $container.on('click', '.star-btn', function() {
            notaSelecionada = parseInt($(this).data('nota'), 10);
            $container.data('nota', notaSelecionada);
            atualizarEstrelas(notaSelecionada);
        });

        // Estado inicial
        atualizarEstrelas(0);
        $container.find('.stars-label').text('Selecione uma nota').css('color', 'rgba(255,255,255,0.4)');
    };

})(jQuery);
