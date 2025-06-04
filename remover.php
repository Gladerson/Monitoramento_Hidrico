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

// Capturando dados do formulário
$mac_address = $_POST['mac_address'];

// Desabilitar verificação de chaves estrangeiras
$conn->query("SET FOREIGN_KEY_CHECKS = 0");

// Buscando status do dispositivo
$sql = "SELECT status FROM dispositivos_cadastrados WHERE mac_address = '$mac_address'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $status = $row['status'];

    if ($status == 'online') {
        // Mover dispositivo para dispositivos_disponiveis
        $sql_insert = "INSERT INTO dispositivos_disponiveis (mac_address, local, municipio, tipo, nome_dispositivo, ultima_atualizacao)
        SELECT mac_address, local, municipio, tipo, nome_dispositivo, NOW() FROM dispositivos_cadastrados WHERE mac_address = '$mac_address'
        ON DUPLICATE KEY UPDATE ultima_atualizacao = NOW()";

        if ($conn->query($sql_insert) === TRUE) {
            // Remover dispositivo da tabela dispositivos_cadastrados
            $sql_delete = "DELETE FROM dispositivos_cadastrados WHERE mac_address = '$mac_address'";
            $conn->query($sql_delete);

            // Remover entradas da tabela registros_dispositivo
            $sql_delete_records = "DELETE FROM registros_dispositivo WHERE mac_address = '$mac_address'";
            $conn->query($sql_delete_records);

            echo "Dispositivo movido para a lista de disponíveis e registros removidos.";
            // Redirecionar para index.php após 2 segundos
            echo "<script>
                    setTimeout(function(){
                       window.location.href = 'index.php';
                    }, 2000);
                  </script>";
        } else {
            echo "Erro ao mover dispositivo: " . $conn->error;
        }
    } else {
        // Remover dispositivo de ambas as tabelas
        $sql_delete = "DELETE FROM dispositivos_disponiveis WHERE mac_address = '$mac_address'";
        $conn->query($sql_delete);

        $sql_delete = "DELETE FROM dispositivos_cadastrados WHERE mac_address = '$mac_address'";
        $conn->query($sql_delete);

        // Remover entradas da tabela registros_dispositivo
        $sql_delete_records = "DELETE FROM registros_dispositivo WHERE mac_address = '$mac_address'";
        $conn->query($sql_delete_records);

        echo "Dispositivo e registros removidos.";
        // Redirecionar para index.php após 2 segundos
        echo "<script>
                setTimeout(function(){
                   window.location.href = 'index.php';
                }, 2000);
              </script>";
    }
} else {
    echo "Dispositivo não encontrado na lista de disponíveis.";
}

// Habilitar verificação de chaves estrangeiras
$conn->query("SET FOREIGN_KEY_CHECKS = 1");

$conn->close();
?>
