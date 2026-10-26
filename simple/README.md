# The simple login app

Five small files. Read them top to bottom and you have read the whole program.

> **Teaching it?** Read [`TEACHING-GUIDE.md`](TEACHING-GUIDE.md) — every line
> explained in teaching order, plus a demo script, common beginner errors and
> viva questions.

| File | Lines | Job |
|---|---|---|
| `login.php` | 71 | show the form, check the password, show the error |
| `home.php` | 36 | the "different page" — only for logged-in users |
| `logout.php` | 10 | forget the user |
| `index.php` | 5 | so opening the folder shows the login page |
| `style.css` | 93 | colours and spacing only (no PHP) |
| `TEACHING-GUIDE.md` | — | how to explain all of it (for the teacher) |

**Log in with:** `admin / 1234` or `student / abc`

## Run it

```bash
cd simple
php -S localhost:8000
```

Open <http://localhost:8000/>.

On XAMPP, copy the `simple` folder into `htdocs` and open
`http://localhost/simple/`.

## The whole idea in 5 lines

```php
session_start();                 // 1. start remembering
if ($_SERVER['REQUEST_METHOD'] === 'POST') {   // 2. was the form sent?
    if ($users[$id] === $pw) {   // 3. right password?
        $_SESSION['login_id'] = $id;           // 4. remember the user
        header('Location: home.php');          // 5. go to another page
    }
}
```

## The four PHP ideas used

1. **`$_POST`** — the values the user typed, sent by the form.
2. **`$_SESSION`** — the server's memory about this visitor. Works because
   PHP gives the browser a cookie called `PHPSESSID`, and the browser sends it
   back on every request.
3. **`header('Location: ...')`** — "don't show this page, go to that one instead".
   Always followed by `exit`.
4. **`htmlspecialchars()`** — makes text safe before printing it, so a user
   cannot type HTML/JavaScript into your page.

## Things this version leaves out on purpose

* **The password is in plain text** (`'admin' => '1234'`). It is easy to read, so
  it is also easy to steal. Real apps store `password_hash()` values and check
  them with `password_verify()` — see `../login-app`.
* **No CSRF token, no session ID regeneration, no cookie deletion on logout.**
  All of that is in `../login-app`, which is the same application with a
  professional structure.

`TEACHING-GUIDE.md` section 15 lists each omission next to the file that fixes it,
so the simple version becomes a stepping stone rather than a bad habit.

## Try this in class

1. Type a wrong password → the red message appears **below** the form.
   The URL does not change, because the page was re-drawn, not redirected.
2. Log in correctly → the URL becomes `home.php`. Look at
   DevTools → Application → Cookies: there is a `PHPSESSID` cookie.
3. Open `home.php` in a private window → you are sent back to `login.php`.
4. Delete the cookie and press F5 on `home.php` → same thing. The cookie was
   the only proof that you had logged in.
5. Change `'admin' => '1234'` to `'admin' => '4321'` and try the old password.
6. Submit the form with a wrong password and look at DevTools → Network: the
   response is `200`, not a redirect. Then log in correctly and watch the `302`.

More exercises, with answers, are in [`TEACHING-GUIDE.md`](TEACHING-GUIDE.md).
