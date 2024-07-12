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

$result = $conn->query("SELECT id, username FROM usuarios WHERE role='vendedor'");
$vendedores = [];
while ($row = $result->fetch_assoc()) {
    $vendedores[] = $row;
}

$conn->close();

header('Content-Type: application/json');
echo json_encode(['vendedores' => $vendedores]);
?>
