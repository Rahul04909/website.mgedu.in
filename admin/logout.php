<?php
/**
 * MG Education - Admin Logout Handler
 * Securely destroys JWT session and cookie, then redirects to login
 */

require_once __DIR__ . '/includes/auth.php';

logoutAdmin();

header("Location: login.php?logout=1");
exit;

