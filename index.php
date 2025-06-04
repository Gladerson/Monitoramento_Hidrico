<!DOCTYPE html>
<html>
<head>
    <title>Sistema de Monitoramento Hídrico da SEMARH</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="./scripts/tailwind.min.css" rel="stylesheet">
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

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
        }

        .modal-content {
            position: relative;
            background: white;
            margin: 2% auto;
            padding: 20px;
            width: 90%;
            max-width: 1200px;
            max-height: 90vh;
            overflow-y: auto;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .button-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .button-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }

        .title-container {
            background: linear-gradient(135deg, #1a365d 0%, #2563eb 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body class="min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <div class="title-container text-center mb-16">
            <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold mb-4">
                Sistema de Monitoramento Hídrico
            </h1>
            <h2 class="text-xl md:text-2xl font-semibold">SEMARH</h2>
        </div>

        <div class="grid md:grid-cols-2 gap-8 max-w-4xl mx-auto">
            <div class="button-card bg-white p-8 rounded-lg shadow-lg text-center cursor-pointer" onclick="showModal('disponiveisModal')">
                <h3 class="text-2xl font-bold mb-4 text-blue-600">Dispositivos Disponíveis</h3>
                <p class="text-gray-600 mb-4">Visualize e gerencie todos os dispositivos disponíveis no sistema</p>
                <div class="inline-block bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors">
                    Acessar Lista
                </div>
            </div>

            <div class="button-card bg-white p-8 rounded-lg shadow-lg text-center cursor-pointer" onclick="showModal('cadastradosModal')">
                <h3 class="text-2xl font-bold mb-4 text-green-600">Dispositivos Cadastrados</h3>
                <p class="text-gray-600 mb-4">Acesse informações detalhadas dos dispositivos já cadastrados</p>
                <div class="inline-block bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700 transition-colors">
                    Visualizar Dados
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Dispositivos Disponíveis -->
    <div id="disponiveisModal" class="modal">
        <div class="modal-content">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-2xl font-bold text-blue-600">Dispositivos Disponíveis</h2>
                <button onclick="hideModal('disponiveisModal')" class="text-gray-600 hover:text-gray-800 text-2xl">&times;</button>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white">
                    <thead class="bg-blue-600 text-white">
                        <tr>
                            <th class="px-6 py-3 text-left">MAC Address</th>
                            <th class="px-6 py-3 text-left">Local</th>
                            <th class="px-6 py-3 text-left">Município</th>
                            <th class="px-6 py-3 text-left">Tipo</th>
                            <th class="px-6 py-3 text-left">Nome do Dispositivo</th>
                            <th class="px-6 py-3 text-left">Última Atualização</th>
                            <th class="px-6 py-3 text-left">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Conexão com o banco de dados
                        $servername = "localhost";
                        $username = "root";
                        $password = "DmC1884";
                        $dbname = "MonitoramentoHidrico";

                        // Criando conexão
                        $conn = new mysqli($servername, $username, $password, $dbname);

                        // Verificando conexão
                        if ($conn->connect_error) {
                            die("Falha na conexão: " . $conn->connect_error);
                        }

                        // Buscando dispositivos disponíveis
                        $sql = "SELECT * FROM dispositivos_disponiveis";
                        $result = $conn->query($sql);

                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                echo "<tr class='hover:bg-gray-50'>";
                                echo "<td class='px-6 py-4'>" . htmlspecialchars($row['mac_address']) . "</td>";
                                echo "<td class='px-6 py-4'>" . htmlspecialchars($row['local']) . "</td>";
                                echo "<td class='px-6 py-4'>" . htmlspecialchars($row['municipio']) . "</td>";
                                echo "<td class='px-6 py-4'>" . htmlspecialchars($row['tipo']) . "</td>";
                                echo "<td class='px-6 py-4'>" . htmlspecialchars($row['nome_dispositivo']) . "</td>";
                                echo "<td class='px-6 py-4'>" . htmlspecialchars($row['ultima_atualizacao']) . "</td>";
                                echo "<td class='px-6 py-4'><form action='cadastrar.php' method='post'><input type='hidden' name='mac_address' value='" . htmlspecialchars($row['mac_address']) . "'><input type='submit' class='bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded' value='Cadastrar'></form></td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='7' class='px-6 py-4 text-center'>Nenhum dispositivo disponível.</td></tr>";
                        }

                        $conn->close();
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Dispositivos Cadastrados -->
    <div id="cadastradosModal" class="modal">
        <div class="modal-content">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-2xl font-bold text-green-600">Dispositivos Cadastrados</h2>
                <button onclick="hideModal('cadastradosModal')" class="text-gray-600 hover:text-gray-800 text-2xl">&times;</button>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white">
                    <thead class="bg-green-600 text-white">
                        <tr>
                            <th class="px-6 py-3 text-left">MAC Address</th>
                            <th class="px-6 py-3 text-left">Local</th>
                            <th class="px-6 py-3 text-left">Município</th>
                            <th class="px-6 py-3 text-left">Tipo</th>
                            <th class="px-6 py-3 text-left">Nome do Dispositivo</th>
                            <th class="px-6 py-3 text-left">Vazão</th>
                            <th class="px-6 py-3 text-left">Volume Acumulado</th>
                            <th class="px-6 py-3 text-left">Status</th>
                            <th class="px-6 py-3 text-left">Última Atualização</th>
                            <th class="px-6 py-3 text-left">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Criando conexão
                        $conn = new mysqli($servername, $username, $password, $dbname);

                        // Verificando conexão
                        if ($conn->connect_error) {
                            die("Falha na conexão: " . $conn->connect_error);
                        }

                        // Buscando dispositivos cadastrados
                        $sql = "SELECT * FROM dispositivos_cadastrados";
                        $result = $conn->query($sql);

                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                echo "<tr class='hover:bg-gray-50'>";
                                echo "<td class='px-6 py-4'>" . htmlspecialchars($row['mac_address']) . "</td>";
                                echo "<td class='px-6 py-4'>" . htmlspecialchars($row['local']) . "</td>";
                                echo "<td class='px-6 py-4'>" . htmlspecialchars($row['municipio']) . "</td>";
                                echo "<td class='px-6 py-4'>" . htmlspecialchars($row['tipo']) . "</td>";
                                echo "<td class='px-6 py-4'><a href='detalhes.php?mac_address=" . htmlspecialchars($row['mac_address']) . "' class='text-blue-600 hover:text-blue-800'>" . htmlspecialchars($row['nome_dispositivo']) . "</a></td>";
                                echo "<td class='px-6 py-4'>" . htmlspecialchars($row['vazao']) . " L/min</td>";
                                echo "<td class='px-6 py-4'>" . htmlspecialchars($row['volume_acumulado']) . " L</td>";
                                echo "<td class='px-6 py-4' style='color:" . ($row['status'] == 'offline' ? 'red' : 'black') . "'>" . htmlspecialchars($row['status']) . "</td>";
                                echo "<td class='px-6 py-4'>" . htmlspecialchars($row['ultima_atualizacao']) . "</td>";
                                echo "<td class='px-6 py-4'>
                                    <form action='remover.php' method='post'>
                                        <input type='hidden' name='mac_address' value='" . htmlspecialchars($row['mac_address']) . "'>
                                        <input type='submit' class='bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded' value='Remover'>
                                    </form>
                                </td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='10' class='px-6 py-4 text-center'>Nenhum dispositivo cadastrado.</td></tr>";
                        }

                        $conn->close();
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        function showModal(modalId) {
            document.getElementById(modalId).style.display = 'block';
            document.body.style.overflow = 'hidden';
        }

        function hideModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        // Fechar modal ao clicar fora dele
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                hideModal(event.target.id);
            }
        }
    </script>
</body>
</html>