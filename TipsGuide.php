<?php
session_start();
$host = "localhost";
$user = "root";
$pass = "";
$db = "ecohabitsdb";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

// Handle video upload
if ($isAdmin && isset($_POST['upload_video'])) {
    $title = $conn->real_escape_string($_POST['title']);
    $description = $conn->real_escape_string($_POST['description']);
    $uploaded_at = date('Y-m-d H:i:s');
    if (isset($_FILES['video_file']) && $_FILES['video_file']['error'] == 0) {
        $filename = uniqid() . '_' . basename($_FILES['video_file']['name']);
        $target = __DIR__ . '/uploads/' . $filename;
        if (move_uploaded_file($_FILES['video_file']['tmp_name'], $target)) {
            $conn->query("INSERT INTO video (title, description, filename, uploaded_at) VALUES ('$title', '$description', '$filename', '$uploaded_at')");
        }
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Handle video delete
if ($isAdmin && isset($_POST['delete_video'])) {
    $id = intval($_POST['delete_video']);
    $res = $conn->query("SELECT filename FROM video WHERE id=$id");
    if ($row = $res->fetch_assoc()) {
        @unlink(__DIR__ . '/uploads/' . $row['filename']);
    }
    $conn->query("DELETE FROM video WHERE id=$id");
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Handle tip add
if ($isAdmin && isset($_POST['add_tip'])) {
    $title = $conn->real_escape_string($_POST['tip_title']);
    $content = $conn->real_escape_string($_POST['tip_content']);
    $created_at = date('Y-m-d H:i:s');
    $uploaded_at = $created_at;
    $conn->query("INSERT INTO tips (title, content, created_at, uploaded_at) VALUES ('$title', '$content', '$created_at', '$uploaded_at')");
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Handle tip delete
if ($isAdmin && isset($_POST['delete_tip'])) {
    $id = intval($_POST['delete_tip']);
    $conn->query("DELETE FROM tips WHERE id=$id");
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Handle tip edit
if ($isAdmin && isset($_POST['edit_tip'])) {
    $id = intval($_POST['tip_id']);
    $title = $conn->real_escape_string($_POST['tip_title']);
    $content = $conn->real_escape_string($_POST['tip_content']);
    $uploaded_at = date('Y-m-d H:i:s');
    $conn->query("UPDATE tips SET title='$title', content='$content', uploaded_at='$uploaded_at' WHERE id=$id");
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Fetch videos and tips
$videos = $conn->query("SELECT * FROM video ORDER BY uploaded_at DESC");
$tips = $conn->query("SELECT * FROM tips ORDER BY created_at DESC");

// For tip editing
$editTip = null;
if ($isAdmin && isset($_POST['edit_tip_id'])) {
    $id = intval($_POST['edit_tip_id']);
    $res = $conn->query("SELECT * FROM tips WHERE id=$id");
    $editTip = $res->fetch_assoc();
}
?>
<?php include __DIR__ . '/includes/cookie_consent.php'; ?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tips & Guide | EcoHabits</title>
    <link rel="icon" type="image/png" href="assets/images/EcoHabits_logo.png">
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
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

        .container {
            max-width: 900px;
            margin: 2rem auto;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            padding: 2rem;
        }

        h2 {
            color: #1F8D49;
            margin-top: 0;
        }

        .video-block,.tip-block {
            margin-bottom: 2rem;
            border-bottom: 1px solid #e0e0e0;
            padding-bottom: 1rem;
        }

        .video-block:last-child,
        .tip-block:last-child {
            border-bottom: none;
        }

        video {
            width: 100%;
            max-width: 500px;
            display: block;
            margin-bottom: 0.5rem;
        }

        .admin-form {
            background: #e8f5e9;
            padding: 2rem 1.5rem 1.5rem 1.5rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            box-shadow: 0 2px 8px rgba(31, 141, 73, 0.07);
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
        }
        .admin-form h3 {
            margin-top: 0;
            margin-bottom: 1.2rem;
            color: #222;
            font-size: 1.4rem;
        }
        .admin-form input[type="text"],
        .admin-form textarea {
            width: 100%;
            margin-bottom: 1rem;
            padding: 0.9rem 1rem;
            border-radius: 6px;
            border: 1px solid #cfd8dc;
            font-size: 1.08rem;
            background: #f7fcf8;
            box-sizing: border-box;
            transition: border 0.2s, box-shadow 0.2s;
        }
        .admin-form textarea {
            min-height: 80px;
            resize: vertical;
        }
        .admin-form input[type="file"] {
            width: 100%;
            margin-bottom: 1.2rem;
            padding: 0.7rem 0.5rem;
            border-radius: 6px;
            border: 1px solid #cfd8dc;
            background: #f7fcf8;
            font-size: 1.05rem;
            box-sizing: border-box;
        }
        .admin-form button {
            background: #219653;
            color: #fff;
            border: none;
            padding: 0.8rem 2rem;
            border-radius: 7px;
            font-size: 1.08rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s, box-shadow 0.2s;
            margin-top: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .admin-form button:hover {
            background: #176c3a;
        }
        .admin-actions a,
        .admin-actions button {
            color: #fff;
            background: #e74c3c;
            border: none;
            padding: 0.3rem 0.8rem;
            border-radius: 4px;
            margin-right: 0.5rem;
            text-decoration: none;
            cursor: pointer;
        }

        .admin-actions a.edit {
            background: #1F8D49;
        }

        .admin-actions a.edit:hover,
        .admin-actions button.edit:hover {
            background: #145c2c;
        }

        .admin-actions a:hover,
        .admin-actions button:hover {
            background: #c0392b;
        }

        .tip-block h4 {
            margin: 0 0 0.5rem 0;
        }

        .tip-block p {
            margin: 0;
        }
        
        @media (max-width: 600px) {
            .admin-form {
                padding: 1rem 0.5rem;
            }
            .container {
                padding: 1rem;
            }
        }
    </style>
</head>

<body>
    <?php include __DIR__ . '/includes/topbar.php'; ?>
    <div class="container">
        <h2>Videos</h2>
        <?php if ($isAdmin): ?>
            <form class="admin-form" method="POST" enctype="multipart/form-data">
                <h3>Upload New Video</h3>
                <input type="text" name="title" placeholder="Video Title" required>
                <textarea name="description" placeholder="Description" required></textarea>
                <input type="file" name="video_file" accept="video/*" required>
                <button type="submit" name="upload_video"><i class="fas fa-upload"></i> Upload Video</button>
            </form>
        <?php endif; ?>

        <?php while ($video = $videos->fetch_assoc()): ?>
            <div class="video-block">
                <h3><?= htmlspecialchars($video['title']) ?></h3>
                <video src="uploads/<?= rawurlencode($video['filename']) ?>" controls></video>
                <p><?= nl2br(htmlspecialchars($video['description'])) ?></p>
                <small>Uploaded: <?= htmlspecialchars($video['uploaded_at']) ?></small>
                <?php if ($isAdmin): ?>
                    <div class="admin-actions">
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this video?');">
                            <input type="hidden" name="delete_video" value="<?= $video['id'] ?>">
                            <button type="submit"><i class="fas fa-trash"></i> Delete</button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        <?php endwhile; ?>

        <h2>Tips & Guide</h2>
        <?php if ($isAdmin): ?>
            <?php if ($editTip): ?>
                <form class="admin-form" method="POST">
                    <h3>Edit Tip</h3>
                    <input type="hidden" name="tip_id" value="<?= $editTip['id'] ?>">
                    <input type="text" name="tip_title" value="<?= htmlspecialchars($editTip['title']) ?>" required>
                    <textarea name="tip_content" required><?= htmlspecialchars($editTip['content']) ?></textarea>
                    <button type="submit" name="edit_tip"><i class="fas fa-save"></i> Save Changes</button>
                    <a href="TipsGuide.php" class="admin-actions edit" style="background:#e74c3c;">Cancel</a>
                </form>
            <?php else: ?>
                <form class="admin-form" method="POST">
                    <h3>Add New Tip</h3>
                    <input type="text" name="tip_title" placeholder="Tip Title" required>
                    <textarea name="tip_content" placeholder="Tip Content" required></textarea>
                    <button type="submit" name="add_tip"><i class="fas fa-plus"></i> Add Tip</button>
                </form>
            <?php endif; ?>
        <?php endif; ?>

        <?php while ($tip = $tips->fetch_assoc()): ?>
            <div class="tip-block">
                <h4><?= htmlspecialchars($tip['title']) ?></h4>
                <p><?= nl2br(htmlspecialchars($tip['content'])) ?></p>
                <small>Created: <?= htmlspecialchars($tip['created_at']) ?></small>
                <?php if ($isAdmin): ?>
                    <div class="admin-actions">
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="edit_tip_id" value="<?= $tip['id'] ?>">
                            <button type="submit" class="edit"><i class="fas fa-edit"></i> Edit</button>
                        </form>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this tip?');">
                            <input type="hidden" name="delete_tip" value="<?= $tip['id'] ?>">
                            <button type="submit"><i class="fas fa-trash"></i> Delete</button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        <?php endwhile; ?>
    </div>
</body>

</html>