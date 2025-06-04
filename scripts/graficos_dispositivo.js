// Arquivo: scripts/graficos_dispositivo.js

const macAddress = new URLSearchParams(window.location.search).get('mac_address');
let mesAtual = new Date().getMonth(); // 0 = janeiro

async function fetchConsumoMensal() {
    const res = await fetch(`get_consumo_mensal.php?mac_address=${macAddress}`);
    return await res.json();
}

async function fetchVazaoMensal(mes) {
    const res = await fetch(`get_vazao_mensal.php?mac_address=${macAddress}&mes=${mes + 1}`); // mes +1 porque PHP começa em 1
    return await res.json();
}

function renderConsumoMensalChart(data) {
    const ctx = document.getElementById('consumoMensalChart').getContext('2d');
    const totalAtual = data[mesAtual]?.total || 0;
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: data.map((_, i) => ["Jan","Fev","Mar","Abr","Mai","Jun","Jul","Ago","Set","Out","Nov","Dez"][i]),
            datasets: [{
                data: data.map(m => m.total || 0),
                backgroundColor: data.map((_, i) => i === mesAtual ? '#2563eb' : '#a0aec0'),
                borderWidth: 1
            }]
        },
        options: {
            plugins: {
                tooltip: { enabled: true },
                legend: { display: false },
                title: {
                    display: true,
                    text: `Consumo Més Atual: ${totalAtual.toFixed(2)} L`,
                    position: 'center',
                    color: '#2563eb',
                    font: {
                        size: 16
                    }
                }
            },
            cutout: '70%'
        }
    });
}

function renderGraficoVazaoDiaria(data) {
    const ctx = document.getElementById('graficoVazaoDiaria').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.map(p => p.dia),
            datasets: [{
                label: 'Vazão Diária (L/min)',
                data: data.map(p => p.vazao),
                fill: false,
                borderColor: '#2563eb',
                tension: 0.1
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

function carregarGraficoVazao(mes) {
    fetchVazaoMensal(mes).then(renderGraficoVazaoDiaria);
}

// Inicializa tudo
fetchConsumoMensal().then(renderConsumoMensalChart);
carregarGraficoVazao(mesAtual);

// Eventos dos botões
const botoes = document.querySelectorAll('#graficoVazaoDiaria ~ .grid button');
botoes.forEach((btn, index) => {
    btn.addEventListener('click', () => {
        carregarGraficoVazao(index);
    });
});
