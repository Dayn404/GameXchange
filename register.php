<?php
require_once 'config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $phones = isset($_POST['phone']) ? $_POST['phone'] : [];
    $addresses = isset($_POST['address']) ? $_POST['address'] : [];
    
    // Validation
    if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "All fields are required!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format!";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match!";
    
    } elseif (empty($phones) || count(array_filter($phones)) == 0) {
        $error = "At least one phone number is required!";
    } elseif (empty($addresses) || count(array_filter($addresses)) == 0) {
        $error = "At least one home address is required!";
    } else {
        // Check if email already exists
        $stmt = $conn->prepare("SELECT UserID FROM Users WHERE Email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = "Email already registered!";
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Begin transaction
            $conn->begin_transaction();
            
            try {
                // Insert user
                $stmt = $conn->prepare("INSERT INTO Users (Name, Email, Password) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $name, $email, $hashed_password);
                $stmt->execute();
                $user_id = $conn->insert_id;
                
                // Insert phone numbers
                $stmt = $conn->prepare("INSERT INTO UserPhone (UserID, Phone) VALUES (?, ?)");
                foreach ($phones as $phone) {
                    $phone = trim($phone);
                    if (!empty($phone)) {
                        $stmt->bind_param("is", $user_id, $phone);
                        $stmt->execute();
                    }
                }
                
                // Insert home addresses
                $stmt = $conn->prepare("INSERT INTO UserAddress (UserID, Address) VALUES (?, ?)");
                foreach ($addresses as $address) {
                    $address = trim($address);
                    if (!empty($address)) {
                        $stmt->bind_param("is", $user_id, $address);
                        $stmt->execute();
                    }
                }
                
                $conn->commit();
                $success = "Registration successful! You can now login.";
                
            } catch (Exception $e) {
                $conn->rollback();
                $error = "Registration failed. Please try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - GameXchange</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <div class="form-container">
        <h2>Register for GameXchange</h2>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" required 
                       value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required minlength="6">
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
            </div>
            
            <div class="form-group">
                <label>Phone Number(s)</label>
                <div id="phone-container" class="phone-inputs">
                    <div class="phone-input-group">
                        <input type="tel" name="phone[]" placeholder="Enter phone number" required>
                    </div>
                </div>
                <button type="button" class="btn-add" onclick="addPhoneField()">+ Add Another Phone</button>
            </div>
            
            <div class="form-group">
                <label>Home Address(es)</label>
                <div id="address-container" class="address-inputs">
                    <div class="address-input-group">
                        <textarea name="address[]" placeholder="Enter your home address (e.g., Street, City, State, ZIP)" required></textarea>
                    </div>
                </div>
                <button type="button" class="btn-add" onclick="addAddressField()">+ Add Another Address</button>
            </div>
            
            <button type="submit" class="btn-primary">Register</button>
        </form>
        
        <div class="text-center">
            <p>Already have an account? <a href="login.php">Login here</a></p>
        </div>
    </div>
    
    <script>
        function addPhoneField() {
            const container = document.getElementById('phone-container');
            const phoneGroup = document.createElement('div');
            phoneGroup.className = 'phone-input-group';
            phoneGroup.innerHTML = `
                <input type="tel" name="phone[]" placeholder="Enter phone number">
                <button type="button" class="btn-remove" onclick="removePhoneField(this)">Remove</button>
            `;
            container.appendChild(phoneGroup);
        }
        
        function removePhoneField(button) {
            button.parentElement.remove();
        }
        
        function addAddressField() {
            const container = document.getElementById('address-container');
            const addressGroup = document.createElement('div');
            addressGroup.className = 'address-input-group';
            addressGroup.innerHTML = `
                <textarea name="address[]" placeholder="Enter your home address (e.g., Street, City, State, ZIP)"></textarea>
                <button type="button" class="btn-remove" onclick="removeAddressField(this)">Remove</button>
            `;
            container.appendChild(addressGroup);
        }
        
        function removeAddressField(button) {
            button.parentElement.remove();
        }
    </script>
</body>
</html>
