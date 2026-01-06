<?php
require_once '../config.php';
requireLogin();

$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

if ($product_id == 0) {
    header('Location: browse_games.php');
    exit();
}

// Get product details
$stmt = $conn->prepare("
    SELECT p.*, r.DailyFee, r.LateFee, r.ProductID as IsRentable
    FROM Product p
    LEFT JOIN RentableProduct r ON p.ProductID = r.ProductID
    WHERE p.ProductID = ? AND p.Availability > 0 AND p.SellerID != ?
");
$stmt->bind_param("ii", $product_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();

if (!$product || $product['IsRentable'] === null) {
    header('Location: browse_games.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $start_date = $_POST['start_date'];
    $rental_days = intval($_POST['rental_days']);
    
    if (empty($start_date) || $rental_days < 1) {
        $error = "Please provide valid rental details!";
    } else {
        $end_date = date('Y-m-d', strtotime($start_date . " + $rental_days days"));
        $amount = $product['DailyFee'] * $rental_days;
        
        $conn->begin_transaction();
        
        try {
            // Create payment record
            $stmt = $conn->prepare("INSERT INTO Payment (UserID, Amount, Date, Status) VALUES (?, ?, CURDATE(), 'success')");
            $stmt->bind_param("id", $user_id, $amount);
            $stmt->execute();
            $payment_id = $conn->insert_id;
            
            // Create rental
            $stmt = $conn->prepare("INSERT INTO Rental (RenterID, ProductID, PaymentID, StartDate, EndDate, Paid) VALUES (?, ?, ?, ?, ?, TRUE)");
            $stmt->bind_param("iiiss", $user_id, $product_id, $payment_id, $start_date, $end_date);
            $stmt->execute();
            $rental_id = $conn->insert_id;
            // Decrement availability by 1 for the rental
            $newAvailability = $product['Availability'] - 1;
            if ($newAvailability < 0) throw new Exception('Not enough stock');
            $stmt = $conn->prepare("UPDATE Product SET Availability = ? WHERE ProductID = ?");
            $stmt->bind_param("ii", $newAvailability, $product_id);
            $stmt->execute();
            
            $conn->commit();
            $success = "Rental successful! Rental ID: #$rental_id. Please return by $end_date to avoid late fees.";
            
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Rental failed. Please try again. " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rent Game - GameXchange</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎮 Rent Game</h1>
            <div class="nav">
                <a href="dashboard.php">Dashboard</a>
                <a href="browse_games.php">Browse Games</a>
                <a href="my_rentals.php">My Rentals</a>
                <a href="../logout.php">Logout</a>
            </div>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <?php echo $success; ?>
                <div style="margin-top: 15px;">
                    <a href="my_rentals.php" class="btn">View My Rentals</a>
                    <a href="browse_games.php" class="btn">Continue Browsing</a>
                </div>
            </div>
        <?php else: ?>
            <div style="background: white; padding: 30px; border-radius: 10px; max-width: 600px; margin: 0 auto;">
                <h2><?php echo htmlspecialchars($product['ProductName']); ?></h2>
                
                <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 20px 0;">
                    <strong>Rental Fees:</strong><br>
                    Daily Fee: $<?php echo number_format($product['DailyFee'], 2); ?>/day<br>
                    Late Fee: $<?php echo number_format($product['LateFee'], 2); ?>/day (if returned late)
                </div>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="start_date">Start Date</label>
                        <input type="date" id="start_date" name="start_date" required 
                               min="<?php echo date('Y-m-d'); ?>"
                               value="<?php echo date('Y-m-d'); ?>"
                               onchange="updateRental()">
                    </div>
                    
                    <div class="form-group">
                        <label for="rental_days">Rental Period (Days)</label>
                        <input type="number" id="rental_days" name="rental_days" value="7" min="1" required 
                               onchange="updateRental()">
                    </div>
                    
                    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 20px 0;">
                        <strong>Rental Summary:</strong><br>
                        <div style="display: flex; justify-content: space-between; margin-top: 10px;">
                            <span>Start Date:</span>
                            <span id="display-start"><?php echo date('M d, Y'); ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-top: 5px;">
                            <span>End Date:</span>
                            <span id="display-end"><?php echo date('M d, Y', strtotime('+7 days')); ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-top: 5px;">
                            <span>Rental Days:</span>
                            <span id="display-days">7</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-top: 5px;">
                            <span>Daily Fee:</span>
                            <span>$<?php echo number_format($product['DailyFee'], 2); ?></span>
                        </div>
                        <hr style="margin: 10px 0;">
                        <div style="display: flex; justify-content: space-between; font-size: 1.2em;">
                            <strong>Total:</strong>
                            <strong id="total-amount">$<?php echo number_format($product['DailyFee'] * 7, 2); ?></strong>
                        </div>
                    </div>
                    
                    <div class="late-fee-warning">
                        <strong>⚠️ Important:</strong> Please return the game by the end date to avoid late fees of 
                        $<?php echo number_format($product['LateFee'], 2); ?> per day.
                    </div>
                    
                    <button type="submit" class="btn-primary">Confirm Rental</button>
                </form>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        function updateRental() {
            const startDate = new Date(document.getElementById('start_date').value);
            const days = parseInt(document.getElementById('rental_days').value) || 1;
            const dailyFee = <?php echo $product['DailyFee']; ?>;
            
            const endDate = new Date(startDate);
            endDate.setDate(endDate.getDate() + days);
            
            const options = { year: 'numeric', month: 'short', day: 'numeric' };
            document.getElementById('display-start').textContent = startDate.toLocaleDateString('en-US', options);
            document.getElementById('display-end').textContent = endDate.toLocaleDateString('en-US', options);
            document.getElementById('display-days').textContent = days;
            document.getElementById('total-amount').textContent = '$' + (dailyFee * days).toFixed(2);
        }
    </script>
</body>
</html>
