<?php
/**
 * Require specific role. Set $requiredRole before including.
 * Example: $requiredRole = 'admin'; require role_check.php;
 */
declare(strict_types=1);

if (!isset($requiredRole) || !is_string($requiredRole)) {
    http_response_code(500);
    exit('Role check misconfigured.');
}

Auth::requireRole($requiredRole);
