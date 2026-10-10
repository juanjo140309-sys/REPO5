<?php
declare(strict_types=1);

$host = 'mysql-juancxo.alwaysdata.net';
$dbname = 'juancxo_hamburguesasgit';
$username = 'juancxo';
$password = 'juancho1422';

try {
    $pdo = new PDO(
        "mysql:host={$host};dbname={$dbname};charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );

    // Crear la tabla automáticamente si todavía no existe.
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS pedidos (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            direccion VARCHAR(255) NOT NULL,
            estado ENUM('Preparando', 'Despachado') NOT NULL DEFAULT 'Preparando',
            cantidad_sencilla INT UNSIGNED NOT NULL DEFAULT 0,
            cantidad_doble INT UNSIGNED NOT NULL DEFAULT 0,
            cantidad_mixta INT UNSIGNED NOT NULL DEFAULT 0,
            creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
} catch (PDOException $e) {
    http_response_code(500);
    exit('No se pudo conectar con la base de datos. Verifica las credenciales y que la base de datos exista en AlwaysData.');
}
