<?php
require_once '../config.php';
requireLogin();

// Get user's statistics
$user_id = $_SESSION['user_id'];

// Count user's products
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM Product WHERE SellerID = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
$my_products = $res->fetch_assoc()['count'];

// Count user's orders
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM Orders WHERE BuyerID = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
$my_orders = $res->fetch_assoc()['count'];

// Count user's rentals
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM Rental WHERE RenterID = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
$my_rentals = $res->fetch_assoc()['count'];

// Count active rentals
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM Rental WHERE RenterID = ? AND ReturnDate IS NULL");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
$active_rentals = $res->fetch_assoc()['count'];

$stmt = $conn->prepare("SELECT o.OrderID, o.OrderDate, (SELECT SUM(Amount) FROM OrderProduct WHERE OrderID = o.OrderID) as Total
    FROM Orders o
    WHERE o.BuyerID = ?
    ORDER BY o.OrderDate DESC
    LIMIT 5");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$recent_orders = $stmt->get_result();

$stmt = $conn->prepare("SELECT r.RentalID, p.ProductName, r.StartDate, r.EndDate, r.ReturnDate
    FROM Rental r
    JOIN Product p ON r.ProductID = p.ProductID
    WHERE r.RenterID = ?
    ORDER BY r.StartDate DESC
    LIMIT 5");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$recent_rentals = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - GameXchange</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎮 GameXchange</h1>
            <div class="nav">
                <a href="dashboard.php">Dashboard</a>
                <a href="browse_games.php">Browse Games</a>
                <a href="sell_list.php">Sell Games</a>
                
                <a href="my_orders.php">My Orders</a>
                <a href="my_rentals.php">My Rentals</a>
                <a href="../logout.php">Logout</a>
            </div>
        </div>
        
        <h2>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</h2>
        
        <div class="dashboard-stats">
            <div class="stat-card">
                <h3><?php echo $my_products; ?></h3>
                <p>My Products</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $my_orders; ?></h3>
                <p>Total Orders</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $my_rentals; ?></h3>
                <p>Total Rentals</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $active_rentals; ?></h3>
                <p>Active Rentals</p>
            </div>
        </div>
        
        <h3>Recent Orders</h3>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Date</th>
                        <th>Total</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($recent_orders->num_rows > 0): ?>
                        <?php while ($order = $recent_orders->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo $order['OrderID']; ?></td>
                                <td><?php echo date('M d, Y', strtotime($order['OrderDate'])); ?></td>
                                <td>$<?php echo number_format($order['Total'], 2); ?></td>
                                <td><a href="my_orders.php" class="btn btn-small">View Details</a></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" style="text-align: center;">No orders yet</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <h3 style="margin-top: 30px;">Recent Rentals</h3>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Rental ID</th>
                        <th>Game</th>
                        <th>Start Date</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($recent_rentals->num_rows > 0): ?>
                        <?php while ($rental = $recent_rentals->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo $rental['RentalID']; ?></td>
                                <td><?php echo htmlspecialchars($rental['ProductName']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($rental['StartDate'])); ?></td>
                                <td>
                                    <?php if ($rental['ReturnDate']): ?>
                                        <span class="status-badge status-available">Returned</span>
                                    <?php else: ?>
                                        <span class="status-badge status-rented">Active</span>
                                    <?php endif; ?>
                                </td>
                                <td><a href="my_rentals.php" class="btn btn-small">View Details</a></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center;">No rentals yet</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <div style="margin-top: 30px; text-align: center;">
            <a href="browse_games.php" class="btn" style="font-size: 1.1em; padding: 15px 30px;">Browse Games</a>
        </div>
    </div>
</body>
</html>
