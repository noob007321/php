<?php
/* =========================================================================
   home.php  —  the "different page" you see after a correct login
   =========================================================================
   The first thing we do: check that this visitor really logged in.
   If not, send them back to login.php. This is the lock on the door.
   ========================================================================= */

session_start();

if (!isset($_SESSION['login_id'])) {
    header('Location: login.php');
    exit;
}

$id = $_SESSION['login_id'];   // who is it? (put there by login.php)
?>
<!DOCTYPE html>
<html>
<head>
    <title>Home</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="box">

        <h1>Welcome, <?= htmlspecialchars($id) ?>!</h1>

        <p class="ok">The login ID and password matched.</p>
        <p>You are now on a different page. Refresh this page — you stay
           logged in, because PHP remembers you in <code>$_SESSION</code>.</p>

        <a class="button" href="logout.php">Log out</a>
    </div>
</body>
</html>
