# PHP — Login Application (student assignment + teaching notes)

A complete, working answer to the handwritten assignment:

| # | Task | Where it is answered |
|---|------|----------------------|
| 1 | A login application: the user types a login ID and password; if they match, go to a different page; otherwise show the error message **below** the login form. | [`login-app/index.php`](login-app/index.php) → [`dashboard.php`](login-app/dashboard.php) |
| 2 | What is an HTTP **request error** and a **response error**? | Interactive page: [`login-app/http-errors.php`](login-app/http-errors.php) · written answer: [TEACHING-GUIDE §7](login-app/TEACHING-GUIDE.md#7-question-2--http-request-errors-vs-response-errors) |
| — | "Web framework?" (top of the sheet) | [TEACHING-GUIDE §8](login-app/TEACHING-GUIDE.md#8-if-your-sheet-also-asks-what-is-a-web-framework) |

```
php/
├── README.md                  ← you are here
└── login-app/
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
    ├── demo/status.php        endpooint that returns any HTTP status code
    ├── README.md              install & run instructions
    └── TEACHING-GUIDE.md      the full lesson: every file, every concept, Q&A
```

## Run it in 30 seconds

```bash
cd login-app
php -S localhost:8000 router.php
# open http://localhost:8000
```

Demo accounts: **admin / admin123** and **student / student123**.

On XAMPP/WAMP/LAMP just copy the `login-app` folder into `htdocs` and open
`http://localhost/login-app/`. Details in [`login-app/README.md`](login-app/README.md).

## Start here if you are teaching

1. Read [`login-app/TEACHING-GUIDE.md`](login-app/TEACHING-GUIDE.md) — it explains
   every line of every file, the exact request/response traffic of a login, the
   security decisions, the HTTP error answer, a classroom demo script and viva questions.
2. Open the app and the HTTP-errors page on a projector; both are built to be demoed.
