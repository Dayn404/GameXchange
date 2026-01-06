<?php
require_once '../config.php';
requireLogin();

$user_id = $_SESSION['user_id'];

// List products that were added by admin (eligible for users to sell copies)
$stmt = $conn->prepare("SELECT p.ProductID, p.ProductName, p.Price, p.Availability, u.UserID as SellerID, u.Name as SellerName, u.Email as SellerEmail, u.role as SellerRole, CASE WHEN a.UserID IS NOT NULL THEN 1 ELSE 0 END as IsAdminUser
    FROM Product p
    JOIN Users u ON p.SellerID = u.UserID
    LEFT JOIN Admins a ON u.UserID = a.UserID
    WHERE LOWER(u.role) = 'admin' OR u.Email = ? OR a.UserID IS NOT NULL
    ORDER BY p.ProductID DESC");

$adminEmail = defined('ADMIN_EMAIL') ? ADMIN_EMAIL : '';
$stmt->bind_param("s", $adminEmail);
$stmt->execute();
$products = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sell Games - GameXchange</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Sell Games</h1>
            <div class="nav">
                <a href="dashboard.php">Dashboard</a>
                <a href="browse_games.php">Browse Games</a>
                <a href="sell_list.php">Sell Games</a>
                <a href="my_orders.php">My Orders</a>
                <a href="my_rentals.php">My Rentals</a>
                <a href="../logout.php">Logout</a>
            </div>
        </div>

        <h2>Products You Can Sell</h2>

        <?php if ($products->num_rows > 0): ?>
            <div class="product-grid">
                <?php while ($product = $products->fetch_assoc()): ?>
                    <div class="product-card">
                        <div class="product-details">
                            <h3><?php echo htmlspecialchars($product['ProductName']); ?></h3>
                            <div class="price">$<?php echo number_format($product['Price'], 2); ?></div>
                            <div style="margin-top:8px; font-weight:600;">
                                <?php if ($product['Availability'] > 0): ?>
                                    In stock: <?php echo intval($product['Availability']); ?>
                                <?php else: ?>
                                    <span style="color: #d9534f;">Out of stock</span>
                                <?php endif; ?>
                            </div>
                            <div class="product-actions" style="margin-top:10px;">
                                <a href="sell_game.php?id=<?php echo $product['ProductID']; ?>" class="btn btn-secondary">Sell Your Copy</a>
                                <a href="view_game.php?id=<?php echo $product['ProductID']; ?>" class="btn btn-small">Details</a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-error">No admin-listed products found.</div>
        <?php endif; ?>
    </div>
</body>
</html>
