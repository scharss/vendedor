SELECT SUM(cuota1 + cuota2 + cuota3 + cuota4 + cuota5 + cuota6 + cuota7 + cuota8 + cuota9 + cuota10) AS total_recaudado
FROM cuotas
JOIN productos ON cuotas.producto_id = productos.id
JOIN usuarios ON productos.vendedor_id = usuarios.id
WHERE usuarios.username = 'vendedor1';
