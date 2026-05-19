<?php
// Simple authentication functions (no hashing for demo)
function authenticateUser($pdo, $username, $password) {
    $stmt = $pdo->prepare("SELECT * FROM Users WHERE username = ? AND password = ?");
    $stmt->execute([$username, $password]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function registerUser($pdo, $user_data) {
    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("INSERT INTO Users (username, password, role) VALUES (?, ?, 'CUSTOMER')");
        $stmt->execute([$user_data['username'], $user_data['password']]);
        $user_id = $pdo->lastInsertId();
        
        $stmt = $pdo->prepare("
            INSERT INTO UserProfile (user_id, first_name, last_name, email, phone, address) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $user_id, 
            $user_data['first_name'], 
            $user_data['last_name'], 
            $user_data['email'], 
            $user_data['phone'], 
            $user_data['address']
        ]);
        
        $pdo->commit();
        return $user_id;
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}
?>