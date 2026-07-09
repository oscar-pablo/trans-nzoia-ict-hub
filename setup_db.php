<?php
$host     = "localhost";
$username = "root";
$password = "";

try {
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database if not exists
    $pdo->exec("CREATE DATABASE IF NOT EXISTS trans_nzoia_ict_hub");
    $pdo->exec("USE trans_nzoia_ict_hub");
    
    // Create admins table with full_name and security questions
    $pdo->exec("CREATE TABLE IF NOT EXISTS admins (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(100) NOT NULL UNIQUE,
        full_name VARCHAR(100) NOT NULL,
        password VARCHAR(255) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        security_question_1 VARCHAR(255) DEFAULT NULL,
        security_answer_1 VARCHAR(255) DEFAULT NULL,
        security_question_2 VARCHAR(255) DEFAULT NULL,
        security_answer_2 VARCHAR(255) DEFAULT NULL,
        security_question_3 VARCHAR(255) DEFAULT NULL,
        security_answer_3 VARCHAR(255) DEFAULT NULL,
        reset_token VARCHAR(255) DEFAULT NULL,
        reset_token_expiry DATETIME DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Insert a default admin if none exists
    $stmt = $pdo->query("SELECT COUNT(*) FROM admins");
    if ($stmt->fetchColumn() == 0) {
        $defaultPassword = password_hash('tnict2025', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO admins (
            username, full_name, password, email, 
            security_question_1, security_answer_1, 
            security_question_2, security_answer_2, 
            security_question_3, security_answer_3
        ) VALUES (
            :username, :full_name, :password, :email, 
            :q1, :a1, 
            :q2, :a2, 
            :q3, :a3
        )");
        $stmt->execute([
            ':username' => 'admin',
            ':full_name' => 'System Administrator',
            ':password' => $defaultPassword,
            ':email' => 'admin@ictnzoia.com',
            ':q1' => 'What was the name of your first school?',
            ':a1' => password_hash('kitale primary', PASSWORD_DEFAULT),
            ':q2' => 'What is your favorite color?',
            ':a2' => password_hash('blue', PASSWORD_DEFAULT),
            ':q3' => 'In what city or town was your first job?',
            ':a3' => password_hash('kitale', PASSWORD_DEFAULT)
        ]);
        echo "Default admin user created (admin / tnict2025 / admin@ictnzoia.com / with default security questions)\n";
    }
    
    // Create enrollments table
    $pdo->exec("CREATE TABLE IF NOT EXISTS enrollments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        first_name VARCHAR(100) NOT NULL,
        middle_name VARCHAR(100) DEFAULT NULL,
        last_name VARCHAR(100) NOT NULL,
        has_id TINYINT(1) DEFAULT 1,
        id_type VARCHAR(50) DEFAULT NULL,
        id_number VARCHAR(100) DEFAULT NULL,
        phone VARCHAR(20) NOT NULL,
        email VARCHAR(100) DEFAULT NULL,
        address VARCHAR(255) NOT NULL,
        course VARCHAR(100) NOT NULL,
        schedule VARCHAR(50) DEFAULT NULL,
        status VARCHAR(20) DEFAULT 'Pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    echo "Database setup completed successfully.\n";
} catch (PDOException $e) {
    die("Database setup failed: " . $e->getMessage() . "\n");
}
