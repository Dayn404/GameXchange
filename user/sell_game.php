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

// Get product and seller info
$stmt = $conn->prepare("SELECT p.*, u.UserID as SellerID, u.Name as SellerName, u.Email as SellerEmail, u.role as SellerRole
    FROM Product p
    JOIN Users u ON p.SellerID = u.UserID
    WHERE p.ProductID = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows == 0) {
    header('Location: browse_games.php');
    exit();
}
$product = $result->fetch_assoc();

// Verify product was added by admin
$isAdminAdded = false;
// Check Users.role or Admins table or ADMIN_EMAIL
if (isset($product['SellerRole']) && strtolower($product['SellerRole']) === 'admin') {
    $isAdminAdded = true;
} elseif (defined('ADMIN_EMAIL') && isset($product['SellerEmail']) && $product['SellerEmail'] === ADMIN_EMAIL) {
    $isAdminAdded = true;
} else {
    // Check Admins table for seller
    $stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM Admins WHERE UserID = ?");
    if ($stmt) {
        $stmt->bind_param("i", $product['SellerID']);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res) {
            $row = $res->fetch_assoc();
            if ($row && $row['cnt'] > 0) $isAdminAdded = true;
        }
    }
}

if (!$isAdminAdded) {
    $error = "This product is not eligible to be sold by users.";
}

// Handle POST: user sells copies to platform
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sell_quantity']) && $isAdminAdded) {
    $quantity = intval($_POST['sell_quantity']);
    if ($quantity < 1) {
        $error = "Invalid quantity.";
    } else {
        $sellPrice = round($product['Price'] * 0.65, 2);
        $amount = $sellPrice * $quantity;

        $conn->begin_transaction();
        try {
            // Create a payment record crediting the seller (current user)
            $stmt = $conn->prepare("INSERT INTO Payment (UserID, Amount, Date, Status) VALUES (?, ?, CURDATE(), 'success')");
            $stmt->bind_param("id", $user_id, $amount);
            $stmt->execute();

            // Increase availability
            $stmt = $conn->prepare("UPDATE Product SET Availability = Availability + ? WHERE ProductID = ?");
            $stmt->bind_param("ii", $quantity, $product_id);
            $stmt->execute();

            $conn->commit();
            $success = "Sell recorded. You received $" . number_format($amount, 2) . ". Availability updated.";

            // refresh product data
            $stmt = $conn->prepare("SELECT Availability FROM Product WHERE ProductID = ?");
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res && $res->num_rows > 0) {
                $product['Availability'] = $res->fetch_assoc()['Availability'];
            }
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Failed to record sale. Please try again.";
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sell Game - GameXchange</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Sell Your Copy</h1>
            <div class="nav">
                <a href="dashboard.php">Dashboard</a>
                <a href="browse_games.php">Browse Games</a>
                <a href="my_orders.php">My Orders</a>
                <a href="my_rentals.php">My Rentals</a>
                <a href="../logout.php">Logout</a>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <div style="background: white; padding: 30px; border-radius: 10px; max-width: 600px; margin: 0 auto;">
            <h2><?php echo htmlspecialchars($product['ProductName']); ?></h2>
            <div style="margin: 10px 0; font-weight:600;">Listed Price: $<?php echo number_format($product['Price'], 2); ?></div>
            <div style="margin: 10px 0; font-weight:600;">Sell Price (65%): $<?php echo number_format(round($product['Price'] * 0.65, 2), 2); ?></div>
            <div style="margin: 10px 0; font-weight:600;">Current Availability: <?php echo intval($product['Availability']); ?></div>

            <?php if ($isAdminAdded): ?>
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="sell_quantity">Quantity to sell</label>
                        <input type="number" id="sell_quantity" name="sell_quantity" value="1" min="1" required>
                    </div>
                    <button type="submit" class="btn-primary">Sell to Platform</button>
                </form>
            <?php else: ?>
                <div class="alert alert-error">This product cannot be sold by users.</div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
