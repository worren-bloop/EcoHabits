<?php
session_start();
$host = "localhost";
$user = "root";
$pass = ""; // your db password
$db = "ecohabitsdb"; // change to your db name

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Logout logic
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: LoginPage.php');
    exit;
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        // User management
        if ($_POST['action'] === 'update') {
            $id = intval($_POST['id']);
            $username = $conn->real_escape_string($_POST['username']);
            $email = $conn->real_escape_string($_POST['email']);
            $password = $_POST['password'] ? password_hash($_POST['password'], PASSWORD_DEFAULT) : null;
            if ($password) {
                $sql = "UPDATE users SET username='$username', email='$email', password='$password' WHERE id=$id";
            } else {
                $sql = "UPDATE users SET username='$username', email='$email' WHERE id=$id";
            }
            $conn->query($sql);
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
            exit;
        } elseif ($_POST['action'] === 'delete') {
            $id = intval($_POST['id']);
            $sql = "DELETE FROM users WHERE id=$id";
            $conn->query($sql);
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
            exit;
        } elseif ($_POST['action'] === 'add') {
            $username = $conn->real_escape_string($_POST['username']);
            $email = $conn->real_escape_string($_POST['email']);
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $sql = "INSERT INTO users (username, email, password) VALUES ('$username', '$email', '$password')";
            $conn->query($sql);
            $id = $conn->insert_id;
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'id' => $id,
                'username' => $username,
                'email' => $email
            ]);
            exit;
        }
    }
}

// AJAX endpoints for username/email uniqueness
if (isset($_GET['check_unique'])) {
    $type = $_GET['type']; // 'user' or 'admin'
    $field = $_GET['field']; // 'username', 'admin_name', or 'email'
    $value = $conn->real_escape_string($_GET['value']);
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if ($type === 'user') {
        $table = 'users';
        $field_db = $field;
    } else {
        $table = 'admin';
        $field_db = $field;
    }
    $where = $id ? "AND id != $id" : "";
    $res = $conn->query("SELECT id FROM $table WHERE $field_db='$value' $where");
    echo $res->num_rows > 0 ? 'used' : 'ok';
    exit;
}
?>
<?php include __DIR__ . '/includes/cookie_consent.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin User Management | EcoHabits</title>
    <link rel="icon" type="image/png" href="assets/images/EcoHabits_logo.png">
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #1F8D49;
            --primary-light: #E8F5E9;
            --primary-dark: #0E5E2B;
            --secondary-color: #2C3E50;
            --danger-color: #E74C3C;
            --danger-light: #FDEDEC;
            --text-color: #333;
            --text-light: #777;
            --border-color: #E0E0E0;
            --bg-color: #F8FAF8;
            --card-bg: #FFFFFF;
            --shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
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


        .admin-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .admin-title {
            font-size: 1.8rem;
            color: var(--secondary-color);
            font-weight: 600;
        }

        .btn {
            padding: 0.6rem 1.2rem;
            border-radius: 6px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
        }

        .btn-danger {
            background-color: var(--danger-color);
            color: white;
        }

        .btn-danger:hover {
            background-color: #C0392B;
        }

        .btn-secondary {
            background-color: var(--secondary-color);
            color: white;
        }

        .btn-secondary:hover {
            background-color: #1A252F;
        }

        .users-table {
            width: 100%;
            border-collapse: collapse;
            background-color: var(--card-bg);
            box-shadow: var(--shadow);
            border-radius: 10px;
            overflow: hidden;
        }

        .users-table th {
            background-color: var(--primary-color);
            color: white;
            padding: 1rem;
            text-align: left;
            font-weight: 500;
        }

        .users-table td {
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
            vertical-align: middle;
        }

        .users-table tr:last-child td {
            border-bottom: none;
        }

        .users-table tr:hover {
            background-color: var(--primary-light);
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background-color: var(--card-bg);
            padding: 2rem;
            border-radius: 10px;
            width: 100%;
            max-width: 500px;
            box-shadow: var(--shadow);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .modal-title {
            font-size: 1.5rem;
            color: var(--secondary-color);
            font-weight: 600;
        }

        .close-modal {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--text-light);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--secondary-color);
        }

        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            font-family: inherit;
            transition: border 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            margin-top: 2rem;
        }

        .editable-field {
            padding: 0.5rem;
            border: 1px solid transparent;
            border-radius: 4px;
            transition: all 0.3s ease;
        }

        .editable-field:hover {
            background-color: var(--primary-light);
        }

        .editable-input {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            font-family: inherit;
        }

        .password-mask {
            letter-spacing: 0.2rem;
        }

        .status-message {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 6px;
            display: none;
        }

        .status-success {
            background-color: #D5F5E3;
            color: #27AE60;
            display: block;
        }

        .status-error {
            background-color: var(--danger-light);
            color: var(--danger-color);
            display: block;
        }

        .btn-add-user, .btn-add-admin {
            margin-bottom: 20px;
        }

        .tab-bar {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 2rem;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(31, 141, 73, 0.07);
            padding: 0.5rem;
            width: fit-content;
        }
        .tab-btn {
            padding: 0.7rem 2.2rem;
            border: none;
            background: none;
            color: #1F8D49;
            font-weight: 600;
            font-size: 1rem;
            border-radius: 6px 6px 0 0;
            cursor: pointer;
            transition: background 0.2s, color 0.2s, box-shadow 0.2s;
            outline: none;
            position: relative;
        }
        .tab-btn.active, .tab-btn:focus {
            background: #1F8D49;
            color: #fff;
            box-shadow: 0 4px 12px rgba(31, 141, 73, 0.10);
            z-index: 2;
        }
        .tab-btn:not(.active):hover {
            background: #e8f5e9;
            color: #145c2c;
        }

        @media (max-width: 768px) {
            .admin-container {
                padding: 0 1rem;
            }

            .users-table {
                display: block;
                overflow-x: auto;
            }

            .action-buttons {
                flex-direction: column;
            }
        }
        .admin-feedback-card, .admin-users-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
            padding: 2em 2em 1.5em 2em;
            max-width: 900px;
            margin: 2em auto 2em auto;
        }
        .admin-feedback-card h2, .admin-users-card h2 {
            margin-top: 0;
            color: #1F8D49;
            font-size: 1.5em;
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        .admin-feedback-table, .admin-users-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            background: #fafbfc;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 1px 4px rgba(31,141,73,0.04);
            margin-top: 1em;
        }
        .admin-feedback-table th, .admin-feedback-table td, .admin-users-table th, .admin-users-table td {
            padding: 0.9em 1em;
            text-align: left;
        }
        .admin-feedback-table th, .admin-users-table th {
            background: #eafaf1;
            color: #1F8D49;
            font-weight: 600;
            border-bottom: 2px solid #d6eadd;
        }
        .admin-feedback-table tr:nth-child(even), .admin-users-table tr:nth-child(even) {
            background: #f5f5f5;
        }
        .admin-feedback-table tr:nth-child(odd), .admin-users-table tr:nth-child(odd) {
            background: #fff;
        }
        .admin-feedback-table td, .admin-users-table td {
            vertical-align: top;
            font-size: 1em;
        }
        .admin-feedback-table td .stars {
            color: #FFD700;
            font-size: 1.2em;
            letter-spacing: 1px;
        }
        .admin-feedback-table button[type="submit"], .admin-users-table button {
            background: #c0392b;
            color: #fff;
            border: none;
            border-radius: 6px;
            padding: 0.4em 1em;
            font-size: 1em;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.2s;
        }
        .admin-feedback-table button[type="submit"]:hover, .admin-users-table button:hover {
            background: #a93226;
        }
        /* Remove .logout-topright absolute positioning */
        /* Add a flex container for the logout button */
        .logout-row {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            width: 100%;
            margin: 1.5em 0 0 0;
            padding-right: 2em;
        }
        @media (max-width: 1000px) {
            .admin-feedback-card, .admin-users-card { max-width: 98vw; padding: 1em; }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/includes/topbar.php'; ?>
    <div class="logout-row">
        <a href="?logout=1" class="btn btn-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
    <div class="admin-container">
        <!-- User Management Section -->
        <div class="admin-users-card">
            <h2>User Management</h2>
            <button id="addUserBtn" class="btn btn-primary btn-add-user"><i class="fas fa-plus"></i> Add User</button>
            <table class="admin-users-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Password</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $result = $conn->query("SELECT id, username, email, password FROM users");
                    while($row = $result->fetch_assoc()) {
                        echo "<tr data-id='{$row['id']}'>
                            <td>{$row['id']}</td>
                            <td class='editable' data-field='username'><span class='editable-field'>{$row['username']}</span><input type='text' class='editable-input' value='{$row['username']}' style='display:none;'></td>
                            <td class='editable' data-field='email'><span class='editable-field'>{$row['email']}</span><input type='email' class='editable-input' value='{$row['email']}' style='display:none;'></td>
                            <td class='editable' data-field='password'><span class='editable-field password-mask'>••••••••</span><input type='password' class='editable-input' placeholder='Leave blank to keep unchanged' style='display:none;'></td>
                            <td><div class='action-buttons'>
                                <button class='edit-btn btn btn-secondary'><i class='fas fa-edit'></i> Edit</button>
                                <button class='save-btn btn btn-primary' style='display:none;'><i class='fas fa-save'></i> Save</button>
                                <button class='delete-btn btn btn-danger'><i class='fas fa-trash'></i> Delete</button>
                            </div></td>
                        </tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
        <!-- FEEDBACK MANAGEMENT SECTION (Admin only) -->
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') { ?>
        <div class="admin-feedback-card">
            <h2>User Feedback</h2>
            <?php
            // Handle feedback deletion
            if (isset($_POST['delete_feedback_id'])) {
                $delete_id = (int)$_POST['delete_feedback_id'];
                $stmt = $conn->prepare("DELETE FROM feedback WHERE id = ?");
                $stmt->bind_param('i', $delete_id);
                $stmt->execute();
            }
            // Fetch all feedback with user info
            $feedbacks = [];
            $sql = "SELECT f.id, f.rating, f.feedback_text, f.created_at, u.username, u.email FROM feedback f JOIN users u ON f.user_id = u.id ORDER BY f.created_at DESC";
            $result = $conn->query($sql);
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $feedbacks[] = $row;
                }
            }
            if (count($feedbacks) === 0) {
                echo '<p>No feedback submitted yet.</p>';
            } else {
                echo '<table class="admin-feedback-table">';
                echo '<tr><th>User</th><th>Email</th><th>Rating</th><th>Feedback</th><th>Date</th><th>Action</th></tr>';
                foreach ($feedbacks as $fb) {
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($fb['username']) . '</td>';
                    echo '<td>' . htmlspecialchars($fb['email']) . '</td>';
                    echo '<td><span class="stars">';
                    for ($i = 1; $i <= 5; $i++) {
                        echo $i <= $fb['rating'] ? '&#9733;' : '&#9734;';
                    }
                    echo ' (' . $fb['rating'] . ')';
                    echo '</span></td>';
                    echo '<td>' . nl2br(htmlspecialchars($fb['feedback_text'])) . '</td>';
                    echo '<td>' . htmlspecialchars($fb['created_at']) . '</td>';
                    echo '<td>';
                    echo '<form method="post" style="display:inline;" onsubmit="return confirm(\'Delete this feedback?\');">';
                    echo '<input type="hidden" name="delete_feedback_id" value="' . $fb['id'] . '">';
                    echo '<button type="submit">Delete</button>';
                    echo '</form>';
                    echo '</td>';
                    echo '</tr>';
                }
                echo '</table>';
            }
            ?>
        </div>
        <?php } ?>
    </div>
    <!-- Add User Modal -->
    <div id="addUserModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Add New User</h3>
                <button class="close-modal">&times;</button>
            </div>
            <form id="addUserForm">
                <div class="form-error" style="color:#e74c3c;margin-bottom:10px;"></div>
                <div class="form-group" style="position:relative;">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" class="form-control" required autocomplete="off">
                    <span class="unique-indicator" id="user-username-indicator" style="position:absolute;right:10px;top:38px;font-size:1.2em;"></span>
                </div>
                <div class="form-group" style="position:relative;">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" class="form-control" required autocomplete="off">
                    <span class="unique-indicator" id="user-email-indicator" style="position:absolute;right:10px;top:38px;font-size:1.2em;"></span>
                </div>
                <div class="form-group" style="position:relative;">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control password-input" required autocomplete="off" style="padding-right:36px;">
                    <img src="assets/images/eye.png" class="toggle-password" data-target="password" style="position:absolute;right:8px;top:38px;width:22px;height:22px;cursor:pointer;" alt="Show/Hide">
                    <ul id="user-password-checklist" style="list-style:none;padding-left:0;margin:8px 0 0 0;font-size:0.95em;">
                        <li data-check="length"><span>✗</span> At least 8 characters</li>
                        <li data-check="upper"><span>✗</span> At least 1 uppercase letter</li>
                        <li data-check="lower"><span>✗</span> At least 1 lowercase letter</li>
                        <li data-check="number"><span>✗</span> At least 1 number</li>
                        <li data-check="special"><span>✗</span> At least 1 special character</li>
                    </ul>
                </div>
                <div class="modal-footer">
                    <button type="button" id="cancelAddUser" class="btn btn-secondary">Cancel</button>
                    <button type="submit" class="btn btn-primary" disabled>Add User</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Show status message
        function showStatus(message, isSuccess) {
            const statusEl = document.getElementById('statusMessage');
            statusEl.textContent = message;
            statusEl.className = isSuccess ? 'status-message status-success' : 'status-message status-error';
            setTimeout(() => {
                statusEl.style.opacity = '0';
                setTimeout(() => {
                    statusEl.style.display = 'none';
                    statusEl.style.opacity = '1';
                }, 300);
            }, 3000);
        }

        // Edit user functionality
        document.querySelectorAll('.edit-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const row = this.closest('tr');
                row.querySelectorAll('.editable').forEach(cell => {
                    const field = cell.getAttribute('data-field');
                    const fieldDisplay = cell.querySelector('.editable-field');
                    const fieldInput = cell.querySelector('.editable-input');
                    
                    fieldDisplay.style.display = 'none';
                    fieldInput.style.display = 'block';
                    
                    if (field === 'password') {
                        fieldInput.value = '';
                    }
                });
                
                this.style.display = 'none';
                row.querySelector('.save-btn').style.display = 'inline-flex';
            });
        });

        // Save user functionality - Updated for instant visual updates
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('save-btn')) {
                const row = e.target.closest('tr');
                const id = row.getAttribute('data-id');
                const username = row.querySelector('[data-field="username"] .editable-input').value;
                const email = row.querySelector('[data-field="email"] .editable-input').value;
                const password = row.querySelector('[data-field="password"] .editable-input').value;
                
                // Update display immediately (optimistic update)
                row.querySelector('[data-field="username"] .editable-field').textContent = username;
                row.querySelector('[data-field="email"] .editable-field').textContent = email;
                if (password) row.querySelector('[data-field="password"] .editable-field').textContent = '••••••••';
                
                // Hide inputs and show display values immediately
                row.querySelectorAll('.editable').forEach(cell => {
                    cell.querySelector('.editable-field').style.display = '';
                    cell.querySelector('.editable-input').style.display = 'none';
                });
                // Remove any eye icons (toggle-password) in this row
                row.querySelectorAll('.toggle-password').forEach(el => el.remove());
                // Switch buttons immediately
                e.target.style.display = 'none';
                row.querySelector('.edit-btn').style.display = 'inline-flex';
                
                // Remove validation UI after save
                const errorDiv = row.querySelector('.inline-form-error');
                if (errorDiv) errorDiv.remove();
                row.querySelectorAll('.unique-indicator').forEach(el => el.remove());
                const checklist = row.querySelector('.inline-password-checklist');
                if (checklist) checklist.remove();
                
                // Then send the request
                fetch('adminAcc.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `action=update&id=${id}&username=${encodeURIComponent(username)}&email=${encodeURIComponent(email)}&password=${encodeURIComponent(password)}`
                })
                .then(res => res.json())
                .then(res => {
                    if (res.success) {
                        showStatus('User updated successfully!', true);
                    } else {
                        showStatus('Error updating user', false);
                        // If error, revert to edit mode
                        row.querySelector('.edit-btn').click();
                    }
                })
                .catch(err => {
                    showStatus('Error updating user', false);
                    console.error(err);
                    // If error, revert to edit mode
                    row.querySelector('.edit-btn').click();
                });
            }
        });

        // Delete user functionality - Updated to match feedback delete style
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('delete-btn') || e.target.closest('.delete-btn')) {
                const button = e.target.classList.contains('delete-btn') ? e.target : e.target.closest('.delete-btn');
                const row = button.closest('tr');
                const id = row.getAttribute('data-id');
                
                // Show confirmation dialog like feedback delete
                if (!confirm('Delete this user?')) return;
                
                // Remove row immediately (optimistic delete)
                row.remove();
                
                // Then send the request
                fetch('adminAcc.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `action=delete&id=${id}`
                })
                .then(res => res.json())
                .then(res => {
                    if (res.success) {
                        showStatus('User deleted successfully!', true);
                    } else {
                        showStatus('Error deleting user', false);
                        // If error, we could reload the page or re-add the row
                        // For now, just show error message
                    }
                })
                .catch(err => {
                    showStatus('Error deleting user', false);
                    console.error(err);
                });
            }
        });

        // Add user modal functionality
        const addUserModal = document.getElementById('addUserModal');
        const addUserBtn = document.getElementById('addUserBtn');
        const closeModal = document.querySelector('.close-modal');
        const cancelAddUser = document.getElementById('cancelAddUser');
        
        addUserBtn.addEventListener('click', () => {
            addUserModal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        });
        
        closeModal.addEventListener('click', () => {
            addUserModal.style.display = 'none';
            document.body.style.overflow = '';
        });
        
        cancelAddUser.addEventListener('click', () => {
            addUserModal.style.display = 'none';
            document.body.style.overflow = '';
        });
        
        // Close modal when clicking outside
        window.addEventListener('click', (e) => {
            if (e.target === addUserModal) {
                addUserModal.style.display = 'none';
                document.body.style.overflow = '';
            }
        });

        // Add user form submission - Updated for instant UI updates
        document.getElementById('addUserForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const form = e.target;
            const username = form.username.value;
            const email = form.email.value;
            const password = form.password.value;
            
            // Close modal instantly
            addUserModal.style.display = 'none';
            document.body.style.overflow = '';
            
            // Create new row instantly with temporary ID
            const tbody = document.querySelector('.admin-users-table tbody');
            const tr = document.createElement('tr');
            tr.setAttribute('data-id', 'temp-' + Date.now()); // Temporary unique ID
            tr.innerHTML = `
                <td>...</td>
                <td class='editable' data-field='username'><span class='editable-field'>${username}</span><input type='text' class='editable-input' value='${username}' style='display:none;'></td>
                <td class='editable' data-field='email'><span class='editable-field'>${email}</span><input type='email' class='editable-input' value='${email}' style='display:none;'></td>
                <td class='editable' data-field='password'><span class='editable-field password-mask'>••••••••</span><input type='password' class='editable-input' placeholder='Leave blank to keep unchanged' style='display:none;'></td>
                <td><div class='action-buttons'>
                    <button class='edit-btn btn btn-secondary'><i class='fas fa-edit'></i> Edit</button>
                    <button class='save-btn btn btn-primary' style='display:none;'><i class='fas fa-save'></i> Save</button>
                    <button class='delete-btn btn btn-danger'><i class='fas fa-trash'></i> Delete</button>
                </div></td>
            `;
            tbody.appendChild(tr);
            bindRowEvents(tr);
            
            // Then send the actual request
            fetch('adminAcc.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `action=add&username=${encodeURIComponent(username)}&email=${encodeURIComponent(email)}&password=${encodeURIComponent(password)}`
            })
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    showStatus('User added successfully!', true);
                    // Update the temporary row with actual data
                    tr.setAttribute('data-id', res.id);
                    tr.querySelector('td:first-child').textContent = res.id;
                } else {
                    showStatus('Error adding user', false);
                    // Remove the temporary row if there was an error
                    tr.remove();
                }
            })
            .catch(err => {
                showStatus('Error adding user', false);
                console.error(err);
                // Remove the temporary row if there was an error
                tr.remove();
            });
            
            // Reset form
            form.reset();
            // Reset password checklist
            const checklist = document.getElementById('user-password-checklist');
            if (checklist) {
                checklist.querySelectorAll('li').forEach(li => {
                    li.querySelector('span').textContent = '✗';
                    li.querySelector('span').style.color = '#aaa';
                    li.style.color = '#aaa';
                });
            }
            // Reset indicators
            document.getElementById('user-username-indicator').textContent = '';
            document.getElementById('user-email-indicator').textContent = '';
            // Disable submit button
            document.querySelector('#addUserForm button[type="submit"]').disabled = true;
        });

        // Helper: Validate email
        function isValidEmail(email) {
            // Must contain @ and a . after the @
            const at = email.indexOf('@');
            const dot = email.lastIndexOf('.');
            return at > 0 && dot > at + 1 && dot < email.length - 1;
        }
        // Helper: AJAX uniqueness check
        function checkUnique(type, field, value, id, cb) {
            fetch(`adminAcc.php?check_unique=1&type=${type}&field=${field}&value=${encodeURIComponent(value)}${id ? `&id=${id}` : ''}`)
                .then(res => res.text())
                .then(res => cb(res === 'ok'));
        }
        // Password checklist logic for user add/edit
        function updateUserPasswordChecklist(password) {
            const checklist = document.getElementById('user-password-checklist');
            if (!checklist) return;
            const checks = {
                length: password.length >= 8,
                upper: /[A-Z]/.test(password),
                lower: /[a-z]/.test(password),
                number: /[0-9]/.test(password),
                special: /[^A-Za-z0-9]/.test(password)
            };
            checklist.querySelectorAll('li').forEach(li => {
                const key = li.getAttribute('data-check');
                if (checks[key]) {
                    li.querySelector('span').textContent = '✓';
                    li.querySelector('span').style.color = '#27ae60';
                    li.style.color = '#27ae60';
                } else {
                    li.querySelector('span').textContent = '✗';
                    li.querySelector('span').style.color = '#aaa';
                    li.style.color = '#aaa';
                }
            });
            // Return true if all requirements met
            return Object.values(checks).every(Boolean);
        }
        // User Add/Edit Validation
        function validateUserForm(form, isEdit, id) {
            const username = form.username.value.trim();
            const email = form.email.value.trim();
            const password = form.password.value;
            const errorDiv = form.querySelector('.form-error');
            let valid = true;
            errorDiv.textContent = '';
            if (!username) { errorDiv.textContent = 'Username required.'; valid = false; }
            else if (!email) { errorDiv.textContent = 'Email required.'; valid = false; }
            else if (!isValidEmail(email)) { errorDiv.textContent = 'Invalid email.'; valid = false; }
            // Password requirements for user
            if (!isEdit && !checkUserPassword(password)) { errorDiv.textContent = 'Password does not meet requirements.'; valid = false; }
            else if (isEdit && password && !checkUserPassword(password)) { errorDiv.textContent = 'Password does not meet requirements.'; valid = false; }
            if (!valid) return false;
            // Uniqueness checks
            return Promise.all([
                new Promise(res => checkUnique('user', 'username', username, isEdit ? id : 0, ok => res(ok))),
                new Promise(res => checkUnique('user', 'email', email, isEdit ? id : 0, ok => res(ok)))
            ]).then(([userOk, emailOk]) => {
                if (!userOk) { errorDiv.textContent = 'Username already used.'; return false; }
                if (!emailOk) { errorDiv.textContent = 'Email already used.'; return false; }
                errorDiv.textContent = '';
                return true;
            });
        }
        function checkUserPassword(password) {
            return password.length >= 8 &&
                /[A-Z]/.test(password) &&
                /[a-z]/.test(password) &&
                /[0-9]/.test(password) &&
                /[^A-Za-z0-9]/.test(password);
        }
        // Enable Save/Add button only if valid
        // Add User Modal
        const addUserForm = document.getElementById('addUserForm');
        if (addUserForm) {
            const addUserBtn = addUserForm.querySelector('button[type="submit"]');
            const passwordInput = addUserForm.querySelector('input[name="password"]');
            passwordInput.addEventListener('input', () => {
                updateUserPasswordChecklist(passwordInput.value);
            });
            addUserForm.addEventListener('input', () => {
                validateUserForm(addUserForm, false, 0).then(valid => {
                    // Only enable if all password requirements met
                    const passOk = updateUserPasswordChecklist(passwordInput.value);
                    addUserBtn.disabled = !(valid && passOk);
                });
            });
            addUserForm.addEventListener('submit', function(e) {
                e.preventDefault();
                validateUserForm(addUserForm, false, 0).then(valid => {
                    const passOk = updateUserPasswordChecklist(passwordInput.value);
                    if (!valid || !passOk) return;
                    const form = e.target;
                    const username = form.username.value;
                    const email = form.email.value;
                    const password = form.password.value;
                    fetch('adminAcc.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: `action=add&username=${encodeURIComponent(username)}&email=${encodeURIComponent(email)}&password=${encodeURIComponent(password)}`
                    })
                    .then(res => res.json())
                    .then(res => {
                        if (res.success) {
                            showStatus('User added successfully!', true);
                            addUserModal.style.display = 'none';
                            document.body.style.overflow = '';
                            form.reset();
                            // Add new row to table
                            const tbody = document.querySelector('.admin-users-table tbody');
                            const tr = document.createElement('tr');
                            tr.setAttribute('data-id', res.id);
                            tr.innerHTML = `
                                <td>${res.id}</td>
                                <td class='editable' data-field='username'><span class='editable-field'>${res.username}</span><input type='text' class='editable-input' value='${res.username}' style='display:none;'></td>
                                <td class='editable' data-field='email'><span class='editable-field'>${res.email}</span><input type='email' class='editable-input' value='${res.email}' style='display:none;'></td>
                                <td class='editable' data-field='password'><span class='editable-field password-mask'>••••••••</span><input type='password' class='editable-input' placeholder='Leave blank to keep unchanged' style='display:none;'></td>
                                <td><div class='action-buttons'>
                                    <button class='edit-btn btn btn-secondary'><i class='fas fa-edit'></i> Edit</button>
                                    <button class='save-btn btn btn-primary' style='display:none;'><i class='fas fa-save'></i> Save</button>
                                    <button class='delete-btn btn btn-danger'><i class='fas fa-trash'></i> Delete</button>
                                </div></td>
                            `;
                            tbody.appendChild(tr);
                            // Re-bind events for new row
                            bindRowEvents(tr);
                        } else {
                            showStatus('Error adding user', false);
                        }
                    })
                    .catch(err => {
                        showStatus('Error adding user', false);
                        console.error(err);
                    });
                });
            });
        }
        // Edit User (Save button)
        document.querySelectorAll('.save-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const row = btn.closest('tr');
                const id = row.getAttribute('data-id');
                const username = row.querySelector('[data-field="username"] .editable-input').value;
                const email = row.querySelector('[data-field="email"] .editable-input').value;
                const password = row.querySelector('[data-field="password"] .editable-input').value;
                const form = document.createElement('form');
                form.innerHTML = `<input name='username' value='${username}'><input name='email' value='${email}'><input name='password' value='${password}'>`;
                validateUserForm(form, true, id).then(valid => {
                    if (!valid) return;
                    fetch('adminAcc.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: `action=update&id=${id}&username=${encodeURIComponent(username)}&email=${encodeURIComponent(email)}&password=${encodeURIComponent(password)}`
                    })
                    .then(res => res.json())
                    .then(res => {
                        if (res.success) {
                            showStatus('User updated successfully!', true);
                            row.querySelector('[data-field="username"] .editable-field').textContent = username;
                            row.querySelector('[data-field="email"] .editable-field').textContent = email;
                            if (password) row.querySelector('[data-field="password"] .editable-field').textContent = '••••••••';
                            row.querySelectorAll('.editable').forEach(cell => {
                                cell.querySelector('.editable-field').style.display = '';
                                cell.querySelector('.editable-input').style.display = 'none';
                            });
                            row.querySelector('.save-btn').style.display = 'none';
                            row.querySelector('.edit-btn').style.display = 'inline-flex';
                        } else {
                            showStatus('Error updating user', false);
                        }
                    })
                    .catch(err => {
                        showStatus('Error updating user', false);
                        console.error(err);
                    });
                });
            });
        });
        // Uniqueness indicator logic for user add
        const userUsernameInput = document.getElementById('username');
        const userEmailInput = document.getElementById('email');
        const userUsernameIndicator = document.getElementById('user-username-indicator');
        const userEmailIndicator = document.getElementById('user-email-indicator');
        if (userUsernameInput && userUsernameIndicator) {
            userUsernameInput.addEventListener('input', function() {
                checkUnique('user', 'username', userUsernameInput.value, 0, ok => {
                    if (!userUsernameInput.value) { userUsernameIndicator.textContent = ''; return; }
                    if (ok) { userUsernameIndicator.textContent = '✓'; userUsernameIndicator.style.color = '#27ae60'; }
                    else { userUsernameIndicator.textContent = '✗'; userUsernameIndicator.style.color = '#e74c3c'; }
                });
            });
        }
        if (userEmailInput && userEmailIndicator) {
            userEmailInput.addEventListener('input', function() {
                checkUnique('user', 'email', userEmailInput.value, 0, ok => {
                    if (!userEmailInput.value) { userEmailIndicator.textContent = ''; return; }
                    if (ok) { userEmailIndicator.textContent = '✓'; userEmailIndicator.style.color = '#27ae60'; }
                    else { userEmailIndicator.textContent = '✗'; userEmailIndicator.style.color = '#e74c3c'; }
                });
            });
        }
        // Password visibility toggle for all password fields
        function setupPasswordToggles() {
            document.querySelectorAll('.toggle-password').forEach(eye => {
                eye.addEventListener('click', function() {
                    const input = document.getElementById(this.getAttribute('data-target'));
                    if (!input) return;
                    if (input.type === 'password') {
                        input.type = 'text';
                        this.src = 'assets/images/eye-slash.png';
                    } else {
                        input.type = 'password';
                        this.src = 'assets/images/eye.png';
                    }
                });
            });
        }
        setupPasswordToggles();
        // Inline edit validation and UI for users
        function setupInlineEditValidation() {
            // For user rows
            document.querySelectorAll('tr[data-id] .edit-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const row = btn.closest('tr');
                    const id = row.getAttribute('data-id');
                    // Add error div if not present
                    let errorDiv = row.querySelector('.inline-form-error');
                    if (!errorDiv) {
                        errorDiv = document.createElement('div');
                        errorDiv.className = 'inline-form-error';
                        errorDiv.style = 'color:#e74c3c;margin-bottom:6px;font-size:0.95em;';
                        row.querySelector('td').prepend(errorDiv);
                    }
                    // Add uniqueness indicators if not present
                    ['username','email'].forEach(field => {
                        const cell = row.querySelector(`[data-field="${field}"]`);
                        if (cell && !cell.querySelector('.unique-indicator')) {
                            const indicator = document.createElement('span');
                            indicator.className = 'unique-indicator';
                            indicator.style = 'margin-left:8px;font-size:1.1em;';
                            cell.appendChild(indicator);
                        }
                    });
                    // Add password checklist for users
                    if (row.querySelector('[data-field="password"]')) {
                        let checklist = row.querySelector('.inline-password-checklist');
                        if (!checklist) {
                            checklist = document.createElement('ul');
                            checklist.className = 'inline-password-checklist';
                            checklist.style = 'list-style:none;padding-left:0;margin:8px 0 0 0;font-size:0.95em;';
                            checklist.innerHTML = `
                                <li data-check="length"><span>✗</span> At least 8 characters</li>
                                <li data-check="upper"><span>✗</span> At least 1 uppercase letter</li>
                                <li data-check="lower"><span>✗</span> At least 1 lowercase letter</li>
                                <li data-check="number"><span>✗</span> At least 1 number</li>
                                <li data-check="special"><span>✗</span> At least 1 special character</li>
                            `;
                            row.querySelector('[data-field="password"]').appendChild(checklist);
                        }
                    }
                    // Add password eye toggle if not present
                    row.querySelectorAll('.editable-input[type="password"]').forEach(input => {
                        if (!input.nextSibling || !input.nextSibling.classList || !input.nextSibling.classList.contains('toggle-password')) {
                            const eye = document.createElement('img');
                            eye.src = 'assets/images/eye.png';
                            eye.className = 'toggle-password';
                            eye.style = 'width:22px;height:22px;cursor:pointer;position:relative;left:6px;top:6px;';
                            eye.setAttribute('data-target', '');
                            input.parentNode.insertBefore(eye, input.nextSibling);
                            eye.addEventListener('click', function() {
                                if (input.type === 'password') {
                                    input.type = 'text';
                                    eye.src = 'assets/images/eye-slash.png';
                                } else {
                                    input.type = 'password';
                                    eye.src = 'assets/images/eye.png';
                                }
                            });
                        }
                    });
                    // Live validation
                    const usernameInput = row.querySelector('[data-field="username"] .editable-input');
                    const emailInput = row.querySelector('[data-field="email"] .editable-input');
                    const passwordInput = row.querySelector('[data-field="password"] .editable-input');
                    const saveBtn = row.querySelector('.save-btn');
                    const origUsername = row.querySelector('[data-field="username"] .editable-field').textContent.trim();
                    const origEmail = row.querySelector('[data-field="email"] .editable-field').textContent.trim();
                    // Helper for user
                    function validateInlineUser() {
                        let valid = true;
                        errorDiv.textContent = '';
                        // Username
                        if (!usernameInput.value.trim()) { errorDiv.textContent = 'Username required.'; valid = false; }
                        // Email
                        else if (!emailInput.value.trim()) { errorDiv.textContent = 'Email required.'; valid = false; }
                        else if (!isValidEmail(emailInput.value.trim())) { errorDiv.textContent = 'Invalid email.'; valid = false; }
                        // Password
                        if (passwordInput && passwordInput.value && !checkUserPassword(passwordInput.value)) { errorDiv.textContent = 'Password does not meet requirements.'; valid = false; }
                        // Uniqueness
                        checkUnique('user', 'username', usernameInput.value.trim(), id, ok => {
                            const indicator = row.querySelector('[data-field="username"] .unique-indicator');
                            if (!usernameInput.value.trim()) { indicator.textContent = ''; }
                            else if (ok) { indicator.textContent = '✓'; indicator.style.color = '#27ae60'; }
                            else { indicator.textContent = '✗'; indicator.style.color = '#e74c3c'; valid = false; }
                            checkUnique('user', 'email', emailInput.value.trim(), id, ok2 => {
                                const indicator2 = row.querySelector('[data-field="email"] .unique-indicator');
                                if (!emailInput.value.trim()) { indicator2.textContent = ''; }
                                else if (ok2) { indicator2.textContent = '✓'; indicator2.style.color = '#27ae60'; }
                                else { indicator2.textContent = '✗'; indicator2.style.color = '#e74c3c'; valid = false; }
                                // Password checklist
                                if (passwordInput) updateInlinePasswordChecklist(passwordInput.value);
                                saveBtn.disabled = !valid;
                            });
                        });
                    }
                    // Attach events
                    if (usernameInput && emailInput && saveBtn) {
                        usernameInput.addEventListener('input', validateInlineUser);
                        emailInput.addEventListener('input', validateInlineUser);
                        if (passwordInput) passwordInput.addEventListener('input', validateInlineUser);
                        // Initial validation
                        validateInlineUser();
                    }
                    // Save button handler: show error if backend fails
                    if (saveBtn) {
                        saveBtn.addEventListener('click', function(e) {
                            e.preventDefault();
                            errorDiv.textContent = '';
                            // For user
                            if (usernameInput && emailInput) {
                                const id = row.getAttribute('data-id');
                                const username = usernameInput.value;
                                const email = emailInput.value;
                                const password = passwordInput ? passwordInput.value : '';
                                fetch('adminAcc.php', {
                                    method: 'POST',
                                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                                    body: `action=update&id=${id}&username=${encodeURIComponent(username)}&email=${encodeURIComponent(email)}&password=${encodeURIComponent(password)}`
                                })
                                .then(res => res.json())
                                .then(res => {
                                    if (res.success) {
                                        showStatus('User updated successfully!', true);
                                        row.querySelector('[data-field="username"] .editable-field').textContent = username;
                                        row.querySelector('[data-field="email"] .editable-field').textContent = email;
                                        if (password) row.querySelector('[data-field="password"] .editable-field').textContent = '••••••••';
                                        row.querySelectorAll('.editable').forEach(cell => {
                                            cell.querySelector('.editable-field').style.display = '';
                                            cell.querySelector('.editable-input').style.display = 'none';
                                        });
                                        row.querySelector('.save-btn').style.display = 'none';
                                        row.querySelector('.edit-btn').style.display = 'inline-flex';
                                    } else {
                                        errorDiv.textContent = res.message || 'Error updating user';
                                    }
                                })
                                .catch(err => {
                                    showStatus('Error updating user', false);
                                    console.error(err);
                                });
                            }
                        });
                    }
                });
            });
        }
        setupInlineEditValidation();

        // Helper to bind edit/delete events to a row - Updated
        function bindRowEvents(row) {
            // Edit button
            const editBtn = row.querySelector('.edit-btn');
            if (editBtn) {
                editBtn.addEventListener('click', function() {
                    row.querySelectorAll('.editable').forEach(cell => {
                        const field = cell.getAttribute('data-field');
                        const fieldDisplay = cell.querySelector('.editable-field');
                        const fieldInput = cell.querySelector('.editable-input');
                        fieldDisplay.style.display = 'none';
                        fieldInput.style.display = 'block';
                        if (field === 'password') fieldInput.value = '';
                    });
                    this.style.display = 'none';
                    row.querySelector('.save-btn').style.display = 'inline-flex';

                    // --- Live validation logic for inline editing ---
                    // Add error div if not present
                    let errorDiv = row.querySelector('.inline-form-error');
                    if (!errorDiv) {
                        errorDiv = document.createElement('div');
                        errorDiv.className = 'inline-form-error';
                        errorDiv.style = 'color:#e74c3c;margin-bottom:6px;font-size:0.95em;';
                        row.querySelector('td').prepend(errorDiv);
                    }
                    // Add uniqueness indicators if not present
                    ['username','email'].forEach(field => {
                        const cell = row.querySelector(`[data-field="${field}"]`);
                        if (cell && !cell.querySelector('.unique-indicator')) {
                            const indicator = document.createElement('span');
                            indicator.className = 'unique-indicator';
                            indicator.style = 'margin-left:8px;font-size:1.1em;';
                            cell.appendChild(indicator);
                        }
                    });
                    // Add password checklist for users
                    if (row.querySelector('[data-field="password"]')) {
                        let checklist = row.querySelector('.inline-password-checklist');
                        if (!checklist) {
                            checklist = document.createElement('ul');
                            checklist.className = 'inline-password-checklist';
                            checklist.style = 'list-style:none;padding-left:0;margin:8px 0 0 0;font-size:0.95em;';
                            checklist.innerHTML = `
                                <li data-check="length"><span>✗</span> At least 8 characters</li>
                                <li data-check="upper"><span>✗</span> At least 1 uppercase letter</li>
                                <li data-check="lower"><span>✗</span> At least 1 lowercase letter</li>
                                <li data-check="number"><span>✗</span> At least 1 number</li>
                                <li data-check="special"><span>✗</span> At least 1 special character</li>
                            `;
                            row.querySelector('[data-field="password"]').appendChild(checklist);
                        }
                    }
                    // Live validation logic
                    const usernameInput = row.querySelector('[data-field="username"] .editable-input');
                    const emailInput = row.querySelector('[data-field="email"] .editable-input');
                    const passwordInput = row.querySelector('[data-field="password"] .editable-input');
                    const saveBtn = row.querySelector('.save-btn');
                    const origUsername = row.querySelector('[data-field="username"] .editable-field').textContent.trim();
                    const origEmail = row.querySelector('[data-field="email"] .editable-field').textContent.trim();
                    function isValidEmail(email) {
                        const at = email.indexOf('@');
                        const dot = email.lastIndexOf('.');
                        return at > 0 && dot > at + 1 && dot < email.length - 1;
                    }
                    function checkUserPassword(password) {
                        return password.length >= 8 &&
                            /[A-Z]/.test(password) &&
                            /[a-z]/.test(password) &&
                            /[0-9]/.test(password) &&
                            /[^A-Za-z0-9]/.test(password);
                    }
                    function checkUnique(type, field, value, id, cb) {
                        fetch(`adminAcc.php?check_unique=1&type=${type}&field=${field}&value=${encodeURIComponent(value)}${id ? `&id=${id}` : ''}`)
                            .then(res => res.text())
                            .then(res => cb(res === 'ok'));
                    }
                    function updateInlinePasswordChecklist(password) {
                        const checklist = row.querySelector('.inline-password-checklist');
                        if (!checklist) return;
                        const checks = {
                            length: password.length >= 8,
                            upper: /[A-Z]/.test(password),
                            lower: /[a-z]/.test(password),
                            number: /[0-9]/.test(password),
                            special: /[^A-Za-z0-9]/.test(password)
                        };
                        checklist.querySelectorAll('li').forEach(li => {
                            const key = li.getAttribute('data-check');
                            if (checks[key]) {
                                li.querySelector('span').textContent = '✓';
                                li.querySelector('span').style.color = '#27ae60';
                                li.style.color = '#27ae60';
                            } else {
                                li.querySelector('span').textContent = '✗';
                                li.querySelector('span').style.color = '#aaa';
                                li.style.color = '#aaa';
                            }
                        });
                        return Object.values(checks).every(Boolean);
                    }
                    function validateInlineUser() {
                        let valid = true;
                        errorDiv.textContent = '';
                        // Username
                        if (!usernameInput.value.trim()) { errorDiv.textContent = 'Username required.'; valid = false; }
                        // Email
                        else if (!emailInput.value.trim()) { errorDiv.textContent = 'Email required.'; valid = false; }
                        else if (!isValidEmail(emailInput.value.trim())) { errorDiv.textContent = 'Invalid email.'; valid = false; }
                        // Password
                        if (passwordInput && passwordInput.value && !checkUserPassword(passwordInput.value)) { errorDiv.textContent = 'Password does not meet requirements.'; valid = false; }
                        // Uniqueness
                        checkUnique('user', 'username', usernameInput.value.trim(), row.getAttribute('data-id'), ok => {
                            const indicator = row.querySelector('[data-field="username"] .unique-indicator');
                            if (!usernameInput.value.trim()) { indicator.textContent = ''; }
                            else if (ok) { indicator.textContent = '✓'; indicator.style.color = '#27ae60'; }
                            else { indicator.textContent = '✗'; indicator.style.color = '#e74c3c'; valid = false; }
                            checkUnique('user', 'email', emailInput.value.trim(), row.getAttribute('data-id'), ok2 => {
                                const indicator2 = row.querySelector('[data-field="email"] .unique-indicator');
                                if (!emailInput.value.trim()) { indicator2.textContent = ''; }
                                else if (ok2) { indicator2.textContent = '✓'; indicator2.style.color = '#27ae60'; }
                                else { indicator2.textContent = '✗'; indicator2.style.color = '#e74c3c'; valid = false; }
                                // Password checklist
                                if (passwordInput) updateInlinePasswordChecklist(passwordInput.value);
                                saveBtn.disabled = !valid;
                            });
                        });
                    }
                    if (usernameInput && emailInput && saveBtn) {
                        usernameInput.addEventListener('input', validateInlineUser);
                        emailInput.addEventListener('input', validateInlineUser);
                        if (passwordInput) passwordInput.addEventListener('input', validateInlineUser);
                        // Initial validation
                        validateInlineUser();
                    }
                });
            }
            // Note: Save and Delete buttons are now handled by event delegation above
        }
        // Bind events to all existing rows on page load
        document.querySelectorAll('.admin-users-table tbody tr').forEach(bindRowEvents);
    </script>
</body>
</html>