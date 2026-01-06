<?php
require_once '../config.php';
requireLogin();
requireAdmin();

// Get all orders with details
$orders = $conn->query("
    SELECT o.OrderID, u.Name as BuyerName, o.OrderDate,
           p.Status as PaymentStatus, p.Amount as PaymentAmount,
           GROUP_CONCAT(CONCAT(pr.ProductName, ' (', op.Quantity, ')') SEPARATOR ', ') as Products
    FROM Orders o
    JOIN Users u ON o.BuyerID = u.UserID
    LEFT JOIN Payment p ON o.PaymentID = p.PaymentID
    LEFT JOIN OrderProduct op ON o.OrderID = op.OrderID
    LEFT JOIN Product pr ON op.ProductID = pr.ProductID
    GROUP BY o.OrderID
    ORDER BY o.OrderDate DESC
");

// Get all rentals with details
$rentals = $conn->query("
    SELECT r.RentalID, u.Name as RenterName, pr.ProductName, 
           r.StartDate, r.EndDate, r.ReturnDate, r.Paid,
           rp.DailyFee, rp.LateFee,
           p.Amount as PaymentAmount, p.Status as PaymentStatus
    FROM Rental r
    JOIN Users u ON r.RenterID = u.UserID
    JOIN Product pr ON r.ProductID = pr.ProductID
    JOIN RentableProduct rp ON r.ProductID = rp.ProductID
    LEFT JOIN Payment p ON r.PaymentID = p.PaymentID
    ORDER BY r.StartDate DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Transactions - GameXchange</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎮 Transactions</h1>
            <div class="nav">
                <a href="dashboard.php">Dashboard</a>
                <a href="manage_games.php">Manage Games</a>
                <a href="add_game.php">Add Game</a>
                <a href="view_transactions.php">Transactions</a>
                <a href="../logout.php">Logout</a>
            </div>
        </div>
        
        <h2>Orders</h2>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Buyer</th>
                        <th>Products</th>
                        <th>Order Date</th>
                        <th>Amount</th>
                        <th>Payment Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($orders->num_rows > 0): ?>
                        <?php while ($order = $orders->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo $order['OrderID']; ?></td>
                                <td><?php echo htmlspecialchars($order['BuyerName']); ?></td>
                                <td><?php echo htmlspecialchars($order['Products']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($order['OrderDate'])); ?></td>
                                <td>$<?php echo number_format($order['PaymentAmount'], 2); ?></td>
                                <td>
                                    <?php
                                    $status = $order['PaymentStatus'];
                                    $class = $status == 'success' ? 'status-available' : 
                                            ($status == 'pending' ? 'status-rented' : 'status-sold');
                                    ?>
                                    <span class="status-badge <?php echo $class; ?>"><?php echo ucfirst($status); ?></span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center;">No orders yet</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <h2 style="margin-top: 40px;">Rentals</h2>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Rental ID</th>
                        <th>Renter</th>
                        <th>Product</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Return Date</th>
                        <th>Status</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($rentals->num_rows > 0): ?>
                        <?php while ($rental = $rentals->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo $rental['RentalID']; ?></td>
                                <td><?php echo htmlspecialchars($rental['RenterName']); ?></td>
                                <td><?php echo htmlspecialchars($rental['ProductName']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($rental['StartDate'])); ?></td>
                                <td><?php echo $rental['EndDate'] ? date('M d, Y', strtotime($rental['EndDate'])) : 'N/A'; ?></td>
                                <td><?php echo $rental['ReturnDate'] ? date('M d, Y', strtotime($rental['ReturnDate'])) : 'Not Returned'; ?></td>
                                <td>
                                    <?php if ($rental['ReturnDate']): ?>
                                        <span class="status-badge status-available">Returned</span>
                                    <?php else: ?>
                                        <span class="status-badge status-rented">Active</span>
                                    <?php endif; ?>
                                </td>
                                <td>$<?php echo number_format($rental['PaymentAmount'], 2); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" style="text-align: center;">No rentals yet</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
