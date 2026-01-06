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

// Check eligibility: user must have bought the product OR rented and returned it
$stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM (
    SELECT op.OrderID FROM OrderProduct op
    JOIN Orders o ON op.OrderID = o.OrderID
    WHERE op.ProductID = ? AND o.BuyerID = ?
    UNION
    SELECT r.RentalID FROM Rental r
    WHERE r.ProductID = ? AND r.RenterID = ? AND r.ReturnDate IS NOT NULL
) t");
$stmt->bind_param("iiii", $product_id, $user_id, $product_id, $user_id);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();

$eligible = ($row['cnt'] > 0);

if (!$eligible) {
    $error = "You can only review games you've purchased or returned from a rental.";
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $eligible) {
    $rating = intval($_POST['rating']);
    $comment = trim($_POST['comment']);

    if ($rating < 1 || $rating > 5) {
        $error = 'Please provide a rating between 1 and 5.';
    } else {
        $stmt = $conn->prepare("INSERT INTO Review (ProductID, UserID, Rating, Comment, Date) VALUES (?, ?, ?, ?, CURDATE())");
        $stmt->bind_param("iiis", $product_id, $user_id, $rating, $comment);
        if ($stmt->execute()) {
            $success = 'Review submitted. Thank you!';
            header('Location: view_game.php?id=' . $product_id);
            exit();
        } else {
            $error = 'Failed to submit review. Please try again.';
        }
    }
}

// Fetch product name for display
$stmt = $conn->prepare("SELECT ProductName FROM Product WHERE ProductID = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows == 0) {
    header('Location: browse_games.php');
    exit();
}
$product = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Review - GameXchange</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Write a Review</h1>
            <div class="nav">
                <a href="dashboard.php">Dashboard</a>
                <a href="browse_games.php">Browse Games</a>
                <a href="my_rentals.php">My Rentals</a>
                <a href="my_orders.php">My Orders</a>
                <a href="../logout.php">Logout</a>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if ($eligible): ?>
            <div style="max-width:600px; margin: 20px auto; background: white; padding: 20px; border-radius:8px;">
                <h2><?php echo htmlspecialchars($product['ProductName']); ?></h2>
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="rating">Rating (1-5)</label>
                        <select id="rating" name="rating" required>
                            <option value="5">5 - Excellent</option>
                            <option value="4">4 - Very Good</option>
                            <option value="3">3 - Good</option>
                            <option value="2">2 - Fair</option>
                            <option value="1">1 - Poor</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="comment">Comment</label>
                        <textarea id="comment" name="comment" rows="5" required></textarea>
                    </div>
                    <button type="submit" class="btn-primary">Submit Review</button>
                </form>
            </div>
        <?php else: ?>
            <div class="alert alert-error">You are not eligible to review this product.</div>
        <?php endif; ?>
    </div>
</body>
</html>
