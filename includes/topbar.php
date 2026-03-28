<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$role = $_SESSION['role'] ?? null;
?>
<div class="Topbar">
    <h1>EcoHabits</h1>
    <div>
        <a href="HomePage.php">Home</a>
        <a href="calculator.php">Carbon Calculator</a>
        <a href="TipsGuide.php">Tips and Guide</a>
        <a href="news.php">News</a>
        <a href="video.php">Video</a>
        <?php if ($role == 'admin'): ?>
            <a href="adminAcc.php">Manage User</a>
        <?php elseif ($role == 'user'): ?>
            <a href="userAcc.php"><img src="assets/images/profile.png" width="48px"></a>
        <?php else: ?>
            <a href="LoginPage.php">Sign up / Log in</a>
        <?php endif; ?>
    </div>
</div>
<style>
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
}
</style> 