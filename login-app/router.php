<?php
/**
 * ==========================================================================
 *  router.php — used ONLY by PHP's built-in development server
 * ==========================================================================
 *
 *  You do not need this file with Apache or nginx. It exists because the
 *  development server can be started like this:
 *
 *      php -S localhost:8000 router.php
 *
 *  ...which makes every request pass through this script first, exactly like
 *  an .htaccess rewrite rule would. The script's job is to say:
 *
 *      real file exists?   -> let PHP serve it normally  (return false)
 *      folder with index?  -> serve that index file
 *      anything else       -> a real 404 Not Found
 *
 *  The last case is the point: without this, a wrong URL can be swallowed by
 *  a fallback page and answered with 200 OK, which teaches students the wrong
 *  lesson about HTTP.
 */

declare(strict_types=1);

$path = urldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/');
$path = '/' . ltrim($path, '/');

$target = realpath(__DIR__ . $path);

// realpath() defeats "../../etc/passwd"-style tricks: the result must still
// be inside this folder before we are willing to serve it.
$insideApp = $target !== false
    && ($target === __DIR__ || str_starts_with($target, __DIR__ . DIRECTORY_SEPARATOR));

if ($insideApp && is_file($target)) {
    return false;                      // let the web server send it as-is
}

if ($insideApp && is_dir($target) && is_file($target . '/index.php')) {
    require $target . '/index.php';    // "/" serves index.php
    return true;
}

// Nothing matched -> a genuine 404, with our own friendly page.
http_response_code(404);
require __DIR__ . '/404.php';
return true;
