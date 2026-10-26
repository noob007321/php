# PHP — Login Application (student assignment + teaching notes)

A complete, working answer to the handwritten assignment:

| # | Task | Where it is answered |
|---|------|----------------------|
| 1 | A login application: the user types a login ID and password; if they match, go to a different page; otherwise show the error message **below** the login form. | Simple: [`simple/login.php`](simple/login.php) → [`home.php`](simple/home.php) · Full: [`login-app/index.php`](login-app/index.php) → [`dashboard.php`](login-app/dashboard.php) |
| 2 | What is an HTTP **request error** and a **response error**? | Interactive page: [`login-app/http-errors.php`](login-app/http-errors.php) · written answer: [TEACHING-GUIDE §7](login-app/TEACHING-GUIDE.md#7-question-2--http-request-errors-vs-response-errors) |
| — | "Web framework?" (top of the sheet) | [TEACHING-GUIDE §8](login-app/TEACHING-GUIDE.md#8-if-your-sheet-also-asks-what-is-a-web-framework) |

## Two versions — pick the one you need

| | `simple/` — start here | `login-app/` — the full version |
|---|---|---|
| Files | 4 (one per job) | 12 (split into layers) |
| Lines of PHP | ~120 in total | ~700 |
| Read time | 5 minutes | an afternoon |
| Passwords | plain text, on purpose | bcrypt hashes |
| Protections | sessions only | hashing, CSRF, escaping, session fixation, 404 page |
| Same login flow (form → check → redirect, or error below the form) | ✅ | ✅ |

Teach `simple/` first. When students ask "but how do real apps do this?", open
`login-app/` and let them compare the two `login` files side by side.

```
php/
├── README.md                  ← you are here
├── simple/                    ← the beginner version (4 files)
│   ├── login.php              form + check + error message below it
│   ├── home.php               the "different page"
│   ├── logout.php             forget the user
│   ├── index.php              so opening the folder shows the login page
│   ├── style.css              90 lines of styling only
│   └── README.md              the short explanation
└── login-app/                 ← the professional version
    ├── index.php              login form + validation + error messages
    ├── dashboard.php          the "different page" after a successful login
    ├── logout.php             destroys the session
    ├── http-errors.php        Question 2, with live experiments
    ├── 404.php                custom "Not Found" page
    ├── router.php             helper for PHP's built-in dev server only
    ├── hash.php               command-line tool to create password hashes
    ├── includes/
    │   ├── config.php         settings + the user "database" + session start
    │   └── auth.php           escaping, login check, session guards, CSRF
    ├── assets/style.css       all the styling
    ├── demo/status.php        endpoint that returns any HTTP status code
    ├── README.md              install & run instructions
    └── TEACHING-GUIDE.md      the full lesson: every file, every concept, Q&A
```

## Run the simple version

```bash
cd simple
php -S localhost:8000
# open http://localhost:8000
```

Log in with **admin / 1234** or **student / abc**.

## Run the full version

```bash
cd login-app
php -S localhost:8000 router.php
# open http://localhost:8000
```

Log in with **admin / admin123** or **student / student123**.

On XAMPP/WAMP/LAMP copy either folder into `htdocs` and open
`http://localhost/simple/login.php` or `http://localhost/login-app/`.

## Start here if you are teaching

1. Read [`simple/README.md`](simple/README.md) — four concepts, five lines of code,
   and five things to try in class.
2. Read [`login-app/TEACHING-GUIDE.md`](login-app/TEACHING-GUIDE.md) — every line of
   the full version, the exact request/response traffic of a login, the security
   decisions, the HTTP error answer, a 30-minute demo script and viva questions.
3. Open the apps and the HTTP-errors page on a projector; both are built to be demoed.
