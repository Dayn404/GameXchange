<?php
require_once '../config.php';
requireLogin();
requireAdmin();

// Get statistics
$stats = [];

// Total users (exclude legacy ADMIN_EMAIL)
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM Users WHERE Email != ?");
$adminEmail = ADMIN_EMAIL;
$stmt->bind_param("s", $adminEmail);
$stmt->execute();
$res = $stmt->get_result();
$stats['users'] = $res->fetch_assoc()['count'];

// Total games
$result = $conn->query("SELECT COUNT(*) as count FROM Product");
$stats['games'] = $result->fetch_assoc()['count'];

// Total orders
$result = $conn->query("SELECT COUNT(*) as count FROM Orders");
$stats['orders'] = $result->fetch_assoc()['count'];

// Total rentals
$result = $conn->query("SELECT COUNT(*) as count FROM Rental");
$stats['rentals'] = $result->fetch_assoc()['count'];

// Active rentals
$result = $conn->query("SELECT COUNT(*) as count FROM Rental WHERE ReturnDate IS NULL");
$stats['active_rentals'] = $result->fetch_assoc()['count'];

// Recent orders
$recent_orders = $conn->query("
    SELECT o.OrderID, u.Name, o.OrderDate, 
           (SELECT SUM(Amount) FROM OrderProduct WHERE OrderID = o.OrderID) as Total
    FROM Orders o
    JOIN Users u ON o.BuyerID = u.UserID
    ORDER BY o.OrderDate DESC
    LIMIT 10
");

// Recent rentals
$recent_rentals = $conn->query("
    SELECT r.RentalID, u.Name, p.ProductName, r.StartDate, r.EndDate, r.ReturnDate
    FROM Rental r
    JOIN Users u ON r.RenterID = u.UserID
    JOIN Product p ON r.ProductID = p.ProductID
    ORDER BY r.StartDate DESC
    LIMIT 10
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - GameXchange</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎮 Admin Dashboard</h1>
            <div class="nav">
                <a href="dashboard.php">Dashboard</a>
                <a href="manage_games.php">Manage Games</a>
                <a href="add_game.php">Add Game</a>
                <a href="view_transactions.php">Transactions</a>
                <a href="../logout.php">Logout</a>
            </div>
        </div>
        
        <h2>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</h2>
        
        <div class="dashboard-stats">
            <div class="stat-card">
                <h3><?php echo $stats['users']; ?></h3>
                <p>Total Users</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $stats['games']; ?></h3>
                <p>Total Games</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $stats['orders']; ?></h3>
                <p>Total Orders</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $stats['rentals']; ?></h3>
                <p>Total Rentals</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $stats['active_rentals']; ?></h3>
                <p>Active Rentals</p>
            </div>
        </div>
        
        <h3>Recent Orders</h3>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Date</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($recent_orders->num_rows > 0): ?>
                        <?php while ($order = $recent_orders->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo $order['OrderID']; ?></td>
                                <td><?php echo htmlspecialchars($order['Name']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($order['OrderDate'])); ?></td>
                                <td>$<?php echo number_format($order['Total'], 2); ?></td>
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
                        <th>Customer</th>
                        <th>Game</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($recent_rentals->num_rows > 0): ?>
                        <?php while ($rental = $recent_rentals->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo $rental['RentalID']; ?></td>
                                <td><?php echo htmlspecialchars($rental['Name']); ?></td>
                                <td><?php echo htmlspecialchars($rental['ProductName']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($rental['StartDate'])); ?></td>
                                <td><?php echo $rental['EndDate'] ? date('M d, Y', strtotime($rental['EndDate'])) : 'N/A'; ?></td>
                                <td>
                                    <?php if ($rental['ReturnDate']): ?>
                                        <span class="status-badge status-available">Returned</span>
                                    <?php else: ?>
                                        <span class="status-badge status-rented">Active</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center;">No rentals yet</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
