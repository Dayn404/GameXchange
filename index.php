<?php
require_once 'config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    if (isAdmin()) {
        header('Location: admin/dashboard.php');
    } else {
        header('Location: user/dashboard.php');
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GameXchange - Buy, Rent & Exchange Video Games</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎮 GameXchange</h1>
            <div class="nav">
                <a href="login.php">Login</a>
                <a href="register.php">Register</a>
            </div>
        </div>
        
        <div style="text-align: center; padding: 50px 0;">
            <h2 style="color: #667eea; margin-bottom: 20px;">Welcome to GameXchange</h2>
            <p style="font-size: 1.2em; color: #666; margin-bottom: 30px;">
                Your one-stop platform to buy, rent, and exchange video games!
            </p>
            
            <div class="dashboard-stats" style="margin-top: 50px;">
                <div class="stat-card">
                    <h3>🎮</h3>
                    <p>Browse Games</p>
                </div>
                <div class="stat-card">
                    <h3>💰</h3>
                    <p>Buy & Sell</p>
                </div>
                <div class="stat-card">
                    <h3>📅</h3>
                    <p>Rent Games</p>
                </div>
                <div class="stat-card">
                    <h3>⭐</h3>
                    <p>Rate & Review</p>
                </div>
            </div>
            
            <div style="margin-top: 50px;">
                <a href="register.php" class="btn" style="font-size: 1.2em; padding: 15px 30px;">Get Started Now</a>
            </div>
        </div>
    </div>
</body>
</html>
