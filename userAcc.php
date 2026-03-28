<?php
session_start();
$servername = "localhost";
$dbusername = "root";
$dbpassword = "";
$dbname = "ecohabitsdb";

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $dbusername, $dbpassword);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Add AJAX detection after session start
$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// Get current user info from session
$user_id = $_SESSION['user_id'] ?? null;
$username = $_SESSION['username'] ?? '';

// Fetch user info from DB
$user = null;
if ($user_id) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Initialize messages
$profile_msg = '';
$password_msg = '';

// AJAX handlers for live validation
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    switch ($_GET['action']) {
        case 'check_username':
            $new_username = trim($_GET['username']);
            if (!empty($new_username)) {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? AND id != ?");
                $stmt->execute([$new_username, $user_id]);
                $exists = $stmt->fetchColumn() > 0;
                echo json_encode(['exists' => $exists]);
            } else {
                echo json_encode(['exists' => false]);
            }
            exit();
            
        case 'check_email':
            $new_email = trim($_GET['email']);
            if (!empty($new_email)) {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ? AND id != ?");
                $stmt->execute([$new_email, $user_id]);
                $exists = $stmt->fetchColumn() > 0;
                echo json_encode(['exists' => $exists]);
            } else {
                echo json_encode(['exists' => false]);
            }
            exit();
    }
}

// Handle profile update (username/email)
if (isset($_POST['update_profile'])) {
    $new_username = trim($_POST['username']);
    $new_email = trim($_POST['email']);
    $success = false;
    if ($new_username === '' || $new_email === '') {
        $profile_msg = 'Username and email cannot be empty.';
    } elseif (strlen($new_username) < 3) {
        $profile_msg = 'Username must be at least 3 characters long.';
    } elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        $profile_msg = 'Please enter a valid email address.';
    } else {
        // Check uniqueness
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE (username = ? OR email = ?) AND id != ?");
        $stmt->execute([$new_username, $new_email, $user_id]);
        if ($stmt->fetchColumn() > 0) {
            $profile_msg = 'Username or email already exists.';
        } else {
            // Update
            $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
            if ($stmt->execute([$new_username, $new_email, $user_id])) {
                $_SESSION['username'] = $new_username;
                $profile_msg = 'Profile updated successfully!';
                $success = true;
                // Refresh user info
                $user['username'] = $new_username;
                $user['email'] = $new_email;
            } else {
                $profile_msg = 'Failed to update profile.';
            }
        }
    }
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => $success, 'message' => $profile_msg]);
        exit();
    }
}

// Handle password update
if (isset($_POST['update_password'])) {
    $old_password = $_POST['old_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    $success = false;
    if ($new_password !== $confirm_password) {
        $password_msg = 'New passwords do not match.';
    } elseif (strlen($new_password) < 8) {
        $password_msg = 'New password must be at least 8 characters long.';
    } elseif (!preg_match('/[A-Z]/', $new_password)) {
        $password_msg = 'New password must contain at least one uppercase letter.';
    } elseif (!preg_match('/[a-z]/', $new_password)) {
        $password_msg = 'New password must contain at least one lowercase letter.';
    } elseif (!preg_match('/[0-9]/', $new_password)) {
        $password_msg = 'New password must contain at least one number.';
    } elseif (!preg_match('/[^A-Za-z0-9]/', $new_password)) {
        $password_msg = 'New password must contain at least one special character.';
    } else {
        // Verify old password
        if (!password_verify($old_password, $user['password'])) {
            $password_msg = 'Current password is incorrect.';
        } else {
            // Update password
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            if ($stmt->execute([$hashed, $user_id])) {
                $password_msg = 'Password updated successfully!';
                $success = true;
                // Update user array for display
                $user['password'] = $hashed;
            } else {
                $password_msg = 'Failed to update password.';
            }
        }
    }
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => $success, 'message' => $password_msg]);
        exit();
    }
}

// Add logout logic at the top
if (isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    header('Location: LoginPage.php');
    exit();
}

// Store original password for display (we'll need to retrieve it differently)
$original_password = '';
if ($user && isset($_POST['show_original_password'])) {
    // Note: In a real application, you should NEVER store plain text passwords
    // This is just for demonstration purposes
    $original_password = $_POST['original_password_display'] ?? '';
}

// Feedback form and logic
if (isset($_POST['submit_feedback'])) {
    $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
    $feedback_text = trim($_POST['feedback_text'] ?? '');
    $user_id = $_SESSION['user_id'] ?? null;
    if ($user_id && $rating >= 1 && $rating <= 5 && $feedback_text !== '') {
        $stmt = $pdo->prepare("INSERT INTO feedback (user_id, rating, feedback_text) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $rating, $feedback_text]);
        $feedback_success = true;
    } else {
        $feedback_error = 'Please provide a rating (1-5) and feedback.';
    }
}
?>
<?php include __DIR__ . '/includes/cookie_consent.php'; ?>
<!DOCTYPE html>
<html lang="en">

<style>
    /* Global reset and basic styling */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: Arial, sans-serif;
}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    line-height: 1.8;
    color: #333;
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
    background-color: #f8faf8;
    background-image: 
        radial-gradient(#1F8D49 0.5px, transparent 0.5px),
        radial-gradient(#1F8D49 0.5px, #f8faf8 0.5px);
    background-size: 20px 20px;
    background-position: 0 0, 10px 10px;
    background-attachment: fixed;
}

/* Topbar Design - Matches Video Page */
.Topbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 20px;
    border-bottom: 1px solid #d4e6d4;
    margin-bottom: 30px;
    background-color: white;
    position: sticky;
    top: 0;
    z-index: 100;
    box-shadow: 0 2px 10px rgba(0,0,0,0.03);
    border-radius: 0 0 12px 12px;
}

.Topbar h1 {
    color: #1F8D49;
    margin: 0;
    font-size: 28px;
    font-weight: 700;
    letter-spacing: -0.5px;
}

.Topbar a {
    color: #34495e;
    text-decoration: none;
    margin-left: 20px;
    font-weight: 500;
    padding: 8px 12px;
    border-radius: 20px;
    transition: all 0.3s ease;
    position: relative;
}

.Topbar a::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 50%;
    width: 0;
    height: 2px;
    background-color: #1F8D49;
    transition: all 0.3s ease;
    transform: translateX(-50%);
}

.Topbar a:hover {
    color: #1F8D49;
    background-color: #f0f8f0;
}

.Topbar a:hover::after {
    width: 70%;
}

.Topbar img {
    vertical-align: middle;
    margin-right: 5px;
    height: 48px;
    filter: brightness(0.8);
    transition: filter 0.3s ease;
}

.Topbar a:hover img {
    filter: brightness(0) saturate(100%) invert(37%) sepia(68%) saturate(362%) hue-rotate(100deg) brightness(91%) contrast(86%);
}

/* Profile container - Enhanced */
.profile-container {
    background: linear-gradient(135deg, #e8f5e8 0%, #f0fdf0 100%);
    padding: 25px;
    border-radius: 15px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    width: 380px;
    border: 2px solid #d4e6d4;
    position: relative;
    overflow: hidden;
    height: fit-content;
}

.profile-container::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #1F8D49, #27ae60, #1F8D49);
    animation: shimmer 2s ease-in-out infinite;
}

@keyframes shimmer {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
}

.profile-container h1 {
    margin-bottom: 25px;
    color: #1F8D49;
    text-align: center;
    font-size: 24px;
    font-weight: 600;
}

/* Profile header (avatar + username) - Enhanced */
.profile-header {
    display: flex;
    flex-direction: column;
    align-items: center;
    margin-bottom: 25px;
    padding: 20px;
    background: rgba(255, 255, 255, 0.7);
    border-radius: 15px;
    backdrop-filter: blur(10px);
}

.avatar {
    background: linear-gradient(135deg, #1F8D49, #27ae60);
    width: 90px;
    height: 90px;
    border-radius: 50%;
    display: flex;
    justify-content: center;
    align-items: center;
    margin-bottom: 15px;
    font-size: 42px;
    color: white;
    box-shadow: 0 4px 15px rgba(31, 141, 73, 0.3);
    transition: transform 0.3s ease;
}

.avatar:hover {
    transform: scale(1.05);
}

.username {
    font-size: 22px;
    color: #1F8D49;
    font-weight: 600;
    margin-bottom: 5px;
}

.user-status {
    font-size: 14px;
    color: #666;
    background: #f0f8f0;
    padding: 4px 12px;
    border-radius: 20px;
}

/* Individual profile items - Enhanced */
.profile-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: rgba(255, 255, 255, 0.8);
    padding: 18px;
    margin-bottom: 15px;
    border-radius: 12px;
    border: 1px solid #e8f5e8;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.profile-item::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 4px;
    background: linear-gradient(135deg, #1F8D49, #27ae60);
    transform: scaleY(0);
    transition: transform 0.3s ease;
}

.profile-item:hover::before {
    transform: scaleY(1);
}

.profile-item:hover {
    background: rgba(255, 255, 255, 0.95);
    transform: translateX(5px);
    box-shadow: 0 4px 15px rgba(31, 141, 73, 0.1);
}

.profile-item label {
    font-weight: 600;
    color: #1F8D49;
    font-size: 16px;
}

.item-content {
    display: flex;
    align-items: center;
    gap: 10px;
}

.item-content span {
    color: #2c3e50;
    font-size: 14px;
    max-width: 150px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.password-display {
    position: relative;
    display: flex;
    align-items: center;
    gap: 8px;
}

.password-toggle {
    background: none;
    border: none;
    color: #1F8D49;
    cursor: pointer;
    font-size: 16px;
    padding: 4px;
    border-radius: 4px;
    transition: background-color 0.3s ease;
}

.password-toggle:hover {
    background-color: #f0f8f0;
}

/* Edit Profile Container - Enhanced */
.edit-container {
    background: linear-gradient(135deg, #f8fffe 0%, #ffffff 100%);
    padding: 30px;
    border-radius: 15px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    width: 420px;
    margin-left: 40px;
    display: inline-block;
    vertical-align: top;
    border: 2px solid #e8f5e8;
    position: relative;
}

.edit-container::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #1F8D49, #27ae60, #1F8D49);
}

.edit-container h2 {
    color: #1F8D49;
    margin-bottom: 20px;
    font-size: 20px;
    font-weight: 600;
    text-align: center;
}

.edit-container label {
    display: block;
    margin-bottom: 8px;
    color: #1F8D49;
    font-weight: 600;
    font-size: 14px;
}

.edit-container input[type="text"],
.edit-container input[type="email"],
.edit-container input[type="password"] {
    width: 100%;
    padding: 12px 15px;
    margin-bottom: 5px;
    border-radius: 8px;
    border: 2px solid #e8f5e8;
    font-size: 14px;
    transition: all 0.3s ease;
    background: #fafafa;
}

.edit-container input:focus {
    outline: none;
    border-color: #1F8D49;
    background: white;
    box-shadow: 0 0 0 3px rgba(31, 141, 73, 0.1);
}

.edit-container input.valid {
    border-color: #27ae60;
    background: #f8fff8;
}

.edit-container input.invalid {
    border-color: #e74c3c;
    background: #fff8f8;
}

.edit-container button {
    background: linear-gradient(135deg, #1F8D49, #27ae60);
    color: white;
    border: none;
    padding: 12px 25px;
    border-radius: 8px;
    cursor: pointer;
    margin-bottom: 15px;
    font-weight: 600;
    font-size: 14px;
    transition: all 0.3s ease;
    width: 100%;
}

.edit-container button:hover {
    background: linear-gradient(135deg, #27ae60, #1F8D49);
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(31, 141, 73, 0.3);
}

.edit-container button:disabled {
    background: #ccc;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}

.edit-container .msg {
    margin-bottom: 15px;
    padding: 10px;
    border-radius: 8px;
    font-size: 14px;
    text-align: center;
}

.edit-container .msg:not(.success) {
    color: #e74c3c;
    background: #fff5f5;
    border: 1px solid #fecaca;
}

.edit-container .msg.success {
    color: #27ae60;
    background: #f0fff4;
    border: 1px solid #bbf7d0;
}

.validation-message {
    font-size: 12px;
    margin-bottom: 10px;
    margin-top: -5px;
    text-align: left;
}

.validation-message.error {
    color: #e74c3c;
}

.validation-message.success {
    color: #27ae60;
}

.password-requirements {
    font-size: 12px;
    text-align: left;
    margin-top: -5px;
    margin-bottom: 10px;
    color: #666;
    background: #f8f9fa;
    padding: 10px;
    border-radius: 6px;
    border-left: 4px solid #e9ecef;
}

.password-requirements ul {
    margin: 5px 0;
    padding-left: 20px;
}

.password-requirements li {
    margin: 3px 0;
    transition: color 0.3s ease;
}

.password-requirements li.valid {
    color: #27ae60;
    font-weight: 600;
}

.password-requirements li.invalid {
    color: #e74c3c;
}

.section-divider {
    margin: 30px 0;
    border: none;
    height: 2px;
    background: linear-gradient(90deg, transparent, #e8f5e8, transparent);
}

/* Responsive Design */
@media (max-width: 900px) {
    .profile-container, .edit-container {
        display: block;
        width: 95%;
        margin: 0 auto 20px auto;
    }
    
    .edit-container {
        margin-left: 0;
    }
}

@media (max-width: 768px) {
    .Topbar {
        flex-direction: column;
        padding: 15px;
        gap: 15px;
    }
    
    .Topbar div {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 10px;
    }
    
    .Topbar a {
        margin-left: 0;
        font-size: 14px;
    }
    
    .profile-container, .edit-container {
        width: 100%;
        padding: 20px;
    }
}

.logout-btn {
  width: 100%;
  background: #e74c3c;
  color: white;
  padding: 12px 0;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-size: 16px;
  font-weight: 600;
  margin-top: 10px;
  box-shadow: 0 2px 8px rgba(231,76,60,0.08);
  transition: all 0.25s cubic-bezier(.4,2,.6,1);
}
.logout-btn:hover {
  background: #c0392b;
  transform: scale(1.04);
  box-shadow: 0 4px 18px 0 rgba(231,76,60,0.25);
}
.req-icon {
    display: inline-block;
    width: 1.2em;
    font-weight: bold;
    font-size: 1.1em;
    margin-right: 4px;
    vertical-align: middle;
}
.feedback-form-vertical {
    display: flex;
    flex-direction: column;
    gap: 1em;
    max-width: 400px;
}
.feedback-form-card {
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    padding: 2em 2em 1.5em 2em;
    max-width: 400px;
    width: 100%;
    margin-top: 2em;
    display: flex;
    flex-direction: column;
    gap: 1.2em;
    align-items: stretch;
}
.feedback-form-card h3 {
    margin-top: 0;
    color: #1F8D49;
    font-size: 1.3em;
    font-weight: 600;
    letter-spacing: 0.5px;
}
.feedback-form-card label {
    font-weight: 500;
    margin-bottom: 0.3em;
    color: #333;
}
.feedback-form-card textarea {
    border-radius: 8px;
    border: 1px solid #ccc;
    padding: 0.7em;
    font-size: 1em;
    resize: vertical;
    min-height: 80px;
    transition: border 0.2s;
}
.feedback-form-card textarea:focus {
    border: 1.5px solid #1F8D49;
    outline: none;
}
.feedback-form-card button[type="submit"] {
    background: #1F8D49;
    color: #fff;
    border: none;
    border-radius: 8px;
    padding: 0.7em 0;
    font-size: 1.1em;
    font-weight: 600;
    cursor: pointer;
    margin-top: 0.5em;
    transition: background 0.2s, box-shadow 0.2s;
    box-shadow: 0 1px 4px rgba(31,141,73,0.08);
}
.feedback-form-card button[type="submit"]:hover {
    background: #176b38;
}
#star-rating {
    display: flex;
    flex-direction: row;
    width: fit-content;
    font-size: 2em;
    color: #FFD700;
    cursor: pointer;
    gap: 0.1em;
}
#star-rating .star {
    transition: color 0.2s, transform 0.15s;
}
#star-rating .star:hover,
#star-rating .star:hover ~ .star {
    color: #FFC107;
    transform: scale(1.15);
}
.feedback-form-card .msg {
    font-size: 1em;
    margin-bottom: 0.5em;
    border-radius: 6px;
    padding: 0.5em 1em;
}
.feedback-form-card .msg[style*='green'] {
    background: #eafaf1;
    color: #1F8D49;
}
.feedback-form-card .msg[style*='red'] {
    background: #ffeaea;
    color: #c0392b;
}
</style>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Page</title>
    <link rel="icon" type="image/png" href="assets/images/EcoHabits_logo.png">
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
    <!-- Link to Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include __DIR__ . '/includes/topbar.php'; ?>
    <div style="display: flex; flex-direction: row; align-items: flex-start; gap: 40px; justify-content: center;">
        <!-- Left Column: Profile + Feedback -->
        <div style="display: flex; flex-direction: column; align-items: center; gap: 20px;">
            <!-- Profile Container -->
            <div class="profile-container">
                
                <h1>Profile</h1>
                <div class="profile-header">
                    <div class="avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="username">
                        <?php echo htmlspecialchars($user['username'] ?? ''); ?>
                    </div>
                </div>
                <div class="profile-item">
                    <label>Email</label>
                    <div class="item-content">
                        <span><?php echo htmlspecialchars($user['email'] ?? ''); ?></span>
                    </div>
                </div>
                <form method="POST" style="margin:0;">
                  <button type="submit" name="logout" class="logout-btn">Log Out</button>
                </form>
            </div>

            <!-- Feedback Form (users cannot see any feedback, only submit) -->
            <div class="feedback-form-card">
                <h3>Submit Feedback</h3>
                <?php if (!empty($feedback_success)): ?>
                    <div class="msg" style="color:green;">Thank you for your feedback!</div>
                <?php elseif (!empty($feedback_error)): ?>
                    <div class="msg" style="color:red;"><?= htmlspecialchars($feedback_error) ?></div>
                <?php endif; ?>
                <form method="post" class="feedback-form-vertical">
                    <label for="rating">Rating:</label>
                    <div id="star-rating">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <span class="star" data-value="<?= $i ?>">&#9733;</span>
                        <?php endfor; ?>
                    </div>
                    <input type="hidden" name="rating" id="rating-input" value="3">
                    <label for="feedback_text">Feedback:</label>
                    <textarea name="feedback_text" id="feedback_text" rows="4" required></textarea>
                    <button type="submit" name="submit_feedback">Submit</button>
                </form>
            </div>
        </div>
        <!-- Right Column: Edit Profile -->
        <div class="edit-container">
            <h2>Edit Profile</h2>
            <?php if ($profile_msg): ?>
                <div class="msg <?php echo strpos($profile_msg, 'success') !== false ? 'success' : ''; ?>"><?php echo $profile_msg; ?></div>
            <?php endif; ?>
            <form method="POST" autocomplete="off" id="profileForm">
                <label for="username">Username</label>
                <input type="text" name="username" id="username" value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>" required>
                <div class="validation-message" id="username-message"></div>

                <label for="email">Email</label>
                <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                <div class="validation-message" id="email-message"></div>

                <button type="submit" name="update_profile" id="update-profile-btn">Update Profile</button>
            </form>

            <hr class="section-divider">

            <h2>Change Password</h2>
            <?php if ($password_msg): ?>
                <div class="msg <?php echo strpos($password_msg, 'success') !== false ? 'success' : ''; ?>"><?php echo $password_msg; ?></div>
            <?php endif; ?>
            <form method="POST" autocomplete="off" id="passwordForm">
                <label for="old_password">Current Password</label>
                <div style="position:relative;">
                    <input type="password" name="old_password" id="old_password" required>
                    <button type="button" onclick="togglePasswordVisibility('old_password', 'old-password-eye')"><i id="old-password-eye" class="fas fa-eye"></i></button>
                </div>
                <div class="validation-message" id="old-password-message"></div>

                <label for="new_password">New Password</label>
                <div style="position:relative;">
                    <input type="password" name="new_password" id="new_password" required>
                    <button type="button" onclick="togglePasswordVisibility('new_password', 'new-password-eye')"><i id="new-password-eye" class="fas fa-eye"></i></button>
                </div>
                <div class="password-requirements" id="password-requirements" style="display:none;">
                    <ul>
                        <li id="length-req"><span class="req-icon" id="length-icon">✖</span> At least 8 characters</li>
                        <li id="uppercase-req"><span class="req-icon" id="uppercase-icon">✖</span> At least 1 uppercase letter</li>
                        <li id="lowercase-req"><span class="req-icon" id="lowercase-icon">✖</span> At least 1 lowercase letter</li>
                        <li id="number-req"><span class="req-icon" id="number-icon">✖</span> At least 1 number</li>
                        <li id="special-req"><span class="req-icon" id="special-icon">✖</span> At least 1 special character</li>
                    </ul>
                </div>

                <div id="confirm-password-section" style="display:none;">
                    <label for="confirm_password">Confirm New Password</label>
                    <div style="position:relative;">
                        <input type="password" name="confirm_password" id="confirm_password" required>
                        <button type="button" onclick="togglePasswordVisibility('confirm_password', 'confirm-password-eye')"><i id="confirm-password-eye" class="fas fa-eye"></i></button>
                    </div>
                    <div class="validation-message" id="confirm-password-message"></div>
                </div>

                <div style="text-align:right; margin-bottom:10px;">
                    <a href="forgot_password.php?from=account" style="font-size:13px; color:#1F8D49; text-decoration:underline; cursor:pointer;" target="_blank">Forgot Password?</a>
                </div>
                <button type="submit" name="update_password" id="update-password-btn" disabled>Change Password</button>
            </form>
        </div>
    </div>

    <script>
        // Function to toggle password visibility for change password form
        function togglePasswordVisibility(inputId, eyeId) {
            const input = document.getElementById(inputId);
            const eye = document.getElementById(eyeId);

            if (input.type === 'password') {
                input.type = 'text';
                eye.className = 'fas fa-eye-slash';
            } else {
                input.type = 'password';
                eye.className = 'fas fa-eye';
            }
        }

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

        // Validation state
        let profileValidation = {
            username: true, // Start as valid since it's pre-populated
            email: true     // Start as valid since it's pre-populated
        };

        let passwordValidation = {
            password: false,
            confirm: false
        };

        // Live username validation
        const usernameInput = document.getElementById('username');
        const usernameMessage = document.getElementById('username-message');

        const checkUsername = debounce(async (username) => {
            if (username.length < 3) {
                usernameInput.className = 'invalid';
                usernameMessage.textContent = 'Username must be at least 3 characters long';
                usernameMessage.className = 'validation-message error';
                profileValidation.username = false;
                updateProfileButton();
                return;
            }

            try {
                const response = await fetch(`?action=check_username&username=${encodeURIComponent(username)}`);
                const data = await response.json();
                
                if (data.exists) {
                    usernameInput.className = 'invalid';
                    usernameMessage.textContent = 'Username already exists';
                    usernameMessage.className = 'validation-message error';
                    profileValidation.username = false;
                } else {
                    usernameInput.className = 'valid';
                    usernameMessage.textContent = 'Username available';
                    usernameMessage.className = 'validation-message success';
                    profileValidation.username = true;
                }
                updateProfileButton();
            } catch (error) {
                console.error('Error checking username:', error);
                profileValidation.username = false;
                updateProfileButton();
            }
        }, 500);

        // Live email validation
        const emailInput = document.getElementById('email');
        const emailMessage = document.getElementById('email-message');

        const checkEmail = debounce(async (email) => {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            
            if (!emailRegex.test(email)) {
                emailInput.className = 'invalid';
                emailMessage.textContent = 'Please enter a valid email address';
                emailMessage.className = 'validation-message error';
                profileValidation.email = false;
                updateProfileButton();
                return;
            }

            try {
                const response = await fetch(`?action=check_email&email=${encodeURIComponent(email)}`);
                const data = await response.json();
                
                if (data.exists) {
                    emailInput.className = 'invalid';
                    emailMessage.textContent = 'Email already registered';
                    emailMessage.className = 'validation-message error';
                    profileValidation.email = false;
                } else {
                    emailInput.className = 'valid';
                    emailMessage.textContent = 'Email available';
                    emailMessage.className = 'validation-message success';
                    profileValidation.email = true;
                }
                updateProfileButton();
            } catch (error) {
                console.error('Error checking email:', error);
                profileValidation.email = false;
                updateProfileButton();
            }
        }, 500);

        // Password validation
        const passwordInput = document.getElementById('new_password');
        const confirmPasswordInput = document.getElementById('confirm_password');
        const confirmPasswordMessage = document.getElementById('confirm-password-message');
        const passwordRequirements = document.getElementById('password-requirements');
        const confirmPasswordSection = document.getElementById('confirm-password-section');

        const lengthReq = document.getElementById('length-req');
        const uppercaseReq = document.getElementById('uppercase-req');
        const lowercaseReq = document.getElementById('lowercase-req');
        const numberReq = document.getElementById('number-req');
        const specialReq = document.getElementById('special-req');
        const lengthIcon = document.getElementById('length-icon');
        const uppercaseIcon = document.getElementById('uppercase-icon');
        const lowercaseIcon = document.getElementById('lowercase-icon');
        const numberIcon = document.getElementById('number-icon');
        const specialIcon = document.getElementById('special-icon');

        function validatePassword(password) {
            const requirements = {
                length: password.length >= 8,
                uppercase: /[A-Z]/.test(password),
                lowercase: /[a-z]/.test(password),
                number: /[0-9]/.test(password),
                special: /[^A-Za-z0-9]/.test(password)
            };

            // Update requirement indicators and icons
            lengthReq.className = requirements.length ? 'valid' : 'invalid';
            lengthIcon.textContent = requirements.length ? '✔' : '✖';
            uppercaseReq.className = requirements.uppercase ? 'valid' : 'invalid';
            uppercaseIcon.textContent = requirements.uppercase ? '✔' : '✖';
            lowercaseReq.className = requirements.lowercase ? 'valid' : 'invalid';
            lowercaseIcon.textContent = requirements.lowercase ? '✔' : '✖';
            numberReq.className = requirements.number ? 'valid' : 'invalid';
            numberIcon.textContent = requirements.number ? '✔' : '✖';
            specialReq.className = requirements.special ? 'valid' : 'invalid';
            specialIcon.textContent = requirements.special ? '✔' : '✖';

            const allValid = Object.values(requirements).every(req => req);
            if (password.length === 0) {
                passwordInput.className = '';
            } else {
                passwordInput.className = allValid ? 'valid' : 'invalid';
            }
            passwordValidation.password = allValid;
            updatePasswordButton();

            // Also check confirm password if it has content
            if (confirmPasswordInput.value) {
                validateConfirmPassword();
            }

            return allValid;
        }

        function validateConfirmPassword() {
            const password = passwordInput.value;
            const confirmPassword = confirmPasswordInput.value;
            
            if (password === confirmPassword && password.length > 0) {
                confirmPasswordInput.className = 'valid';
                confirmPasswordMessage.textContent = 'Passwords match';
                confirmPasswordMessage.className = 'validation-message success';
                passwordValidation.confirm = true;
            } else {
                confirmPasswordInput.className = 'invalid';
                confirmPasswordMessage.textContent = 'Passwords do not match';
                confirmPasswordMessage.className = 'validation-message error';
                passwordValidation.confirm = false;
            }
            updatePasswordButton();
        }

        // Update buttons based on validation
        function updateProfileButton() {
            const updateProfileBtn = document.getElementById('update-profile-btn');
            if (profileValidation.username && profileValidation.email) {
                updateProfileBtn.disabled = false;
                updateProfileBtn.style.backgroundColor = '#1F8D49';
                updateProfileBtn.style.transform = 'none';
                updateProfileBtn.style.boxShadow = 'none';
            } else {
                updateProfileBtn.disabled = true;
                updateProfileBtn.style.backgroundColor = '#ccc';
                updateProfileBtn.style.transform = 'none';
                updateProfileBtn.style.boxShadow = 'none';
            }
        }

        function updatePasswordButton() {
            const updatePasswordBtn = document.getElementById('update-password-btn');
            if (passwordValidation.password && passwordValidation.confirm) {
                updatePasswordBtn.disabled = false;
                updatePasswordBtn.style.backgroundColor = '#1F8D49';
                updatePasswordBtn.style.transform = 'none';
                updatePasswordBtn.style.boxShadow = 'none';
            } else {
                updatePasswordBtn.disabled = true;
                updatePasswordBtn.style.backgroundColor = '#ccc';
                updatePasswordBtn.style.transform = 'none';
                updatePasswordBtn.style.boxShadow = 'none';
            }
        }

        // Initial validation
        updateProfileButton();
        updatePasswordButton();

        // Event listeners for input fields
        usernameInput.addEventListener('input', () => checkUsername(usernameInput.value));
        emailInput.addEventListener('input', () => checkEmail(emailInput.value));
        // Show/hide password requirements and confirm password fields based on input
        passwordInput.addEventListener('input', () => {
            if (passwordInput.value.length > 0) {
                passwordRequirements.style.display = 'block';
                confirmPasswordSection.style.display = 'block';
            } else {
                passwordRequirements.style.display = 'none';
                confirmPasswordSection.style.display = 'none';
                confirmPasswordInput.value = '';
                confirmPasswordInput.className = '';
                confirmPasswordMessage.textContent = '';
                confirmPasswordMessage.className = 'validation-message';
            }
            validatePassword(passwordInput.value);
        });
        confirmPasswordInput.addEventListener('input', () => validateConfirmPassword());

        // Initial password validation
        validatePassword(passwordInput.value);
        validateConfirmPassword();

        // Handle form submission for profile update
        document.getElementById('profileForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            // Check if validation passes
            if (!profileValidation.username || !profileValidation.email) {
                alert('Please fix the validation errors before submitting.');
                return;
            }
            
            const formData = new FormData(e.target);
            formData.append('update_profile', '1');
            
            try {
                const response = await fetch('userAcc.php', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // Show success message
                    const msgDiv = document.querySelector('.edit-container .msg');
                    if (msgDiv) {
                        msgDiv.remove();
                    }
                    
                    const successMsg = document.createElement('div');
                    successMsg.className = 'msg success';
                    successMsg.textContent = result.message;
                    
                    const profileForm = document.getElementById('profileForm');
                    profileForm.parentNode.insertBefore(successMsg, profileForm);
                    
                    // Update the displayed profile information
                    document.querySelector('.username').textContent = formData.get('username');
                    document.querySelector('.profile-item .item-content span').textContent = formData.get('email');
                    
                    // Clear the input fields
                    document.getElementById('username').value = '';
                    document.getElementById('email').value = '';
                    
                    // Clear validation messages and classes
                    usernameInput.className = '';
                    emailInput.className = '';
                    usernameMessage.textContent = '';
                    usernameMessage.className = 'validation-message';
                    emailMessage.textContent = '';
                    emailMessage.className = 'validation-message';
                    
                    // Show success alert
                    alert('Profile updated successfully!');
                } else {
                    // Show error message
                    const msgDiv = document.querySelector('.edit-container .msg');
                    if (msgDiv) {
                        msgDiv.remove();
                    }
                    
                    const errorMsg = document.createElement('div');
                    errorMsg.className = 'msg';
                    errorMsg.textContent = result.message;
                    
                    const profileForm = document.getElementById('profileForm');
                    profileForm.parentNode.insertBefore(errorMsg, profileForm);
                    
                    alert('Error: ' + result.message);
                }
            } catch (error) {
                console.error('Error updating profile:', error);
                alert('An error occurred while updating your profile. Please try again.');
            }
        });

        // Handle form submission for password update
        document.getElementById('passwordForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            // Check if validation passes
            if (!passwordValidation.password || !passwordValidation.confirm) {
                alert('Please ensure all password requirements are met and passwords match.');
                return;
            }
            
            const formData = new FormData(e.target);
            formData.append('update_password', '1');
            
            try {
                const response = await fetch('userAcc.php', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // Show success message
                    const msgDiv = document.querySelector('.edit-container .msg');
                    if (msgDiv) {
                        msgDiv.remove();
                    }
                    
                    const successMsg = document.createElement('div');
                    successMsg.className = 'msg success';
                    successMsg.textContent = result.message;
                    
                    const passwordForm = document.getElementById('passwordForm');
                    passwordForm.parentNode.insertBefore(successMsg, passwordForm);
                    
                    // Clear the password form
                    document.getElementById('old_password').value = '';
                    document.getElementById('new_password').value = '';
                    document.getElementById('confirm_password').value = '';
                    
                    // Hide password requirements and confirm section
                    passwordRequirements.style.display = 'none';
                    confirmPasswordSection.style.display = 'none';
                    
                    // Reset validation state
                    passwordValidation.password = false;
                    passwordValidation.confirm = false;
                    updatePasswordButton();
                    
                    // Show success alert
                    alert('Password changed successfully!');
                } else {
                    // Show error message
                    const msgDiv = document.querySelector('.edit-container .msg');
                    if (msgDiv) {
                        msgDiv.remove();
                    }
                    
                    const errorMsg = document.createElement('div');
                    errorMsg.className = 'msg';
                    errorMsg.textContent = result.message;
                    
                    const passwordForm = document.getElementById('passwordForm');
                    passwordForm.parentNode.insertBefore(errorMsg, passwordForm);
                    
                    alert('Error: ' + result.message);
                }
            } catch (error) {
                console.error('Error updating password:', error);
                alert('An error occurred while updating your password. Please try again.');
            }
        });
    </script>
    <script>
// Simple star rating UI
const stars = document.querySelectorAll('.star');
const ratingInput = document.getElementById('rating-input');
stars.forEach(star => {
    star.addEventListener('click', function() {
        const val = this.getAttribute('data-value');
        ratingInput.value = val;
        stars.forEach((s, idx) => {
            s.style.color = idx < val ? '#FFD700' : '#ccc';
        });
    });
});
// Set default color
const defaultVal = ratingInput.value;
stars.forEach((s, idx) => {
    s.style.color = idx < defaultVal ? '#FFD700' : '#ccc';
});
</script>
</body>
</html>