<?php
require 'page_db.php';
try {
    // Check if full_name column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM admins LIKE 'full_name'");
    $columnExists = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$columnExists) {
        $pdo->exec("ALTER TABLE admins ADD COLUMN full_name VARCHAR(100) NOT NULL DEFAULT 'System Administrator' AFTER username");
        echo "Column full_name added to admins table.\n";
    } else {
        echo "Column full_name already exists.\n";
    }
    
    // Check if security_question_1 column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM admins LIKE 'security_question_1'");
    $securityExists = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$securityExists) {
        $pdo->exec("ALTER TABLE admins 
            ADD COLUMN security_question_1 VARCHAR(255) DEFAULT NULL AFTER email,
            ADD COLUMN security_answer_1 VARCHAR(255) DEFAULT NULL AFTER security_question_1,
            ADD COLUMN security_question_2 VARCHAR(255) DEFAULT NULL AFTER security_answer_1,
            ADD COLUMN security_answer_2 VARCHAR(255) DEFAULT NULL AFTER security_question_2,
            ADD COLUMN security_question_3 VARCHAR(255) DEFAULT NULL AFTER security_answer_2,
            ADD COLUMN security_answer_3 VARCHAR(255) DEFAULT NULL AFTER security_question_3
        ");
        echo "Security question columns added to admins table.\n";
        
        // Populate default questions and answers for existing admin if needed
        $stmt = $pdo->query("SELECT id, username FROM admins WHERE security_question_1 IS NULL");
        $adminsWithoutQuestions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($adminsWithoutQuestions)) {
            $updateStmt = $pdo->prepare("UPDATE admins SET 
                security_question_1 = :q1,
                security_answer_1 = :a1,
                security_question_2 = :q2,
                security_answer_2 = :a2,
                security_question_3 = :q3,
                security_answer_3 = :a3
                WHERE id = :id
            ");
            foreach ($adminsWithoutQuestions as $adminRow) {
                $updateStmt->execute([
                    ':q1' => 'What was the name of your first school?',
                    ':a1' => password_hash('kitale primary', PASSWORD_DEFAULT),
                    ':q2' => 'What is your favorite color?',
                    ':a2' => password_hash('blue', PASSWORD_DEFAULT),
                    ':q3' => 'In what city or town was your first job?',
                    ':a3' => password_hash('kitale', PASSWORD_DEFAULT),
                    ':id' => $adminRow['id']
                ]);
                echo "Populated default security questions for admin '{$adminRow['username']}'.\n";
            }
        }
    } else {
        echo "Security question columns already exist.\n";
    }
} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}

