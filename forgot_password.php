<?php
session_start();
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ecohabitsdb";

// Ensure $from is defined before any HTML or use
$from = (isset($_POST['from']) && $_POST['from'] === 'account') ? 'account' : ((isset($_GET['from']) && $_GET['from'] === 'account') ? 'account' : 'login');

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

$message = '';
$show_link = false;
$reset_link = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    $email = trim($_POST['email']);
    if (!empty($email)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user) {
            // Generate token and expiry
            $token = bin2hex(random_bytes(32));
            $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
            // Store in DB
            $stmt = $pdo->prepare("UPDATE users SET password_reset_token = ?, token_expiry = ? WHERE id = ?");
            $success = $stmt->execute([$token, $expiry, $user['id']]);
            if (!$success) {
                die('Failed to update user: ' . implode(' | ', $stmt->errorInfo()));
            }
            // Determine source for reset link
            // $from = isset($_GET['from']) && $_GET['from'] === 'account' ? 'account' : 'login'; // Moved to top
            // Prepare reset link
            $reset_link = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . "/reset_password.php?token=$token&source=$from";
            // For real email: mail($email, 'Password Reset', "Reset your password: $reset_link");
            $show_link = true;
        }
        // Always show this message for security
        $message = 'If the email exists in our system, a reset link has been sent.';
    } else {
        $message = 'Please enter your email address.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password | EcoHabits</title>
    <link rel="icon" type="image/png" href="assets/images/EcoHabits_logo.png">
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
    <style>
        body { font-family: Arial, sans-serif; background: #f8faf8; display: flex; justify-content: center; align-items: center; height: 100vh; }
        .container { background: #fff; padding: 32px 40px; border-radius: 12px; box-shadow: 0 4px 24px rgba(0,0,0,0.08); min-width: 320px; }
        h2 { color: #1F8D49; margin-bottom: 18px; }
        input[type="email"] { width: 100%; padding: 12px; border-radius: 6px; border: 1px solid #ccc; margin-bottom: 18px; font-size: 16px; }
        button { background: #1F8D49; color: #fff; border: none; padding: 12px 0; width: 100%; border-radius: 6px; font-size: 16px; cursor: pointer; }
        .msg { margin-bottom: 12px; color: #1F8D49; }
        .reset-link { background: #f0f8f0; padding: 10px; border-radius: 6px; margin-top: 10px; word-break: break-all; }
        a { color: #1F8D49; text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Forgot Password</h2>
        <?php if ($message): ?>
            <div class="msg"><?php echo $message; ?></div>
        <?php endif; ?>
        <?php if ($show_link): ?>
            <div class="reset-link">
                <strong>Reset Link (for testing):</strong><br>
                <a href="<?php echo htmlspecialchars($reset_link); ?>">Click here to reset your password</a>
                <br><small>Link: <?php echo htmlspecialchars($reset_link); ?></small>
            </div>
        <?php endif; ?>
        <form method="POST" autocomplete="off">
            <input type="email" name="email" placeholder="Enter your email" required>
            <button type="submit">Send Reset Link</button>
        </form>
        <div style="margin-top:16px; text-align:center;">
            <?php if ($from === 'account'): ?>
                <a href="userAcc.php">Back to Account</a>
            <?php else: ?>
                <a href="LoginPage.php">Back to Login</a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html> 