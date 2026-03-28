<?php
// Cookie Consent Banner - Ready to Include
if (isset($_POST['accept_cookies'])) {
    setcookie("cookie_consent", "1", time() + (10 * 365 * 24 * 60 * 60), "/"); // 10 years
    header("Location: " . $_SERVER['REQUEST_URI']); // Refresh to hide banner
    exit();
}
?>
<?php if (!isset($_COOKIE['cookie_consent'])): ?>
    <div id="cookie-banner" style="position:fixed;bottom:0;left:0;width:100%;background:#222;color:#fff;padding:15px;text-align:center;z-index:9999;">
        This website uses cookies to ensure you get the best experience. 
        <form method="post" style="display:inline;">
            <button type="submit" name="accept_cookies" style="margin-left:10px;padding:5px 15px;">Accept</button>
        </form>
    </div>
<?php endif; ?> 