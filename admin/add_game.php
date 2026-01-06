<?php
require_once '../config.php';
requireLogin();
requireAdmin();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $product_name = trim($_POST['product_name']);
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $platforms = isset($_POST['platforms']) ? $_POST['platforms'] : [];
    $image_url = trim($_POST['image_url']);
    $product_type = $_POST['product_type'];
    $daily_fee = isset($_POST['daily_fee']) ? floatval($_POST['daily_fee']) : 0;
    $late_fee = isset($_POST['late_fee']) ? floatval($_POST['late_fee']) : 0;
    $availability = isset($_POST['availability']) ? intval($_POST['availability']) : 1;
    
    if (empty($product_name) || empty($description) || empty($platforms)) {
        $error = "Product name, description, and at least one platform are required!";
    } elseif ($product_type == 'both' || $product_type == 'rentable') {
        if ($daily_fee <= 0 || $late_fee <= 0) {
            $error = "Daily fee and late fee are required for rentable products!";
        }
    }
    
    if (empty($error)) {
        $conn->begin_transaction();
        
        try {
            // Insert product (admin is the seller with UserID 1 or current admin)
            $stmt = $conn->prepare("INSERT INTO Product (SellerID, ProductName, Description, Price, Availability) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("issdi", $_SESSION['user_id'], $product_name, $description, $price, $availability);
            $stmt->execute();
            $product_id = $conn->insert_id;
            
            // Insert platforms
            $stmt = $conn->prepare("INSERT INTO ProductPlatform (ProductID, Platform) VALUES (?, ?)");
            foreach ($platforms as $platform) {
                $platform = trim($platform);
                if (!empty($platform)) {
                    $stmt->bind_param("is", $product_id, $platform);
                    $stmt->execute();
                }
            }
            
            // Insert image if provided
            if (!empty($image_url)) {
                $stmt = $conn->prepare("INSERT INTO ProductImage (ProductID, ImageURL) VALUES (?, ?)");
                $stmt->bind_param("is", $product_id, $image_url);
                $stmt->execute();
            }
            
            // Insert into BuyableProduct and/or RentableProduct
            if ($product_type == 'buyable' || $product_type == 'both') {
                $stmt = $conn->prepare("INSERT INTO BuyableProduct (ProductID) VALUES (?)");
                $stmt->bind_param("i", $product_id);
                $stmt->execute();
            }
            
            if ($product_type == 'rentable' || $product_type == 'both') {
                $stmt = $conn->prepare("INSERT INTO RentableProduct (ProductID, DailyFee, LateFee) VALUES (?, ?, ?)");
                $stmt->bind_param("idd", $product_id, $daily_fee, $late_fee);
                $stmt->execute();
            }
            
            $conn->commit();
            $success = "Game added successfully!";
            
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Failed to add game. Error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Game - GameXchange</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎮 Add New Game</h1>
            <div class="nav">
                <a href="dashboard.php">Dashboard</a>
                <a href="manage_games.php">Manage Games</a>
                <a href="add_game.php">Add Game</a>
                <a href="../logout.php">Logout</a>
            </div>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="" style="max-width: 600px;">
            <div class="form-group">
                <label for="product_name">Game Name</label>
                <input type="text" id="product_name" name="product_name" required>
            </div>
            
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="5" required></textarea>
            </div>
            
            <div class="form-group">
                <label for="price">Price ($)</label>
                <input type="number" id="price" name="price" step="0.01" min="0" required>
            </div>

            <div class="form-group">
                <label for="availability">Availability (number of copies)</label>
                <input type="number" id="availability" name="availability" step="1" min="0" value="1" required>
            </div>
            
            <div class="form-group">
                <label>Platforms</label>
                <div id="platform-container" class="phone-inputs">
                    <div class="phone-input-group">
                        <input type="text" name="platforms[]" placeholder="e.g., PS5, Xbox, PC" required>
                    </div>
                </div>
                <button type="button" class="btn-add" onclick="addPlatformField()">+ Add Another Platform</button>
            </div>
            
            <div class="form-group">
                <label for="image_url">Image URL (optional)</label>
                <input type="url" id="image_url" name="image_url" placeholder="https://example.com/image.jpg">
            </div>
            
            <div class="form-group">
                <label for="product_type">Product Type</label>
                <select id="product_type" name="product_type" onchange="toggleRentalFields()" required>
                    <option value="buyable">Buyable Only</option>
                    <option value="rentable">Rentable Only</option>
                    <option value="both">Both Buyable and Rentable</option>
                </select>
            </div>
            
            <div id="rental-fields" style="display: none;">
                <div class="form-group">
                    <label for="daily_fee">Daily Rental Fee ($)</label>
                    <input type="number" id="daily_fee" name="daily_fee" step="0.01" min="0">
                </div>
                
                <div class="form-group">
                    <label for="late_fee">Late Fee per Day ($)</label>
                    <input type="number" id="late_fee" name="late_fee" step="0.01" min="0">
                </div>
            </div>
            
            <button type="submit" class="btn-primary">Add Game</button>
        </form>
    </div>
    
    <script>
        function addPlatformField() {
            const container = document.getElementById('platform-container');
            const platformGroup = document.createElement('div');
            platformGroup.className = 'phone-input-group';
            platformGroup.innerHTML = `
                <input type="text" name="platforms[]" placeholder="e.g., PS5, Xbox, PC">
                <button type="button" class="btn-remove" onclick="removePlatformField(this)">Remove</button>
            `;
            container.appendChild(platformGroup);
        }
        
        function removePlatformField(button) {
            button.parentElement.remove();
        }
        
        function toggleRentalFields() {
            const productType = document.getElementById('product_type').value;
            const rentalFields = document.getElementById('rental-fields');
            
            if (productType === 'rentable' || productType === 'both') {
                rentalFields.style.display = 'block';
                document.getElementById('daily_fee').required = true;
                document.getElementById('late_fee').required = true;
            } else {
                rentalFields.style.display = 'none';
                document.getElementById('daily_fee').required = false;
                document.getElementById('late_fee').required = false;
            }
        }
    </script>
</body>
</html>
