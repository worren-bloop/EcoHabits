<?php
session_start();
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ecohabitsdb";

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

$token = $_GET['token'] ?? '';
$source = $_GET['source'] ?? 'login';
$message = '';
$show_form = false;

if ($token) {
    $stmt = $pdo->prepare("SELECT id, token_expiry FROM users WHERE password_reset_token = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    if ($user && strtotime($user['token_expiry']) > time()) {
        $show_form = true;
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
            $password = $_POST['password'];
            if (strlen($password) < 8) {
                $message = 'Password must be at least 8 characters.';
            } else {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ?, password_reset_token = NULL, token_expiry = NULL WHERE id = ?");
                $stmt->execute([$hashed, $user['id']]);
                if ($source === 'account') {
                    $message = 'Password reset successful!';
                } else {
                    $message = 'Password reset successful!';
                }
                $show_form = false;
            }
        }
    } else {
        $message = 'Invalid or expired token.';
    }
} else {
    $message = 'No token provided.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password | EcoHabits</title>
    <link rel="icon" type="image/png" href="assets/images/EcoHabits_logo.png">
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
    <style>
        body { font-family: Arial, sans-serif; background: #f8faf8; display: flex; justify-content: center; align-items: center; height: 100vh; }
        .container { background: #fff; padding: 32px 40px; border-radius: 12px; box-shadow: 0 4px 24px rgba(0,0,0,0.08); min-width: 320px; }
        h2 { color: #1F8D49; margin-bottom: 18px; }
        input[type="password"] { width: 100%; padding: 12px; border-radius: 6px; border: 1px solid #ccc; margin-bottom: 18px; font-size: 16px; }
        button { background: #1F8D49; color: #fff; border: none; padding: 12px 0; width: 100%; border-radius: 6px; font-size: 16px; cursor: pointer; }
        .msg { margin-bottom: 12px; color: #1F8D49; }
        a { color: #1F8D49; text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Reset Password</h2>
        <?php if ($message): ?>
            <div class="msg"><?php echo $message; ?></div>
        <?php endif; ?>
        <?php if ($show_form): ?>
<form method="POST" autocomplete="off" id="resetForm">
    <div class="password-wrapper">
        <input type="password" name="password" id="reset-password" placeholder="New password (min 8 chars)" required>
        <span class="toggle-eye" onclick="togglePassword('reset-password', 'reset-eye')">
            <img id="reset-eye" src="assets/images/eye.png" alt="Show/Hide" width="28">
        </span>
    </div>
    <div class="password-requirements" id="password-requirements">
        <ul>
            <li id="length-req">At least 8 characters</li>
            <li id="uppercase-req">At least 1 uppercase letter</li>
            <li id="lowercase-req">At least 1 lowercase letter</li>
            <li id="number-req">At least 1 number</li>
            <li id="special-req">At least 1 special character</li>
        </ul>
    </div>
    <button type="submit" id="reset-btn" disabled>Reset Password</button>
</form>
<script>
    const passwordInput = document.getElementById('reset-password');
    const lengthReq = document.getElementById('length-req');
    const uppercaseReq = document.getElementById('uppercase-req');
    const lowercaseReq = document.getElementById('lowercase-req');
    const numberReq = document.getElementById('number-req');
    const specialReq = document.getElementById('special-req');
    const resetBtn = document.getElementById('reset-btn');

    function validatePassword(password) {
        const requirements = {
            length: password.length >= 8,
            uppercase: /[A-Z]/.test(password),
            lowercase: /[a-z]/.test(password),
            number: /[0-9]/.test(password),
            special: /[^A-Za-z0-9]/.test(password)
        };
        lengthReq.className = requirements.length ? 'valid' : 'invalid';
        uppercaseReq.className = requirements.uppercase ? 'valid' : 'invalid';
        lowercaseReq.className = requirements.lowercase ? 'valid' : 'invalid';
        numberReq.className = requirements.number ? 'valid' : 'invalid';
        specialReq.className = requirements.special ? 'valid' : 'invalid';
        const allValid = Object.values(requirements).every(Boolean);
        passwordInput.className = allValid ? 'valid' : 'invalid';
        resetBtn.disabled = !allValid;
        return allValid;
    }
    passwordInput.addEventListener('input', (e) => {
        validatePassword(e.target.value);
    });
    // Initial state
    validatePassword(passwordInput.value);

    // Show/hide password toggle
    function togglePassword(inputId, eyeId) {
        var pwd = document.getElementById(inputId);
        var eye = document.getElementById(eyeId);
        if (pwd.type === "password") {
            pwd.type = "text";
            eye.src = "assets/images/eye-slash.png";
        } else {
            pwd.type = "password";
            eye.src = "assets/images/eye.png";
        }
    }
</script>
<style>
    .password-wrapper {
        position: relative;
        width: 100%;
    }
    #reset-password {
        width: 100%;
        padding-right: 44px;
        box-sizing: border-box;
        height: 48px;
        font-size: 18px;
    }
    .toggle-eye {
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        cursor: pointer;
        z-index: 2;
    }
    .toggle-eye img { vertical-align: middle; }
    input.valid {
        border: 2px solid #27ae60;
        background: #f8fff8;
    }
    input.invalid {
        border: 2px solid #e74c3c;
        background: #fff8f8;
    }
    .password-requirements {
        margin-top: 8px;
        font-size: 15px;
    }
    .password-requirements ul { margin: 5px 0; padding-left: 20px; }
    .password-requirements li { margin: 4px 0; }
    .password-requirements li.valid { color: #27ae60; }
    .password-requirements li.invalid { color: #e74c3c; }
</style>
<?php endif; ?>
    </div>
</body>
</html> 