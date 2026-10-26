<?php
/**
 * ==========================================================================
 *  config.php — settings and the "database" of users
 * ==========================================================================
 *
 *  This file is loaded by every page. It contains:
 *    1. Some app settings (name, etc.)
 *    2. start_session()  — one place where the session is started
 *    3. all_users()      — the list of users allowed to log in
 *
 *  NOTE FOR STUDENTS
 *  -----------------
 *  Real applications keep users in a database (MySQL). Here we keep them in a
 *  plain PHP array so you can *see* the data. Nothing else in the app needs to
 *  change when you later replace all_users() with a database query — that is
 *  the whole point of putting the data access in one function.
 */

declare(strict_types=1);

/*
 * Guard: this is a library, not a page. If somebody types its URL into the
 * browser, PHP would start executing it with nothing to show. We compare the
 * file that the web server is executing with this file, and answer 404 so the
 * visitor learns nothing about our folder structure.
 * (The real fix is to keep includes/ outside the document root.)
 */
if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    http_response_code(404);
    exit;
}

/** The text shown in the browser tab and on the page. */
const APP_NAME = 'Student Portal';

/**
 * The users who may log in.
 *
 * Structure:  login ID  =>  [ 'name' => display name, 'password' => HASH ]
 *
 * The password is NOT stored as plain text. It is stored as a one-way hash
 * produced by password_hash(). You can never "read" the password back out of
 * a hash — you can only verify a guess with password_verify().
 *
 *   admin   / admin123
 *   student / student123
 *
 * To add a user: run `php hash.php "your-password"` and paste the output here.
 */
function all_users(): array
{
    return [
        'admin' => [
            'name'     => 'Administrator',
            'password' => '$2y$12$p/PF9JOBLn.9y.rqKiKzu.Rm5YStbmAX4tAgC/70BUowFW5QYodxO',
        ],
        'student' => [
            'name'     => 'Rahul Sharma',
            'password' => '$2y$12$zSaJoy8bNz71kjuzjEiFA.lRmXFWD/b0rFcljYzBJrXgElFmSoAgm',
        ],
    ];
}

/**
 * Start (or resume) the PHP session.
 *
 * A session is how PHP remembers "this browser is logged in" between two
 * requests. PHP sends the browser a cookie called PHPSESSID; on every later
 * request the browser sends that cookie back, and PHP loads the matching file
 * from the server (by default in /tmp) into the $_SESSION array.
 *
 * The options below are simply good security defaults:
 *   httponly     -> JavaScript cannot read the cookie (blocks many XSS thefts)
 *   samesite=Lax -> the cookie is not sent by cross-site POST requests (CSRF)
 *   use_strict_mode -> ignore session IDs that PHP did not create itself
 */
function start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return; // already started — starting twice would raise a warning
    }

    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'path'     => '/',
    ]);
    ini_set('session.use_strict_mode', '1');

    session_start();
}
