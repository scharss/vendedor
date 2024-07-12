<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
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

$productos_vendedor = [];
$vendedor_seleccionado = null;

// CRUD operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_user'])) {
        $username = $_POST['new_username'];
        $password = $_POST['new_password'];
        $role = $_POST['new_role'];
        $sql = "INSERT INTO usuarios (username, password, role) VALUES ('$username', '$password', '$role')";
        $conn->query($sql);
    } elseif (isset($_POST['update_user'])) {
        $id = $_POST['user_id'];
        $username = $_POST['edit_username'];
        $password = $_POST['edit_password'];
        $role = $_POST['edit_role'];
        $sql = "UPDATE usuarios SET username='$username', password='$password', role='$role' WHERE id=$id";
        $conn->query($sql);
    } elseif (isset($_POST['delete_user'])) {
        $id = $_POST['user_id'];
        $sql = "DELETE FROM usuarios WHERE id=$id";
        $conn->query($sql);
    } elseif (isset($_POST['create_producto'])) {
        $vendedor_id = $_POST['vendedor_id'];
        $nombre_producto = $_POST['nombre_producto'];
        $precio = $_POST['precio'];
        $sql = "INSERT INTO productos (vendedor_id, nombre_producto, precio) VALUES ('$vendedor_id', '$nombre_producto', '$precio')";
        if ($conn->query($sql) === TRUE) {
            echo "<script>alert('Producto asignado correctamente');</script>";
        } else {
            echo "<script>alert('Error al asignar producto: " . $conn->error . "');</script>";
        }
    } elseif (isset($_POST['buscar_vendedor']) || isset($_POST['vendedor_id'])) {
        $vendedor_seleccionado = isset($_POST['buscar_vendedor']) ? $_POST['buscar_vendedor'] : $_POST['vendedor_id'];
        $productos_vendedor = $conn->query("SELECT productos.id, productos.nombre_producto, productos.precio 
                                            FROM productos 
                                            WHERE productos.vendedor_id = $vendedor_seleccionado");
    } elseif (isset($_POST['delete_producto'])) {
        $id = $_POST['producto_id'];
        $sql = "DELETE FROM productos WHERE id=$id";
        $conn->query($sql);
    }
}

$users = $conn->query("SELECT * FROM usuarios");
$vendedores = $conn->query("SELECT * FROM usuarios WHERE role='vendedor'");

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Gestión de Ventas</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .nav-tabs .nav-link.active {
            background-color: #007bff;
            color: #fff;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h2 class="text-center">Administrador - Gestión de Ventas</h2>

        <ul class="nav nav-tabs" id="adminTab" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" id="usuarios-tab" data-toggle="tab" href="#usuarios" role="tab" aria-controls="usuarios" aria-selected="true">Creación de Usuarios y Contraseñas</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="vendedores-tab" data-toggle="tab" href="#vendedores" role="tab" aria-controls="vendedores" aria-selected="false">Gestión de Vendedores</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="reportes-tab" data-toggle="tab" href="#reportes" role="tab" aria-controls="reportes" aria-selected="false">Reportes</a>
            </li>
        </ul>

        <div class="tab-content" id="adminTabContent">
            <div class="tab-pane fade show active" id="usuarios" role="tabpanel" aria-labelledby="usuarios-tab">
                
                <h3 class="mt-4">Creación de Usuarios y Contraseñas</h3>
                <form method="POST" class="mb-3">
                    <div class="form-group">
                        <label for="new_username">Nombre de Usuario</label>
                        <input type="text" class="form-control" id="new_username" name="new_username" required>
                    </div>
                    <div class="form-group">
                        <label for="new_password">Contraseña</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                    </div>
                    <div class="form-group">
                        <label for="new_role">Rol</label>
                        <select class="form-control" id="new_role" name="new_role" required>
                            <option value="admin">Administrador</option>
                            <option value="vendedor">Vendedor</option>
                        </select>
                    </div>
                    <button type="submit" name="create_user" class="btn btn-primary">Crear Usuario</button>
                </form>

                <h3 class="mt-4">Usuarios Existentes</h3>
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre de Usuario</th>
                            <th>Rol</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $users->fetch_assoc()): ?>
                        <tr>
                            <td><?= $row['id'] ?></td>
                            <td><?= $row['username'] ?></td>
                            <td><?= $row['role'] ?></td>
                            <td>
                                <form method="POST" class="form-inline">
                                    <input type="hidden" name="user_id" value="<?= $row['id'] ?>">
                                    <div class="form-group mr-2">
                                        <input type="text" class="form-control" name="edit_username" value="<?= $row['username'] ?>" required>
                                    </div>
                                    <div class="form-group mr-2">
                                        <input type="password" class="form-control" name="edit_password" value="<?= $row['password'] ?>" required>
                                    </div>
                                    <div class="form-group mr-2">
                                        <select class="form-control" name="edit_role" required>
                                            <option value="admin" <?= $row['role'] == 'admin' ? 'selected' : '' ?>>Administrador</option>
                                            <option value="vendedor" <?= $row['role'] == 'vendedor' ? 'selected' : '' ?>>Vendedor</option>
                                        </select>
                                    </div>
                                    <button type="submit" name="update_user" class="btn btn-warning mr-2">Actualizar</button>
                                    <button type="submit" name="delete_user" class="btn btn-danger">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <div class="tab-pane fade" id="vendedores" role="tabpanel" aria-labelledby="vendedores-tab">
                <h3 class="mt-4">Gestión de Vendedores</h3>
                <form method="POST" class="mb-3">
                    <div class="form-group">
                        <label for="buscar_vendedor">Seleccionar Vendedor</label>
                        <select class="form-control" id="buscar_vendedor" name="buscar_vendedor" onchange="this.form.submit()" required>
                            <option value="">Seleccione un vendedor</option>
                            <?php while ($row = $vendedores->fetch_assoc()): ?>
                            <option value="<?= $row['id'] ?>" <?= isset($_POST['buscar_vendedor']) && $_POST['buscar_vendedor'] == $row['id'] ? 'selected' : '' ?>><?= $row['username'] ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </form>
                <?php if ($vendedor_seleccionado): ?>
                <form method="POST" class="mb-3">
                    <input type="hidden" name="vendedor_id" value="<?= $vendedor_seleccionado ?>">
                    <div class="form-group">
                        <label for="nombre_producto">Nombre del Producto</label>
                        <input type="text" class="form-control" id="nombre_producto" name="nombre_producto" required>
                    </div>
                    <div class="form-group">
                        <label for="precio">Precio</label>
                        <input type="number" step="0.01" class="form-control" id="precio" name="precio" required>
                    </div>
                    <button type="submit" name="create_producto" class="btn btn-primary">Asignar Producto</button>
                </form>
                <?php if ($productos_vendedor->num_rows > 0): ?>
                <h3 class="mt-4">Productos Asignados al Vendedor</h3>
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Producto</th>
                            <th>Precio</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $productos_vendedor->fetch_assoc()): ?>
                        <tr>
                            <td><?= $row['id'] ?></td>
                            <td><?= $row['nombre_producto'] ?></td>
                            <td><?= $row['precio'] ?></td>
                            <td>
                                <form method="POST" class="form-inline">
                                    <input type="hidden" name="producto_id" value="<?= $row['id'] ?>">
                                    <button type="submit" name="delete_producto" class="btn btn-danger">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p>No hay productos asignados a este vendedor.</p>
                <?php endif; ?>
                <?php endif; ?>
            </div>
            <div class="tab-pane fade" id="reportes" role="tabpanel" aria-labelledby="reportes-tab">
                 <!--<h3 class="mt-4">Reportes</h3>-->
                <!-- Formulario para generar reportes -->
                <div class="container mt-5">
        <h2 class="text-center">Reportes de Ventas</h2>

        <div class="form-group">
            <label for="vendedorSelect">Seleccionar Vendedor</label>
            <select class="form-control" id="vendedorSelect" onchange="obtenerReporte()">
                <option value="">Seleccione un vendedor</option>
                <!-- Opciones de vendedores generadas dinámicamente -->
            </select>
        </div>

        <div id="reporte" class="mt-4">
            <h4 id="nombreVendedor"></h4>
            <p>Total Recaudado: <span id="totalRecaudado"></span></p>
        </div>
    </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        $(document).ready(function(){
            var hash = window.location.hash;
            if (hash) {
                $('.nav-tabs a[href="' + hash + '"]').tab('show');
            }

            $('.nav-tabs a').on('shown.bs.tab', function(e) {
                window.location.hash = e.target.hash;
            });
        });

        document.addEventListener("DOMContentLoaded", function() {
            fetch('get_vendedores.php')
                .then(response => response.json())
                .then(data => {
                    const vendedorSelect = document.getElementById('vendedorSelect');
                    data.vendedores.forEach(vendedor => {
                        const option = document.createElement('option');
                        option.value = vendedor.id;
                        option.textContent = vendedor.username;
                        vendedorSelect.appendChild(option);
                    });
                });
        });

        function obtenerReporte() {
            const vendedorId = document.getElementById('vendedorSelect').value;
            if (vendedorId) {
                fetch(`get_reporte.php?vendedor_id=${vendedorId}`)
                    .then(response => response.json())
                    .then(data => {
                        document.getElementById('nombreVendedor').textContent = `Reporte de ${data.username}`;
                        if (data.total_recaudado === null || parseFloat(data.total_recaudado) === 0) {
                            document.getElementById('totalRecaudado').textContent = 'El vendedor no tiene recaudos';
                        } else {
                            document.getElementById('totalRecaudado').textContent = `$${parseFloat(data.total_recaudado).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
                        }
                    });
            } else {
                document.getElementById('nombreVendedor').textContent = '';
                document.getElementById('totalRecaudado').textContent = '';
            }
        }
    </script>
</body>
</html>
