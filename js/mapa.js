// PUXAR GEOLOCALIZAÇÃO
const map = L.map('map').setView([-15.7800, -47.9300], 15);
window.mapaAethos = map; // Expor globalmente para mapa-locais.js

function obterLocal(){
    if(navigator.geolocation){
        navigator.geolocation.getCurrentPosition(sucesso, erro, {
            enableHighAccuracy: true,
            timeout: 15000,
            maximumAge: 0
        })
    }else{
        alert("Geolocalização não suportada")
    };   
};

let userMarker = null;
let isRelocating = false;

function sucesso(position){
    const latitude = position.coords.latitude;
    const longitude = position.coords.longitude;

    // Centraliza o mapa na posição do usuário
    map.setView([latitude, longitude], 15);

    if (userMarker) {
        map.removeLayer(userMarker);
    }

    // Cria o marcador separadamente — CORRETO
    userMarker = L.marker([latitude, longitude], { draggable: true })
        .addTo(map)
        .bindPopup("📍 Você está aqui<br><small style='color: #666;'>(Arraste o pino se a localização estiver imprecisa)</small>")
        .openPopup();
        
    userMarker.on('dragend', function(e) {
        const novaPos = userMarker.getLatLng();
        map.setView(novaPos, 15);
    });
        
    isRelocating = false;
};


function erro(err){
    console.warn(`Erro(${err.code}): ${err.message}`);
    if (isRelocating) {
        alert("Sua localização está bloqueada ou indisponível no momento. Por favor, clique no ícone de cadeado na barra de endereço do seu navegador, PERMITA a Localização e clique no botão novamente.");
        isRelocating = false;
    } else {
        console.warn("Não foi possível obter sua localização inicial. Usando visão padrão.");
    }
}

// LÓGICA DO MODAL DE PERMISSÃO (UX)
const modalGeo = document.getElementById('modal-geo');
const btnGeoPermitir = document.getElementById('btn-geo-permitir');
const btnGeoNegar = document.getElementById('btn-geo-negar');

if (modalGeo && btnGeoPermitir && btnGeoNegar) {
    btnGeoPermitir.addEventListener('click', () => {
        modalGeo.style.display = 'none';
        obterLocal();
    });

    btnGeoNegar.addEventListener('click', () => {
        modalGeo.style.display = 'none';
        // Fica na visão padrão
    });
} else {
    obterLocal();
}

const btnRelocate = document.getElementById('btn-relocate');
if (btnRelocate) {
    btnRelocate.addEventListener('click', () => {
        isRelocating = true;
        // Se já tivermos o marcador do usuário e não quisermos chamar a API de novo,
        // podemos apenas focar nele, mas como o usuário pode ter se movido, chamamos obterLocal.
        obterLocal();
    });
}

// Usar o tile layer padrão do OpenStreetMap (que será convertido para Dark Mode via filtro CSS)
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
    maxZoom: 19
}).addTo(map);

// ==========================================
// BUSCA MANUAL DE ENDEREÇO (Nominatim Autocomplete)
// ==========================================
const userLocInput = document.getElementById('userLocationInput');
const caixaEnderecos = document.getElementById('caixaEnderecos');

if (userLocInput && caixaEnderecos) {
    let locTimeout;
    userLocInput.addEventListener('input', function() {
        const query = this.value.trim();
        caixaEnderecos.innerHTML = '';
        caixaEnderecos.style.display = 'none';

        if (query.length < 4) return;

        clearTimeout(locTimeout);
        locTimeout = setTimeout(() => {
            fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&limit=4&addressdetails=1&countrycodes=BR`)
                .then(res => res.json())
                .then(data => {
                    if (data && data.length > 0) {
                        caixaEnderecos.style.display = 'block';
                        data.forEach(item => {
                            let div = document.createElement('div');
                            div.className = 'sugestao-item';
                            div.innerHTML = `<i class="fa fa-map-marker-alt"></i> ${item.display_name}`;
                            div.onclick = function() {
                                // Mover pino para cá!
                                userLocInput.value = item.display_name.split(',')[0];
                                caixaEnderecos.style.display = 'none';

                                const lat = parseFloat(item.lat);
                                const lon = parseFloat(item.lon);

                                map.setView([lat, lon], 15);

                                if (userMarker) {
                                    map.removeLayer(userMarker);
                                }

                                userMarker = L.marker([lat, lon], { draggable: true })
                                    .addTo(map)
                                    .bindPopup("📍 Você está aqui")
                                    .openPopup();

                                userMarker.on('dragend', function(e) {
                                    const novaPos = userMarker.getLatLng();
                                    map.setView(novaPos, 15);
                                });
                            };
                            caixaEnderecos.appendChild(div);
                        });
                    }
                })
                .catch(err => console.error("Erro na busca de endereço", err));
        }, 500);
    });

    // Fechar ao clicar fora
    document.addEventListener('click', function(e) {
        if (e.target !== userLocInput && e.target !== caixaEnderecos) {
            caixaEnderecos.style.display = 'none';
        }
    });
}

// LÓGICA DA BUSCA E AUTOCOMPLETAR
let marcacaoAtual = null;
let circuloRaioAtual = null;
const searchInput = document.getElementById('searchInput');
const radiusInput = document.getElementById('radiusInput');
const searchBtn = document.getElementById('searchBtn');
const caixaSugestoes = document.getElementById('caixaSugestoes');
let tempoEspera;

// Dicionário de Fuzzy Search
const typoDict = {
    'carate': 'karate', 'kaarate': 'karate', 'kapoeira': 'capoeira',
    'judo': 'judo', 'judó': 'judo', 'futbol': 'futebol', 'futbal': 'futebol',
    'futeból': 'futebol', 'nataçao': 'natacao', 'natacao': 'natacao',
    'volei': 'volei', 'crosfit': 'crossfit', 'crosfite': 'crossfit',
    'muaitai': 'muay thai', 'muaythai': 'muay thai', 'jiujitsu': 'jiu-jitsu'
};

function normalizeString(str) {
    if (!str) return '';
    let normalized = str.normalize("NFD").replace(/[\u0300-\u036f]/g, "").toLowerCase().trim();
    return typoDict[normalized] || normalized;
}

// Função para desenhar o raio
function desenharRaio(lat, lon, raioKm) {
    if (circuloRaioAtual) {
        map.removeLayer(circuloRaioAtual);
    }
    const raioMetros = raioKm * 1000;
    circuloRaioAtual = L.circle([lat, lon], {
        color: '#A5B4FC',
        fillColor: '#A5B4FC',
        fillOpacity: 0.15,
        radius: raioMetros,
        weight: 2,
        dashArray: '5, 10'
    }).addTo(map);
    
    // Ajustar o zoom para caber o círculo
    map.fitBounds(circuloRaioAtual.getBounds());
}

function filtrarMarcadoresNoRaio(centro, raioKm, termoBusca) {
    let locaisEncontrados = 0;
    const queryNorm = normalizeString(termoBusca);

    window.marcadoresAethos.forEach(marker => {
        const markerPos = marker.getLatLng();
        const distanciaMetros = map.distance(centro, markerPos);
        
        const data = marker.aethosData;
        const nomeNorm = normalizeString(data.nome);
        const modNorm = normalizeString(data.modalidade);
        
        const matchesQuery = !queryNorm || nomeNorm.includes(queryNorm) || modNorm.includes(queryNorm);

        if (distanciaMetros <= (raioKm * 1000) && matchesQuery) {
            if (!map.hasLayer(marker)) marker.addTo(map);
            locaisEncontrados++;
        } else {
            if (map.hasLayer(marker)) map.removeLayer(marker);
        }
    });

    return locaisEncontrados;
}

// Busca ao clicar no botão (Filtro Local de Esportes)
searchBtn.addEventListener('click', () => {
    const rawQuery = searchInput.value.trim();
    const raioKm = parseFloat(radiusInput.value) || 5;
    caixaSugestoes.style.display = 'none';

    if (!window.marcadoresAethos || window.marcadoresAethos.length === 0) {
        console.warn("Nenhum marcador carregado ainda.");
        return;
    }

    // 1º Tentar buscar nos esportes com a posição do usuário (pino) ou centro do mapa
    const centerAtual = userMarker ? userMarker.getLatLng() : map.getCenter();
    let locaisEncontrados = filtrarMarcadoresNoRaio(centerAtual, raioKm, rawQuery);

    if (locaisEncontrados > 0 || rawQuery === '') {
        desenharRaio(centerAtual.lat, centerAtual.lng, raioKm);
        map.setView(centerAtual, map.getZoom()); // Garante que a tela foque no centro da busca
    } else {
        // 2º Se não achou nenhum esporte, tentar buscar como POI/Endereço no Nominatim
        const searchBtnOldText = searchBtn.innerHTML;
        searchBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i>';
        
        $.ajax({
            url: `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(rawQuery)}&countrycodes=br&limit=1`,
            method: 'GET',
            success: function(data) {
                searchBtn.innerHTML = searchBtnOldText;
                if (data && data.length > 0) {
                    const novoCentro = L.latLng(data[0].lat, data[0].lon);
                    map.setView(novoCentro, 14); // Move o mapa para o POI
                    desenharRaio(novoCentro.lat, novoCentro.lng, raioKm);
                    
                    // Mostra todos os esportes ao redor deste novo local (string de busca vazia)
                    let esportesNoNovoLocal = filtrarMarcadoresNoRaio(novoCentro, raioKm, "");
                    
                    if (esportesNoNovoLocal === 0) {
                        alert(`Encontramos o local "${data[0].display_name.split(',')[0]}", mas não há esportes num raio de ${raioKm}km daqui.`);
                    }
                } else {
                    alert('Nenhum esporte encontrado perto de você, e o local pesquisado também não foi encontrado no mapa.');
                }
            },
            error: function() {
                searchBtn.innerHTML = searchBtnOldText;
                alert('Erro ao comunicar com o servidor de mapas. Tente novamente.');
            }
        });
    }
});

// Aciona busca ao pressionar Enter no campo de texto
searchInput.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') {
        e.preventDefault();
        searchBtn.click();
    }
});

// Autocomplete simples baseado nos marcadores carregados
searchInput.addEventListener('input', (evento)=>{
    const textoDigitado = evento.target.value.trim();
    const queryNorm = normalizeString(textoDigitado);
    caixaSugestoes.innerHTML = '';

    if (queryNorm.length < 1 || !window.marcadoresAethos) {
        caixaSugestoes.style.display = 'none';
        return;
    }

    const sugestoes = new Set();
    window.marcadoresAethos.forEach(marker => {
        const data = marker.aethosData;
        const modNorm = normalizeString(data.modalidade);
        const nomeNorm = normalizeString(data.nome);
        
        if (modNorm.includes(queryNorm)) {
            sugestoes.add(data.modalidade.charAt(0).toUpperCase() + data.modalidade.slice(1));
        }
        if (nomeNorm.includes(queryNorm)) {
            sugestoes.add(data.nome);
        }
    });

    if (sugestoes.size > 0) {
        caixaSugestoes.style.display = 'block';
        let count = 0;
        sugestoes.forEach(sugestao => {
            if (count >= 5) return;
            const item = document.createElement('button');
            item.className = 'list-group-item list-group-item-action text-start';
            item.textContent = sugestao;
            
            item.addEventListener('click', () => {
                caixaSugestoes.style.display = 'none';
                searchInput.value = sugestao;
                searchBtn.click();
            });

            caixaSugestoes.appendChild(item);
            count++;
        });
    } else {
        caixaSugestoes.style.display = 'none';
    }
});