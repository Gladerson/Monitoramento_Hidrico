<?php
$mac_address = $_GET['mac_address'] ?? '';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Detalhes do Dispositivo - Sistema de Monitoramento Hídrico da SEMARH</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="./scripts/tailwind.min.css" rel="stylesheet">
    <script src="./scripts/chart.umd.min.js"></script>
    <style>
        body {
            background-image: url('./img/gargalheiras.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            position: relative;
        }
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.9);
            z-index: -1;
        }
        .title-container {
            background: linear-gradient(135deg, #1a365d 0%, #2563eb 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
        }
        .month-btn {
            transition: all 0.2s ease;
        }
        .month-btn.active {
            background-color: #2563eb;
            color: white;
        }
        /* Garante que o canvas seja clicável */
        #monthlyConsumptionChart {
            position: relative;
            z-index: 10;
        }
    </style>
</head>
<body class="min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <div class="title-container text-center mb-16">
            <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold mb-4">
                Detalhes do Dispositivo
            </h1>
            <h2 class="text-xl md:text-2xl font-semibold">SEMARH</h2>
        </div>
        <div class="bg-white p-6 rounded-lg shadow-lg max-w-6xl mx-auto">
            <div class="flex justify-between items-center mb-8">
                <h2 class="text-2xl font-bold text-blue-600">Informações do Dispositivo</h2>
                <a href="index.php" class="text-gray-600 hover:text-gray-800 text-xl">Voltar</a>
            </div>
            <div id="device-info" class="mb-8"></div>
            <div class="mb-8 flex flex-wrap justify-center gap-4">
                <form id="zero-records-form" class="inline-block">
                    <input type="hidden" name="mac_address" value="<?= htmlspecialchars($mac_address); ?>">
                    <button type="submit" class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
                        Zerar Registros
                    </button>
                </form>
                <a href="download_logs.php?mac_address=<?= htmlspecialchars($mac_address); ?>" 
                   class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded inline-block">
                    Baixar Todos os Registros (.xlsx)
                </a>
                <button onclick="openLiveModal()" 
                        class="bg-blue-600 hover:bg-blue-800 text-white font-bold py-2 px-4 rounded">
                    Ver imagens ao vivo
                </button>
            </div>
            <div class="flex flex-col lg:flex-row gap-6">
                <!-- Coluna esquerda: Tabela de registros -->
                <div class="lg:w-1/2">
                    <div id="device-logs"></div>
                </div>
                <!-- Coluna direita: Gráficos -->
                <div class="lg:w-1/2">
                    <div class="mb-8 bg-white p-4 rounded-lg shadow-md">
                        <h3 class="text-xl font-semibold mb-4 text-blue-600">Consumo Mensal de Água</h3>
                        <div class="flex justify-center">
                            <div class="relative" style="width: 300px; height: 300px;">
                                <canvas id="monthlyConsumptionChart"></canvas>
                                <div class="absolute inset-0 flex items-center justify-center flex-col pointer-events-none">
                                    <p class="text-lg font-bold" id="currentMonthName"></p>
                                    <p class="text-2xl font-bold" id="currentMonthConsumption">0 L</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white p-4 rounded-lg shadow-md">
                        <h3 class="text-xl font-semibold mb-4 text-blue-600">Registro de Vazões Diárias</h3>
                        <div class="mb-4">
                            <canvas id="dailyFlowChart" height="200"></canvas>
                        </div>
                        <div class="grid grid-cols-3 gap-2 mt-4">
                            <button class="month-btn py-2 px-4 bg-gray-100 hover:bg-gray-200 rounded-md font-medium" data-month="1">Janeiro</button>
                            <button class="month-btn py-2 px-4 bg-gray-100 hover:bg-gray-200 rounded-md font-medium" data-month="2">Fevereiro</button>
                            <button class="month-btn py-2 px-4 bg-gray-100 hover:bg-gray-200 rounded-md font-medium" data-month="3">Março</button>
                            <button class="month-btn py-2 px-4 bg-gray-100 hover:bg-gray-200 rounded-md font-medium" data-month="4">Abril</button>
                            <button class="month-btn py-2 px-4 bg-gray-100 hover:bg-gray-200 rounded-md font-medium" data-month="5">Maio</button>
                            <button class="month-btn py-2 px-4 bg-gray-100 hover:bg-gray-200 rounded-md font-medium" data-month="6">Junho</button>
                            <button class="month-btn py-2 px-4 bg-gray-100 hover:bg-gray-200 rounded-md font-medium" data-month="7">Julho</button>
                            <button class="month-btn py-2 px-4 bg-gray-100 hover:bg-gray-200 rounded-md font-medium" data-month="8">Agosto</button>
                            <button class="month-btn py-2 px-4 bg-gray-100 hover:bg-gray-200 rounded-md font-medium" data-month="9">Setembro</button>
                            <button class="month-btn py-2 px-4 bg-gray-100 hover:bg-gray-200 rounded-md font-medium" data-month="10">Outubro</button>
                            <button class="month-btn py-2 px-4 bg-gray-100 hover:bg-gray-200 rounded-md font-medium" data-month="11">Novembro</button>
                            <button class="month-btn py-2 px-4 bg-gray-100 hover:bg-gray-200 rounded-md font-medium" data-month="12">Dezembro</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Modal de vídeo ao vivo -->
    <div id="liveModal" class="fixed inset-0 z-50 hidden bg-black bg-opacity-60 flex items-center justify-center">
        <div class="bg-white rounded-lg overflow-hidden shadow-xl w-full max-w-4xl">
            <div class="flex justify-between items-center bg-blue-600 text-white px-6 py-4">
                <h3 class="text-xl font-semibold">Transmissão ao Vivo</h3>
                <button onclick="closeLiveModal()" class="text-white text-2xl leading-none">×</button>
            </div>
            <div class="p-4 bg-black flex justify-center">
                <img src="http://172.16.100.240:5000/video_feed/<?= htmlspecialchars($mac_address); ?>" 
                     class="rounded-lg shadow-lg max-h-[75vh]" alt="Stream da Câmera">
            </div>
        </div>
    </div>  
    <script>
    // Variáveis globais para os gráficos
    let monthlyConsumptionChart;
    let dailyFlowChart;
    let currentMonth = new Date().getMonth() + 1; // Mês atual (1-12)
    let selectedMonth = new Date().getMonth(); // Mês selecionado para o gráfico de consumo mensal
    const monthNames = ["Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", 
                        "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro"];
    function openLiveModal() {
        document.getElementById("liveModal").classList.remove("hidden");
    }
    function closeLiveModal() {
        document.getElementById("liveModal").classList.add("hidden");
    }
    function loadDeviceInfo() {
        fetch(`get_device_info.php?mac_address=<?= urlencode($mac_address); ?>`)
            .then(response => response.json())
            .then(data => {
                const html = `
                    <div class="grid md:grid-cols-2 gap-6">
                        <div class="bg-white p-6 rounded-lg shadow-md">
                            <h3 class="text-xl font-semibold mb-4 text-blue-600">Detalhes Gerais</h3>
                            <p class="mb-2"><strong>MAC Address:</strong> ${data.mac_address}</p>
                            <p class="mb-2"><strong>Local:</strong> ${data.local}</p>
                            <p class="mb-2"><strong>Município:</strong> ${data.municipio}</p>
                            <p class="mb-2"><strong>Tipo:</strong> ${data.tipo}</p>
                            <p class="mb-2"><strong>Nome do Dispositivo:</strong> ${data.nome_dispositivo}</p>
                        </div>
                        <div class="bg-white p-6 rounded-lg shadow-md">
                            <h3 class="text-xl font-semibold mb-4 text-green-600">Informações de Monitoramento</h3>
                            <p class="mb-2"><strong>Vazão:</strong> ${data.vazao} L/min</p>
                            <p class="mb-2"><strong>Volume Acumulado:</strong> ${data.volume_acumulado} L</p>
                            <p class="mb-2"><strong>Status:</strong> <span style="color:${data.status === 'offline' ? 'red' : 'green'}">${data.status}</span></p>
                            <p class="mb-2"><strong>Última Atualização:</strong> ${data.ultima_atualizacao}</p>
                        </div>
                    </div>`;
                document.getElementById('device-info').innerHTML = html;
            })
            .catch(() => {
                document.getElementById('device-info').innerHTML = 
                    '<p class="text-center text-red-600">Erro ao carregar informações</p>';
            });
    }
    function loadDeviceLogs() {
        fetch(`get_device_logs.php?mac_address=<?= urlencode($mac_address); ?>`)
            .then(response => response.json())
            .then(data => {
                let html = `
                    <h2 class="text-2xl font-bold text-blue-600 mb-4">Registros do Dispositivo (diário)</h2>
                    <div class="overflow-x-auto">
                        <table class="min-w-full bg-white">
                            <thead class="bg-blue-600 text-white">
                                <tr>
                                    <th class="px-6 py-3 text-left">Timestamp</th>
                                    <th class="px-6 py-3 text-left">Vazão (L/min)</th>
                                    <th class="px-6 py-3 text-left">Volume Acumulado (L)</th>
                                </tr>
                            </thead>
                            <tbody>`;
                if (data.length > 0) {
                    data.forEach(log => {
                        html += `
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">${log.timestamp}</td>
                                <td class="px-6 py-4">${log.vazao} L/min</td>
                                <td class="px-6 py-4">${log.volume_acumulado} L</td>
                            </tr>`;
                    });
                } else {
                    html += `
                        <tr>
                            <td colspan="3" class="px-6 py-4 text-center">Sem registros disponíveis.</td>
                        </tr>`;
                }
                html += `</tbody></table></div>`;
                document.getElementById('device-logs').innerHTML = html;
            })
            .catch(() => {
                document.getElementById('device-logs').innerHTML = 
                    '<p class="text-center text-red-600">Erro ao carregar registros</p>';
            });
    }
    function loadMonthlyConsumptionData() {
        fetch(`get_monthly_consumption.php?mac_address=<?= urlencode($mac_address); ?>`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`Erro HTTP: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (!data.monthlyVolumes || !Array.isArray(data.monthlyVolumes) || data.monthlyVolumes.length !== 12) {
                    throw new Error('Dados de consumo mensal inválidos');
                }
                console.log('Dados de consumo mensal recebidos:', data.monthlyVolumes);
                updateMonthlyConsumptionChart(data);
            })
            .catch(error => {
                console.error('Erro ao carregar dados de consumo mensal:', error);
                document.getElementById('currentMonthName').textContent = 'Erro';
                document.getElementById('currentMonthConsumption').textContent = 'N/A';
            });
    }
    function loadDailyFlowData(month) {
        fetch(`get_daily_flow.php?mac_address=<?= urlencode($mac_address); ?>&month=${month}`)
            .then(response => response.json())
            .then(data => {
                updateDailyFlowChart(data, month);
            })
            .catch(error => {
                console.error('Erro ao carregar dados de vazão diária:', error);
            });
    }
    function updateMonthlyConsumptionText(monthIndex, monthlyVolumes) {
        if (monthIndex < 0 || monthIndex >= monthNames.length || !monthlyVolumes[monthIndex]) {
            console.warn(`Índice de mês inválido ou volume ausente: ${monthIndex}`);
            return;
        }
        console.log(`Atualizando texto para o mês ${monthNames[monthIndex]} com volume ${monthlyVolumes[monthIndex]}`);
        document.getElementById('currentMonthName').textContent = monthNames[monthIndex];
        document.getElementById('currentMonthConsumption').textContent = 
            `${monthlyVolumes[monthIndex].toLocaleString()} L`;
    }
    function initializeMonthlyConsumptionChart() {
        const canvas = document.getElementById('monthlyConsumptionChart');
        const ctx = canvas.getContext('2d');
        // Dados iniciais (serão atualizados depois)
        const data = {
            labels: monthNames,
            datasets: [{
                data: Array(12).fill(10), // Valores iniciais
                backgroundColor: [
                    '#3498db', '#2ecc71', '#f1c40f', '#e74c3c', '#9b59b6', '#1abc9c',
                    '#34495e', '#16a085', '#f39c12', '#d35400', '#c0392b', '#8e44ad'
                ],
                borderWidth: 1,
                cutout: '70%'
            }]
        };
        monthlyConsumptionChart = new Chart(ctx, {
            type: 'doughnut',
            data: data,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `${context.label}: ${context.raw.toLocaleString()} L`;
                            }
                        }
                    }
                },
                onClick: (event, elements, chart) => {
                    console.log('Evento onClick disparado', elements);
                    if (elements.length > 0) {
                        const elementIndex = elements[0].index;
                        selectedMonth = elementIndex; // Atualiza o mês selecionado
                        const monthlyVolumes = chart.data.datasets[0].data;
                        console.log(`Elemento clicado: Mês ${monthNames[elementIndex]}`);
                        updateMonthlyConsumptionText(elementIndex, monthlyVolumes);
                    } else {
                        console.log('Nenhum elemento clicado no gráfico');
                    }
                }
            }
        });
        // Manipulador de clique manual no canvas
        canvas.addEventListener('click', (event) => {
            console.log('Clique manual detectado no canvas');
            const elements = monthlyConsumptionChart.getElementsAtEventForMode(
                event, 'nearest', { intersect: true }, false
            );
            if (elements.length > 0) {
                const elementIndex = elements[0].index;
                selectedMonth = elementIndex; // Atualiza o mês selecionado
                const monthlyVolumes = monthlyConsumptionChart.data.datasets[0].data;
                console.log(`Elemento clicado: Mês ${monthNames[elementIndex]}`);
                updateMonthlyConsumptionText(elementIndex, monthlyVolumes);
            } else {
                console.log('Nenhum elemento encontrado no clique manual');
            }
        });
        // Inicializa com o mês atual
        selectedMonth = new Date().getMonth();
        console.log('Inicializando gráfico com mês:', monthNames[selectedMonth]);
        document.getElementById('currentMonthName').textContent = monthNames[selectedMonth];
        document.getElementById('currentMonthConsumption').textContent = '0 L';
    }
    function initializeDailyFlowChart() {
        const ctx = document.getElementById('dailyFlowChart').getContext('2d');
        // Dados iniciais (serão atualizados depois)
        const data = {
            labels: Array.from({length: 31}, (_, i) => i + 1), // Dias de 1 a 31
            datasets: [{
                label: 'Vazão (L/min)',
                data: Array(31).fill(null),
                borderColor: '#3498db',
                backgroundColor: 'rgba(52, 152, 219, 0.2)',
                borderWidth: 2,
                tension: 0.4,
                pointRadius: 4,
                pointBackgroundColor: '#3498db'
            }]
        };
        dailyFlowChart = new Chart(ctx, {
            type: 'line',
            data: data,
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Vazão (L/min)'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Dia do mês'
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    }
    function updateMonthlyConsumptionChart(data) {
        // Atualiza os dados do gráfico
        monthlyConsumptionChart.data.datasets[0].data = data.monthlyVolumes;
        monthlyConsumptionChart.update();
        // Atualiza o texto com o mês selecionado
        console.log('Atualizando gráfico com mês selecionado:', monthNames[selectedMonth]);
        updateMonthlyConsumptionText(selectedMonth, data.monthlyVolumes);
    }
    function updateDailyFlowChart(data, month) {
        // Determina o número de dias no mês
        const daysInMonth = new Date(new Date().getFullYear(), month, 0).getDate();
        // Atualiza os rótulos para refletir o número correto de dias
        dailyFlowChart.data.labels = Array.from({length: daysInMonth}, (_, i) => i + 1);
        // Atualiza os dados do gráfico
        dailyFlowChart.data.datasets[0].data = data.dailyFlows;
        dailyFlowChart.data.datasets[0].label = `Vazão - ${monthNames[month-1]}`;
        dailyFlowChart.update();
        // Atualiza os botões de mês (destaca o selecionado)
        document.querySelectorAll('.month-btn').forEach(btn => {
            if (parseInt(btn.dataset.month) === month) {
                btn.classList.add('active');
            } else {
                btn.classList.remove('active');
            }
        });
    }
    document.getElementById('zero-records-form').addEventListener('submit', function(e) {
        e.preventDefault();
        if (confirm('Tem certeza que deseja zerar todos os registros deste dispositivo?')) {
            fetch('zerar_registros.php', {
                method: 'POST',
                body: new FormData(this)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Registros zerados com sucesso!');
                    loadDeviceLogs();
                    loadMonthlyConsumptionData();
                    loadDailyFlowData(currentMonth);
                } else {
                    alert('Erro ao zerar registros: ' + data.message);
                }
            })
            .catch(() => {
                alert('Erro ao processar solicitação');
            });
        }
    });
    // Adiciona event listeners para os botões de mês
    document.querySelectorAll('.month-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const month = parseInt(this.dataset.month);
            currentMonth = month;
            loadDailyFlowData(month);
        });
    });
    // Inicializa a página
    loadDeviceInfo();
    loadDeviceLogs();
    initializeMonthlyConsumptionChart();
    initializeDailyFlowChart();
    loadMonthlyConsumptionData();
    loadDailyFlowData(currentMonth);
    // Atualiza os dados periodicamente
    setInterval(loadDeviceInfo, 2000);
    setInterval(loadDeviceLogs, 2000);
    setInterval(loadMonthlyConsumptionData, 10000);
    setInterval(() => loadDailyFlowData(currentMonth), 10000);
    </script>
