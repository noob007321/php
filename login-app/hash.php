<?php
/**
 * ==========================================================================
 *  hash.php — a tiny command-line tool to make password hashes
 * ==========================================================================
 *
 *  Run it in a terminal (NOT in the browser):
 *
 *      php hash.php "MySecret123"
 *
 *  Output, for example:
 *
 *      $2y$12$p/PF9JOBLn.9y.rqKiKzu.Rm5YStbm...
 *
 *  Paste that line into config.php as the user's 'password'.
 *
 *  Why a separate file? Because password_hash() must never be reachable from
 *  a web request while you are learning: anyone who opened it in a browser
 *  could create hashes, and it would leak which algorithm and cost you use.
 *  We also refuse to run it when PHP is inside a web server.
 */

declare(strict_types=1);

// php_sapi_name() is "cli" in a terminal, "cli-server"/"fpm-fcgi"/"apache2handler"
// when a browser is involved.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;   // pretend this file does not exist
}

$password = $argv[1] ?? null;

if ($password === null || $password === '') {
    fwrite(STDERR, "Usage: php hash.php \"the password\"\n");
    exit(1);
}

// PASSWORD_DEFAULT is currently bcrypt ($2y$). The format is
//   $2y$<cost>$<22 chars salt><31 chars hash>
// The salt is random and stored inside the hash, so the same password gives
// a different hash every time — that is why you cannot compare hashes for
// equality, you must use password_verify().
$hash = password_hash($password, PASSWORD_DEFAULT);

echo $hash . PHP_EOL;
echo 'length: ' . strlen($hash) . ' characters, algorithm: ' . password_get_info($hash)['algoName'] . PHP_EOL;
