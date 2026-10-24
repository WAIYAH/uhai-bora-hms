<?php
/*
 * Branded "page not found" response. Used by Apache (.htaccess) for missing
 * files and blocked paths; always returns HTTP status 404.
 */
require __DIR__ . '/includes/bootstrap.php';

not_found();
