<?php
$host = 'localhost';
$dbname = 'counter';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

try {
    $pdo->exec($sql);
} catch(PDOException $e) {
    die("Error creating user_profiles table: " . $e->getMessage());
}

try {
    $pdo->exec($sql);
} catch(PDOException $e) {
    die("Error creating table: " . $e->getMessage());
}

// Function to save user count status
function saveUserCountStatus($userId, $count) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO user_count_status (user_id, count) VALUES (?, ?)");
    return $stmt->execute([$userId, $count]);
}

function createUser($username, $password) {
    global $pdo;
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
    return $stmt->execute([$username, $hashedPassword]);
}

function verifyUser($username, $password) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && password_verify($password, $user['password'])) {
        return $user['username'];
    }
    return false;
}

function saveCount($userId, $count) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO counts (user_id, count) VALUES (?, ?) ON DUPLICATE KEY UPDATE count = ?");
    return $stmt->execute([$userId, $count, $count]);
}

function getCount($userId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT count FROM counts WHERE user_id = ?");
    $stmt->execute([$userId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ? $result['count'] : 0;
}
?>
