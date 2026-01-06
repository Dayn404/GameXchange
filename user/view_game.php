<?php
require_once '../config.php';
requireLogin();

$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$user_id = $_SESSION['user_id'];

if ($product_id == 0) {
    header('Location: browse_games.php');
    exit();
}

// Get product details
$stmt = $conn->prepare("
    SELECT p.*, 
            p.Availability,
           GROUP_CONCAT(DISTINCT pp.Platform SEPARATOR ', ') as Platforms,
           pi.ImageURL,
           u.Name as SellerName, u.Email as SellerEmail, u.role as SellerRole,
           CASE WHEN b.ProductID IS NOT NULL THEN 1 ELSE 0 END as IsBuyable,
           CASE WHEN r.ProductID IS NOT NULL THEN 1 ELSE 0 END as IsRentable,
           r.DailyFee, r.LateFee
    FROM Product p
    JOIN Users u ON p.SellerID = u.UserID
    LEFT JOIN ProductPlatform pp ON p.ProductID = pp.ProductID
    LEFT JOIN ProductImage pi ON p.ProductID = pi.ProductID
    LEFT JOIN BuyableProduct b ON p.ProductID = b.ProductID
    LEFT JOIN RentableProduct r ON p.ProductID = r.ProductID
    WHERE p.ProductID = ?
    GROUP BY p.ProductID
");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header('Location: browse_games.php');
    exit();
}

$product = $result->fetch_assoc();

// Get reviews (prepared)
$stmt = $conn->prepare("SELECT r.*, u.Name FROM Review r JOIN Users u ON r.UserID = u.UserID WHERE r.ProductID = ? ORDER BY r.Date DESC");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$reviews = $stmt->get_result();

// Calculate average rating (prepared)
$stmt = $conn->prepare("SELECT AVG(Rating) as AvgRating, COUNT(*) as Count FROM Review WHERE ProductID = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$avg_rating_result = $stmt->get_result();
$rating_data = $avg_rating_result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['ProductName']); ?> - GameXchange</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎮 Game Details</h1>
            <div class="nav">
                <a href="dashboard.php">Dashboard</a>
                <a href="browse_games.php">Browse Games</a>
                <a href="my_products.php">My Products</a>
                <a href="../logout.php">Logout</a>
            </div>
        </div>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 30px;">
            <div>
                <?php if ($product['ImageURL']): ?>
                    <img src="<?php echo htmlspecialchars($product['ImageURL']); ?>" 
                         alt="<?php echo htmlspecialchars($product['ProductName']); ?>"
                         style="width: 100%; border-radius: 10px;">
                <?php else: ?>
                    <div style="width: 100%; height: 400px; background: #f0f0f0; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 5em;">
                        🎮
                    </div>
                <?php endif; ?>
            </div>
            
            <div>
                <h2><?php echo htmlspecialchars($product['ProductName']); ?></h2>
                
                <?php if ($rating_data['Count'] > 0): ?>
                    <div class="rating" style="font-size: 1.3em; margin: 10px 0;">
                        ⭐ <?php echo number_format($rating_data['AvgRating'], 1); ?> 
                        (<?php echo $rating_data['Count']; ?> reviews)
                    </div>
                <?php endif; ?>
                
                <div style="margin: 20px 0;">
                    <strong>Seller:</strong> <?php echo htmlspecialchars($product['SellerName']); ?>
                </div>
                
                <?php if ($product['Platforms']): ?>
                    <div style="margin: 10px 0;">
                        <strong>Platforms:</strong> 
                        <span class="platform"><?php echo htmlspecialchars($product['Platforms']); ?></span>
                    </div>
                <?php endif; ?>
                
                <div style="margin: 20px 0;">
                    <strong>Description:</strong><br>
                    <p style="margin-top: 10px; color: #666;">
                        <?php echo nl2br(htmlspecialchars($product['Description'])); ?>
                    </p>
                </div>
                
                <div class="price" style="font-size: 2em; margin: 20px 0;">
                    $<?php echo number_format($product['Price'], 2); ?>
                </div>
                <div style="margin-top:6px; font-weight:600;">
                    <?php if ($product['Availability'] > 0): ?>
                        In stock: <?php echo intval($product['Availability']); ?>
                    <?php else: ?>
                        <span style="color: #d9534f;">Out of stock</span>
                    <?php endif; ?>
                </div>
                
                <?php if ($product['IsRentable']): ?>
                    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 20px 0;">
                        <strong>Rental Information:</strong><br>
                        Daily Fee: $<?php echo number_format($product['DailyFee'], 2); ?>/day<br>
                        Late Fee: $<?php echo number_format($product['LateFee'], 2); ?>/day
                    </div>
                <?php endif; ?>
                
                <?php if ($product['Availability'] > 0 && $product['SellerID'] != $user_id): ?>
                    <div class="product-actions" style="margin-top: 30px;">
                                    <?php if ($product['IsBuyable'] && $product['Availability'] > 0): ?>
                                         <a href="buy_game.php?id=<?php echo $product['ProductID']; ?>" 
                                             class="btn btn-success" style="flex: 1; text-align: center; padding: 15px;">Buy Now</a>
                                    <?php endif; ?>
                        
                        <?php if ($product['IsRentable']): ?>
                            <a href="rent_game.php?id=<?php echo $product['ProductID']; ?>" 
                               class="btn btn-info" style="flex: 1; text-align: center; padding: 15px;">Rent Now</a>
                        <?php endif; ?>

                        <?php
                        // Show Sell option when original product was added by admin
                        $showSell = false;
                        if (isset($product['SellerRole']) && strtolower($product['SellerRole']) === 'admin') $showSell = true;
                        if (defined('ADMIN_EMAIL') && isset($product['SellerEmail']) && $product['SellerEmail'] === ADMIN_EMAIL) $showSell = true;
                        if ($showSell && $product['SellerID'] != $user_id): ?>
                            <a href="sell_game.php?id=<?php echo $product['ProductID']; ?>" 
                               class="btn btn-secondary" style="flex: 1; text-align: center; padding: 15px; margin-top:8px;">Sell Your Copy</a>
                        <?php endif; ?>
                    </div>
                <?php elseif ($product['SellerID'] == $user_id): ?>
                    <div class="alert alert-error">This is your own product.</div>
                <?php else: ?>
                    <div class="alert alert-error">This product is currently unavailable.</div>
                <?php endif; ?>
            </div>
        </div>
        
        <h3>Reviews</h3>
        <a href="add_review.php?id=<?php echo $product_id; ?>" class="btn" style="margin-bottom: 20px;">Write a Review</a>
        
        <?php if ($reviews->num_rows > 0): ?>
            <?php while ($review = $reviews->fetch_assoc()): ?>
                <div class="review-card">
                    <div class="review-header">
                        <strong><?php echo htmlspecialchars($review['Name']); ?></strong>
                        <span class="rating">
                            <?php for ($i = 0; $i < $review['Rating']; $i++): ?>⭐<?php endfor; ?>
                        </span>
                    </div>
                    <small style="color: #666;"><?php echo date('M d, Y', strtotime($review['Date'])); ?></small>
                    <p style="margin-top: 10px;"><?php echo nl2br(htmlspecialchars($review['Comment'])); ?></p>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p style="color: #666;">No reviews yet. Be the first to review this game!</p>
        <?php endif; ?>
    </div>
</body>
</html>
