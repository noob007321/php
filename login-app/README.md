# Login Application — install and run

A plain-PHP login system with no framework and no database. Every file is
commented for teaching, and nothing is hidden behind a library.

## What you need

* PHP 8.0 or newer (the code uses `match`, `str_starts_with`, typed functions).
* Any web server — Apache/nginx, or PHP's own built-in server for development.

No Composer, no npm, no MySQL required for the basic version.

## Option A — PHP's built-in server (fastest)

```bash
cd login-app
php -S localhost:8000 router.php
```

Then open <http://localhost:8000>.

`router.php` makes the development server behave like Apache: real files are
served, a folder with an `index.php` is served, and everything else gets a real
`404`. Without it, the built-in server can answer unknown URLs with the wrong
status code, which confuses students.

> If you leave the router out (`php -S localhost:8000`), everything still works;
> you only lose the proper 404 behaviour.

## Option B — XAMPP / WAMP / LAMP

1. Copy the whole `login-app` folder into the web root:
   * XAMPP (Windows): `C:\xampp\htdocs\login-app`
   * XAMPP (macOS): `/Applications/XAMPP/htdocs/login-app`
   * Ubuntu: `/var/www/html/login-app`
2. Start Apache in the XAMPP control panel.
3. Visit <http://localhost/login-app/>.

For the custom 404 page, add to `htdocs/.htaccess`:

```apache
ErrorDocument 404 /login-app/404.php
```

## Demo accounts

| Login ID | Password    | Name          |
|----------|-------------|---------------|
| `admin`  | `admin123`  | Administrator |
| `student`| `student123`| Rahul Sharma  |

Both are defined in `includes/config.php` as **bcrypt hashes**, never as plain text.

## Add a user

```bash
php hash.php "MyNewPassword"
```

Paste the printed hash into the array in `includes/config.php`:

```php
'priya' => [
    'name'     => 'Priya Patel',
    'password' => '$2y$12$....paste here....',
],
```

`hash.php` refuses to run inside a web server on purpose.

## Things to try (for students)

1. Log in correctly → you land on `dashboard.php`. Press F5: you stay logged in.
2. Log in with a wrong password → you stay on `index.php` and the red message
   appears **below** the form.
3. Delete the `PHPSESSID` cookie in DevTools → pressing F5 throws you out,
   because the server no longer recognises you.
4. Open `dashboard.php` in a private window → you are redirected to the login page.
5. Open `http-errors.php` and click the status-code buttons with DevTools → Network open.

## Troubleshooting

| Symptom | Cause |
|---|---|
| "Headers already sent" warning | something was printed (even a blank line or BOM) before `session_start()` / `header()` |
| Login never succeeds | the hash in `config.php` was edited by hand — regenerate it with `hash.php` |
| Session lost on every click | PHP cannot write to its session folder (`session.save_path`), or the browser blocks cookies |
| Blank page | `display_errors` is off and PHP hit a fatal error — check the server's error log |
