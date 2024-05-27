<?php
include 'db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        if ($user['lock_until'] && strtotime($user['lock_until']) > time()) {
            echo "Account is locked. Try again later.";
        } else {
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $stmt = $conn->prepare("UPDATE users SET failed_attempts = 0, lock_until = NULL WHERE id = ?");
                $stmt->execute([$user['id']]);
                header("Location: home.php");
                exit;
            } else {
                $failed_attempts = $user['failed_attempts'] + 1;
                if ($failed_attempts >= 3) {
                    $lock_until = date('Y-m-d H:i:s', strtotime('+10 minutes'));
                    $stmt = $conn->prepare("UPDATE users SET failed_attempts = ?, lock_until = ? WHERE id = ?");
                    $stmt->execute([$failed_attempts, $lock_until, $user['id']]);
                } else {
                    $stmt = $conn->prepare("UPDATE users SET failed_attempts = ? WHERE id = ?");
                    $stmt->execute([$failed_attempts, $user['id']]);
                }
                echo "Invalid credentials. Try again.";
            }
        }
    } else {
        echo "No user found with that email.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
</head>
<body>
    <form action="login.php" method="POST">
        <label>Email:</label>
        <input type="email" name="email" required>
        <label>Password:</label>
        <input type="password" name="password" required>
        <button type="submit">Login</button>
    </form>
</body>
</html>
