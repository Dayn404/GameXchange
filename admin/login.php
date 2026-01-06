<?php
require_once '../config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = "Email and password are required!";
    } else {
        $stmt = $conn->prepare("SELECT UserID, Name, Email, Password FROM Users WHERE Email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            if ($user['Email'] !== ADMIN_EMAIL) {
                $error = 'Not an admin account.';
            } elseif (password_verify($password, $user['Password'])) {
                // Set admin session
                $_SESSION['user_id'] = $user['UserID'];
                $_SESSION['user_name'] = $user['Name'];
                $_SESSION['user_email'] = $user['Email'];
                $_SESSION['is_admin'] = true;
                header('Location: dashboard.php');
                exit();
            } else {
                $error = 'Invalid email or password!';
            }
        } else {
            $error = 'Invalid email or password!';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - GameXchange</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
    <div class="form-container">
        <h2>Admin Login</h2>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn-primary">Login as Admin</button>
        </form>
    </div>
</body>
</html>
