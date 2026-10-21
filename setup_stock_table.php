<?php
// Database configuration
$host = 'localhost';
$port = '3307';
$dbname = 'dermatology_clinic';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create stock table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS stock (
            stock_id INT AUTO_INCREMENT PRIMARY KEY,
            item_name VARCHAR(100) NOT NULL DEFAULT 'Injection',
            quantity INT NOT NULL DEFAULT 0,
            low_stock_threshold INT NOT NULL DEFAULT 5,
            last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            updated_by INT,
            FOREIGN KEY (updated_by) REFERENCES medical_staff(staff_id) ON DELETE SET NULL
        )
    ");

    // Insert initial injection stock if not exists
    $stmt = $pdo->query("SELECT COUNT(*) FROM stock WHERE item_name = 'Injection'");
    if ($stmt->fetchColumn() == 0) {
        $pdo->exec("INSERT INTO stock (item_name, quantity, low_stock_threshold) VALUES ('Injection', 0, 5)");
    }

    // Add injection_used column to medical_record table if it doesn't exist
    $stmt = $pdo->query("SHOW COLUMNS FROM medical_record LIKE 'injection_used'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE medical_record ADD COLUMN injection_used TINYINT(1) DEFAULT 0");
    }

    echo "Stock table setup completed successfully!";
    echo "<br><a href='manage_stock.php'>Go to Stock Management</a>";
    echo "<br><a href='staff_dashboard.php'>Go to Dashboard</a>";

} catch(PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
