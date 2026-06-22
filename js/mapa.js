// PUXAR GEOLOCALIZAÇÃO
const map = L.map('map').setView([-15.7800, -47.9300], 15);

function obterLocal(){
    if(navigator.geolocation){
        navigator.geolocation.getCurrentPosition(sucesso, erro, {
            enableHighAccuracy: true,
            timeout: 6000,
            maximumAge: 0
        })
    }else{
        alert("Geolocalização não suportada")
    };   
};

function sucesso(position){
    const latitude = position.coords.latitude;
    const longitude = position.coords.longitude;

    // Centraliza o mapa na posição do usuário
    map.setView([latitude, longitude], 15);

    // Cria o marcador separadamente — CORRETO (anteriormente vinculava popup ao mapa, não ao marcador)
    L.marker([latitude, longitude])
        .addTo(map)
        .bindPopup("📍 Você está aqui")
        .openPopup();
};


function erro(err){
    console.warn(`Erro(${err.code}): ${err.message}`);
    alert("Não foi possível obter sua localização");
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
        // Fica na visão padrão (Brasília)
    });
} else {
    // Fallback caso o modal não exista no DOM
    obterLocal();
}

// Usar o tile layer padrão do OpenStreetMap (que será convertido para Dark Mode via filtro CSS)
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
    maxZoom: 19
}).addTo(map);

// LÓGICA DA BUSCA E AUTOCOMPLETAR
let marcacaoAtual = null;
let circuloRaioAtual = null;
const searchInput = document.getElementById('searchInput');
const radiusInput = document.getElementById('radiusInput');
const searchBtn = document.getElementById('searchBtn');
const caixaSugestoes = document.getElementById('caixaSugestoes');
let tempoEspera;

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

// Busca ao clicar no botão (Filtro Local de Esportes)
searchBtn.addEventListener('click', () => {
    const query = searchInput.value.trim().toLowerCase();
    const raioKm = parseFloat(radiusInput.value) || 5;
    
    caixaSugestoes.style.display = 'none';

    // Obtém o centro atual do mapa (localização do usuário)
    const center = map.getCenter();
    desenharRaio(center.lat, center.lng, raioKm);

    if (!window.marcadoresAethos || window.marcadoresAethos.length === 0) {
        console.warn("Nenhum marcador carregado ainda.");
        return;
    }

    let locaisEncontrados = 0;

    // Percorre todos os marcadores carregados na memória
    window.marcadoresAethos.forEach(marker => {
        const markerPos = marker.getLatLng();
        const distanciaMetros = map.distance(center, markerPos);
        
        const data = marker.aethosData;
        const matchesQuery = !query || 
                             data.nome.includes(query) || 
                             data.modalidade.includes(query);

        // Se está dentro do raio E bate com a pesquisa
        if (distanciaMetros <= (raioKm * 1000) && matchesQuery) {
            if (!map.hasLayer(marker)) {
                marker.addTo(map);
            }
            locaisEncontrados++;
        } else {
            if (map.hasLayer(marker)) {
                map.removeLayer(marker);
            }
        }
    });

    if (locaisEncontrados === 0 && query !== '') {
        alert('Nenhum esporte ou local encontrado nesse raio com o termo pesquisado.');
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
    const textoDigitado = evento.target.value.trim().toLowerCase();
    caixaSugestoes.innerHTML = '';

    if (textoDigitado.length < 1 || !window.marcadoresAethos) {
        caixaSugestoes.style.display = 'none';
        return;
    }

    const sugestoes = new Set();
    window.marcadoresAethos.forEach(marker => {
        const data = marker.aethosData;
        if (data.modalidade.includes(textoDigitado)) {
            // Sugerir a modalidade
            sugestoes.add(data.modalidade.charAt(0).toUpperCase() + data.modalidade.slice(1));
        }
        if (data.nome.includes(textoDigitado)) {
            sugestoes.add(data.nome);
        }
    });

    if (sugestoes.size > 0) {
        caixaSugestoes.style.display = 'block';
        let count = 0;
        sugestoes.forEach(sugestao => {
            if (count >= 5) return; // Limitar a 5 sugestões
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