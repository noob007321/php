<?php
/**
 * ==========================================================================
 *  demo/status.php — answer Question 2 with a live experiment
 * ==========================================================================
 *
 *  Every HTTP response starts with a status code. This tiny endpoint lets the
 *  HTTP-errors page ask for any code and see what the browser receives.
 *
 *      demo/status.php?code=404
 *
 *  The code is checked against a whitelist of real codes: we never let the
 *  URL decide an arbitrary status, otherwise the endpoint could be abused.
 */

declare(strict_types=1);

/** 4xx = the REQUEST was wrong. 5xx = the RESPONSE could not be built. */
const STATUS_CODES = [
    // 2xx — success
    200 => ['OK', 'success'],
    201 => ['Created', 'success'],
    204 => ['No Content', 'success'],
    // 3xx — redirection
    301 => ['Moved Permanently', 'redirect'],
    302 => ['Found', 'redirect'],
    304 => ['Not Modified', 'redirect'],
    // 4xx — REQUEST ERROR: the client sent something we cannot accept
    400 => ['Bad Request', 'request error'],
    401 => ['Unauthorized', 'request error'],
    403 => ['Forbidden', 'request error'],
    404 => ['Not Found', 'request error'],
    405 => ['Method Not Allowed', 'request error'],
    408 => ['Request Timeout', 'request error'],
    409 => ['Conflict', 'request error'],
    413 => ['Content Too Large', 'request error'],
    415 => ['Unsupported Media Type', 'request error'],
    418 => ['I am a teapot', 'request error'],
    422 => ['Unprocessable Content', 'request error'],
    429 => ['Too Many Requests', 'request error'],
    // 5xx — RESPONSE ERROR: the server failed to produce a valid response
    500 => ['Internal Server Error', 'response error'],
    501 => ['Not Implemented', 'response error'],
    502 => ['Bad Gateway', 'response error'],
    503 => ['Service Unavailable', 'response error'],
    504 => ['Gateway Timeout', 'response error'],
];

$requested = filter_input(INPUT_GET, 'code', FILTER_VALIDATE_INT);
$code      = ($requested !== null && isset(STATUS_CODES[$requested])) ? $requested : 400;

[$reason, $kind] = STATUS_CODES[$code];

// http_response_code() writes the status line:  "HTTP/1.1 404 Not Found"
http_response_code($code);
header('Content-Type: application/json; charset=utf-8');
header('X-Demo-Code: ' . $code);

echo json_encode([
    'code'   => $code,
    'reason' => $reason,
    'kind'   => $kind,
    'method' => $_SERVER['REQUEST_METHOD'],
    'uri'    => $_SERVER['REQUEST_URI'],
    'note'   => match ($kind) {
        'success'       => 'The request worked and the server has data to send back.',
        'redirect'      => 'The resource is somewhere else; the Location header says where.',
        'request error' => 'The CLIENT asked for something the server will not do. Often fixable by the user.',
        'response error' => 'The SERVER broke while building the response. The client did nothing wrong.',
        default         => '',
    },
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
