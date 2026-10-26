# Teaching Guide — PHP Login Application & HTTP Errors

Everything you need in front of a class: the mental model, every file explained,
the exact traffic of a login attempt, the security decisions, the answer to
Question 2, a demo script, viva questions and homework.

**Contents**

1. [What PHP actually is](#1-what-php-actually-is)
2. [The request/response cycle in this app](#2-the-requestresponse-cycle-in-this-app)
3. [File by file](#3-file-by-file)
4. [The login algorithm](#4-the-login-algorithm)
5. [Sessions and cookies](#5-sessions-and-cookies)
6. [Security: the five protections](#6-security-the-five-protections)
7. [Question 2 — HTTP request errors vs response errors](#7-question-2--http-request-errors-vs-response-errors)
8. [If your sheet also asks "What is a web framework?"](#8-if-your-sheet-also-asks-what-is-a-web-framework)
9. [Classroom demo script](#9-classroom-demo-script-30-minutes)
10. [Viva / exam questions with answers](#10-viva--exam-questions-with-answers)
11. [Homework and extensions](#11-homework-and-extensions)
12. [Appendix A — the same app with MySQL](#12-appendix-a--the-same-app-with-mysql)
13. [Appendix B — the absolute minimum version](#appendix-b--the-absolute-minimum-version-first-10-minutes-of-class)

---

## 1. What PHP actually is

> PHP is a program that runs **on the server** and writes **text** — usually HTML —
> which the server then sends to the browser.

Three sentences that clear up 90 % of student confusion:

* The browser **never** sees your PHP code. View Source in the browser shows only
  the HTML that PHP *produced*. If you can read `<?php ... ?>` in the browser,
  something is wrong with the server setup.
* PHP starts fresh on **every request**. Variables from the previous click are
  gone. That is exactly why we need sessions (section 5).
* PHP is not "inside" HTML and HTML is not "inside" PHP — they are mixed in one
  file, and PHP simply decides which parts reach the browser.

The little `<?= $x ?>` you will see everywhere is shorthand for
`<?php echo $x; ?>`.

### The shape of every request

```
Browser ──HTTP request──> Web server ──> PHP interpreter ──> HTML
        <──HTTP response──               <──
```

Our login is one page that is requested twice — once to *show* the form (GET)
and once to *submit* it (POST). That is the whole architecture of question 1.

---

## 2. The request/response cycle in this app

### Scenario A — the user opens the login page

```
GET /index.php HTTP/1.1
Host: localhost:8000
```

* `$_SERVER['REQUEST_METHOD']` is `"GET"`, so the `if ($_SERVER['REQUEST_METHOD'] === 'POST')`
  block in `index.php` is skipped entirely.
* The page is drawn with an empty form and no error messages.
* Response: `200 OK` + the HTML + `Set-Cookie: PHPSESSID=...`

### Scenario B — wrong password

```
POST /index.php HTTP/1.1
Content-Type: application/x-www-form-urlencoded

csrf_token=84c8...&login_id=admin&password=wrongpass
```

PHP fills `$_POST`, we validate, `verify_login()` returns `null`, we push a
message into `$errors`, and — this is the part students must understand — we
**do not redirect**. We fall through to the HTML and re-render the same page.

Response: `200 OK` + the form again + the red box *below* the form.

> Why `200` and not `401`? Because the HTTP exchange itself succeeded: the form
> page was delivered perfectly. The status code describes the *transport*, not
> your business rule. (Section 7 has the full discussion.)

### Scenario C — correct password

```
POST /index.php ... -> verify_login() returns the user array
                    -> login_user() writes $_SESSION and gives a NEW session id
                    -> redirect('dashboard.php') sends:

HTTP/1.1 302 Found
Location: dashboard.php
Set-Cookie: PHPSESSID=<new id>; path=/; HttpOnly; SameSite=Lax
```

The browser then makes a **second** request, all by itself:

```
GET /dashboard.php HTTP/1.1
Cookie: PHPSESSID=<new id>
```

PHP loads the session file, finds `user_id`, `require_login()` returns the user
and the dashboard is rendered: `200 OK`. That second request is why the login
"moved you to a different page" — the server never *pushed* a page, it told the
browser where to ask next.

### Scenario D — someone types `dashboard.php` while logged out

`require_login()` sees no `user_id` in the session and calls `redirect('index.php')`:
`302 Found` → login page. The protected HTML is never generated.

---

## 3. File by file

```
login-app/
├── index.php          ← the form (GET) and the login attempt (POST)
├── dashboard.php      ← protected page
├── logout.php         ← destroys the session, then redirects
├── http-errors.php    ← Question 2
├── 404.php            ← custom Not Found page
├── router.php         ← dev-server only helper
├── hash.php           ← CLI tool: make a password hash
├── includes/
│   ├── config.php     ← settings, users, start_session()
│   └── auth.php       ← all the login logic in functions
└── assets/style.css   ← presentation only
```

Splitting *presentation* (`index.php` HTML, `style.css`), *logic* (`auth.php`)
and *data* (`config.php`) is the beginner version of a pattern professionals call
"separation of concerns". It is why you can replace the array in `config.php`
with a database and touch nothing else (section 12).

### 3.1 `includes/config.php`

**`declare(strict_types=1);`**
Makes PHP throw an error when a value of the wrong type reaches a function
instead of silently converting it. `"5 apples" + 3` teaches nothing if it
returns `8`; strict types make the mistake visible.

**The guard at the top**

```php
if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    http_response_code(404);
    exit;
}
```

This file is a *library*, not a page. If a visitor types
`/includes/config.php` into the browser, PHP would execute it with nothing to
display — or worse, leak an error message with your paths in it. We compare
"the file the server is executing" with "this file"; if they are the same,
somebody opened it directly, so we answer `404`. (`__FILE__` is the full path of
the current file — a magic constant.)

The real-world fix is stronger: keep `includes/` **outside** the web root so the
web server cannot reach it at all.

**`const APP_NAME`**
A constant is a named value that cannot change. Uses `const` because it is a
compile-time constant; `define()` is the older, function-based way.

**`all_users(): array`**

```php
return [
    'admin' => [
        'name'     => 'Administrator',
        'password' => '$2y$12$p/PF9JOBLn.9y.rqKiKzu...',
    ],
    ...
];
```

* This is a **keyed array**: the login ID is the key, and the value is another
  array (a "record") with `name` and `password`.
* The password is stored as a **hash**, not as `admin123`. Section 6.1 explains
  why, and why that string can never be reversed.
* Function instead of a global variable, so that a page cannot accidentally
  overwrite the user list, and so the database version can simply `return` rows.
* `: array` is the return type — a promise to the reader (and to PHP).

**`start_session(): void`**

```php
if (session_status() === PHP_SESSION_ACTIVE) return;   // don't start twice
session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'path' => '/']);
ini_set('session.use_strict_mode', '1');
session_start();
```

* `session_start()` is the function that makes `$_SESSION` exist. It must run
  **before any output** — before even a blank line or a UTF-8 BOM — because it
  sends the `Set-Cookie` header, and headers go before the body.
* `session_status()` check: including this file twice would otherwise raise
  "a session had already been started" warnings.
* The three options are security defaults explained in 5.2.
* `: void` means "returns nothing on purpose".

### 3.2 `includes/auth.php`

This file is the brain. Every page begins with one line:

```php
require __DIR__ . '/includes/auth.php';
```

* `require` = "stop everything if this file is missing". (`include` would only warn.)
* `__DIR__` = the folder of the current file, so the path works no matter where
  the site is installed. Never write `require 'includes/auth.php'` — it resolves
  against the *entry* script and breaks the moment a file moves.
* The file pulls in `config.php`, starts the session, and defines the helpers.

**`e()` — escape everything you print**

```php
function e(?string $value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
```

If a user's name were `<script>alert(1)</script>` and we printed it raw, the
browser would *execute* it. `htmlspecialchars()` turns `<` into `&lt;` so the
browser shows the characters instead of running them.

| Printed as | Browser does |
|---|---|
| `<script>alert(1)</script>` | runs the script (XSS attack) |
| `&lt;script&gt;alert(1)&lt;/script&gt;` | shows the text |

`ENT_QUOTES` also escapes `'` and `"`, which matters when the value lands inside
an HTML attribute (`value="..."`). `ENT_SUBSTITUTE` replaces invalid UTF-8
sequences instead of returning an empty string.

**`redirect()`**

```php
header('Location: ' . $url);
exit;
```

* `header()` writes a line into the **response headers**, before the body.
* `Location:` makes the browser request that URL instead. The status is `302`
  by default.
* `exit` is not optional. Without it PHP keeps executing and appends the rest of
  the page to a redirect — students see "the page half loads then jumps", or a
  "headers already sent" warning.

**`find_user()`**

```php
$users = all_users();
$key = strtolower(trim($loginId));
return $users[$key] ?? null;
```

* `trim()` removes surrounding spaces — users copy-paste " admin".
* `strtolower()` makes the ID case-insensitive.
* `??` is the null-coalescing operator: "use the right side if the left does not
  exist". Without it, an unknown ID would raise "Undefined array key".

**`verify_login()` — the heart of the app**

```php
$dummyHash = '$2y$12$zSaJoy8bNz71kjuzjEiFA...';
$user       = find_user($loginId);
$hash       = $user['password'] ?? $dummyHash;
$passwordOk = password_verify($password, $hash);

if ($user === null || $passwordOk === false) return null;
return $user;
```

Three teaching points in five lines:

1. `password_verify($typed, $storedHash)` hashes the typed password **using the
   salt and cost stored inside** `$storedHash` and compares the results. You
   cannot compare hashes with `==` because the same password produces a
   different hash every time (that is the point of a random salt).
2. It is deliberately slow — roughly 100 ms. An attacker testing a billion
   guesses pays a billion × 100 ms. Logging in once, nobody notices.
3. The "dummy hash" trick: when the login ID does not exist we still call
   `password_verify()` against a real hash, so both failure paths take about the
   same time. Otherwise an attacker could time the response and discover which
   login IDs are real (*timing attack*).
4. We return `null` for *both* "no such user" and "wrong password", and
   `index.php` shows one vague message. Same reason: never tell an attacker
   which usernames exist (*username enumeration*).

**`login_user()` and `session_regenerate_id(true)`**

```php
session_regenerate_id(true);
$_SESSION['user_id']    = 'admin';
$_SESSION['name']       = 'Administrator';
$_SESSION['login_time'] = date('d M Y, H:i:s');
```

`session_regenerate_id(true)` gives the user a brand-new session ID and deletes
the old session file. Reason (section 6.4): if an attacker can plant a known
session ID in the victim's browser before login (*session fixation*), then after
login the attacker would still know the ID and would be logged in too. Replacing
the ID at the exact moment privileges change kills that attack.

**`require_login()` — the lock on the door**

```php
$user = current_user();
if ($user === null) redirect('index.php');
return $user;
```

`dashboard.php` starts with `$user = require_login();`. A visitor without a
session never receives a single byte of protected HTML — the guard runs before
the page is generated, not after.

**`logout_user()`** — empties `$_SESSION`, expires the cookie (a date in the
past is the standard "delete this cookie" instruction), then `session_destroy()`
removes the server-side file. All three steps matter: leaving the cookie behind
means the browser keeps sending a dead ID; leaving the file means the data still
exists on disk.

**`csrf_token()` / `csrf_valid()`** — see 6.3.

### 3.3 `index.php` — the form and the decision

The file is deliberately written in three numbered parts so students can follow
it top to bottom: **0 listen, 1+2 decide, 3 draw.**

**Part 0 — already logged in?**

```php
if (current_user() !== null) redirect('dashboard.php');
```

No point showing a login form to someone who is already inside.

**Part 1 — reading the input**

```php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $loginId  = trim((string) ($_POST['login_id'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
```

* `$_SERVER` is a **superglobal**: filled by the web server with `REQUEST_METHOD`,
  `REQUEST_URI`, `HTTP_USER_AGENT`, and so on.
* `$_POST` is the superglobal that holds the form fields of a POST request, keyed
  by the `name` attribute of each input. A GET form would fill `$_GET` instead.
* `($_POST['login_id'] ?? '')` guards against the field not existing at all — a
  user can delete `name="login_id"` from the HTML in DevTools, and the server
  must not crash.
* `trim()` + `(string)` normalises whatever arrives.

**Never trust the browser.** `required`, `maxlength`, `type="email"` are
*convenience*, not security: the form can be edited, or bypassed with `curl`
entirely. Validation that matters happens here, on the server.

**Part 2 — the decision**

```php
if (!csrf_valid($_POST['csrf_token'] ?? null)) $errors[] = 'Your session expired. Please try again.';
if ($loginId === '')  $errors[] = 'Login ID is required.';
if ($password === '') $errors[] = 'Password is required.';

if ($errors === []) {
    $user = verify_login($loginId, $password);
    if ($user === null) {
        $errors[] = 'Invalid login ID or password. Please try again.';
    } else {
        login_user($loginId, $user);
        redirect('dashboard.php');
    }
}
```

`$errors` is an **array**, not a string: several problems can be reported at
once, and each becomes one `<li>`. `[]` is an empty array — "no errors yet".

Order matters: only call `verify_login()` when the fields are actually filled
in, otherwise you would run a 100 ms hash check on empty input.

**Part 3 — drawing the page**

```php
<input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
...
<input type="text" name="login_id" value="<?= e($loginId) ?>" ...>
...
<?php if ($errors !== []): ?>
    <div class="alert alert-error" role="alert">
        <strong>Login failed</strong>
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?= e($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>
```

* The message block sits **after** the form in the HTML — exactly what the
  assignment asks ("display the error messages below the login page"). This is
  why we cannot redirect on failure: a new request would lose `$errors`.
* `value="<?= e($loginId) ?>"` puts back what the user typed, so they do not have
  to retype their ID after a wrong password.
* `method="post"` keeps the password out of the URL bar and out of the server's
  access log; `action=""` posts back to the same page, which is why the whole
  decision logic lives in this one file.
* `role="alert"` tells screen readers to announce the message.

### 3.4 `dashboard.php` — the different page

```php
require __DIR__ . '/includes/auth.php';
$user = require_login();
```

Two lines and the page is protected. Then it uses `$user['name']` and
`$user['id']`, prints the session ID and — for teaching — dumps the whole
`$_SESSION` inside a `<details>` element so students can see exactly what the
server remembers about them.

`print_r($_SESSION)` is fine *here* because it is a learning tool. In a real
application this block would be deleted: it exposes internal state.

Note what is *not* in the session: the password, and the hash. Only an ID, a
display name and a timestamp.

### 3.5 `logout.php`

```php
logout_user();
redirect('index.php?logged_out=1');
```

No HTML. A page whose only job is to change state is called an **action
endpoint**. The `?logged_out=1` query parameter is just a flag so `index.php`
can show "You have been logged out successfully" via

```php
$loggedOut = isset($_GET['logged_out']);
```

(Professional footnote: logging out with a plain `GET` link is a tiny CSRF hole —
another site could log your users out. Harmless, so we accept it for simplicity.
A stricter version uses a POST form with a CSRF token.)

### 3.6 `hash.php` — the password-hash generator

```php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$hash = password_hash($argv[1], PASSWORD_DEFAULT);
```

* Run from a terminal: `php hash.php "MySecret123"`.
* `$argv` is the array of command-line arguments; `$argv[1]` is the first one
  after the script name.
* `PHP_SAPI !== 'cli'` blocks it from the web. A page that creates hashes should
  never be reachable by a browser.
* The output looks like `$2y$12$p/PF9JOBLn...` — in that format
  `$2y$` is bcrypt, `12` is the **cost** (2¹² = 4096 rounds of the hashing
  loop), then 22 characters of random salt, then the hash. Everything needed to
  verify is inside the string, which is why `password_verify()` needs no second
  argument.
* The same password hashed twice prints two different strings — because the salt
  is random. That is expected and correct.

---

## 4. The login algorithm

```text
START
  │
  ├─ Is this a POST?
  │     no  → draw the empty form, STOP
  │     yes ↓
  │
  ├─ Does the CSRF token match the session?
  │     no  → errors += "session expired"
  │
  ├─ Is login_id empty?   yes → errors += "Login ID is required"
  ├─ Is password empty?   yes → errors += "Password is required"
  │
  ├─ errors not empty?
  │     yes → draw the form again WITH the error list below it, STOP
  │
  ├─ Look up login_id in the user list (case-insensitive, trimmed)
  ├─ password_verify(typed, storedHash)?
  │     no  → errors += "Invalid login ID or password" → draw form again, STOP
  │
  └─ SUCCESS
        session_regenerate_id(true)
        $_SESSION['user_id'] = ...
        302 Found → dashboard.php → 200 OK
```

The two properties to hammer home:

1. **Failure never redirects.** It re-renders the same page so `$errors` survives.
2. **Success always redirects.** The page after login must be a new request, so
   that pressing F5 re-loads the dashboard instead of re-posting the password
   (the "confirm form resubmission" dialog).

---

## 5. Sessions and cookies

### 5.1 The problem

HTTP is **stateless**: every request is a stranger. Request #2 knows nothing
about request #1 — including "this person just logged in".

### 5.2 The solution

1. `session_start()` gives the visitor an ID like `ba924c869bc26762940db35834b5974d`.
2. PHP writes it into a cookie: `Set-Cookie: PHPSESSID=ba924...; HttpOnly; SameSite=Lax`.
3. The browser sends that cookie back on every request to the same site.
4. PHP reads the ID, opens the matching file in its `session.save_path`
   (`/tmp/sess_ba924...` on Linux), and fills `$_SESSION` with what is in it.

```
Browser                              Server
  │  POST login_id=admin&password=...  │
  ├───────────────────────────────────►│  verify → $_SESSION['user_id']='admin'
  │  302 + Set-Cookie: PHPSESSID=NEW   │  file /tmp/sess_NEW contains user_id
  │◄───────────────────────────────────┤
  │  GET /dashboard.php                │
  │  Cookie: PHPSESSID=NEW             │
  ├───────────────────────────────────►│  PHP reads /tmp/sess_NEW → logged in
  │  200 + dashboard HTML              │
  │◄───────────────────────────────────┘
```

**The browser only holds the ID.** Names and roles stay on the server. That is
why "hacking a login" by editing a cookie is impossible here: there is nothing
in the cookie except a meaningless random string.

### 5.3 The cookie flags in `config.php`

| Flag | What it stops |
|---|---|
| `HttpOnly` | JavaScript reading the cookie → even a successful XSS cannot steal the session |
| `SameSite=Lax` | the cookie being sent on cross-site POSTs → most CSRF |
| `path=/` | the cookie being limited to one folder (would break after a redirect) |
| `use_strict_mode` | PHP accepting a session ID the server never created |
| `session_regenerate_id(true)` at login | session fixation |

`Secure` is not set because our development server runs on plain `http://`;
turn it on in production (HTTPS only).

### 5.4 How long does a session last?

By default until the browser is closed, or until PHP's garbage collector cleans
`session.save_path` (`session.gc_maxlifetime`, 1440 seconds = 24 min of
*inactivity* by default). The session ID itself is regenerated at login.

---

## 6. Security: the five protections

### 6.1 Passwords are hashed, never stored

| Bad | Better | What we do |
|---|---|---|
| store `admin123` | store `md5("admin123")` | store `password_hash()` (bcrypt) |
| anyone with the file knows every password | rainbow tables crack MD5 in seconds | slow + random salt per password |

* Hashing is **one-way**: you cannot get the password back out, you can only
  test a guess.
* MD5/SHA-1/SHA-256 are *fast*, which is exactly wrong for passwords — a GPU
  tests billions per second.
* `password_hash(PASSWORD_DEFAULT)` currently means **bcrypt**, with a cost
  factor (the `12` in `$2y$12$...`). The cost makes each attempt take ~100 ms —
  invisible for one login, crippling for a brute-force attack. Raise the cost as
  hardware gets faster; older hashes keep verifying and `password_needs_rehash()`
  tells you when to upgrade one.
* When a better algorithm becomes the default, PHP versions of the hash still
  verify correctly, and `password_needs_rehash()` lets you upgrade on the next
  login.

### 6.2 Output escaping — XSS

Anything that came from a user (or a database, which usually got it from a user)
must go through `e()` before it is printed. This applies to the login ID echoed
back into the field, the name on the dashboard, the requested URL on the 404
page, even error messages.

### 6.3 CSRF tokens

*Cross-Site Request Forgery*: a page on `evil.com` contains a form that posts to
`yoursite.com/delete-account.php`. The browser happily attaches your session
cookie, so the request looks legitimate.

The defence: every form contains a random secret that is also stored in the
session. `evil.com` cannot read the secret (same-origin policy blocks it), so it
cannot build a valid form.

```php
// creating it (once per session)
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
// checking it
hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
```

`random_bytes()` is a cryptographically secure generator — never use
`rand()`/`mt_rand()` for anything security related. `hash_equals()` compares in
constant time, so the comparison itself leaks nothing.

### 6.4 Session fixation

An attacker visits your site, gets session ID `X`, and tricks the victim into
using `X` (a link with `?PHPSESSID=X`, or a subdomain that can set cookies).
When the victim logs in, the server promotes session `X` to "logged in" — and
the attacker, who still knows `X`, is now logged in as the victim.

`session_regenerate_id(true)` at the moment of login makes the promoted ID
meaningless. The `true` deletes the old session file immediately.

### 6.5 SQL injection — the preview

The moment students replace the array with a database, this appears:

```php
// NEVER
$sql = "SELECT * FROM users WHERE login_id = '$loginId'";
// typing  ' OR '1'='1  logs in as the first user
```

The answer is prepared statements (section 12). We are not vulnerable today only
because there is no SQL yet — say that out loud in class so nobody thinks plain
array lookup teaches the safe habit.

---

## 7. Question 2 — HTTP request errors vs response errors

*(The live version of this section is `http-errors.php`; open it on the
projector and click the buttons while you talk.)*

### 7.1 First, the vocabulary

Every HTTP interaction is one **request** and one **response**.

```
REQUEST                                  RESPONSE
POST /index.php HTTP/1.1                 HTTP/1.1 200 OK
Host: localhost:8000                     Content-Type: text/html; charset=UTF-8
Content-Type: application/x-www-form-... Set-Cookie: PHPSESSID=...
Cookie: PHPSESSID=...
                                         <!DOCTYPE html> ...
login_id=admin&password=admin123         ...body...
```

The **status code** is a three-digit number in the first line of the response.
It always has the same shape: `1xx/2xx/3xx/4xx/5xx` + a reason phrase.

| Class | Name | Meaning |
|---|---|---|
| 1xx | Informational | "I received the first part, continue" |
| 2xx | Success | "Here is what you asked for" |
| 3xx | Redirection | "Look somewhere else" |
| **4xx** | **Client error — the REQUEST is wrong** | "I understand you, but I refuse" |
| **5xx** | **Server error — the RESPONSE could not be produced** | "I tried and failed" |

So in exam language:

> **An HTTP request error is a 4xx response.** The request the client sent was
> invalid, unauthorised, forbidden, aimed at a URL that does not exist, or in an
> unsupported format. The server is healthy; repeating the same request changes
> nothing until the **client** changes something.
>
> **An HTTP response error is a 5xx response.** The request was acceptable, but
> the server failed while generating the response — a crash, an unhandled
> exception, a database that is down, a timeout. The client did nothing wrong
> and may succeed by simply retrying; a **developer or operator** must fix it.

### 7.2 The codes a student must know

**4xx — request errors**

| Code | Phrase | Typical cause |
|---|---|---|
| 400 | Bad Request | malformed syntax, missing/invalid parameter |
| 401 | Unauthorized | not authenticated — log in first (it really means "unauthenticated") |
| 403 | Forbidden | authenticated, but not allowed to access this |
| 404 | Not Found | no resource at that URL (wrong path, deleted page) |
| 405 | Method Not Allowed | URL exists but not for GET/POST; response includes `Allow:` |
| 408 | Request Timeout | client took too long to send the request |
| 409 | Conflict | clashes with current state (duplicate username) |
| 413 | Content Too Large | upload bigger than the server limit |
| 415 | Unsupported Media Type | wrong `Content-Type` |
| 422 | Unprocessable Content | syntax fine, values fail validation |
| 429 | Too Many Requests | rate limited; see `Retry-After` |

**5xx — response errors**

| Code | Phrase | Typical cause |
|---|---|---|
| 500 | Internal Server Error | the catch-all: PHP fatal error, exception, bad config |
| 501 | Not Implemented | method/feature not supported at all |
| 502 | Bad Gateway | proxy got a garbage answer from the app behind it |
| 503 | Service Unavailable | overloaded or maintenance; see `Retry-After` |
| 504 | Gateway Timeout | proxy waited too long for the app |

### 7.3 The mistakes students make (say these in class)

1. **"404 is a server error."** No — the server is fine, the *URL* is wrong.
   It is a client error even when a server-generated page renders it.
2. **"401 and 403 are the same."** 401 = *I don't know who you are* (log in).
   403 = *I know who you are and you still can't* (no permission).
3. **"Wrong password should return 401."** Not for an HTML form. The form page
   was delivered successfully (`200 OK`); the *business result* was "invalid".
   401 is for APIs where the client must supply credentials via
   `Authorization:` and the request is rejected before it is processed.
4. **"A redirect is an error."** 3xx is not an error. Our successful login
   returns `302 Found`.
5. **"If the page loads, the status must be 200."** Custom error pages are
   *rendered* by the server but still sent with 404/500. If a 404 page returns
   200, search engines index it and monitoring never notices the broken link.
6. **"5xx means the user typed something wrong."** Then it would be 4xx. 5xx is
   always "our side failed".

### 7.4 How to see it in the browser

* DevTools → **Network** tab → reload → click a request → **Status** column and
  the **Headers** panel show the status line, response headers and body.
* `curl -I http://localhost:8000/nothing.php` shows only the headers (the `-I`
  means HEAD).
* In PHP, the status is set with `http_response_code(404);` — visible in
  `404.php` and in `demo/status.php`.

### 7.5 Where each one happens in *this* project

| Event | Status | Why |
|---|---|---|
| Open the login page | `200 OK` | normal delivery |
| Wrong password | `200 OK` + red message | form page delivered; credentials rejected in the body |
| Empty fields | `200 OK` + message | same |
| Correct password | `302 Found` → `dashboard.php` | tell the browser where to go next |
| Dashboard while logged out | `302 Found` → `index.php` | guard redirects |
| Unknown URL | `404 Not Found` (`404.php`) | web server found nothing |
| `includes/config.php` opened directly | `404 Not Found` | library guard |
| `demo/status.php?code=500` | `500 Internal Server Error` | deliberate experiment |
| A fatal error in a page | `500 Internal Server Error` | PHP crashed while building the response |

---

## 8. If your sheet also asks "What is a web framework?"

A **framework** is a ready-made skeleton for an application: routing, request and
response objects, sessions, database access, templating, validation, testing —
already written and tested by other people. You add the parts that are unique to
your problem. Famous PHP frameworks: **Laravel**, **Symfony**, **CodeIgniter**,
**Slim**.

Why this assignment uses **plain PHP** instead:

* Every mechanism is visible. A framework's `Auth::attempt($credentials)` hides
  the hash comparison, the session regeneration and the redirect — great in
  production, useless for a first lesson.
* You learn what the framework is *doing for you*, which is exactly what makes a
  framework debuggable later.

A one-sentence answer for the exam:

> A web framework is a reusable library that provides the common structure of a
> web application — routing, HTTP handling, sessions, database layer, templates
> and security helpers — so developers write only application-specific code.
> Laravel and Symfony are the best known in PHP.

---

## 9. Classroom demo script (30 minutes)

| Min | Do this | Say this |
|---|---|---|
| 0–3 | Open `index.php`. View Source. | "The browser received only HTML. My `<?php` lines are gone — they ran on the server." |
| 3–6 | Submit a **wrong** password. | "Same URL, same page, but look at the red box *below* the form. The server re-rendered the page and kept my typed ID." |
| 6–8 | Submit an **empty** form (DevTools → delete the `required` attribute first). | "The browser check disappeared when we edited the HTML — the server check did not. Server-side validation is the real one." |
| 8–12 | Log in correctly. Watch DevTools → Network. | "Two requests: a `302` with `Location: dashboard.php`, then a `200` for the dashboard. That is what 'a different page' means in HTTP." |
| 12–15 | Press **F5** on the dashboard. | "No 'resubmit form' warning — because we redirected after POST." |
| 15–18 | DevTools → Application → Cookies → delete `PHPSESSID` → F5. | "The server has no idea who I am any more. The session lives on the *server*; the cookie is only the ticket." |
| 18–21 | Open `dashboard.php` in a private window. | "302 back to the login page: `require_login()` ran before a single line of HTML." |
| 21–24 | `php -r "echo password_hash('admin123', PASSWORD_DEFAULT);"` twice. | "Two different results for the same password — that it is why you must use `password_verify()`, never `==`." |
| 24–30 | Open `http-errors.php`, click 404, 401, 500. | "4xx: the request was wrong. 5xx: the server failed to build the response. Open the Network tab and see it happen." |

---

## 10. Viva / exam questions with answers

**Q: Why does the login page use POST and not GET?**
GET puts the values in the URL (`index.php?password=...`), where they are saved in
browser history and the server's access log. POST sends them in the request body.
POST is also the method for requests that change state.

**Q: Why did you redirect after a successful login instead of showing the dashboard directly?**
So that the dashboard is a separate GET request. Pressing F5 then reloads the
page instead of re-submitting the password (the "confirm form resubmission"
dialog), and the URL in the address bar reflects where the user actually is.

**Q: Why not redirect after a failed login?**
The error messages live in `$errors`, which exists only for the current request.
A redirect starts a new request and loses them. Re-rendering keeps the messages,
the typed ID and the same URL.

**Q: What is `$_POST`?**
A superglobal associative array that PHP fills with the body of a POST request
using `application/x-www-form-urlencoded` (or multipart) encoding, keyed by the
inputs' `name` attributes.

**Q: Why is the password not visible in `config.php`?**
It is stored as a bcrypt hash. Hashing is one-way; you verify a guess with
`password_verify()`. If the file leaks, the attacker still has to brute-force
every password.

**Q: What does `session_start()` do?**
Creates or resumes a session: reads the `PHPSESSID` cookie, loads the matching
session file into `$_SESSION` (or creates a new ID and sends the cookie). It must
run before any output because it sends headers.

**Q: Where is the session data stored — browser or server?**
Server. `session.save_path` (usually `/tmp`), one file per session ID. The
browser only receives the ID.

**Q: What happens if a user copies their cookie to another computer?**
They are logged in there too — the session is identified by the ID alone. That is
why HTTPS and `HttpOnly` matter, and why sensitive apps re-check password or
expire sessions.

**Q: Difference between a cookie and a session?**
A cookie is data stored **in the browser** and sent with every request; a session
is data stored **on the server**, referenced by a cookie that only holds an ID.
Sessions are safer for sensitive data; cookies are the transport.

**Q: Why `htmlspecialchars()` on output?**
To neutralise HTML/JavaScript that came from a user, preventing stored or
reflected XSS.

**Q: What is CSRF and how did you prevent it?**
A foreign site makes the visitor's browser submit our form with their cookies
attached. We put a random per-session token in the form and in the session, and
compare them on POST; a foreign site cannot read the token.

**Q: What is session fixation?**
Forcing a known session ID on the victim. Fixed by `session_regenerate_id(true)`
at login.

**Q: 404 — request error or response error?**
Request error (4xx): the client asked for a resource that does not exist.

**Q: 500 — whose fault?**
The server's. The request was valid; the application failed while building the
response.

**Q: Why does the wrong-password response say 200 OK?**
Because the *HTTP exchange* succeeded — the form page was delivered. The failure
is at the application level and is shown in the page body. Status codes describe
the HTTP transaction, not business logic.

---

## 11. Homework and extensions

**Beginner**
1. Add a third user `priya` / `priya123` — generate the hash with `php hash.php`.
2. Show the typed login ID back in the field after a failure (already done) and
   *the number of characters* typed in the password field.
3. Change the error messages to your college's wording, and add a "show password"
   checkbox (a few lines of JavaScript — no PHP needed).
4. Make login IDs case-insensitive — read `find_user()` and explain in one line
   why it already is.

**Intermediate**
5. Lock the account for 30 seconds after 3 failed attempts, using
   `$_SESSION['attempts']`. Discuss why this must be per-IP or per-account in
   production (sessions are trivially reset by deleting a cookie).
6. Add "remember me" with a random token stored in a `remember_tokens` table —
   and never with the password in the cookie.
7. Add a `role` field (`admin` / `student`) and a page only admins may open;
   answer with `403 Forbidden` instead of a redirect.
8. Add a proper 404 test: `curl -I http://localhost:8000/xyz` and check the status
   line, then make `404.php` return 200 by accident and explain what breaks.

**Advanced**
9. Replace the array with MySQL (appendix below) using PDO prepared statements,
   then try the injection string `' OR '1'='1` in the login box and watch it fail
   safely.
10. Add `password_needs_rehash()` so old hashes upgrade silently at login.
11. Add HTTPS locally (`php -S` with a self-signed certificate is awkward — use
    XAMPP's SSL or `mkcert`) and turn on the `secure` cookie flag.
12. Write three PHPUnit tests: valid login, invalid password, dashboard access
    without a session.

---

## 12. Appendix A — the same app with MySQL

Only `config.php` changes; every other file stays identical. That is the reward
for putting data access in one function.

**Table**

```sql
CREATE TABLE users (
    id       INT AUTO_INCREMENT PRIMARY KEY,
    login_id VARCHAR(50)  NOT NULL UNIQUE,
    name     VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role     ENUM('admin','student') NOT NULL DEFAULT 'student'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Connection + lookup (`includes/config.php`)**

```php
function db(): PDO
{
    static $pdo = null;               // one connection per request
    if ($pdo === null) {
        $pdo = new PDO(
            'mysql:host=localhost;dbname=college;charset=utf8mb4',
            'root',
            '',
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,   // real prepared statements
            ]
        );
    }
    return $pdo;
}

function find_user(string $loginId): ?array
{
    $stmt = db()->prepare('SELECT login_id, name, password FROM users WHERE login_id = ?');
    $stmt->execute([strtolower(trim($loginId))]);
    $row = $stmt->fetch();
    return $row === false ? null : $row;
}
```

**Why `?` and not string concatenation**

```php
// VULNERABLE — the input becomes part of the SQL text
$pdo->query("SELECT * FROM users WHERE login_id = '$loginId'");
// login_id = "' OR '1'='1"  →  every row returned  →  logged in

// SAFE — the value is sent separately and can never be parsed as SQL
$stmt = $pdo->prepare('SELECT * FROM users WHERE login_id = ?');
$stmt->execute([$loginId]);
```

`auth.php` needs no change at all: it already calls `find_user()` and
`password_verify()`. In production you would also: store the DB password in an
environment variable, not in the file; use a user with only `SELECT` rights on
that table; and log failures without logging the password.

---

## Appendix B — the absolute minimum version (first 10 minutes of class)

Students see a 40-line login first, then the same idea grown into this project.
Save as `login.php` in a folder and open it — nothing else needed.

```php
<?php
// login.php — the simplest possible login. NOT safe, but it shows the idea.

$users = [
    'admin'   => 'admin123',
    'student' => 'student123',
];

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['login_id'] ?? '';
    $pw = $_POST['password'] ?? '';

    // Does the login ID exist AND is the password equal?
    if (isset($users[$id]) && $users[$id] === $pw) {
        header('Location: home.php');
        exit;
    }
    $error = 'Invalid login ID or password.';
}
?>
<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>Login</title></head>
<body>
    <h1>Login</h1>

    <form method="post">
        <label>Login ID <input type="text" name="login_id"></label><br><br>
        <label>Password <input type="password" name="password"></label><br><br>
        <button type="submit">Log in</button>
    </form>

    <?php if ($error !== ''): ?>
        <p style="color:red"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
</body>
</html>
```

`home.php` is then a page that must check the session — or, in the truly minimal
version, just a static "Welcome" page that anybody could open by guessing the URL.

**What this version is missing, and why each gap matters** — this list *is* the
lesson, and every point is implemented in the real app:

| Missing | Consequence | Fixed in |
|---|---|---|
| no session | `home.php` is readable by anyone who types the URL | §5, `auth.php` |
| plain-text passwords | a stolen file reveals every password | §6.1 |
| no escaping | a crafted login ID injects JavaScript | §6.2 |
| no CSRF token | another site can submit the form as you | §6.3 |
| no `session_regenerate_id()` | session fixation | §6.4 |
| no server-side validation | empty fields reach the comparison logic | §3.3 |
| no `hash_equals` / vague errors | leaks which usernames exist | §3.2 |

Show the minimal version, make it work, then say: *"now let's break it"* — log in
from a second browser tab by typing `home.php` directly. The silence in the room
is the best introduction to sessions you will ever get.

---

### One-page summary

* PHP runs on the server and sends HTML. The browser never sees the code.
* A login is one form page handling two requests: GET draws it, POST decides.
* **Failure → re-render the same page** so the errors (below the form) survive.
* **Success → 302 redirect** to a different, protected page.
* State between requests lives in `$_SESSION`; the browser only holds the ID.
* Hash passwords, escape output, verify a CSRF token, regenerate the session ID.
* 4xx = the request was wrong (client). 5xx = the server failed to answer
  (server). 3xx is not an error. A wrong password in an HTML form is `200 OK`
  with a message in the body.
