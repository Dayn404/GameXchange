<?php
require_once '../config.php';
requireLogin();

$user_id = $_SESSION['user_id'];

// Get all available products (excluding user's own products)
$products = $conn->query("
        SELECT p.*, 
            p.Availability,
           GROUP_CONCAT(DISTINCT pp.Platform SEPARATOR ', ') as Platforms,
           pi.ImageURL,
           u.Name as SellerName, u.Email as SellerEmail, u.role as SellerRole,
           CASE WHEN b.ProductID IS NOT NULL THEN 1 ELSE 0 END as IsBuyable,
           CASE WHEN r.ProductID IS NOT NULL THEN 1 ELSE 0 END as IsRentable,
           r.DailyFee, r.LateFee,
           AVG(rev.Rating) as AvgRating,
           COUNT(rev.UserID) as ReviewCount
    FROM Product p
    JOIN Users u ON p.SellerID = u.UserID
    LEFT JOIN ProductPlatform pp ON p.ProductID = pp.ProductID
    LEFT JOIN ProductImage pi ON p.ProductID = pi.ProductID
    LEFT JOIN BuyableProduct b ON p.ProductID = b.ProductID
    LEFT JOIN RentableProduct r ON p.ProductID = r.ProductID
    LEFT JOIN Review rev ON p.ProductID = rev.ProductID
    WHERE p.Availability > 0
    GROUP BY p.ProductID
    ORDER BY p.ProductID DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Games - GameXchange</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎮 Browse Games</h1>
            <div class="nav">
                <a href="dashboard.php">Dashboard</a>
                <a href="browse_games.php">Browse Games</a>
                <a href="my_products.php">My Products</a>
                <a href="my_orders.php">My Orders</a>
                <a href="my_rentals.php">My Rentals</a>
                <a href="../logout.php">Logout</a>
            </div>
        </div>
        
        <h2>Available Games</h2>
        
        <?php if ($products->num_rows > 0): ?>
            <div class="product-grid">
                <?php while ($product = $products->fetch_assoc()): ?>
                    <div class="product-card">
                        <?php if ($product['ImageURL']): ?>
                            <img src="<?php echo htmlspecialchars($product['ImageURL']); ?>" 
                                 alt="<?php echo htmlspecialchars($product['ProductName']); ?>"
                                 class="product-image">
                        <?php else: ?>
                            <div class="product-image" style="display: flex; align-items: center; justify-content: center; background: #f0f0f0; font-size: 3em;">
                                🎮
                            </div>
                        <?php endif; ?>
                        
                        <div class="product-details">
                            <h3><?php echo htmlspecialchars($product['ProductName']); ?></h3>
                            <p style="color: #666; font-size: 0.9em;">Sold by: <?php echo htmlspecialchars($product['SellerName']); ?></p>
                            
                            <?php if ($product['Platforms']): ?>
                                <span class="platform"><?php echo htmlspecialchars($product['Platforms']); ?></span>
                            <?php endif; ?>
                            
                            <div class="price">$<?php echo number_format($product['Price'], 2); ?></div>
                            
                            <?php if ($product['IsRentable']): ?>
                                <p style="color: #666; font-size: 0.9em; margin-top: 5px;">
                                    Rent: $<?php echo number_format($product['DailyFee'], 2); ?>/day
                                </p>
                            <?php endif; ?>
                            <div style="margin-top:8px; font-weight:600;">
                                <?php if ($product['Availability'] > 0): ?>
                                    In stock: <?php echo intval($product['Availability']); ?>
                                <?php else: ?>
                                    <span style="color: #d9534f;">Out of stock</span>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($product['AvgRating']): ?>
                                <div class="rating" style="margin-top: 10px;">
                                    ⭐ <?php echo number_format($product['AvgRating'], 1); ?> 
                                    (<?php echo $product['ReviewCount']; ?> reviews)
                                </div>
                            <?php endif; ?>
                            
                            <div class="product-actions">
                                <?php if ($product['IsBuyable'] && $product['SellerID'] != $user_id && $product['Availability'] > 0): ?>
                                    <a href="buy_game.php?id=<?php echo $product['ProductID']; ?>" 
                                       class="btn btn-success btn-small">Buy</a>
                                <?php endif; ?>
                                
                                <?php if ($product['IsRentable'] && $product['SellerID'] != $user_id): ?>
                                    <a href="rent_game.php?id=<?php echo $product['ProductID']; ?>" 
                                       class="btn btn-info btn-small">Rent</a>
                                <?php endif; ?>
                                
                                <a href="view_game.php?id=<?php echo $product['ProductID']; ?>" 
                                   class="btn btn-small">Details</a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-error">No games available at the moment.</div>
        <?php endif; ?>
    </div>
</body>
</html>
