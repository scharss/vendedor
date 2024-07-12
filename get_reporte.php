<?php
require 'vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$servername = $_ENV['DB_SERVER'];
$username = $_ENV['DB_USERNAME'];
$password = $_ENV['DB_PASSWORD'];
$dbname = $_ENV['DB_NAME'];

// Crear conexión
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar conexión
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$vendedor_id = $_GET['vendedor_id'];
$result = $conn->query("SELECT username FROM usuarios WHERE id=$vendedor_id");
$vendedor = $result->fetch_assoc();

$total_recaudado_result = $conn->query("SELECT SUM(cuota1 + cuota2 + cuota3 + cuota4 + cuota5 + cuota6 + cuota7 + cuota8 + cuota9 + cuota10) as total_recaudado
                                        FROM cuotas 
                                        JOIN productos ON cuotas.producto_id = productos.id
                                        WHERE productos.vendedor_id = $vendedor_id");
$total_recaudado = $total_recaudado_result->fetch_assoc()['total_recaudado'];

$conn->close();

header('Content-Type: application/json');
echo json_encode(['username' => $vendedor['username'], 'total_recaudado' => $total_recaudado]);
?>
