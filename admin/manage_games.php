<?php
require_once '../config.php';
requireLogin();
requireAdmin();

// Handle delete request
if (isset($_GET['delete'])) {
    $product_id = intval($_GET['delete']);
    
    // Check if product has any orders or rentals
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM OrderProduct WHERE ProductID = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $order_count = $res->fetch_assoc()['count'];

    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM Rental WHERE ProductID = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $rental_count = $res->fetch_assoc()['count'];
    
    if ($order_count > 0 || $rental_count > 0) {
        $error = "Cannot delete game with existing orders or rentals!";
    } else {
        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("DELETE FROM ProductImage WHERE ProductID = ?");
            $stmt->bind_param("i", $product_id);
            $stmt->execute();

            $stmt = $conn->prepare("DELETE FROM ProductPlatform WHERE ProductID = ?");
            $stmt->bind_param("i", $product_id);
            $stmt->execute();

            $stmt = $conn->prepare("DELETE FROM BuyableProduct WHERE ProductID = ?");
            $stmt->bind_param("i", $product_id);
            $stmt->execute();

            $stmt = $conn->prepare("DELETE FROM RentableProduct WHERE ProductID = ?");
            $stmt->bind_param("i", $product_id);
            $stmt->execute();

            $stmt = $conn->prepare("DELETE FROM Product WHERE ProductID = ?");
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            $conn->commit();
            $success = "Game deleted successfully!";
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Failed to delete game!";
        }
    }
}

// Handle availability update from admin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_availability'])) {
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $availability = isset($_POST['availability']) ? intval($_POST['availability']) : 0;
    if ($product_id > 0) {
        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("UPDATE Product SET Availability = ? WHERE ProductID = ?");
            $stmt->bind_param("ii", $availability, $product_id);
            $stmt->execute();
            $conn->commit();
            $success = "Availability updated successfully!";
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Failed to update availability.";
        }
    }
}

// Get all products
$products = $conn->query("
    SELECT p.*, p.Availability,
           GROUP_CONCAT(DISTINCT pp.Platform) as Platforms,
           pi.ImageURL,
           CASE WHEN b.ProductID IS NOT NULL THEN 1 ELSE 0 END as IsBuyable,
           CASE WHEN r.ProductID IS NOT NULL THEN 1 ELSE 0 END as IsRentable,
           r.DailyFee, r.LateFee
    FROM Product p
    LEFT JOIN ProductPlatform pp ON p.ProductID = pp.ProductID
    LEFT JOIN ProductImage pi ON p.ProductID = pi.ProductID
    LEFT JOIN BuyableProduct b ON p.ProductID = b.ProductID
    LEFT JOIN RentableProduct r ON p.ProductID = r.ProductID
    GROUP BY p.ProductID
    ORDER BY p.ProductID DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Games - GameXchange</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎮 Manage Games</h1>
            <div class="nav">
                <a href="dashboard.php">Dashboard</a>
                <a href="manage_games.php">Manage Games</a>
                <a href="add_game.php">Add Game</a>
                <a href="../logout.php">Logout</a>
            </div>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Game Name</th>
                        <th>Price</th>
                        <th>Availability</th>
                        <th>Platforms</th>
                        <th>Type</th>
                        <th>Availability Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($products->num_rows > 0): ?>
                        <?php while ($product = $products->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $product['ProductID']; ?></td>
                                <td><?php echo htmlspecialchars($product['ProductName']); ?></td>
                                <td>$<?php echo number_format($product['Price'], 2); ?></td>
                                <td><?php echo intval($product['Availability']); ?></td>
                                <td><?php echo htmlspecialchars($product['Platforms']); ?></td>
                                <td>
                                    <?php
                                    $types = [];
                                    if ($product['IsBuyable']) $types[] = 'Buyable';
                                    if ($product['IsRentable']) $types[] = 'Rentable ($' . $product['DailyFee'] . '/day)';
                                    echo implode(', ', $types);
                                    ?>
                                </td>
                                <td>
                                    <?php if (isset($product['Availability']) && $product['Availability'] > 0): ?>
                                        <span class="status-badge status-available">Available</span>
                                    <?php else: ?>
                                        <span class="status-badge status-sold">Unavailable</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <form method="POST" style="display:inline-block; margin-right:8px;">
                                        <input type="hidden" name="product_id" value="<?php echo $product['ProductID']; ?>">
                                        <input type="number" name="availability" min="0" value="<?php echo intval($product['Availability']); ?>" style="width:80px;">
                                        <button type="submit" name="update_availability" class="btn btn-primary btn-small">Update</button>
                                    </form>
                                    <a href="?delete=<?php echo $product['ProductID']; ?>" 
                                       onclick="return confirm('Are you sure you want to delete this game?')"
                                       class="btn btn-danger btn-small">Delete</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" style="text-align: center;">No games found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
