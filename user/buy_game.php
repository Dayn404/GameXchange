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
    SELECT p.*, b.ProductID as IsBuyable
    FROM Product p
    LEFT JOIN BuyableProduct b ON p.ProductID = b.ProductID
    WHERE p.ProductID = ? AND p.Availability > 0 AND p.SellerID != ?
");
        $stmt->bind_param("ii", $product_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $product = $result->fetch_assoc();

        if (!$product || $product['IsBuyable'] === null) {
            header('Location: browse_games.php');
            exit();
        }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
    $address_id = isset($_POST['address_id']) ? intval($_POST['address_id']) : 0;
    
    if ($quantity < 1) {
        $error = "Invalid quantity!";
    } elseif ($address_id == 0) {
        $error = "Please select a delivery address!";
    } elseif (!isset($product['Availability']) || $product['Availability'] < $quantity) {
        $error = "Requested quantity not available in stock.";
    } else {
        $conn->begin_transaction();
        
        try {
            $amount = $product['Price'] * $quantity;
            
            // Create payment record
            $stmt = $conn->prepare("INSERT INTO Payment (UserID, Amount, Date, Status) VALUES (?, ?, CURDATE(), 'success')");
            $stmt->bind_param("id", $user_id, $amount);
            $stmt->execute();
            $payment_id = $conn->insert_id;
            
            // Create order
            $stmt = $conn->prepare("INSERT INTO Orders (BuyerID, PaymentID, OrderDate) VALUES (?, ?, CURDATE())");
            $stmt->bind_param("ii", $user_id, $payment_id);
            $stmt->execute();
            $order_id = $conn->insert_id;
            
            // Create order product
            $stmt = $conn->prepare("INSERT INTO OrderProduct (OrderID, ProductID, Quantity, Amount) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiid", $order_id, $product_id, $quantity, $amount);
            $stmt->execute();
            
            // Decrement availability
            $newAvailability = $product['Availability'] - $quantity;
            if ($newAvailability < 0) {
                throw new Exception('Not enough stock');
            }
            $stmt = $conn->prepare("UPDATE Product SET Availability = ? WHERE ProductID = ?");
            $stmt->bind_param("ii", $newAvailability, $product_id);
            $stmt->execute();
            
            $conn->commit();
            $success = "Purchase successful! Order ID: #$order_id";
            
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Purchase failed. Please try again.";
        }
    }
}

// Get user addresses
$stmt = $conn->prepare("SELECT * FROM UserAddress WHERE UserID = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$addresses = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buy Game - GameXchange</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎮 Buy Game</h1>
            <div class="nav">
                <a href="dashboard.php">Dashboard</a>
                <a href="browse_games.php">Browse Games</a>
                <a href="my_orders.php">My Orders</a>
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
                    <a href="my_orders.php" class="btn">View My Orders</a>
                    <a href="browse_games.php" class="btn">Continue Shopping</a>
                </div>
            </div>
        <?php else: ?>
            <div style="background: white; padding: 30px; border-radius: 10px; max-width: 600px; margin: 0 auto;">
                <h2><?php echo htmlspecialchars($product['ProductName']); ?></h2>
                <div class="price" style="margin: 20px 0;">$<?php echo number_format($product['Price'], 2); ?></div>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="quantity">Quantity</label>
                        <input type="number" id="quantity" name="quantity" value="1" min="1" required 
                               onchange="updateTotal()">
                    </div>
                    
                    <?php if ($addresses->num_rows > 0): ?>
                        <div class="form-group">
                            <label for="address_id">Delivery Address</label>
                            <select id="address_id" name="address_id" required>
                                <option value="">Select an address</option>
                                <?php while ($address = $addresses->fetch_assoc()): ?>
                                    <option value="<?php echo $address['AddressID']; ?>">
                                        <?php echo htmlspecialchars($address['Address']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-error">
                            You need to add a delivery address first. Please update your profile.
                        </div>
                    <?php endif; ?>
                    
                    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 20px 0;">
                        <strong>Order Summary:</strong><br>
                        <div style="display: flex; justify-content: space-between; margin-top: 10px;">
                            <span>Price:</span>
                            <span>$<?php echo number_format($product['Price'], 2); ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-top: 5px;">
                            <span>Quantity:</span>
                            <span id="display-quantity">1</span>
                        </div>
                        <hr style="margin: 10px 0;">
                        <div style="display: flex; justify-content: space-between; font-size: 1.2em;">
                            <strong>Total:</strong>
                            <strong id="total-amount">$<?php echo number_format($product['Price'], 2); ?></strong>
                        </div>
                    </div>
                    
                    <?php if ($addresses->num_rows > 0): ?>
                        <button type="submit" class="btn-primary">Confirm Purchase</button>
                    <?php endif; ?>
                </form>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        function updateTotal() {
            const quantity = document.getElementById('quantity').value;
            const price = <?php echo $product['Price']; ?>;
            const total = price * quantity;
            
            document.getElementById('display-quantity').textContent = quantity;
            document.getElementById('total-amount').textContent = '$' + total.toFixed(2);
        }
    </script>
</body>
</html>
