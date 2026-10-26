# Teaching Guide — the simple login app

For a first PHP class. Five small files, ~120 lines of PHP, no framework, no
database. Read this once and you can teach it; every line of code is explained
in the order you would say it out loud.

**Contents**

1. [Before class — what PHP is](#1-before-class--what-php-is)
2. [The five files](#2-the-five-files)
3. [The story of one login](#3-the-story-of-one-login)
4. [`login.php` line by line](#4-loginphp-line-by-line)
5. [`home.php` — the lock on the door](#5-homephp--the-lock-on-the-door)
6. [`logout.php` and `index.php`](#6-logoutphp-and-indexphp)
7. [The four PHP ideas, in one page](#7-the-four-php-ideas-in-one-page)
8. [Why the error appears *below* the form](#8-why-the-error-appears-below-the-form)
9. [Mistakes your students will make](#9-mistakes-your-students-will-make)
10. [Blackboard summary](#10-blackboard-summary)
11. [20-minute demo script](#11-20-minute-demo-script)
12. [Viva questions with answers](#12-viva-questions-with-answers)
13. [Homework](#13-homework)
14. [Question 2: HTTP request error vs response error](#14-question-2-http-request-error-vs-response-error)
15. [What this version leaves out — and where it is](#15-what-this-version-leaves-out--and-where-it-is)
16. [The complete files, with line numbers](#16-the-complete-files-with-line-numbers)

---

## 1. Before class — what PHP is

Say this first, before any code:

> **PHP is a program that runs on the server and writes text — usually HTML.
> The browser never sees your PHP code. It only receives what PHP printed.**

Then prove it in 10 seconds:

```bash
cd simple
php -S localhost:8000
```

Open <http://localhost:8000>, right-click → **View Page Source**. The HTML is
there; `<?php` is gone. That single demonstration prevents a week of confusion.

Three sentences worth repeating all lesson:

* PHP runs again, from scratch, on **every request**. Variables from the last
  click are gone. That is exactly why we need sessions (section 7).
* `<?php ... ?>` = "run this on the server". Everything outside the tags is
  plain HTML, sent to the browser untouched.
* `<?= $x ?>` is short for `<?php echo $x; ?>` — see it as "print this here".

**Logins:** `admin / 1234` and `student / abc`.

---

## 2. The five files

| File | Lines | Job |
|---|---|---|
| `login.php` | 71 | show the form, check the password, show the error |
| `home.php` | 36 | the "different page", only for logged-in users |
| `logout.php` | 10 | forget the user |
| `index.php` | 5 | so opening the folder shows the login page |
| `style.css` | 93 | colours only — **no PHP**, and nothing here affects the logic |

Tell the class: *"The program is essentially one file — `login.php`. Everything
else is small."* That removes the fear of a 5-file project.

---

## 3. The story of one login

The login page is **one file visited three times**. This table is the whole
architecture; draw it on the board.

| Step | What the user does | What the browser sends | Method | What PHP does | Reply |
|---|---|---|---|---|---|
| 1 | Opens the page | `GET /login.php` | GET | the `POST` block is skipped | `200` + empty form |
| 2 | Types wrong details, clicks Log in | `POST /login.php` with `login_id=admin&password=wrong` | POST | the password does not match, so `$error` is set | `200` + the **same** page, with a red message below the form |
| 3 | Types the right details | `POST /login.php` with `login_id=admin&password=1234` | POST | the password matches, so save the session and redirect | `302` + `Location: home.php` |
| 4 | (browser obeys the redirect) | `GET /home.php` (+ cookie) | GET | session says "admin is logged in" | `200` + the welcome page |

Two things to point at:

* In step 2 the page looks like a new page, but **it is not**. Same URL, same
  file, redrawn with a message in it. Nothing was saved anywhere — the message
  only exists for that one request.
* In step 3 the server never sends the home page. It says *"go and ask for
  `home.php`"* (`302`), and the browser makes a second request — which is why
  the address bar changes and the **Back** button behaves.

---

## 4. `login.php` line by line

Open the file and follow the numbers 1 → 2 → 3 that are already in the comments.

### `session_start();`

> "Start remembering things about this visitor."

PHP gives the browser a cookie called `PHPSESSID` (a long random string) and
keeps a matching file on the server. Later, `$_SESSION` is that file, seen as an
array. **Without this line, `$_SESSION['login_id'] = $id` would not work and the
user would never stay logged in.**

It must run **before any HTML**. If anything — even a blank line — is printed
first, PHP has already sent the headers and you get:

```
Warning: session_start(): Session cannot be started after headers have already been sent
```

### The `$users` array

```php
$users = [
    'admin'   => '1234',
    'student' => 'abc',
];
```

Say this clearly: *"This is standing in for a MySQL table. For learning, an array
is enough — the logic is identical."*

`$users['admin']` is `'1234'`. The login ID is the **key**, the password is the
**value**. Point out the shape: `=>` means "has the value".

> ⚠️ Plain-text passwords are wrong in real life. We use them here **so you can
> read them**. Section 15 shows the safe version, and it is one function call.

### `$error = '';`

An empty string means "no error yet". The page can then ask, at the bottom,
`if ($error !== '')` — "is there anything to show?". Starting it as `''` avoids
an "undefined variable" warning on the first visit.

### `if ($_SERVER['REQUEST_METHOD'] === 'POST')`

> "Is this request a form submission, or just someone opening the page?"

`$_SERVER` is a **superglobal** — a built-in array PHP fills for every request.
It contains `REQUEST_METHOD`, `REQUEST_URI`, `HTTP_USER_AGENT` and more.

* Step 1 (opening the page) is a **GET**. The whole block is skipped. The form
  appears empty.
* Steps 2 and 3 (clicking Log in) are **POST**. The block runs.

The same file therefore behaves as two different pages. That is the trick that
keeps this project to one file.

### `$id = $_POST['login_id'] ?? '';`

* `$_POST` holds the values the form sent, keyed by each input's `name`
  attribute. `name="login_id"` → `$_POST['login_id']`.
* `?? ''` means "if the key does not exist, use an empty string". A user can
  delete an input in DevTools before submitting; without `??`, PHP prints
  *Undefined array key* and the page looks broken.
* `$pw` is the same for the password field.

**The most important sentence of the lesson:**

> The browser's checks (`required`, `maxlength`) are **convenience**, not
> security. Anyone can edit the HTML or skip it with `curl`. Checks that matter
> happen here, on the server.

### The decision

```php
if (isset($users[$id]) && $users[$id] === $pw) {
```

Read it aloud in English: *"If this login ID exists in the array **and** its
password is exactly equal to what was typed, then…"*

* `isset()` first — if the ID is not in the array, `$users['nobody']` would be a
  warning. Checking existence first makes the `&&` short-circuit safely.
* `===` (three equals) means *equal and the same type*. Two equals (`==`) would
  accept `0 == 'abc'`-style surprises. Teach `===` from day one.
* Order matters: `&&` evaluates left to right and stops at the first `false`, so
  the password is never compared for an unknown ID.

### Success: three lines, each essential

```php
$_SESSION['login_id'] = $id;    // 1. remember who logged in
header('Location: home.php');   // 2. tell the browser where to go
exit;                           // 3. stop this page right here
```

1. **`$_SESSION`** — server-side memory. The browser never sees this; it only
   holds the session ID in its cookie.
2. **`header('Location: ...')`** — writes into the *response headers*: "do not
   show this page, request `home.php` instead". PHP sends `302 Found` by
   default.
3. **`exit;`** — without it PHP keeps running and prints the rest of the file
   after the redirect. Symptom: *"the page half loads and then jumps"*, or a
   `headers already sent` warning. **Every redirect gets `exit`.**

### Failure: no redirect

```php
} else {
    $error = 'Invalid login ID or password.';
}
```

Notice what does **not** happen: no `header()`, no `exit`. PHP simply falls
through to the HTML at the bottom and draws the page again — this time with a
message. Section 8 explains why this is the correct design for this assignment.

### The HTML part

```php
<form method="post">
    <input type="text" name="login_id" value="<?= htmlspecialchars($id ?? '') ?>">
    <input type="password" name="password">
    <button type="submit">Log in</button>
</form>
```

* `method="post"` keeps the password out of the address bar and out of the
  server's access log. (With GET it would appear as
  `login.php?login_id=admin&password=1234` — visible in history and logs.)
* No `action="..."` means "submit to this same URL" — which is why one file can
  do everything.
* `value="<?= ... ?>"` puts back what the user typed, so they do not have to
  retype the ID after a wrong password. `$id ?? ''` because `$id` does not exist
  on the first visit.
* `htmlspecialchars()` makes the value safe before printing it (section 7).

```php
<?php if ($error !== ''): ?>
    <!-- the error message appears BELOW the form -->
    <p class="error"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>
```

* This block is **below the form in the HTML**, which is exactly what the
  assignment asks for.
* `if (...): ... endif;` is the long form of `if { }`. It reads better inside
  HTML.
* Nothing is printed when there is no error — the box simply is not created.

---

## 5. `home.php` — the lock on the door

```php
session_start();

if (!isset($_SESSION['login_id'])) {
    header('Location: login.php');
    exit;
}

$id = $_SESSION['login_id'];
```

Read it as: *"Open the memory. Do I have a `login_id` in it? If not, back to the
login page. If yes, continue."*

Why this matters — ask the class:

> **What happens if you just type `localhost:8000/home.php` into a fresh
> browser?**
>
> `$_SESSION['login_id']` was never set, so the condition is true, and PHP sends
> `302` back to `login.php`. The welcome page is never built.

This is called **protecting a page**. A page that trusts the user is not a
login system; it is a page with a login form in front of it.

Also explain what is *not* in the session: the password. There is no reason to
remember it, so we don't.

> On a real site this check would be in a shared file (`auth.php`) included by
> every protected page. With one protected page, writing it inline is clearer.

---

## 6. `logout.php` and `index.php`

**`logout.php`**

```php
session_start();     // open the session
session_destroy();   // throw it away
header('Location: login.php');
exit;
```

`session_destroy()` deletes the server-side data, so the session ID in the
cookie becomes meaningless. The file prints no HTML at all — it only changes
state and redirects.

Ask: *"Why `session_start()` in a logout file?"* Because you must *open* the
session before you can destroy it; PHP has to know which session file to delete.

**`index.php`**

```php
header('Location: login.php');
exit;
```

Five lines so that `http://localhost:8000/` works without the student having to
type `/login.php`. A folder's `index.php` is what a web server serves by default.

> Small warning to mention: `session_destroy()` alone leaves the cookie in the
> browser. The full version in `../login-app/logout.php` also deletes the cookie.
> It works here because the session ID no longer matches anything on the server.

---

## 7. The four PHP ideas, in one page

| Idea | Where it appears | One-line meaning |
|---|---|---|
| `$_POST` | `$_POST['login_id']` | the values the user typed into the form |
| `$_SESSION` | `$_SESSION['login_id'] = $id` | the server's memory about this visitor |
| `header('Location: ...')` + `exit` | after a correct password | "go to another page" — must be followed by `exit` |
| `htmlspecialchars()` | every printed value | make text safe before showing it |

### `$_POST` vs `$_GET`

| | GET | POST |
|---|---|---|
| Values travel in | the URL | the request body |
| Visible in the address bar | yes | no |
| Good for | searching, links, filters | logins, anything secret, anything that changes data |
| Filled by the form's | `method="get"` (the default!) | `method="post"` |

That last row is why `method="post"` is written explicitly. Forget it and the
password appears in the URL.

### Why sessions exist

HTTP has no memory: request #2 knows nothing about request #1. So after a
correct password, PHP writes `login_id` into a file on the server, gives the
browser a cookie holding only the **file's name** (`PHPSESSID`), and on every
later request the browser sends that cookie back.

> **The browser holds the key, not the content.** Editing the cookie does not
> make you an admin — there is nothing in it except a meaningless random ID.

Show this: DevTools → **Application → Cookies → `http://localhost:8000`** →
`PHPSESSID`. Then delete it and press F5 — the user is logged out instantly.
That single experiment teaches more than any diagram.

### Why `htmlspecialchars()`

If the user types this as their login ID:

```
<script>alert('hacked')</script>
```

and we print it raw with `<?= $id ?>`, the browser **runs** it. With
`htmlspecialchars()`, `<` becomes `&lt;`, so the browser shows the text instead
of executing it. Same output for honest users, harmless for attackers.

> Rule for the class: **anything that came from a user is escaped before it is
> printed.** No exceptions.

---

## 8. Why the error appears *below* the form

The assignment says: *"else display the error message below the login page."*
Here is why the code is written the way it is, and what the wrong version looks
like.

**What we do (correct):** on a wrong password, keep going and redraw the same
page. `$error` still exists, so the message can be printed underneath the form.

```php
} else {
    $error = 'Invalid login ID or password.';
}
// ... HTML ...
<?php if ($error !== ''): ?>
    <p class="error"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>
```

**The tempting-but-wrong version:**

```php
} else {
    header('Location: login.php?error=wrong');   // ❌
    exit;
}
```

It *looks* like it works, because the login page appears again. But:

* `$error` is destroyed — a new request starts with empty variables. You have to
  pass the message in the URL and print `$_GET['error']`, which anyone can edit
  (`?error=You are stupid` on your page).
* The user's typed login ID is lost, so they must type it again.
* A redirect is for *"the thing you asked for is elsewhere"*. A wrong password is
  not elsewhere — the same page just has something to say.

**Rule of thumb for the class:**

> **Failure → redraw the same page.** **Success → redirect to a different page.**
> Success redirects so that pressing F5 reloads the dashboard instead of asking
> *"resend the form?"*.

---

## 9. Mistakes your students will make

Keep this table visible. These are the actual errors beginners hit.

| What they will write | What happens | The fix |
|---|---|---|
| `if ($users[$id] == $pw)` with no `isset()` | `Warning: Undefined array key` for an unknown ID | check `isset()` first |
| `header(...)` without `exit;` | the page half-renders, then redirects; or "headers already sent" | always `exit;` after a redirect |
| a blank line or space before the first `<?php` | `session_start(): Session cannot be started after headers have already been sent` (or `Cannot modify header information` if a `header()` call comes first) | the `<?php` must be the very first character of the file |
| `$_POST['login_id']` when the input has `name="username"` | `Undefined array key "login_id"` | the `name` attribute must match the key exactly |
| `session_start()` missing on `home.php` | `$_SESSION` is empty → always redirected to login | start the session on **every** page that uses it |
| comparing with `=` instead of `===` | the password gets *overwritten*, and the check is always true | `===` compares |
| printing `<?= $id ?>` instead of `<?= htmlspecialchars($id) ?>` | a crafted login ID runs JavaScript | always escape |
| `action="login.php"` plus the check at the top of the file | works, but students get confused about which page runs | leave `action` empty |
| `php -S localhost:8000` started in the wrong folder | `404 Not Found` for `login.php` | start it **inside** `simple/`, or use `-t simple` |
| editing `login.php` but the browser shows the old page | browser cache | Ctrl+F5 |

Two more habits worth insisting on:

* Errors on screen are **for you, not for users**. In production you turn
  `display_errors` off and read the log.
* Always test by typing the URL of the protected page directly. Beginner logins
  break there first.

---

## 10. Blackboard summary

Draw this, not the code:

```
                 GET  (open the page)
                        │
                        ▼
              ┌──────────────────┐
              │    login.php     │◄──────────┐
              │                  │           │
              │  form is shown   │           │
              └────────┬─────────┘           │
                       │ POST (Log in)       │
                       ▼                     │
                password correct?            │
                  │           │              │
               no │           │ yes          │
                  ▼           ▼              │
        $error set     $_SESSION['login_id'] │
        redraw page    header(Location:      │
        (message       home.php) + exit      │
         below form)         │               │
                  │          ▼               │
                  │     ┌──────────┐         │
                  └────►│ home.php │         │
                        │  locked  │─────────┘
                        └──────────┘  not logged in → back to login
```

And the five-line version of the whole idea:

```php
session_start();                              // start remembering
if ($_SERVER['REQUEST_METHOD'] === 'POST') {  // was the form sent?
    if ($users[$id] === $pw) {                // right password?
        $_SESSION['login_id'] = $id;          // remember the user
        header('Location: home.php'); exit;   // go to the other page
    }
}
```

---

## 11. 20-minute demo script

| Min | Do | Say |
|---|---|---|
| 0–2 | Open the page, **View Source** | "No PHP here. The browser only got HTML — PHP already ran on the server." |
| 2–4 | Show the 5 files | "The whole program is one file, `login.php`. The rest are 5–35 lines." |
| 4–7 | Delete the `name="login_id"` from the input in DevTools, submit | "The login failed — because `$_POST` is keyed by the `name` attribute. Nothing was sent, so `?? ''` made it an empty string and the check failed politely. **Without** `?? ''` the page would print *Undefined array key* instead." |
| 7–10 | Submit a **wrong** password | "Same URL, but a red message below the form — and my login ID is still there. Nothing was saved; the page was just drawn again." |
| 10–12 | Submit the **correct** password with DevTools → Network open | "Two requests: `302` to `home.php`, then `200` for the page. The server never sent the dashboard — it told the browser where to ask." |
| 12–14 | Press **F5** on `home.php` | "No 'resubmit form' warning. That is why we redirect after a successful POST." |
| 14–16 | DevTools → Application → Cookies → delete `PHPSESSID` → F5 | "Logged out. The password is on the server; the browser only had a ticket." |
| 16–18 | Open `home.php` in a private window | "`302` back to login — the page protects itself. Never trust the address bar." |
| 18–20 | Show `../login-app/index.php` next to `login.php` | "Same idea, professional structure: hashed passwords, CSRF, escaping everywhere. Everything you just learned is in there, spread over files." |

---

## 12. Viva questions with answers

**Q: Why is the form submitted with POST and not GET?**
GET puts the values in the URL (`login.php?password=1234`), where they are saved
in browser history and server logs. POST sends them in the request body.

**Q: Which array holds the typed values?**
`$_POST`, keyed by the `name` attribute of each input.

**Q: Why `session_start()`?**
To create or resume the session so `$_SESSION` can be used. Without it, the user
is not remembered and stays logged out.

**Q: Where is the user's data stored after login — browser or server?**
On the server, in a session file. The browser only receives a cookie called
`PHPSESSID` containing the session ID.

**Q: What does `header('Location: home.php')` do, and why `exit` after it?**
It adds a redirect instruction to the HTTP response headers. `exit` stops the
script so the rest of the page is not printed after the redirect.

**Q: Why not redirect when the password is wrong?**
Because the error message lives in a variable that would be lost in a new
request — and the requirement is to show the message **below the form** on the
same page.

**Q: What does `isset()` protect against?**
An "Undefined array key" warning when the login ID is not in the `$users` array.

**Q: Difference between `==` and `===`?**
`===` compares value **and** type; `==` converts types first, which can make
different values look equal.

**Q: Why `htmlspecialchars()`?**
To stop user input from becoming HTML or JavaScript in the page (XSS). It turns
`<` into `&lt;` so the browser shows text instead of running it.

**Q: What happens if you open `home.php` without logging in?**
`$_SESSION['login_id']` is not set, so the page redirects to `login.php` and the
welcome content is never generated.

**Q: Why is `index.php` only 5 lines?**
So the folder's default page ( `/` ) sends the visitor to the login page.

**Q: Is storing the password in the array safe?**
No. It is readable by anyone who sees the file. Real applications store
`password_hash()` values and check them with `password_verify()` — see
`../login-app/includes/config.php`.

**Q: How does the browser know it is the same user on the next request?**
The `PHPSESSID` cookie is sent automatically with every request to the same
site; PHP uses it to find the right session file.

---

## 13. Homework

**Easy**
1. Change the password of `admin` to `12345` and log in with the new one.
2. Add a third user: `priya / priya123`.
3. Change the error text to whatever your college would say.
4. Show how many characters the user typed in the password field.

**Medium**
5. Save the typed login ID in `$_SESSION` on failure instead of using `$id`, and
   explain in one line why the current version is simpler.
6. Show a welcome message on `home.php` with the login time
   (`$_SESSION['login_time'] = date('d M Y H:i');`).
7. Add a `role` to each user and print "Admin" or "Student" on `home.php`.
8. Make login IDs case-insensitive with `strtolower()` and test `ADMIN`.

**Harder**
9. Change the login to use `password_hash()` / `password_verify()` — generate
   the hash with `php -r "echo password_hash('1234', PASSWORD_DEFAULT);"`.
10. Move the "is this user logged in?" check into a file called `auth.php` and
    include it in `home.php`.
11. Add a "3 failed attempts" counter in the session. Discuss why deleting the
    cookie resets it (and what real sites do about that).

---

## 14. Question 2: HTTP request error vs response error

The assignment also asks this. The short answer for the class:

Every HTTP exchange is one **request** from the browser and one **response** from
the server. The response starts with a three-digit **status code**:

| Range | Meaning |
|---|---|
| 2xx | success — `200 OK` |
| 3xx | the thing is elsewhere — `302 Found` (our successful login) |
| **4xx** | **request error — the client is wrong** |
| **5xx** | **response error — the server is wrong** |

> **Request error (4xx):** the client sent something the server cannot accept —
> a URL that does not exist (`404`), missing credentials (`401`), no permission
> (`403`), wrong method (`405`), too large an upload (`413`), too many requests
> (`429`). The server is healthy; the **client** must change something.
>
> **Response error (5xx):** the request was fine, but the server failed while
> building the answer — a PHP crash (`500`), a dead database, a timeout
> (`504`). The client did nothing wrong; a **developer** must fix it.

Two corrections students always need:

* A **wrong password** here returns `200 OK`, not `401` — the form page was
  delivered successfully; the failure is shown *in the page*. 401 is for APIs
  that reject the request outright.
* A **redirect (`302`) is not an error**. Our successful login uses it.

Where to see them in this project:

| Action | Status |
|---|---|
| Open the login page | `200 OK` |
| Wrong password | `200 OK` + message in the page |
| Correct password | `302 Found` → `home.php` |
| `home.php` while logged out | `302 Found` → `login.php` |
| A URL that does not exist | `404 Not Found` |
| A bug in the PHP | `500 Internal Server Error` |

To demo it live, open **`../login-app/http-errors.php`** — clickable buttons that
return real 401/404/500 responses while the class watches DevTools → Network.
The full written answer is in `../login-app/TEACHING-GUIDE.md` section 7.

---

## 15. What this version leaves out — and where it is

Say this out loud, so nobody thinks plain text is acceptable practice:

| Missing here | Why it matters | Same idea, done properly |
|---|---|---|
| password hashing | a leaked file reveals every password | `../login-app/includes/config.php` (`password_hash`) |
| escaping everything | a crafted login ID runs JavaScript | `../login-app/includes/auth.php` (`e()`) |
| CSRF token | another site can submit the form for the user | `auth.php` (`csrf_token`, `csrf_valid`) |
| new session ID at login | session fixation | `auth.php` (`session_regenerate_id`) |
| cookie deletion at logout | the cookie outlives the login | `../login-app/logout.php` |
| SQL safety | the moment a database is added | MySQL appendix in the full guide |
| structure (`includes/`) | one file cannot hold a real app | `../login-app/` |

Suggested line to close the lesson:

> "Everything you have seen today — one file, a `$_POST` check, a session, a
> redirect, and a message drawn under the form — is the skeleton of every login
> page in the world. The full version in `login-app` is the same skeleton with
> armour on."

---

## 16. The complete files, with line numbers

Project the file, keep this page open, and point. Every number below is the real
line number in the file, so you can say *"look at line 30"* and the whole class
looks at the same place.

### 16.1 `login.php` — the whole file (71 lines)

```php
 1 | <?php
 2 | /* =========================================================================
 3 |    login.php  —  THE SIMPLE VERSION
 4 |    -------------------------------------------------------------------------
 5 |    Log in with:   admin / 1234        student / abc
 6 |    This one file does 3 things, in this order:
 7 | 
 8 |        1. LOOK   — did the form arrive? read the ID and password
 9 |        2. CHECK  — are they correct?  yes -> home.php   no -> $error
10 |        3. SHOW   — print the form, then the error under it
11 |    ========================================================================= */
12 | 
13 | session_start();        // needed so we can remember the user later
14 | 
15 | // The "database". A real app would use MySQL; for learning an array is enough.
16 | $users = [
17 |     'admin'   => '1234',
18 |     'student' => 'abc',
19 | ];
20 | 
21 | $error = '';            // empty = no problem (yet)
22 | 
23 | // ---- 1. LOOK ------------------------------------------------------------
24 | if ($_SERVER['REQUEST_METHOD'] === 'POST') {
25 | 
26 |     $id = $_POST['login_id'] ?? '';     // what the user typed
27 |     $pw = $_POST['password'] ?? '';
28 | 
29 |     // ---- 2. CHECK -------------------------------------------------------
30 |     if (isset($users[$id]) && $users[$id] === $pw) {
31 | 
32 |         $_SESSION['login_id'] = $id;    // remember this user
33 |         header('Location: home.php');   // send them to the other page
34 |         exit;                           // stop this page
35 | 
36 |     } else {
37 |         $error = 'Invalid login ID or password.';
38 |     }
39 | }
40 | ?>
41 | <!DOCTYPE html>
42 | <html>
43 | <head>
44 |     <title>Login</title>
45 |     <link rel="stylesheet" href="style.css">
46 | </head>
47 | <body>
48 |     <div class="box">
49 | 
50 |         <h1>Login</h1>
51 | 
52 |         <!-- ---- 3. SHOW ------------------------------------------------- -->
53 |         <form method="post">
54 |             <label>Login ID</label>
55 |             <input type="text" name="login_id" value="<?= htmlspecialchars($id ?? '') ?>">
56 | 
57 |             <label>Password</label>
58 |             <input type="password" name="password">
59 | 
60 |             <button type="submit">Log in</button>
61 |         </form>
62 | 
63 |         <?php if ($error !== ''): ?>
64 |             <!-- the error message appears BELOW the form -->
65 |             <p class="error"><?= htmlspecialchars($error) ?></p>
66 |         <?php endif; ?>
67 | 
68 |         <p class="hint">Try: admin / 1234 or student / abc</p>
69 |     </div>
70 | </body>
71 | </html>
```

**Lines worth pointing at**

| Line | Code | Say this |
|---|---|---|
| 13 | `session_start();` | "Start the server's memory for this visitor. Delete this line and nobody can stay logged in." |
| 16 | `$users = [` | "This array is standing in for a MySQL table. Two users, two passwords." |
| 21 | `$error = '';` | "Empty message = nothing is wrong yet. The page will ask about this variable at line 63." |
| 24 | `if ($_SERVER['REQUEST_METHOD'] === 'POST') {` | "GET means the page was opened; POST means the button was clicked. This line is the fork between the two." |
| 26 | `$id = $_POST['login_id'] ?? '';` | "Read what was typed. `?? ''` prevents a warning if the field was removed from the form." |
| 30 | `if (isset($users[$id]) && $users[$id] === $pw) {` | "Does this login ID exist **and** is the password exactly equal? `isset` first, so an unknown ID cannot cause a warning." |
| 32 | `$_SESSION['login_id'] = $id;` | "Remember **who** logged in. The browser never sees this value." |
| 33 | `header('Location: home.php');` | "Tell the browser: don't show this page, request `home.php` instead. That is the 302." |
| 34 | `exit;` | "Stop here. Without this line PHP keeps printing the form after the redirect." |
| 37 | `$error = 'Invalid login ID or password.';` | "The only thing failure does: write a message. No redirect — we stay on this page." |
| 39 | `}` | "End of the POST block. Notice what is missing: no `header()`, no `exit`. On failure the file simply continues into the HTML below." |
| 53 | `<form method="post">` | "POST, because the password must not appear in the URL. No `action`, so the form submits to this same file." |
| 55 | `value="<?= htmlspecialchars($id ?? '') ?>"` | "Put the typed login ID back, safely escaped. That is why the ID is still there after a wrong password." |
| 63 | `<?php if ($error !== ''): ?>` | "Nothing is shown on the first visit, because `$error` is empty." |
| 65 | `<p class="error"><?= htmlspecialchars($error) ?></p>` | "The requirement of the assignment: the message appears **below** the login form." |

Read the count first: **71 lines, and only about 20 of them are PHP logic.**
The rest is the HTML form and the comments you are reading now.

### 16.2 `home.php` — the protected page (36 lines)

The PHP part is lines 1–17; everything after that is presentation. The full code
is quoted in [section 5](#5-homephp--the-lock-on-the-door).

| Line | Code | Say this |
|---|---|---|
| 9 | `session_start();` | "Every page that uses `$_SESSION` must start the session first. Forget this and the user is always logged out." |
| 11 | `if (!isset($_SESSION['login_id'])) {` | "The lock on the door: is there a logged-in user in the session?" |
| 12 | `header('Location: login.php');` | "No session → straight back to the login page. This runs before any HTML, so a stranger never sees this page's content." |
| 13 | `exit;` | "Stop. Nothing below this line is executed for a stranger." |
| 16 | `$id = $_SESSION['login_id'];` | "Now we know who it is. The value came from `login.php` line 32." |
| 27 | `<h1>Welcome, <?= htmlspecialchars($id) ?>!</h1>` | "Escape before printing — the name came from user input, so it could contain HTML." |

### 16.3 `logout.php` (10 lines)

| Line | Code | Say this |
|---|---|---|
| 6 | `session_start();` | "Open the session — you cannot destroy what you have not opened." |
| 7 | `session_destroy();` | "Delete the session data on the server. The cookie in the browser now points at nothing." |
| 9 | `header('Location: login.php');` | "This file prints no HTML at all — it changes state and redirects." |
| 10 | `exit;` | "Same rule as everywhere else: redirect, then exit." |

### 16.4 `index.php` (5 lines)

| Line | Code | Say this |
|---|---|---|
| 4 | `header('Location: login.php');` | "So `http://localhost:8000/` works. A web server serves `index.php` by default when you ask for a folder." |
| 5 | `exit;` | "Five lines, one job." |

---

### One-page recap

* PHP runs on the server; the browser only receives HTML.
* One file, two requests: GET draws the form, POST decides what happens.
* **Wrong password → redraw the same page**, so the message can appear below
  the form. **Right password → `302` redirect** to the protected page.
* The browser keeps a ticket (`PHPSESSID`); the server keeps the memory
  (`$_SESSION`).
* `header('Location: …')` always needs `exit;`.
* Escape anything you print with `htmlspecialchars()`.
* 4xx = the request was wrong; 5xx = the server failed. A wrong password in an
  HTML form is `200 OK` with a message in the body.
