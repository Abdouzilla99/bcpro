<?php
session_start();

// ===================================
// ==   SET YOUR PANEL PASSWORD HERE  ==
// ===================================
$panel_password = "admin";
// ===================================

$error = '';
if (isset($_SESSION['panel_loggedin']) && $_SESSION['panel_loggedin'] === true) {
    header('Location: dashboard.php');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['password']) && $_POST['password'] === $panel_password) {
        $_SESSION['panel_loggedin'] = true;
        header('Location: dashboard.php');
        exit;
    } else {
        $error = "Incorrect Password!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Panel Login</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <div class="login-container">
        <h2>Admin Panel Login</h2>
        <form method="post" action="login.php"><input type="password" name="password" placeholder="Password"
                required><button type="submit">Login</button><?php if ($error): ?>
                <p class="error"><?php echo $error; ?></p><?php endif; ?>
        </form>
    </div>
</body>

</html>