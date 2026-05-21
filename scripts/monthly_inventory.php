<?php

// Load environment variables from .env file
require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

// Load .env file
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();
//print_r($_ENV);

// Retrieve database credentials from environment variables
$dbHost = $_ENV['DB_HOST'] ?? 'localhost';
$dbName = $_ENV['DB_DATABASE'] ?? 'database';
$dbUser = $_ENV['DB_USERNAME'] ?? 'root';
$dbPass = $_ENV['DB_PASSWORD'] ?? '';

// Set up DSN and options
$dsn = "mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    // Connect to the database
    $pdo = new PDO($dsn, $dbUser, $dbPass, $options);

    // Define your INSERT query
    $query = "
    insert into inventory_history(producto,variante,stock,fecha) 
(select b.descripcion, a.descripcion, a.stock, DATE_SUB(CURDATE(), INTERVAL 1 DAY) as inventario from variants a join products b on (a.id_producto = b.id) 
where a.enabled = 1
order by b.id, a.id );
    ";
    
    // Prepare the statement
    $stmt = $pdo->prepare($query);
    
    // Bind parameters and execute
    $stmt->execute();
    
    echo "Records inserted successfully!";
} catch (PDOException $e) {
    // Handle errors
    echo "Database error: " . $e->getMessage();
} catch (Exception $e) {
    // Handle other errors
    echo "Error: " . $e->getMessage();
}

