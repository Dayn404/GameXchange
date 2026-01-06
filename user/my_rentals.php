<?php
require_once '../config.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Handle return action
if (isset($_GET['return'])) {
    $rental_id = intval($_GET['return']);
    $return_date = date('Y-m-d');
    
    // Get rental details
    $stmt = $conn->prepare("
        SELECT r.*, rp.LateFee 
        FROM Rental r
        JOIN RentableProduct rp ON r.ProductID = rp.ProductID
        WHERE r.RentalID = ? AND r.RenterID = ? AND r.ReturnDate IS NULL
    ");
    $stmt->bind_param("ii", $rental_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $rental = $result->fetch_assoc();
        
        $conn->begin_transaction();
        try {
            // Update rental with return date
            $stmt = $conn->prepare("UPDATE Rental SET ReturnDate = ? WHERE RentalID = ?");
            $stmt->bind_param("si", $return_date, $rental_id);
            $stmt->execute();
            
            // Calculate late fee if applicable
            $end_date = new DateTime($rental['EndDate']);
            $actual_return = new DateTime($return_date);
            
            if ($actual_return > $end_date) {
                $late_days = $actual_return->diff($end_date)->days;
                $late_fee = $late_days * $rental['LateFee'];
                
                // Create payment for late fee
                $stmt = $conn->prepare("INSERT INTO Payment (UserID, Amount, Date, Status) VALUES (?, ?, ?, 'pending')");
                $stmt->bind_param("ids", $user_id, $late_fee, $return_date);
                $stmt->execute();
                
                $success = "Game returned successfully. Late fee of $" . number_format($late_fee, 2) . " applied ($late_days days late).";
            } else {
                $success = "Game returned successfully on time!";
            }
            
            // Increment product availability when returned
            $product_id = $rental['ProductID'];
            $stmt = $conn->prepare("UPDATE Product SET Availability = Availability + 1 WHERE ProductID = ?");
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            
            $conn->commit();
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Failed to process return. Please try again.";
        }
    }
}

// Get all rentals for user (prepared)
$stmt = $conn->prepare("SELECT r.*, p.ProductName, rp.DailyFee, rp.LateFee, pay.Amount as RentalAmount, pay.Status as PaymentStatus
    FROM Rental r
    JOIN Product p ON r.ProductID = p.ProductID
    JOIN RentableProduct rp ON r.ProductID = rp.ProductID
    LEFT JOIN Payment pay ON r.PaymentID = pay.PaymentID
    WHERE r.RenterID = ?
    ORDER BY r.StartDate DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$rentals = $stmt->get_result();

// Get pending late fees (prepared)
$stmt = $conn->prepare("SELECT SUM(Amount) as TotalLateFees FROM Payment WHERE UserID = ? AND Status = 'pending'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$late_fees = $stmt->get_result();
$late_fee_total = $late_fees->fetch_assoc()['TotalLateFees'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Rentals - GameXchange</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎮 My Rentals</h1>
            <div class="nav">
                <a href="dashboard.php">Dashboard</a>
                <a href="browse_games.php">Browse Games</a>
                <a href="my_orders.php">My Orders</a>
                <a href="my_rentals.php">My Rentals</a>
                <a href="../logout.php">Logout</a>
            </div>
        </div>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($late_fee_total > 0): ?>
            <div class="late-fee-warning">
                <strong>⚠️ You have pending late fees: $<?php echo number_format($late_fee_total, 2); ?></strong>
                <p>Please settle your late fees to continue renting games.</p>
            </div>
        <?php endif; ?>
        
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Rental ID</th>
                        <th>Game</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Return Date</th>
                        <th>Status</th>
                        <th>Amount</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($rentals->num_rows > 0): ?>
                        <?php while ($rental = $rentals->fetch_assoc()): ?>
                            <?php
                            // Calculate if late
                            $is_late = false;
                            $late_days = 0;
                            $late_fee_amount = 0;
                            
                            if (!$rental['ReturnDate'] && $rental['EndDate']) {
                                $end_date = new DateTime($rental['EndDate']);
                                $today = new DateTime();
                                if ($today > $end_date) {
                                    $is_late = true;
                                    $late_days = $today->diff($end_date)->days;
                                    $late_fee_amount = $late_days * $rental['LateFee'];
                                }
                            }
                            ?>
                            <tr <?php if ($is_late): ?>style="background: #fff3cd;"<?php endif; ?>>
                                <td>#<?php echo $rental['RentalID']; ?></td>
                                <td><?php echo htmlspecialchars($rental['ProductName']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($rental['StartDate'])); ?></td>
                                <td><?php echo $rental['EndDate'] ? date('M d, Y', strtotime($rental['EndDate'])) : 'N/A'; ?></td>
                                <td>
                                    <?php if ($rental['ReturnDate']): ?>
                                        <?php echo date('M d, Y', strtotime($rental['ReturnDate'])); ?>
                                    <?php else: ?>
                                        <span style="color: #dc3545; font-weight: bold;">Not Returned</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($rental['ReturnDate']): ?>
                                        <span class="status-badge status-available">Returned</span>
                                    <?php elseif ($is_late): ?>
                                        <span class="status-badge status-sold">LATE (<?php echo $late_days; ?> days)</span>
                                    <?php else: ?>
                                        <span class="status-badge status-rented">Active</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    $<?php echo number_format($rental['RentalAmount'], 2); ?>
                                    <?php if ($is_late): ?>
                                        <br><span style="color: #dc3545; font-weight: bold;">
                                            + $<?php echo number_format($late_fee_amount, 2); ?> late fee
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!$rental['ReturnDate']): ?>
                                        <a href="?return=<?php echo $rental['RentalID']; ?>" 
                                           class="btn btn-success btn-small"
                                           onclick="return confirm('Are you sure you want to mark this rental as returned?')">
                                            Return Game
                                        </a>
                                    <?php else: ?>
                                        <a href="add_review.php?id=<?php echo $rental['ProductID']; ?>" 
                                           class="btn btn-small">Review</a>
                                    <?php endif; ?>
                                </td>
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
