<?php
session_start();
require_once 'database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];

// Fetch user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullName = $_POST['full_name'];
    $city = $_POST['city'];
    $address = $_POST['address'];
    $favoriteReciter = $_POST['favorite_reciter'];
    $displayMode = $_POST['display_mode'];

    // Handle profile picture upload
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
        $targetDir = "uploads/";
        $targetFile = $targetDir . basename($_FILES["profile_picture"]["name"]);
        $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
        
        // Check if image file is a actual image or fake image
        $check = getimagesize($_FILES["profile_picture"]["tmp_name"]);
        if($check !== false) {
            move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $targetFile);
            $profilePicture = $targetFile;
        }
    } else {
        $profilePicture = $user['profile_picture']; // Keep existing picture if no new upload
    }

    // Update user data in database
    $stmt = $pdo->prepare("UPDATE users SET full_name = ?, city = ?, address = ?, favorite_reciter = ?, display_mode = ?, profile_picture = ? WHERE id = ?");
    $stmt->execute([$fullName, $city, $address, $favoriteReciter, $displayMode, $profilePicture, $userId]);

    $successMessage = "Profile updated successfully!";
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile - Mrgamji Counter</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: <?php echo $user['display_mode'] == 'dark' ? '#333' : '#f4f4f4'; ?>;
            color: <?php echo $user['display_mode'] == 'dark' ? '#fff' : '#333'; ?>;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: <?php echo $user['display_mode'] == 'dark' ? '#444' : '#fff'; ?>;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1 {
            text-align: center;
            color: #3498db;
        }
        form {
            display: grid;
            gap: 15px;
        }
        label {
            font-weight: bold;
        }
        input[type="text"], input[type="file"], select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        input[type="submit"] {
            background-color: #3498db;
            color: #fff;
            border: none;
            padding: 10px 15px;
            cursor: pointer;
            border-radius: 4px;
        }
        input[type="submit"]:hover {
            background-color: #2980b9;
        }
        .success-message {
            background-color: #2ecc71;
            color: #fff;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        .profile-picture {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            margin: 0 auto 20px;
            display: block;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>User Profile</h1>
        <?php
        if (isset($successMessage)) {
            echo "<div class='success-message'>$successMessage</div>";
        }
        ?>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
            <?php
            // Check if the users table exists
            $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
            $usersTableExists = $stmt->rowCount() > 0;

            if (!$usersTableExists) {
                // Create the users table if it doesn't exist
                $sql = "CREATE TABLE users (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    username VARCHAR(255) NOT NULL UNIQUE,
                    password VARCHAR(255) NOT NULL
                )";
                $pdo->exec($sql);
            }

            // Now create the user_profiles table
            $sql = "CREATE TABLE IF NOT EXISTS user_profiles (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL UNIQUE,
                full_name VARCHAR(255),
                city VARCHAR(100),
                address VARCHAR(255),
                favorite_reciter VARCHAR(100),
                display_mode ENUM('light', 'dark') DEFAULT 'light',
                profile_picture VARCHAR(255),
                FOREIGN KEY (user_id) REFERENCES users(id)
            )";
            $pdo->exec($sql);

            $profilePicture = isset($user['profile_picture']) && !empty($user['profile_picture']) ? $user['profile_picture'] : 'default-avatar.png';
            $profilePicturePath = 'uploads/' . $profilePicture;
            if (file_exists($profilePicturePath)) {
                echo '<img src="' . htmlspecialchars($profilePicturePath) . '" alt="Profile Picture" class="profile-picture">';
            } else {
                echo '<img src="default-avatar.png" alt="Profile Picture" class="profile-picture">';
            }
            ?>
            
            <label for="profile_picture">Profile Picture:</label>
            <input type="file" name="profile_picture" id="profile_picture" accept="image/*">
            
            <label for="full_name">Full Name:</label>
            <input type="text" name="full_name" id="full_name" value="<?php echo $user['full_name'] ?? ''; ?>" required>
            
            <label for="city">City:</label>
            <input type="text" name="city" id="city" value="<?php echo $user['city'] ?? ''; ?>">
            
            <label for="address">Address:</label>
            <input type="text" name="address" id="address" value="<?php echo $user['address'] ?? ''; ?>">
            
            <label for="favorite_reciter">Favorite Islamic Reciter:</label>
            <input type="text" name="favorite_reciter" id="favorite_reciter" value="<?php echo $user['favorite_reciter'] ?? ''; ?>">
            
            <label for="display_mode">Display Mode:</label>
            <select name="display_mode" id="display_mode">
                <option value="light" <?php echo ($user['display_mode'] ?? '') == 'light' ? 'selected' : ''; ?>>Light Mode</option>
                <option value="dark" <?php echo ($user['display_mode'] ?? '') == 'dark' ? 'selected' : ''; ?>>Dark Mode</option>
            </select>
            
            <input type="submit" value="Update Profile">
        </form>
    </div>
</body>
</html>
