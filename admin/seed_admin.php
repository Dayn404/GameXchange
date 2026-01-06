<?php

require_once '../config.php';

if (!defined('ADMIN_EMAIL') || empty(ADMIN_EMAIL)) {
    echo "ADMIN_EMAIL not set in config.php";
    exit();
}

$email = ADMIN_EMAIL;

$stmt = $conn->prepare("SELECT UserID FROM Users WHERE Email = ?");
$stmt->bind_param('s', $email);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) {
    echo "No user found with email: " . htmlspecialchars($email) . ". Create the user first (e.g., via register.php).";
    exit();
}
$user = $res->fetch_assoc();
$user_id = $user['UserID'];

$stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM Admins WHERE UserID = ?");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
if ($row['cnt'] > 0) {
    echo "User is already an admin.";
    exit();
}

$stmt = $conn->prepare("INSERT INTO Admins (UserID, Role) VALUES (?, 'super')");
$stmt->bind_param('i', $user_id);
if ($stmt->execute()) {
    echo "Admin record created for user_id=" . $user_id;
} else {
    echo "Failed to create admin record.";
}

?>

