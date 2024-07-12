CREATE DATABASE gestion_ventas;

USE gestion_ventas;

CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'vendedor') NOT NULL
);

INSERT INTO usuarios (username, password, role) VALUES
('admin', 'Sseñorial256@', 'admin'),
('Lina', 'Linaseñorial1', 'admin');

CREATE TABLE productos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vendedor_id INT,
    nombre_producto VARCHAR(100),
    precio DECIMAL(10, 2),
    cuotas_totales INT DEFAULT 10,
    FOREIGN KEY (vendedor_id) REFERENCES usuarios(id)
);

CREATE TABLE cuotas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    producto_id INT,
    nombre_cliente VARCHAR(100),
    telefono_cliente VARCHAR(15),
    cuota1 DECIMAL(10, 2),
    cuota2 DECIMAL(10, 2),
    cuota3 DECIMAL(10, 2),
    cuota4 DECIMAL(10, 2),
    cuota5 DECIMAL(10, 2),
    cuota6 DECIMAL(10, 2),
    cuota7 DECIMAL(10, 2),
    cuota8 DECIMAL(10, 2),
    cuota9 DECIMAL(10, 2),
    cuota10 DECIMAL(10, 2),
    FOREIGN KEY (producto_id) REFERENCES productos(id)
);
