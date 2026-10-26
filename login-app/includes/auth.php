<?php
/**
 * ==========================================================================
 *  auth.php — everything about "who is logged in"
 * ==========================================================================
 *
 *  Every page begins with:      require __DIR__ . '/includes/auth.php';
 *
 *  That one line loads config.php, starts the session, and gives the page the
 *  functions below:
 *
 *      e($text)                     escape text before printing it (XSS)
 *      redirect($url)               send the browser to another page
 *      find_user($loginId)          look a user up in the array
 *      verify_login($id, $password) check a login ID + password pair
 *      login_user($id, $user)       remember the user in the session
 *      current_user()               who is logged in right now? (or null)
 *      require_login()              kick out visitors who are not logged in
 *      logout_user()                forget the user completely
 *      csrf_token() / csrf_valid()  protect the form from forged submissions
 */

declare(strict_types=1);

// Same guard as config.php: auth.php must never be opened directly.
if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/config.php';

start_session();   // needs to happen before any HTML is printed

/* =========================================================================
 *  1. Tiny helpers
 * ========================================================================= */

/**
 * Escape a string so it is safe to print inside HTML.
 *
 * If a name contained  <script>alert(1)</script>  and we printed it directly,
 * the browser would run that script. htmlspecialchars() turns < into &lt; so
 * the browser displays the text instead of executing it. Always escape
 * anything that came from the user.
 */
function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Send an HTTP redirect and stop the script.
 *
 * header() writes a line into the HTTP *response headers*. "Location: x"
 * tells the browser "do not render this page, request x instead".
 * exit is essential: without it PHP would keep running and still send the
 * rest of the page after the redirect header.
 */
function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

/* =========================================================================
 *  2. Looking users up and checking passwords
 * ========================================================================= */

/**
 * Find one user by login ID. Returns null when the ID does not exist.
 * Keys in our array are lower-case, so we normalise user input first.
 */
function find_user(string $loginId): ?array
{
    $users = all_users();
    $key   = strtolower(trim($loginId));

    return $users[$key] ?? null;   // ?? = "use the right side if the left is null"
}

/**
 * The heart of the login: does this login ID / password pair exist?
 *
 * Returns the user array on success, or null on failure.
 *
 * password_verify($typedPassword, $storedHash) recomputes the hash of the
 * typed password using the salt and cost stored inside $storedHash and
 * compares the two. It is intentionally slow (~100 ms) to make brute force
 * expensive. It returns true/false — it is NOT a string comparison.
 */
function verify_login(string $loginId, string $password): ?array
{
    // Used only when the login ID does not exist. Verifying against a dummy
    // hash keeps the response time similar for "unknown user" and "wrong
    // password", so an attacker cannot discover which login IDs are real.
    $dummyHash = '$2y$12$zSaJoy8bNz71kjuzjEiFA.lRmXFWD/b0rFcljYzBJrXgElFmSoAgm';

    $user       = find_user($loginId);
    $hash       = $user['password'] ?? $dummyHash;
    $passwordOk = password_verify($password, $hash);

    if ($user === null || $passwordOk === false) {
        return null;            // same answer for both cases — on purpose
    }

    return $user;
}

/* =========================================================================
 *  3. The session: remembering the user between requests
 * ========================================================================= */

/**
 * Save the logged-in user in $_SESSION.
 *
 * session_regenerate_id(true) throws away the session ID the visitor had
 * before logging in and gives them a fresh one. Without this, an attacker
 * who managed to fix a victim's session ID beforehand ("session fixation")
 * would be logged in as soon as the victim logs in.
 */
function login_user(string $loginId, array $user): void
{
    session_regenerate_id(true);

    $_SESSION['user_id']    = strtolower(trim($loginId));
    $_SESSION['name']       = $user['name'];
    $_SESSION['login_time'] = date('d M Y, H:i:s');
}

/** Who is logged in? Returns null when nobody is. */
function current_user(): ?array
{
    if (!isset($_SESSION['user_id'])) {
        return null;
    }

    return [
        'id'         => (string) $_SESSION['user_id'],
        'name'       => (string) ($_SESSION['name'] ?? $_SESSION['user_id']),
        'login_time' => (string) ($_SESSION['login_time'] ?? ''),
    ];
}

/**
 * Guard for protected pages.
 *
 * dashboard.php starts with:   $user = require_login();
 * A visitor without a valid session is redirected to the login page before
 * a single byte of the protected page is produced.
 */
function require_login(): array
{
    $user = current_user();

    if ($user === null) {
        redirect('index.php');
    }

    return $user;
}

/** Delete the session data, delete the cookie, delete the session file. */
function logout_user(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires'  => time() - 42000,   // a date in the past = "delete me"
            'path'     => $p['path'],
            'domain'   => $p['domain'],
            'secure'   => $p['secure'],
            'httponly' => $p['httponly'],
            'samesite' => $p['samesite'] ?? 'Lax',
        ]);
    }

    session_destroy();
}

/* =========================================================================
 *  4. CSRF protection
 * ========================================================================= */

/**
 * CSRF = Cross-Site Request Forgery: a different website makes the visitor's
 * browser submit our form without the visitor knowing. The fix is to put a
 * random secret in the form and in the session; a foreign site cannot read
 * the secret, so it cannot produce a valid form.
 *
 * csrf_token() creates the secret once per session and returns it;
 * index.php prints it in a hidden input, and checks it again on POST.
 */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

/** Compare the token that came back from the form with the one in the session. */
function csrf_valid(?string $token): bool
{
    $expected = $_SESSION['csrf_token'] ?? '';

    // hash_equals compares in constant time, so the comparison itself cannot
    // be used to guess the token byte by byte.
    return $expected !== '' && $token !== null && hash_equals($expected, $token);
}
