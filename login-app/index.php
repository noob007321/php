<?php
/**
 * ==========================================================================
 *  index.php — the login page  (Question 1 of the assignment)
 * ==========================================================================
 *
 *  This single file does three jobs, in this order:
 *
 *    1. LISTEN   — if the form was submitted (POST), read the inputs
 *    2. DECIDE   — validate them; on success go to dashboard.php,
 *                  on failure collect error messages in $errors
 *    3. DRAW     — print the HTML form, and print $errors below the form
 *
 *  Reading top to bottom is reading the whole logic of the login.
 */

declare(strict_types=1);

require __DIR__ . '/includes/auth.php';

/* -------------------------------------------------------------------------
 * 0. Already logged in? Then there is no reason to show the form again.
 * ---------------------------------------------------------------------- */
if (current_user() !== null) {
    redirect('dashboard.php');
}

/* -------------------------------------------------------------------------
 * 1 + 2. LISTEN and DECIDE
 * ---------------------------------------------------------------------- */

$errors  = [];   // a list of error messages — empty means "no problem"
$loginId = '';   // we keep what the user typed so the field is not cleared

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Read the inputs. trim() removes spaces at both ends.
    // $_POST is a superglobal array filled by PHP from the request body.
    $loginId  = trim((string) ($_POST['login_id'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    // 1a. Was this form really produced by our site? (CSRF check)
    if (!csrf_valid($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Your session expired. Please try again.';
    }

    // 1b. Server-side validation: the browser can be bypassed, the server
    //     cannot. Never trust that an input exists just because the HTML
    //     said "required".
    if ($loginId === '') {
        $errors[] = 'Login ID is required.';
    }
    if ($password === '') {
        $errors[] = 'Password is required.';
    }

    // 1c. Only check credentials when the fields are actually filled in.
    if ($errors === []) {

        $user = verify_login($loginId, $password);

        if ($user === null) {
            // Deliberately vague: we do not say WHICH part was wrong,
            // otherwise we would tell attackers which login IDs exist.
            $errors[] = 'Invalid login ID or password. Please try again.';
        } else {
            // SUCCESS — remember the user, then send them to another page.
            login_user($loginId, $user);
            redirect('dashboard.php');
        }
    }
}

/* -------------------------------------------------------------------------
 * 3. DRAW the page
 * ---------------------------------------------------------------------- */
$loggedOut = isset($_GET['logged_out']);   // true after logout.php redirects
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Log in — <?= APP_NAME ?></title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<main class="card">

    <header class="card-head">
        <div class="logo">&#127891;</div>
        <h1><?= APP_NAME ?></h1>
        <p class="muted">Sign in to continue</p>
    </header>

    <?php if ($loggedOut): ?>
        <div class="alert alert-info" role="status">
            You have been logged out successfully.
        </div>
    <?php endif; ?>

    <!-- action="" posts to the same URL, method="post" hides the password
         from the URL bar and from the server's access log -->
    <form method="post" action="" class="form">

        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

        <div class="field">
            <label for="login_id">Login ID</label>
            <input
                type="text"
                id="login_id"
                name="login_id"
                value="<?= e($loginId) ?>"
                placeholder="e.g. admin"
                autocomplete="username"
                autofocus>
        </div>

        <div class="field">
            <label for="password">Password</label>
            <input
                type="password"
                id="password"
                name="password"
                placeholder="Enter your password"
                autocomplete="current-password">
        </div>

        <button type="submit" class="btn">Log in</button>
    </form>

    <?php if ($errors !== []): ?>
        <!-- THE ERROR MESSAGES, PRINTED BELOW THE LOGIN FORM -->
        <div class="alert alert-error" role="alert">
            <strong>Login failed</strong>
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= e($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="demo">
        <span class="demo-title">Demo accounts</span>
        <code>admin / admin123</code>
        <code>student / student123</code>
    </div>

    <footer class="card-foot">
        <a class="foot-link" href="http-errors.php">HTTP request &amp; response errors &rarr;</a>
        <span>PHP <?= PHP_VERSION ?> &middot; session <code><?= e(substr(session_id(), 0, 8)) ?>&hellip;</code></span>
    </footer>

</main>

</body>
</html>
