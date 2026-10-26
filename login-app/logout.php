<?php
/**
 * ==========================================================================
 *  logout.php — throw away the session
 * ==========================================================================
 *
 *  This file prints no HTML at all: it does its work and redirects. A page
 *  that only changes state is called an "action" endpoint.
 */

declare(strict_types=1);

require __DIR__ . '/includes/auth.php';

logout_user();

// The "?logged_out=1" part is only so index.php can show a friendly message.
redirect('index.php?logged_out=1');
