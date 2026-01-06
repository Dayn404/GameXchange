<?php
require_once 'config.php';

$error = '';
$role = isset($_GET['role']) ? $_GET['role'] : null; // 'user' or 'admin'

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $login_role = isset($_POST['role']) ? $_POST['role'] : null;
    
    if (empty($email) || empty($password)) {
        $error = "Email and password are required!";
    } else {
        $stmt = $conn->prepare("SELECT UserID, Name, Email, Password, role FROM Users WHERE Email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            
            if (password_verify($password, $user['Password'])) {
                // Check if trying to login as admin
                if ($login_role === 'admin') {
                    // Allow admin login if user's role is 'admin', or they exist in Admins table, or match ADMIN_EMAIL
                    $isAdminUser = false;
                    if (isset($user['role']) && strtolower($user['role']) === 'admin') {
                        $isAdminUser = true;
                    }

                    if (!$isAdminUser) {
                        // check Admins table for the user id
                        $checkStmt = $conn->prepare("SELECT COUNT(*) as cnt FROM Admins WHERE UserID = ?");
                        if ($checkStmt) {
                            $checkStmt->bind_param("i", $user['UserID']);
                            $checkStmt->execute();
                            $checkRes = $checkStmt->get_result();
                            if ($checkRes) {
                                $row = $checkRes->fetch_assoc();
                                if ($row && $row['cnt'] > 0) {
                                    $isAdminUser = true;
                                }
                            }
                        }
                    }

                    if ($user['Email'] === ADMIN_EMAIL) {
                        $isAdminUser = true;
                    }

                    if ($isAdminUser) {
                        $_SESSION['user_id'] = $user['UserID'];
                        $_SESSION['user_name'] = $user['Name'];
                        $_SESSION['user_email'] = $user['Email'];
                        $_SESSION['is_admin'] = true;
                        header('Location: admin/dashboard.php');
                        exit();
                    } else {
                        $error = "This account does not have admin privileges!";
                    }
                } else {
                    // Regular user login: prevent users with admin role from logging in here
                    if (isset($user['role']) && strtolower($user['role']) === 'admin') {
                        $error = "Admin accounts must login via Admin Login. Please select 'Login as Admin' option.";
                    } elseif ($user['Email'] === ADMIN_EMAIL) {
                        $error = "Admin accounts must login via Admin Login. Please select 'Login as Admin' option.";
                    } else {
                        $_SESSION['user_id'] = $user['UserID'];
                        $_SESSION['user_name'] = $user['Name'];
                        $_SESSION['user_email'] = $user['Email'];
                        $_SESSION['is_admin'] = false;
                        header('Location: user/dashboard.php');
                        exit();
                    }
                }
            } else {
                $error = "Invalid email or password!";
            }
        } else {
            $error = "Invalid email or password!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - GameXchange</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <div class="form-container">
        <?php if (!$role): ?>
            <!-- Role Selection Screen -->
            <div style="text-align: center; padding: 40px;">
                <h2>Welcome to GameXchange</h2>
                <p style="color: #666; margin-bottom: 40px; font-size: 1.1em;">Select your login type:</p>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; max-width: 600px; margin: 0 auto;">
                    <!-- User Login Option -->
                    <div style="padding: 40px; border: 2px solid #ddd; border-radius: 10px; cursor: pointer; transition: all 0.3s ease;" 
                         onmouseover="this.style.borderColor='#4CAF50'; this.style.boxShadow='0 4px 8px rgba(0,0,0,0.1)'" 
                         onmouseout="this.style.borderColor='#ddd'; this.style.boxShadow='none'"
                         onclick="window.location.href='?role=user'">
                        <div style="font-size: 3em; margin-bottom: 15px;">👤</div>
                        <h3 style="margin: 15px 0; color: #333;">User Login</h3>
                        <p style="color: #666; font-size: 0.95em; margin: 0;">Login as a regular user to buy, sell, and rent games</p>
                        <button type="button" class="btn btn-success" style="margin-top: 20px; width: 100%;">Login as User</button>
                    </div>
                    
                    <!-- Admin Login Option -->
                    <div style="padding: 40px; border: 2px solid #ddd; border-radius: 10px; cursor: pointer; transition: all 0.3s ease;" 
                         onmouseover="this.style.borderColor='#FF9800'; this.style.boxShadow='0 4px 8px rgba(0,0,0,0.1)'" 
                         onmouseout="this.style.borderColor='#ddd'; this.style.boxShadow='none'"
                         onclick="window.location.href='?role=admin'">
                        <div style="font-size: 3em; margin-bottom: 15px;">🔐</div>
                        <h3 style="margin: 15px 0; color: #333;">Admin Login</h3>
                        <p style="color: #666; font-size: 0.95em; margin: 0;">Login as an administrator to manage the platform</p>
                        <button type="button" class="btn btn-warning" style="margin-top: 20px; width: 100%; background-color: #FF9800;">Login as Admin</button>
                    </div>
                </div>
                
                <div style="margin-top: 40px; padding-top: 30px; border-top: 1px solid #eee;">
                    <p>Don't have an account? <a href="register.php" style="color: #4CAF50; font-weight: bold;">Register here</a></p>
                </div>
            </div>
        <?php else: ?>
            <!-- Login Form -->
            <h2>
                <?php if ($role === 'user'): ?>
                    User Login
                <?php else: ?>
                    Admin Login
                <?php endif; ?>
            </h2>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <input type="hidden" name="role" value="<?php echo htmlspecialchars($role); ?>">
                
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" autofocus>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit" class="btn-primary">
                    <?php if ($role === 'user'): ?>
                        Login as User
                    <?php else: ?>
                        Login as Admin
                    <?php endif; ?>
                </button>
            </form>
            
            <div class="text-center" style="margin-top: 20px;">
                <p>
                    <a href="login.php" style="color: #666;">← Back to role selection</a>
                </p>
                <?php if ($role === 'user'): ?>
                    <p>Don't have an account? <a href="register.php">Register here</a></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
