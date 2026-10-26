<?php
/**
 * ==========================================================================
 *  404.php — a custom "Not Found" page
 * ==========================================================================
 *
 *  The web server could not find the file that was requested, so it runs this
 *  page instead. The important line is the first one: the page must still be
 *  sent with the 404 status code, otherwise browsers, search engines and
 *  monitoring tools all believe the request succeeded.
 *
 *  Apache setup (in httpdocs/.htaccess):
 *      ErrorDocument 404 /404.php
 *  nginx setup:
 *      error_page 404 /404.php;
 */

declare(strict_types=1);

http_response_code(404);

// Whatever the visitor asked for, shown back with htmlspecialchars() so a
// crafted URL cannot inject markup into our error page.
$requested = $_SERVER['REQUEST_URI'] ?? '/';

/*
 * Where is this app installed?
 *
 * An error page can be reached from ANY url depth (/login-app/nothing.php,
 * /a/b/c), so a relative link like "assets/style.css" would resolve against a
 * folder that does not exist. Instead we work out the URL path of the app
 * itself: compare its real folder on disk with the document root.
 *
 *   document root / var/www/html      app folder /var/www/html/login-app
 *   => base = "/login-app"
 *   document root / var/www/html      app folder /var/www/html
 *   => base = ""
 */
$docRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: '';
$appRoot = realpath(__DIR__) ?: '';
$base    = ($docRoot !== '' && str_starts_with($appRoot, $docRoot))
    ? str_replace('\\', '/', substr($appRoot, strlen($docRoot)))
    : '';
$base    = rtrim($base, '/');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>404 Not Found</title>
    <link rel="stylesheet" href="<?= $base ?>/assets/style.css">
</head>
<body>

<main class="card">

    <header class="card-head">
        <div class="logo">&#128269;</div>
        <h1>404 &mdash; Not Found</h1>
        <p class="muted">The server looked for this page and there is nothing there.</p>
    </header>

    <div class="alert alert-error" role="alert">
        <strong>A 4xx error means the request was wrong</strong>
        The URL you asked for does not exist on this server. Nothing was
        broken on our side; this is a client-side (request) error.
    </div>

    <div class="demo">
        <span class="demo-title">You requested</span>
        <code>GET <?= htmlspecialchars($requested, ENT_QUOTES, 'UTF-8') ?></code>
        <span class="demo-title" style="margin-top:8px">Status sent</span>
        <code>HTTP/1.1 404 Not Found</code>
    </div>

    <div class="actions" style="margin-top:18px">
        <a class="btn" href="<?= $base ?>/index.php">Go to the login page</a>
    </div>

    <footer class="card-foot">
        <a class="foot-link" href="<?= $base ?>/http-errors.php">HTTP request &amp; response errors &rarr;</a>
    </footer>

</main>

</body>
</html>
