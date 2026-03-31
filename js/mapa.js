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

    map.setView([latitude, longitude]).addTo(map)
    .bindPopup("Você está aqui")
    .openPopup();
};

function erro(err){
    console.warn(`Erro(${err.code}): ${err.message}`);
    alert("Não foi possível obter sua localização");
}

obterLocal();

L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy <a href="https://www.openstreetmap.org/">OpenStreetMap</a> contributors'
}).addTo(map);

// LÓGICA DA BUSCA E AUTOCOMPLETAR
let marcacaoAtual = null;
const searchInput = document.getElementById('searchInput');
const searchBtn = document.getElementById('searchBtn');
const caixaSugestoes = document.getElementById('caixaSugestoes');
let tempoEspera;

// Busca ao clicar no botão
searchBtn.addEventListener('click', async () => {
    const query = searchInput.value.trim();
    if(!query) return;

    caixaSugestoes.style.display = 'none';

    const url= `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}`;
    try{
        const response = await fetch (url);
        const results = await response.json();
        
        if(results.length > 0){
            const { lat, lon, display_name } = results[0];
            if(marcacaoAtual){
                map.removeLayer(marcacaoAtual);
            }

            marcacaoAtual = L.marker([lat, lon]).addTo(map)
                .bindPopup(display_name)
                .openPopup();
            map.setView([lat, lon], 14);

        }else{
            alert('Nenhum Local encontrado.')
        }
    }catch (error){
        console.error('Ocorreu um Erro ao Buscar o Local:', error);
        alert('Nenhum Local encontrado.');
    };
});

// Autocompletar ao digitar
searchInput.addEventListener('input', (evento)=>{
    const textoDigitado = evento.target.value.trim();

    if (textoDigitado.length < 1 ){
        caixaSugestoes.style.display = 'none';
        return;
    }

    clearTimeout(tempoEspera);

    tempoEspera = setTimeout(() => {
        buscarSugestoesAPI(textoDigitado);
    }, 0);
});

async function buscarSugestoesAPI(query){
    const url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&limit=5`; 

    try{
        const response = await fetch(url);
        const results = await response.json();

        caixaSugestoes.innerHTML = '';

        if(results.length > 0){
            caixaSugestoes.style.display = 'block';

            results.forEach(local => {
                const item = document.createElement('button');
                item.className = 'list-group-item list-group-item-action text-start';
                item.textContent = local.display_name;

                item.addEventListener('click', () => {
                    caixaSugestoes.style.display = 'none';
                    searchInput.value = local.display_name;
                    
                    if(marcacaoAtual){ map.removeLayer(marcacaoAtual); }
                    marcacaoAtual = L.marker([local.lat, local.lon]).addTo(map).bindPopup(local.display_name).openPopup();
                    map.setView([local.lat, local.lon], 14);
                });

                caixaSugestoes.appendChild(item);
            });
        }else{
            caixaSugestoes.style.display =  'none';
        }
    } catch (error){
        console.error('Erro ao buscar sugestões', error );
    }
}