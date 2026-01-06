<?php
require_once '../config.php';
requireLogin();

$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$user_id = $_SESSION['user_id'];

if ($order_id == 0) {
    header('Location: my_orders.php');
    exit();
}

// Get order details
$stmt = $conn->prepare("
    SELECT o.*, p.Amount as PaymentAmount, p.Status as PaymentStatus, p.Date as PaymentDate
    FROM Orders o
    LEFT JOIN Payment p ON o.PaymentID = p.PaymentID
    WHERE o.OrderID = ? AND o.BuyerID = ?
");
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header('Location: my_orders.php');
    exit();
}

$order = $result->fetch_assoc();

// Get order products
// Get order products (prepared)
$stmt = $conn->prepare("SELECT op.*, p.ProductName, p.Description FROM OrderProduct op JOIN Product p ON op.ProductID = p.ProductID WHERE op.OrderID = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$products = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details - GameXchange</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎮 Order Details</h1>
            <div class="nav">
                <a href="dashboard.php">Dashboard</a>
                <a href="my_orders.php">My Orders</a>
                <a href="../logout.php">Logout</a>
            </div>
        </div>
        
        <div style="background: white; padding: 30px; border-radius: 10px;">
            <h2>Order #<?php echo $order['OrderID']; ?></h2>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin: 20px 0;">
                <div>
                    <strong>Order Date:</strong><br>
                    <?php echo date('F d, Y', strtotime($order['OrderDate'])); ?>
                </div>
                <div>
                    <strong>Payment Status:</strong><br>
                    <?php
                    $status = $order['PaymentStatus'];
                    $class = $status == 'success' ? 'status-available' : 
                            ($status == 'pending' ? 'status-rented' : 'status-sold');
                    ?>
                    <span class="status-badge <?php echo $class; ?>">
                        <?php echo ucfirst($status); ?>
                    </span>
                </div>
            </div>
            
            <h3 style="margin-top: 30px;">Order Items</h3>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Quantity</th>
                            <th>Price</th>
                            <th>Subtotal</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $total = 0;
                        while ($product = $products->fetch_assoc()):
                            $total += $product['Amount'];
                        ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($product['ProductName']); ?></strong><br>
                                    <small style="color: #666;"><?php echo htmlspecialchars(substr($product['Description'], 0, 100)); ?>...</small>
                                </td>
                                <td><?php echo $product['Quantity']; ?></td>
                                <td>$<?php echo number_format($product['Amount'] / $product['Quantity'], 2); ?></td>
                                <td>$<?php echo number_format($product['Amount'], 2); ?></td>
                                <td>
                                    <a href="add_review.php?id=<?php echo $product['ProductID']; ?>" 
                                       class="btn btn-small">Review</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                    <tfoot>
                        <tr style="font-weight: bold; font-size: 1.1em;">
                            <td colspan="3" style="text-align: right;">Total:</td>
                            <td>$<?php echo number_format($total, 2); ?></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
