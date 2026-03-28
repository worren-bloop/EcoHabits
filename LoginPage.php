<?php
session_start();

// Database configuration
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

$active_form = 'signup';

$login_error = "";
$signup_error = "";
$signup_success = "";

//Handle login
if (isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    if (!empty($username) && !empty($password)) {
        try {
            // Check users table
            $stmt = $pdo->prepare("SELECT id, username, password FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = 'user';
                header("Location: HomePage.php");
                exit();
            } else {
                // Check admin table
                $stmt = $pdo->prepare("SELECT id, admin_name, password FROM admin WHERE admin_name = ?");
                $stmt->execute([$username]);
                $admin = $stmt->fetch();
                // Debug output for admin login
                error_log('Login attempt: username=' . $username . ', password=' . $password);
                if ($admin) {
                    error_log('DB admin_name=' . $admin['admin_name'] . ', DB password=' . $admin['password']);
                } else {
                    error_log('No admin found for username=' . $username);
                }
                if ($admin && $password === $admin['password']) {
                    $_SESSION['admin_id'] = $admin['id'];
                    $_SESSION['admin_name'] = $admin['admin_name'];
                    $_SESSION['role'] = 'admin';
                    header("Location: HomePage.php");
                    exit();
                } else {
                    $login_error = "Invalid username or password";
                    $active_form = 'login'; // Set to show login form on error
                }
            }
        } catch(PDOException $e) {
            $login_error = "Login failed. Please try again.";
            $active_form = 'login'; // Set to show login form on error
        }
    } else {
        $login_error = "Please fill in all fields";
        $active_form = 'login'; // Set to show login form on error
    }
}

// Handle signup (users only)
if (isset($_POST['signup'])) {
    $username = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    if (!empty($username) && !empty($email) && !empty($password)) {
        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $signup_error = "Invalid email format";
        } elseif (strlen($password) < 8) {
            $signup_error = "Password must be at least 8 characters long";
        } else {
            try {
                // Check if username or email already exists
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
                $stmt->execute([$username, $email]);
                
                if ($stmt->fetchColumn() > 0) {
                    $signup_error = "Username or email already exists";
                } else {
                    // Create new user
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
                    
                    if ($stmt->execute([$username, $email, $hashed_password])) {
                        $_SESSION['user_id'] = $pdo->lastInsertId();
                        $_SESSION['username'] = $username;
                        $_SESSION['role'] = 'user';
                        header("Location: HomePage.php");
                        exit();
                    } else {
                        $signup_error = "Registration failed. Please try again.";
                    }
                }
            } catch(PDOException $e) {
                $signup_error = "Registration failed. Please try again.";
            }
        }
    } else {
        $signup_error = "Please fill in all fields";
    }
}

// AJAX handlers for live validation
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    switch ($_GET['action']) {
        case 'check_username':
            $username = trim($_GET['username']);
            if (!empty($username)) {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
                $stmt->execute([$username]);
                $exists = $stmt->fetchColumn() > 0;
                echo json_encode(['exists' => $exists]);
            } else {
                echo json_encode(['exists' => false]);
            }
            exit();
            
        case 'check_email':
            $email = trim($_GET['email']);
            if (!empty($email)) {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
                $stmt->execute([$email]);
                $exists = $stmt->fetchColumn() > 0;
                echo json_encode(['exists' => $exists]);
            } else {
                echo json_encode(['exists' => false]);
            }
            exit();
    }
}
?>
<?php include __DIR__ . '/includes/cookie_consent.php'; ?>
 <?php include __DIR__ . '/includes/topbar.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | EcoHabits</title>
    <link rel="icon" type="image/png" href="assets/images/EcoHabits_logo.png">
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
    <style>
        video {
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            min-width: 100%;
            min-height: 100%;
            z-index:-100;
        }
        
        body {
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0px;
        }

        @media (max-width: 768px) {
            body {
                padding-top: 0px;
            }
        }

        .Topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            margin-bottom: 10px;
            background-color: rgba(255, 255, 255, 0.5);
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
            border-radius: 15px;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            width: 95%;
            max-width: 1200px;
            margin: 0 auto 20px auto;
        }

        .logo-brand {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .Topbar h2 {
            color: #1F8D49;
            margin: 0;
            font-size: clamp(18px, 4vw, 28px);
            font-weight: 700;
            letter-spacing: -0.5px;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .Topbar a {
            background: transparent;
            color: #1F8D49;
            border-radius: 50px;
            padding: 12px 32px;
            font-size: 2rem;
            font-weight: 500;
            text-decoration: none;
            display: inline-block;
            position: relative;
            transition: color 0.2s, background 0.2s;
            box-shadow: none;
        }
        .Topbar a.active, .Topbar a:focus, .Topbar a:hover {
            color: #1F8D49;
            background: #f4fbf5;
        }
        .Topbar a.active::after, .Topbar a:focus::after, .Topbar a:hover::after {
            content: '';
            display: block;
            margin: 0 auto;
            width: 60%;
            height: 4px;
            background: #1F8D49;
            border-radius: 2px;
            margin-top: 6px;
        }

        .mobile-menu-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 24px;
            color: #1F8D49;
            cursor: pointer;
            padding: 5px;
        }

        .mobile-menu-toggle:hover {
            background-color: rgba(31, 141, 73, 0.1);
            border-radius: 8px;
        }

        /* Responsive design */
        @media (max-width: 768px) {
            .Topbar {
                flex-direction: column;
                align-items: flex-start;
                padding: 15px;
                width: 25%;
            }

            .logo-brand {
                width: 100%;
                justify-content: space-between;
            }

            .mobile-menu-toggle {
                display: block;
            }

            .nav-links {
                width: 100%;
                flex-direction: column;
                gap: 5px;
                margin-top: 15px;
                max-height: 0;
                overflow: hidden;
                transition: max-height 0.3s ease;
            }

            .nav-links.active {
                max-height: 500px;
            }
        }

        @media (max-width: 480px) {
            .Topbar {
                width: calc(100% - 24px);
                margin: 8px 12px 15px 12px;
                padding: 12px;
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
            }

            .Topbar h2 {
                font-size: 20px;
            }

            .nav-links a {
                font-size: 14px;
                padding: 10px 15px;
            }
        }

        /* Large screens */
        @media (min-width: 1200px) {
            .nav-links {
                gap: 20px;
            }

            .Topbar a {
                padding: 12px 20px;
                font-size: 16px;
            }
        }

        /* Tablet view (768px and below) */
        @media (max-width: 768px) {
            .Topbar {
                flex-direction: column;
                align-items: flex-start;
                padding: 15px;
                width: 90%;
                max-width: none;
            }
            .logo-brand {
                width: 100%;
                justify-content: space-between;
            }
            .mobile-menu-toggle {
                display: block;
            }
            .nav-links {
                width: 100%;
                flex-direction: column;
                gap: 5px;
                margin-top: 15px;
                max-height: 0;
                overflow: hidden;
                transition: max-height 0.3s ease;
            }
            .nav-links.active {
                max-height: 500px;
            }
            .nav-links a {
                width: 100%;
                text-align: center;
                padding: 12px 15px;
            }
        }
        /* Mobile view (480px and below) */
        @media (max-width: 480px) {
            .Topbar {
                width: calc(100% - 20px);
                margin: 10px auto 20px auto;
                padding: 15px;
                position: relative;
                top: auto;
                left: auto;
                right: auto;
            }
            .Topbar h2 {
                font-size: 22px;
            }
            .nav-links a {
                font-size: 16px;
                padding: 12px 20px;
            }
            body {
                padding-top: 0;
            }
        }
        /* Extra small screens (360px and below) */
        @media (max-width: 360px) {
            .Topbar {
                width: calc(100% - 16px);
                margin: 8px auto 15px auto;
                padding: 12px;
            }
            .Topbar h2 {
                font-size: 20px;
            }
            .nav-links a {
                font-size: 14px;
                padding: 10px 15px;
            }
        }
        /* Additional improvements for the form container on mobile */
        @media (max-width: 480px) {
            .form-container {
                width: 95%;
                padding: 3vh;
                margin: 10px auto;
            }
            .username, .email, .password {
                width: 100%;
                margin-bottom: 15px;
            }
            .password-wrapper {
                width: 100%;
            }
            .validation-message {
                width: 100%;
            }
            .password-requirements {
                width: 100%;
            }
        }

        /* Rest of the original styles */
        .logo {
            width: 15vh;
            border-radius: 100%;
            border: 5px solid grey;
        }

        /* Typing animation only for .typewriter class */
        .typewriter {
            font-size: clamp(24px, 6vw, 40px);
            overflow: hidden;
            animation: typewriter 3s steps(20) infinite alternate,
                       blink 800ms steps(20) infinite normal;
            white-space: nowrap;
            border-right: 4px solid #FFFFFF;
            width: 10%;
            text-align: center;
            color: white;
            margin: 2vh;
        }
        @keyframes typewriter {
            from {
                width: 0%;
            }
            to {
                width: 100%;
            }
        }
        @keyframes blink {
            from {
                border-color: white;
            }
            to {
                border-color: transparent;
            }
        }

        h3 {
            margin: auto;
        }

        .form-container {
            background: rgba(255, 255, 255, 0.79);
            padding: 5vh;
            border-radius: 5vh;
            text-align: center;
            font-size: clamp(16px, 4vw, 25px);
            max-width: 500px;
            width: 90%;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .username, .email, .password {
            width: 80%;
            border: none;
            border-radius: 25px;
            padding: 2vh;
            margin-bottom: 2vh;
            font-size: clamp(14px, 3vw, 20px);
            background-color: rgba(255, 255, 255, 0.8);
            border: 2px solid transparent;
            transition: border-color 0.3s ease;
            box-sizing: border-box;
        }

        .password-wrapper {
            position: relative;
            width: 80%;
            margin: 0 auto 2vh auto;
            display: block;
        }
        .password-wrapper .password {
            width: 100%;
            padding-right: 40px; /* space for the eye icon */
            box-sizing: border-box;
        }
        .toggle-eye {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            z-index: 2;
        }

        .username.valid, .email.valid, .password.valid {
            border-color: #27ae60;
        }

        .username.invalid, .email.invalid, .password.invalid {
            border-color: #e74c3c;
        }

        .enterEmail {
            margin: 2vh;
        }

        .continue, .login-btn {
            width: auto;
            border-radius: 25px;
            border: none;
            background-color: black;
            color: white;
            font-size: clamp(14px, 3vw, 20px);
            padding: 2vh 12vh;
            cursor: pointer;
            transition: all 0.3s ease;
            margin: 10px 0;
        }

        .continue:hover, .login-btn:hover {
            background-color: #333;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }

        .continue:disabled, .login-btn:disabled {
            background-color: #8f8d8d;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .terms {
            font-size: clamp(10px, 2vw, 12px);
            color: rgba(0, 0, 0, 0.9);
            margin-top: 3vh;
            margin-bottom: 0;
        }
        
        /* New styles for toggle between forms */
        .form-toggle {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
        }
        
        .toggle-btn {
            background: none;
            border: none;
            padding: 10px 20px;
            font-size: clamp(14px, 3vw, 18px);
            cursor: pointer;
            color: #1F8D49;
            font-weight: bold;
            position: relative;
        }
        
        .toggle-btn.active {
            color: #1F8D49;
        }
        
        .toggle-btn.active::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 20%;
            width: 60%;
            height: 3px;
            background-color: #1F8D49;
        }
        
        .form-section {
            display: none;
        }
        
        .form-section.active {
            display: block;
        }
        
        .forgot-password {
            display: block;
            margin-top: 10px;
            font-size: clamp(12px, 2vw, 14px);
            color: #1F8D49;
            text-decoration: none;
        }
        
        .forgot-password:hover {
            text-decoration: underline;
        }

        .validation-message {
            font-size: clamp(10px, 2vw, 12px);
            margin-top: -15px;
            margin-bottom: 10px;
            text-align: left;
            width: 80%;
            margin-left: auto;
            margin-right: auto;
        }

        .validation-message.error {
            color: #e74c3c;
        }

        .validation-message.success {
            color: #27ae60;
        }

        .password-requirements {
            font-size: clamp(10px, 2vw, 12px);
            text-align: left;
            width: 80%;
            margin: -15px auto 10px auto;
            color: #666;
        }

        .password-requirements ul {
            margin: 5px 0;
            padding-left: 20px;
        }

        .password-requirements li {
            margin: 2px 0;
        }

        .password-requirements li.valid {
            color: #27ae60;
        }

        .password-requirements li.invalid {
            color: #e74c3c;
        }

        .error-message {
            color: #e74c3c;
            font-size: clamp(12px, 2vw, 14px);
            margin-top: 10px;
        }

        .success-message {
            color: #27ae60;
            font-size: clamp(12px, 2vw, 14px);
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <video autoplay muted loop>
        <source src="assets/media/login_bg.mp4" type="video/mp4">
    </video>

    <img class="logo" src="assets/images/EcoHabits_logo.png" alt="ecohabits Logo">

    <div class="welcome">
        <h1 class="typewriter">Welcome to EcoHabits</h1>
    </div>

    <div class="form-container">

        <input type="hidden" id="active-form-field" value="<?php echo $active_form; ?>">
        
        <div class="form-toggle">
            <button class="toggle-btn <?php echo $active_form === 'signup' ? 'active' : ''; ?>" onclick="showForm('signup')">Sign Up</button>
            <button class="toggle-btn <?php echo $active_form === 'login' ? 'active' : ''; ?>" onclick="showForm('login')">Log In</button>
        </div>
        
        <!-- Signup Form -->
        <div id="signup-form" class="form-section active">
            <form action="" method="POST" id="signupForm">
                <h3>Create an account</h3>
                <p class="enterEmail">Enter your details to sign up</p>

                <input class="username" type="text" name="name" placeholder="Username" id="signup-username" required>
                <div class="validation-message" id="username-message"></div>

                <input class="email" type="email" name="email" placeholder="email@domain.com" id="signup-email" required>
                <div class="validation-message" id="email-message"></div>

                <div class="password-wrapper">
                    <input class="password" type="password" name="password" placeholder="Password" id="signup-password" required>
                    <span class="toggle-eye" onclick="togglePassword('signup-password', 'signup-eye')">
                        <img id="signup-eye" src="assets/images/eye.png" alt="Show/Hide" width="35">
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
                
                <button type="submit" name="signup" class="continue" id="signup-btn" disabled>Sign Up</button>
                
                <?php if (!empty($signup_error)): ?>
                    <p class="error-message"><?php echo $signup_error; ?></p>
                <?php endif; ?>
                
                <?php if (!empty($signup_success)): ?>
                    <p class="success-message"><?php echo $signup_success; ?></p>
                <?php endif; ?>
                
                <p class="terms">By clicking continue, you agree to our <a href="TermsCondition.php" target="_blank" rel="noopener noreferrer">Terms and Condition</a></p>
            </form>
        </div>
        
        <!-- Login Form -->
        <div id="login-form" class="form-section">
            <form action="" method="POST" id="loginForm">
                <h3>Welcome back</h3>
                <p class="enterEmail">Enter your details to log in</p>
                <input class="username" type="text" name="username" placeholder="Username" required>
                <div class="password-wrapper">
                    <input class="password" type="password" name="password" placeholder="Password" id="login-password" required>
                    <span class="toggle-eye" onclick="togglePassword('login-password', 'login-eye')">
                        <img id="login-eye" src="assets/images/eye.png" alt="Show/Hide" width="35">
                    </span>
                </div>
                <button type="submit" class="login-btn" name="login">Log In</button>
                <?php if (!empty($login_error)): ?>
                    <p class="error-message"><?php echo $login_error; ?></p>
                <?php endif; ?>
            </form>
            <!-- Place the forgot password link OUTSIDE the form so it always works -->
            <a href="forgot_password.php?from=login" class="forgot-password" target="_blank">Forgot password?</a>
        </div>
    </div>

    <script>
        // Debounce function to limit API calls
        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }

        // Live username validation
        const usernameInput = document.getElementById('signup-username');
        const usernameMessage = document.getElementById('username-message');

        const checkUsername = debounce(async (username) => {
            if (username.length < 3) {
                usernameInput.className = 'username invalid';
                usernameMessage.textContent = 'Username must be at least 3 characters long';
                usernameMessage.className = 'validation-message error';
                validationState.username = false;
                updateSubmitButton();
                return false;
            }

            try {
                const response = await fetch(`?action=check_username&username=${encodeURIComponent(username)}`);
                const data = await response.json();
                
                if (data.exists) {
                    usernameInput.className = 'username invalid';
                    usernameMessage.textContent = 'Username already exists';
                    usernameMessage.className = 'validation-message error';
                    validationState.username = false;
                } else {
                    usernameInput.className = 'username valid';
                    usernameMessage.textContent = 'Username available';
                    usernameMessage.className = 'validation-message success';
                    validationState.username = true;
                }
                updateSubmitButton();
                return validationState.username;
            } catch (error) {
                console.error('Error checking username:', error);
                validationState.username = false;
                updateSubmitButton();
                return false;
            }
        }, 500);

        // Live email validation
        const emailInput = document.getElementById('signup-email');
        const emailMessage = document.getElementById('email-message');

        const checkEmail = debounce(async (email) => {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            
            if (!emailRegex.test(email)) {
                emailInput.className = 'email invalid';
                emailMessage.textContent = 'Please enter a valid email address';
                emailMessage.className = 'validation-message error';
                validationState.email = false;
                updateSubmitButton();
                return false;
            }

            try {
                const response = await fetch(`?action=check_email&email=${encodeURIComponent(email)}`);
                const data = await response.json();
                
                if (data.exists) {
                    emailInput.className = 'email invalid';
                    emailMessage.textContent = 'Email already registered';
                    emailMessage.className = 'validation-message error';
                    validationState.email = false;
                } else {
                    emailInput.className = 'email valid';
                    emailMessage.textContent = 'Email available';
                    emailMessage.className = 'validation-message success';
                    validationState.email = true;
                }
                updateSubmitButton();
                return validationState.email;
            } catch (error) {
                console.error('Error checking email:', error);
                validationState.email = false;
                updateSubmitButton();
                return false;
            }
        }, 500);

        // Live password validation
        const passwordInput = document.getElementById('signup-password');
        const lengthReq = document.getElementById('length-req');
        const uppercaseReq = document.getElementById('uppercase-req');
        const lowercaseReq = document.getElementById('lowercase-req');
        const numberReq = document.getElementById('number-req');
        const specialReq = document.getElementById('special-req');

        function validatePassword(password) {
            const requirements = {
                length: password.length >= 8,
                uppercase: /[A-Z]/.test(password),
                lowercase: /[a-z]/.test(password),
                number: /[0-9]/.test(password),
                special: /[^A-Za-z0-9]/.test(password)
            };

            // Update requirement indicators
            lengthReq.className = requirements.length ? 'valid' : 'invalid';
            uppercaseReq.className = requirements.uppercase ? 'valid' : 'invalid';
            lowercaseReq.className = requirements.lowercase ? 'valid' : 'invalid';
            numberReq.className = requirements.number ? 'valid' : 'invalid';
            specialReq.className = requirements.special ? 'valid' : 'invalid';

            const allValid = Object.values(requirements).every(req => req);
            passwordInput.className = allValid ? 'password valid' : 'password invalid';
            
            validationState.password = allValid;
            updateSubmitButton();
            return allValid;
        }

        // Form validation state
        let validationState = {
            username: false,
            email: false,
            password: false
        };

        function updateSubmitButton() {
            const signupBtn = document.getElementById('signup-btn');
            const allValid = Object.values(validationState).every(valid => valid);
            signupBtn.disabled = !allValid;
        }

        // Event listeners
        usernameInput.addEventListener('input', (e) => {
            checkUsername(e.target.value);
        });

        emailInput.addEventListener('input', (e) => {
            checkEmail(e.target.value);
        });

        passwordInput.addEventListener('input', (e) => {
            validatePassword(e.target.value);
        });

        // Initial validation state check
        function checkInitialState() {
            if (usernameInput.value) checkUsername(usernameInput.value);
            if (emailInput.value) checkEmail(emailInput.value);
            if (passwordInput.value) validatePassword(passwordInput.value);
        }

        // Run initial check when page loads
        document.addEventListener('DOMContentLoaded', checkInitialState);

        // Other existing functions
        function toggleMobileMenu() {
            const navLinks = document.getElementById('navLinks');
            navLinks.classList.toggle('active');
        }

        // Close mobile menu when clicking outside
        document.addEventListener('click', function(event) {
            const topbar = document.querySelector('.Topbar');
            const navLinks = document.getElementById('navLinks');
            
            if (!topbar.contains(event.target) && navLinks.classList.contains('active')) {
                navLinks.classList.remove('active');
            }
        });

        // Handle window resize
        window.addEventListener('resize', function() {
            const navLinks = document.getElementById('navLinks');
            if (window.innerWidth > 768) {
                navLinks.classList.remove('active');
            }
        });
        
        // Toggle between signup and login forms
        function showForm(formType) {
            // Update toggle buttons
            document.querySelectorAll('.toggle-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Hide all forms
            document.querySelectorAll('.form-section').forEach(form => {
                form.classList.remove('active');
            });
            
            // Show selected form and activate its button
            if (formType === 'signup') {
                document.getElementById('signup-form').classList.add('active');
                document.querySelectorAll('.toggle-btn')[0].classList.add('active');
            } else {
                document.getElementById('login-form').classList.add('active');
                document.querySelectorAll('.toggle-btn')[1].classList.add('active');
            }
        }

        // Call this on page load with the PHP value
        document.addEventListener('DOMContentLoaded', function() {
            // Get the active form from PHP (we'll pass this via a hidden input)
            const activeForm = document.getElementById('active-form-field').value;
            showForm(activeForm);
            
            // Rest of your initialization code...
            checkInitialState();
        });

        function togglePassword(inputId, eyeId) {
            var pwd = document.getElementById(inputId);
            var eye = document.getElementById(eyeId);
            if (pwd.type === "password") {
                pwd.type = "text";
                eye.src = "assets/images/eye-slash.png"; // Use a different icon for "hide"
            } else {
                pwd.type = "password";
                eye.src = "assets/images/eye.png";
            }
        }
    </script>
</body>
</html>