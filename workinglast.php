<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'vendedor') {
    header("Location: index.html");
    exit();
}

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

$vendedor_id = $_SESSION['user_id'];
$productos = $conn->query("SELECT * FROM productos WHERE vendedor_id = $vendedor_id");
$subvendedores = $conn->query("SELECT * FROM usuarios WHERE vendedor_id = $vendedor_id");

$producto = null;
$cuotas = null;
$total_pagado = 0;
$cuotas_pagadas = 0;
$cuotas_pendientes = 10;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['producto_id']) && !isset($_POST['update_cuotas']) && !isset($_POST['assign_producto'])) {
        $producto_id = $_POST['producto_id'];
        $producto = $conn->query("SELECT * FROM productos WHERE id = $producto_id")->fetch_assoc();
        $cuotas = $conn->query("SELECT * FROM cuotas WHERE producto_id = $producto_id")->fetch_assoc();
        if (!$cuotas) {
            $conn->query("INSERT INTO cuotas (producto_id) VALUES ($producto_id)");
            $cuotas = $conn->query("SELECT * FROM cuotas WHERE producto_id = $producto_id")->fetch_assoc();
        }
    } elseif (isset($_POST['update_cuotas'])) {
        $producto_id = $_POST['producto_id'];
        $nombre_cliente = $_POST['nombre_cliente'];
        $telefono_cliente = $_POST['telefono_cliente'];
        $cuota1 = $_POST['cuota1'];
        $cuota2 = $_POST['cuota2'];
        $cuota3 = $_POST['cuota3'];
        $cuota4 = $_POST['cuota4'];
        $cuota5 = $_POST['cuota5'];
        $cuota6 = $_POST['cuota6'];
        $cuota7 = $_POST['cuota7'];
        $cuota8 = $_POST['cuota8'];
        $cuota9 = $_POST['cuota9'];
        $cuota10 = $_POST['cuota10'];

        $sql = "UPDATE cuotas SET 
                nombre_cliente = '$nombre_cliente', 
                telefono_cliente = '$telefono_cliente',
                cuota1 = $cuota1,
                cuota2 = $cuota2,
                cuota3 = $cuota3,
                cuota4 = $cuota4,
                cuota5 = $cuota5,
                cuota6 = $cuota6,
                cuota7 = $cuota7,
                cuota8 = $cuota8,
                cuota9 = $cuota9,
                cuota10 = $cuota10
                WHERE producto_id = $producto_id";
        if ($conn->query($sql) === TRUE) {
            echo "<script>alert('Datos actualizados correctamente');</script>";
        } else {
            echo "<script>alert('Error al actualizar los datos: " . $conn->error . "');</script>";
        }

        // Recargar datos de cuotas después de actualizar
        $producto = $conn->query("SELECT * FROM productos WHERE id = $producto_id")->fetch_assoc();
        $cuotas = $conn->query("SELECT * FROM cuotas WHERE producto_id = $producto_id")->fetch_assoc();
    } elseif (isset($_POST['create_subvendedor'])) {
        $subvendedor_username = $_POST['subvendedor_username'];
        $subvendedor_password = $_POST['subvendedor_password'];
        $sql = "INSERT INTO usuarios (username, password, role, vendedor_id) VALUES ('$subvendedor_username', '$subvendedor_password', 'vendedor', $vendedor_id)";
        if ($conn->query($sql) === TRUE) {
            echo "<script>alert('Subvendedor creado correctamente');</script>";
        } else {
            echo "<script>alert('Error al crear subvendedor: " . $conn->error . "');</script>";
        }
        // Recargar la lista de subvendedores
        $subvendedores = $conn->query("SELECT * FROM usuarios WHERE vendedor_id = $vendedor_id");
    } elseif (isset($_POST['assign_producto'])) {
        foreach ($_POST['subvendedor_id'] as $producto_id => $subvendedor_id) {
            $total_pagado = $_POST['total_pagado'][$producto_id];
            $sql = "UPDATE productos SET subvendedor_id = $subvendedor_id, total_pagado = $total_pagado WHERE id = $producto_id AND vendedor_id = $vendedor_id";
            if ($conn->query($sql) === TRUE) {
                echo "<script>console.log('Producto asignado al subvendedor correctamente');</script>";
            } else {
                echo "<script>alert('Error al asignar producto: " . $conn->error . "');</script>";
            }
        }
        // Recargar la lista de productos
        $productos = $conn->query("SELECT * FROM productos WHERE vendedor_id = $vendedor_id");
    }

    if (isset($cuotas)) {
        $cuotas_pagadas = 0;
        $total_pagado = 0;

        for ($i = 1; $i <= 10; $i++) {
            if ($cuotas["cuota$i"] > 0) {
                $cuotas_pagadas++;
                $total_pagado += $cuotas["cuota$i"];
            }
        }
        $cuotas_pendientes = 10 - $cuotas_pagadas;
    }
}

function formatMoney($number) {
    return '$' . number_format($number, 2);
}

// Calcular el total pagado por todos los subvendedores
$total_pagado_query = $conn->query("SELECT SUM(total_pagado) as total FROM productos WHERE vendedor_id = $vendedor_id");
$total_pagado_result = $total_pagado_query->fetch_assoc();
$total_pagado_all = $total_pagado_result['total'];


?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendedor - Gestión de Productos</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const inputs = document.querySelectorAll('input[type="number"]');
            inputs.forEach(input => {
                input.addEventListener('blur', function (event) {
                    const value = parseFloat(event.target.value.replace(/[^0-9.-]+/g,""));
                    if (!isNaN(value)) {
                        event.target.value = value.toLocaleString('en-US', { style: 'currency', currency: 'USD' });
                    }
                });
            });

            const searchInput = document.getElementById('search_product');
            const productSelect = document.getElementById('producto_id');
            const options = productSelect.options;

            searchInput.addEventListener('input', function () {
                const searchTerm = searchInput.value.toLowerCase();
                for (let i = 0; i < options.length; i++) {
                    const option = options[i];
                    const text = option.text.toLowerCase();
                    if (text.includes(searchTerm)) {
                        option.style.display = '';
                    } else {
                        option.style.display = 'none';
                    }
                }
            });

            document.getElementById('search_button').addEventListener('click', function () {
                const searchTerm = searchInput.value.toLowerCase();
                for (let i = 0; i < options.length; i++) {
                    const option = options[i];
                    const text = option.text.toLowerCase();
                    if (text.includes(searchTerm)) {
                        option.selected = true;
                        productSelect.dispatchEvent(new Event('change'));
                        break;
                    }
                }
            });
        });
    </script>
</head>
<body>
    <div class="container mt-5">
        <h2 class="text-center">Vendedor - Gestión de Productos</h2>
        <form method="POST" class="mb-3">
            <div class="form-group">
                <label for="search_product">Buscar Producto</label>
                <div class="input-group">
                    <input type="text" class="form-control" id="search_product" placeholder="Escriba el nombre del producto">
                    <div class="input-group-append">
                        <button type="button" class="btn btn-primary" id="search_button">Buscar</button>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label for="producto_id">Seleccionar Producto</label>
                <select class="form-control" id="producto_id" name="producto_id" onchange="this.form.submit()" required>
                    <option value="">Seleccione un producto</option>
                    <?php while ($row = $productos->fetch_assoc()): ?>
                    <option value="<?= $row['id'] ?>" <?= isset($_POST['producto_id']) && $_POST['producto_id'] == $row['id'] ? 'selected' : '' ?>><?= $row['nombre_producto'] ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
        </form>
        
        <?php if (isset($producto)): ?>
        <h3 class="mt-4">Detalles del Producto</h3>
        <p>Nombre del Producto: <?= $producto['nombre_producto'] ?></p>
        <p>Precio: <?= formatMoney($producto['precio']) ?></p>
        <p>Cuotas Pagadas: <?= $cuotas_pagadas ?></p>
        <p>Total Pagado: <?= formatMoney($total_pagado) ?></p>
        <p>Cuotas Pendientes: <?= $cuotas_pendientes ?></p>

        <h3 class="mt-4">Información del Cliente y Cuotas</h3>
        <form method="POST">
            <input type="hidden" name="producto_id" value="<?= $producto['id'] ?>">
            <div class="form-group">
                <label for="nombre_cliente">Nombre del Cliente</label>
                <input type="text" class="form-control" id="nombre_cliente" name="nombre_cliente" value="<?= $cuotas['nombre_cliente'] ?? '' ?>" required>
            </div>
            <div class="form-group">
                <label for="telefono_cliente">Teléfono del Cliente</label>
                <input type="text" class="form-control" id="telefono_cliente" name="telefono_cliente" value="<?= $cuotas['telefono_cliente'] ?? '' ?>" required>
            </div>
            <?php for ($i = 1; $i <= 10; $i++): ?>
            <div class="form-group">
                <label for="cuota<?= $i ?>">Cuota <?= $i ?></label>
                <input type="text" class="form-control money-input" id="cuota<?= $i ?>" name="cuota<?= $i ?>" value="<?= number_format($cuotas["cuota$i"], 2, '.', '') ?? 0 ?>">
            </div>
            <?php endfor; ?>
            <button type="submit" name="update_cuotas" class="btn btn-primary">Actualizar Cuotas</button>
        </form>
        <?php endif; ?>

        <h3 class="mt-4">Crear Subvendedor</h3>
        <form method="POST">
            <div class="form-group">
                <label for="subvendedor_username">Nombre de Usuario</label>
                <input type="text" class="form-control" id="subvendedor_username" name="subvendedor_username" required>
            </div>
            <div class="form-group">
                <label for="subvendedor_password">Contraseña</label>
                <input type="password" class="form-control" id="subvendedor_password" name="subvendedor_password" required>
            </div>
            <button type="submit" name="create_subvendedor" class="btn btn-primary">Crear Subvendedor</button>
        </form>

        <h3 class="mt-4">Subvendedores</h3>
        <ul class="list-group">
            <?php while ($subvendedor = $subvendedores->fetch_assoc()): ?>
                <li class="list-group-item"><?= $subvendedor['username'] ?></li>
            <?php endwhile; ?>
        </ul>

        <h3 class="mt-4">Asignar Subvendedor y Total Pagado a Productos</h3>
        <form method="POST">
            <table class="table">
                <thead>
                    <tr>
                        <th>Productos</th>
                        <th>Subvendedores</th>
                        <th>Total pagado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $productos_result = $conn->query("SELECT * FROM productos WHERE vendedor_id = $vendedor_id");
                    while ($row = $productos_result->fetch_assoc()): ?>
                        <tr>
                            <td><?= $row['nombre_producto'] ?></td>
                            <td>
                                <select class="form-control" name="subvendedor_id[<?= $row['id'] ?>]">
                                    <option value="">Seleccione un subvendedor</option>
                                    <?php
                                    $subvendedores_result = $conn->query("SELECT * FROM usuarios WHERE vendedor_id = $vendedor_id");
                                    while ($sub_row = $subvendedores_result->fetch_assoc()): ?>
                                        <option value="<?= $sub_row['id'] ?>" <?= $row['subvendedor_id'] == $sub_row['id'] ? 'selected' : '' ?>><?= $sub_row['username'] ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </td>
                            <td>
                            <input type="text" class="form-control" name="total_pagado[<?= $row['id'] ?>]" value="<?= number_format($row['total_pagado'], 2, '.', '') ?>">
            </td>
        </tr>
    <?php endwhile; ?>
    <tr class="table-info">
        <td colspan="2"><strong>Total pagado por todos los subvendedores:</strong></td>
        <td><strong><?= formatMoney($total_pagado_all) ?></strong></td>
    </tr>
</tbody>
</table>
<button type="submit" name="assign_producto" class="btn btn-primary">Asignar Subvendedor y Total Pagado</button>
</form>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<?php
    // Cerrar la conexión al final del archivo
    $conn->close();
    ?>
</body>
</html>