<?php
/**
 * ==========================================================================
 *  http-errors.php — Question 2, as a page you can demo on a projector
 * ==========================================================================
 *
 *  It is a plain page: no form, no login. The only PHP work is generating the
 *  table from an array (instead of typing 20 rows of HTML by hand) and
 *  printing the status code this page itself was served with.
 */

declare(strict_types=1);

$groups = [
    '1xx — Informational' => [
        'The server got the first part of the request and is asking the client to continue. Rarely seen in normal browsing.',
        [],
    ],
    '2xx — Success' => [
        'The request was received, understood and accepted. These are not errors.',
        [
            [200, 'OK', 'The normal answer: here is the page / the JSON / the file.'],
            [201, 'Created', 'A POST created something new (used a lot in APIs).'],
            [204, 'No Content', 'It worked and there is nothing to send back — often after DELETE or a logout.'],
        ],
    ],
    '3xx — Redirection' => [
        'The client must look somewhere else. Our login page uses 302 for this: after a correct password the server answers "Found: go to dashboard.php".',
        [
            [301, 'Moved Permanently', 'The URL changed for good; browsers and search engines cache it.'],
            [302, 'Found', 'Temporary jump, used by every PHP header("Location: ...") redirect.'],
            [304, 'Not Modified', 'Your cached copy is still fresh — no need to download it again.'],
        ],
    ],
    '4xx — REQUEST ERROR (the client is wrong)' => [
        'The server understood the request and refuses it because the request itself is bad. Fixing it is the client\'s job: sign in, correct the URL, send valid fields.',
        [
            [400, 'Bad Request', 'Malformed syntax or missing/invalid parameters.'],
            [401, 'Unauthorized', 'You are not authenticated — log in first. (Confusingly, it means "unauthenticated".)'],
            [403, 'Forbidden', 'I know who you are, but you are not allowed to see this.'],
            [404, 'Not Found', 'Nothing lives at this URL.'],
            [405, 'Method Not Allowed', 'This URL exists, but not for GET/POST. Sent with an Allow header.'],
            [408, 'Request Timeout', 'The client took too long to finish sending its request.'],
            [409, 'Conflict', 'The request clashes with the current state — e.g. a duplicate username.'],
            [413, 'Content Too Large', 'The upload body is bigger than the server accepts.'],
            [415, 'Unsupported Media Type', 'Wrong Content-Type, e.g. JSON sent where the server expects a form.'],
            [422, 'Unprocessable Content', 'The syntax is fine but the values fail validation (empty name, bad email).'],
            [429, 'Too Many Requests', 'Rate limited — slow down. Comes with Retry-After.'],
        ],
    ],
    '5xx — RESPONSE ERROR (the server is wrong)' => [
        'The request was perfectly fine, but the server failed while producing the response. The user can only retry later; a developer must read the logs.',
        [
            [500, 'Internal Server Error', 'The catch-all: a PHP exception, fatal error, or uncaught problem.'],
            [501, 'Not Implemented', 'The server does not support the requested method/feature at all.'],
            [502, 'Bad Gateway', 'A proxy/reverse-proxy got an invalid answer from the app behind it.'],
            [503, 'Service Unavailable', 'Down for maintenance or overloaded. Sent with Retry-After.'],
            [504, 'Gateway Timeout', 'The proxy waited for the app and gave up.'],
        ],
    ],
];

$quickDemo = [200, 400, 401, 403, 404, 405, 418, 422, 429, 500, 502, 503];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>HTTP request errors &amp; response errors</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body class="page">

<main class="sheet">

    <nav class="crumbs">
        <a href="index.php">&larr; Back to the login app</a>
        <span class="badge">Question 2</span>
    </nav>

    <h1>HTTP request errors and response errors</h1>
    <p class="lead">
        Every HTTP exchange is one <em>request</em> from the client
        (GET /login.php) followed by one <em>response</em> from the server
        (<code>200 OK</code> + HTML). There is no such thing as an HTTP
        error without a response — an error <strong>is</strong> a response
        whose status code starts with 4 or 5.
    </p>

    <div class="two-col">
        <div class="panel panel-amber">
            <h2>4xx — request error</h2>
            <p>The <strong>client</strong> is at fault: bad URL, missing field, no login, wrong method.</p>
            <p class="fine">The same request repeated will keep failing until the client changes something.</p>
        </div>
        <div class="panel panel-red">
            <h2>5xx — response error</h2>
            <p>The <strong>server</strong> is at fault: it crashed, timed out or lost its database connection.</p>
            <p class="fine">A retry may work. Nothing the user typed can fix it — a developer must.</p>
        </div>
    </div>

    <h2 class="mt">Try it live</h2>
    <p class="lead small">
        Each button calls <code>demo/status.php?code=…</code>. The server sets that
        status with <code>http_response_code()</code>, and this page reads it from
        <code>response.status</code> in JavaScript. Open DevTools &rarr; Network
        while clicking.
    </p>

    <div class="chips">
        <?php foreach ($quickDemo as $c): ?>
            <button type="button" class="chip" data-code="<?= $c ?>"><?= $c ?></button>
        <?php endforeach; ?>
    </div>

    <pre id="demo-out" class="console" aria-live="polite">Click a code above to send a real HTTP request…</pre>

    <h2 class="mt">The full picture</h2>

    <?php foreach ($groups as $title => [$intro, $rows]): ?>
        <section class="group">
            <h3><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h3>
            <p class="lead small"><?= htmlspecialchars($intro, ENT_QUOTES, 'UTF-8') ?></p>
            <?php if ($rows !== []): ?>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr><th>Code</th><th>Reason phrase</th><th>When you meet it</th></tr>
                        </thead>
                        <tbody>
                        <?php foreach ($rows as [$code, $reason, $meaning]): ?>
                            <tr>
                                <td><code class="code-pill"><?= $code ?></code></td>
                                <td class="nowrap"><?= htmlspecialchars($reason, ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($meaning, ENT_QUOTES, 'UTF-8') ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    <?php endforeach; ?>

    <h2 class="mt">How this assignment uses them</h2>
    <ul class="bullets">
        <li><strong>Wrong password</strong> — our login page answers <code>200 OK</code> and shows the message in the page. It does <em>not</em> send 401: the form page loaded fine, only the credentials were rejected. Status codes describe the HTTP exchange, not the business result.</li>
        <li><strong>Successful login</strong> — <code>302 Found</code> + <code>Location: dashboard.php</code>. The browser then makes a second request for the dashboard, which returns <code>200 OK</code>.</li>
        <li><strong>Visiting dashboard.php while logged out</strong> — <code>302 Found</code> back to the login page, because <code>require_login()</code> calls <code>redirect()</code>.</li>
        <li><strong>A PHP bug in dashboard.php</strong> — the web server has nothing valid to send, so it returns <code>500 Internal Server Error</code> and logs the details to the error log, not to the visitor.</li>
        <li><strong>Typing a URL that does not exist</strong> — <code>404 Not Found</code>, produced by the web server (Apache/nginx/PHP's built-in server) before any of our PHP runs.</li>
    </ul>

    <h3 class="mt">Request vs response, side by side</h3>
    <div class="table-wrap">
        <table>
            <thead><tr><th></th><th>Request</th><th>Response</th></tr></thead>
            <tbody>
            <tr><td>Sent by</td><td>Browser, curl, mobile app</td><td>Web server / PHP</td></tr>
            <tr><td>Starts with</td><td><code>POST /index.php HTTP/1.1</code></td><td><code>HTTP/1.1 200 OK</code></td></tr>
            <tr><td>Carries</td><td>Method, path, headers, optional body (the form fields)</td><td>Status code, headers, body (the HTML)</td></tr>
            <tr><td>Its error range</td><td><strong>4xx</strong> — the server rejects it</td><td><strong>5xx</strong> — the server failed while answering it</td></tr>
            <tr><td>Typical cause</td><td>Typo in URL, missing session, wrong HTTP method, oversized upload</td><td>Exception in PHP, database down, script timeout, bad upstream</td></tr>
            <tr><td>Who must fix it</td><td>The client (user or developer of the client)</td><td>The server developer / operator</td></tr>
            </tbody>
        </table>
    </div>

    <p class="lead mt">
        This page was served with status <code><?= http_response_code() ?></code>
        to <code><?= htmlspecialchars($_SERVER['REQUEST_METHOD'] . ' ' . $_SERVER['REQUEST_URI'], ENT_QUOTES, 'UTF-8') ?></code>
        over HTTP/1.1.
    </p>

    <footer class="card-foot">PHP <?= PHP_VERSION ?></footer>

</main>

<script>
/* Read the live status of demo/status.php. fetch() resolves even for 404 or
   500 — an HTTP error is still a successful network exchange — so we must
   look at response.ok / response.status ourselves. */
const out = document.getElementById('demo-out');

document.querySelectorAll('.chip').forEach(function (button) {
    button.addEventListener('click', async function () {
        const code = button.dataset.code;
        button.disabled = true;
        out.textContent = 'Requesting demo/status.php?code=' + code + ' …';

        try {
            const response = await fetch('demo/status.php?code=' + code);
            const data = await response.json();

            out.textContent =
                'GET demo/status.php?code=' + code + '\n' +
                'HTTP/1.1 ' + response.status + ' ' + response.statusText + '\n' +
                'response.ok = ' + response.ok + '\n' +
                'kind        = ' + data.kind + '\n\n' +
                JSON.stringify(data, null, 2);
        } catch (error) {
            out.textContent = 'Network failure: ' + error.message;
        } finally {
            button.disabled = false;
        }
    });
});
</script>

</body>
</html>
