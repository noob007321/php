<?php
/* =========================================================================
   login.php  —  THE SIMPLE VERSION
   -------------------------------------------------------------------------
   Log in with:   admin / 1234        student / abc
   This one file does 3 things, in this order:

       1. LOOK   — did the form arrive? read the ID and password
       2. CHECK  — are they correct?  yes -> home.php   no -> $error
       3. SHOW   — print the form, then the error under it
   ========================================================================= */

session_start();        // needed so we can remember the user later

// The "database". A real app would use MySQL; for learning an array is enough.
$users = [
    'admin'   => '1234',
    'student' => 'abc',
];

$error = '';            // empty = no problem (yet)

// ---- 1. LOOK ------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id = $_POST['login_id'] ?? '';     // what the user typed
    $pw = $_POST['password'] ?? '';

    // ---- 2. CHECK -------------------------------------------------------
    if (isset($users[$id]) && $users[$id] === $pw) {

        $_SESSION['login_id'] = $id;    // remember this user
        header('Location: home.php');   // send them to the other page
        exit;                           // stop this page

    } else {
        $error = 'Invalid login ID or password.';
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="box">

        <h1>Login</h1>

        <!-- ---- 3. SHOW ------------------------------------------------- -->
        <form method="post">
            <label>Login ID</label>
            <input type="text" name="login_id" value="<?= htmlspecialchars($id ?? '') ?>">

            <label>Password</label>
            <input type="password" name="password">

            <button type="submit">Log in</button>
        </form>

        <?php if ($error !== ''): ?>
            <!-- the error message appears BELOW the form -->
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <p class="hint">Try: admin / 1234 or student / abc</p>
    </div>
</body>
</html>
