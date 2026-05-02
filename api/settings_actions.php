<?php
/**
 * api/settings_actions.php
 */
session_start();
require_once "../waf_init.php";

header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    http_response_code(401);
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

try {
    $pdo = new PDO(
        'mysql:host=localhost;dbname=waf_db;charset=utf8mb4',
        'root', '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
} catch (PDOException $e) {
    die(json_encode(['success' => false, 'message' => 'DB error']));
}

$body   = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $body['action'] ?? '';

switch ($action) {

    // 1. WAF mode: 'block' or 'detect'
    case 'set_waf_mode':
        $mode = $body['mode'] ?? 'block';
        if (!in_array($mode, ['block', 'detect'])) die(json_encode(['success'=>false,'message'=>'Invalid mode']));
        $pdo->prepare("UPDATE rule SET action = ?")->execute([$mode]);
        echo json_encode(['success' => true, 'message' => 'WAF mode set to ' . $mode]);
        break;

    // 2. Toggle rule on/off
    case 'toggle_rule':
        $type      = $body['type']   ?? '';
        $is_active = (int)($body['active'] ?? 0);
        if (!in_array($type, ['is_sqli','is_xss','is_cmdi'])) die(json_encode(['success'=>false,'message'=>'Invalid type']));
        $pdo->prepare("UPDATE rule SET is_active = ? WHERE type = ?")->execute([$is_active, $type]);
        echo json_encode(['success' => true, 'message' => strtoupper(str_replace('is_','',$type)) . ' rule ' . ($is_active?'enabled':'disabled')]);
        break;

    // 3. Block IP manually
    case 'block_ip':
        $ip          = trim($body['ip']     ?? '');
        $reason      = trim($body['reason'] ?? 'Manual block via settings');
        $is_permanent= (int)($body['permanent'] ?? 0);
        $duration    = max(1, (int)($body['duration'] ?? 24));

        if (!filter_var($ip, FILTER_VALIDATE_IP)) die(json_encode(['success'=>false,'message'=>'Invalid IP']));

        $check = $pdo->prepare("SELECT id FROM blocked_ip WHERE ip = ? AND (is_permanent=1 OR expires_at>NOW())");
        $check->execute([$ip]);
        if ($check->fetchColumn()) die(json_encode(['success'=>false,'message'=>'IP already blocked']));

        $expires = $is_permanent ? null : date('Y-m-d H:i:s', strtotime("+{$duration} hours"));
        $pdo->prepare("INSERT INTO blocked_ip (ip,reason,blocked_at,expires_at,is_permanent,rule_id) VALUES (?,?,NOW(),?,?,1)")
            ->execute([$ip, $reason, $expires, $is_permanent]);
        echo json_encode(['success'=>true,'message'=>"IP $ip blocked",'expires'=>$expires??'Never']);
        break;

    // 4. Unblock IP
    case 'unblock_ip':
        $ip = trim($body['ip'] ?? '');
        if (!filter_var($ip, FILTER_VALIDATE_IP)) die(json_encode(['success'=>false,'message'=>'Invalid IP']));
        $pdo->prepare("DELETE FROM blocked_ip WHERE ip = ?")->execute([$ip]);
        echo json_encode(['success'=>true,'message'=>"IP $ip unblocked"]);
        break;

    // 5. Get blocked IPs
    case 'get_blocked_ips':
        $rows = $pdo->query("
            SELECT ip, reason, blocked_at, expires_at, is_permanent,
                CASE WHEN is_permanent=1 THEN 'Permanent'
                     WHEN expires_at>NOW() THEN 'Active'
                     ELSE 'Expired' END AS status
            FROM blocked_ip ORDER BY blocked_at DESC
        ")->fetchAll();
        echo json_encode(['success'=>true,'data'=>$rows]);
        break;

    // 6. Add user
    case 'add_user':
        $username = trim($body['username'] ?? '');
        $email    = trim($body['email']    ?? '');
        $password = $body['password']      ?? '';
        $role_id  = (int)($body['role_id'] ?? 0);

        if (!$username || !$email || !$password || $role_id < 1)
            die(json_encode(['success'=>false,'message'=>'All fields required']));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL))
            die(json_encode(['success'=>false,'message'=>'Invalid email']));
        if (strlen($password) < 6)
            die(json_encode(['success'=>false,'message'=>'Password min 6 characters']));

        $check = $pdo->prepare("SELECT id FROM user WHERE username=? OR email=?");
        $check->execute([$username,$email]);
        if ($check->fetchColumn()) die(json_encode(['success'=>false,'message'=>'Username or email already exists']));

        $pdo->prepare("INSERT INTO user (username,email,password_hash,role_id,created_at,is_active) VALUES (?,?,?,?,NOW(),1)")
            ->execute([$username, $email, md5($password ), $role_id]);
        echo json_encode(['success'=>true,'message'=>"User '$username' created"]);
        break;

    // 7. Get users
    case 'get_users':
        $rows = $pdo->query("
            SELECT u.id, u.username, u.email, r.name AS role, r.id AS role_id,
                   u.created_at, u.last_login, u.is_active
            FROM user u JOIN role r ON r.id=u.role_id
            ORDER BY u.created_at DESC
        ")->fetchAll();
        echo json_encode(['success'=>true,'data'=>$rows]);
        break;

    // 8. Toggle user active
    case 'toggle_user':
        $user_id   = (int)($body['user_id']   ?? 0);
        $is_active = (int)($body['is_active'] ?? 0);
        if ($user_id < 1) die(json_encode(['success'=>false,'message'=>'Invalid user']));
        $pdo->prepare("UPDATE user SET is_active=? WHERE id=?")->execute([$is_active,$user_id]);
        echo json_encode(['success'=>true,'message'=>'User updated']);
        break;

    // 10. Change user role
    case 'change_role':
        $user_id = (int)($body['user_id'] ?? 0);
        $role_id = (int)($body['role_id'] ?? 0);
        if ($user_id < 1 || $role_id < 1) die(json_encode(['success'=>false,'message'=>'Invalid user or role']));
        $roleCheck = $pdo->prepare("SELECT id FROM role WHERE id=?");
        $roleCheck->execute([$role_id]);
        if (!$roleCheck->fetchColumn()) die(json_encode(['success'=>false,'message'=>'Role does not exist']));
        $pdo->prepare("UPDATE user SET role_id=? WHERE id=?")->execute([$role_id,$user_id]);
        echo json_encode(['success'=>true,'message'=>'Role updated']);
        break;

    // 11. Delete user
    case 'delete_user':
        $user_id = (int)($body['user_id'] ?? 0);
        if ($user_id < 1) die(json_encode(['success'=>false,'message'=>'Invalid user']));
        // Prevent deleting yourself
        $self = $pdo->prepare("SELECT id FROM user WHERE username=?");
        $self->execute([$_SESSION['username']]);
        if ($self->fetchColumn() == $user_id) die(json_encode(['success'=>false,'message'=>'You cannot delete your own account']));
        $pdo->prepare("DELETE FROM user WHERE id=?")->execute([$user_id]);
        echo json_encode(['success'=>true,'message'=>'User deleted']);
        break;

    // 9. Get rules state
    case 'get_rules':
        $rows = $pdo->query("SELECT type,is_active,action FROM rule WHERE type IN ('is_sqli','is_xss','is_cmdi')")->fetchAll();
        // Also get current mode from first rule
        $mode = $rows[0]['action'] ?? 'block';
        echo json_encode(['success'=>true,'data'=>$rows,'mode'=>$mode]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success'=>false,'message'=>'Unknown action']);
}