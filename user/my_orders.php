<?php
require_once '../config.php';
requireLogin();

$user_id = $_SESSION['user_id'];

// Get all orders for user with details
$stmt = $conn->prepare("SELECT o.OrderID, o.OrderDate, p.Amount as PaymentAmount, p.Status as PaymentStatus,
           GROUP_CONCAT(CONCAT(pr.ProductName, ' (x', op.Quantity, ')') SEPARATOR ', ') as Products
    FROM Orders o
    LEFT JOIN Payment p ON o.PaymentID = p.PaymentID
    LEFT JOIN OrderProduct op ON o.OrderID = op.OrderID
    LEFT JOIN Product pr ON op.ProductID = pr.ProductID
    WHERE o.BuyerID = ?
    GROUP BY o.OrderID
    ORDER BY o.OrderDate DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$orders = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - GameXchange</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎮 My Orders</h1>
            <div class="nav">
                <a href="dashboard.php">Dashboard</a>
                <a href="browse_games.php">Browse Games</a>
                <a href="my_products.php">My Products</a>
                <a href="my_orders.php">My Orders</a>
                <a href="my_rentals.php">My Rentals</a>
                <a href="../logout.php">Logout</a>
            </div>
        </div>
        
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Products</th>
                        <th>Order Date</th>
                        <th>Total Amount</th>
                        <th>Payment Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($orders->num_rows > 0): ?>
                        <?php while ($order = $orders->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo $order['OrderID']; ?></td>
                                <td><?php echo htmlspecialchars($order['Products']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($order['OrderDate'])); ?></td>
                                <td>$<?php echo number_format($order['PaymentAmount'], 2); ?></td>
                                <td>
                                    <?php
                                    $status = $order['PaymentStatus'];
                                    $class = $status == 'success' ? 'status-available' : 
                                            ($status == 'pending' ? 'status-rented' : 'status-sold');
                                    ?>
                                    <span class="status-badge <?php echo $class; ?>">
                                        <?php echo ucfirst($status); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="order_details.php?id=<?php echo $order['OrderID']; ?>" 
                                       class="btn btn-small">View Details</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center;">
                                No orders yet. <a href="browse_games.php">Start shopping!</a>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
