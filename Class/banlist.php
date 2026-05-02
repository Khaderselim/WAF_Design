<?php
/**
 * IP Blacklist / Banlist Checker
 */

use WAF\Class\URLAttackChecker;

include_once "URLAttackChecker.php";

$uac = new URLAttackChecker();

function getVisIpAddr(): string {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip  = trim($ips[0]);
    } else {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }
    return filter_var($ip, FILTER_VALIDATE_IP) ?: '127.0.0.1';
}

function isIpBanned(): bool {
    global $uac; // ← declare global so function can access it

    try {
        $ip  = getVisIpAddr();
        $pdo = $uac->getDb(); // ← reuse existing connection from URLAttackChecker

        $stmt = $pdo->prepare("
            SELECT id FROM blocked_ip
            WHERE ip = ?
            AND (is_permanent = 1 OR expires_at > NOW())
            LIMIT 1
        ");
        $stmt->execute([$ip]);
        $blocked = (bool) $stmt->fetchColumn();

        if ($blocked) {
            // Log the blocked attempt
            $traffic = $uac->collectTrafficData();
            $uac->insertTrafficLog($traffic, 0, 1);
        }

        return $blocked;

    } catch (Exception $e) {
        error_log("Banlist check error: " . $e->getMessage());
        return false; // fail open — don't block on DB error
    }
}

function blockIfBanned(): void {
    if (isIpBanned()) {
        http_response_code(403);
        header('Content-Type: application/json');
        die(json_encode([
            'error' => 'Your IP address has been blocked by the security system',
            'code'  => 'IP_BLOCKED',
            'ip'    => getVisIpAddr(),
        ]));
    }
}

// Auto-execute on include
blockIfBanned();