<?php

namespace WAF\Class;

use Codewithkyrian\Transformers\Transformers;
use function Codewithkyrian\Transformers\Pipelines\pipeline;
use PDO;
use Exception;

/**
 * URLAttackChecker Class
 * 
 * Detects, logs, and blocks URL attacks using ML classification.
 * Handles:
 * - URL/Input classification for SQL injection, XSS, command injection
 * - Traffic logging
 * - Browser info collection
 * - Alert generation
 * - IP blocking
 */
class URLAttackChecker
{
 // Configuration
 private const DB_HOST = 'localhost';
 private const DB_NAME  = 'waf_db';
 private const DB_USER  = 'root';
 private const DB_PASS  = '';
 private const MODEL_CACHE = __DIR__ . '/models';
 private const MODEL_ID = 'hf_model_fixed';
 private const BLOCK_THRESHOLD= 0.85;
 private const ALERT_THRESHOLD= 0.50;
 private const BLOCK_DURATION = '24 hours';  // How long to block an IP

 // Severity mapping for attack types
 private const SEVERITY_MAP = [
  'is_sqli' => ['severity' => 'HIGH',  'rule_id' => 1],
  'is_xss'  => ['severity' => 'MEDIUM','rule_id' => 2],
  'is_cmdi' => ['severity' => 'CRITICAL', 'rule_id' => 3],
 ];

 private ?PDO $pdo = null;
 private $classifier = null;
 private array $lastResult = [];
 private bool $shouldBlock = false;

 /**
  * Constructor - Initialize database connection
  */
 public function __construct()
 {
  $this->initializeDatabase();
 }

 /**
  * Initialize PDO database connection
  */
 private function initializeDatabase(): void
 {
  try {
$this->pdo = new PDO(
 'mysql:host=' . self::DB_HOST . ';dbname=' . self::DB_NAME . ';charset=utf8mb4',
 self::DB_USER,
 self::DB_PASS,
 [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC ]
);
  } catch (Exception $e) {
throw new Exception("Database connection failed: " . $e->getMessage());
  }
 }
    // Add this inside URLAttackCheckerTestSuite class
    public function getDb(): PDO {
        return $this->pdo; // expose the existing connection
    }

 /**
  * Get or initialize the ML classifier
  */
 private function getClassifier()
 {
  if ($this->classifier === null) {
try {
 Transformers::setup()->setCacheDir(self::MODEL_CACHE)->apply();
 $this->classifier = pipeline('text-classification', self::MODEL_ID, quantized: false);
} catch (Exception $e) {
 throw new Exception("Failed to initialize classifier: " . $e->getMessage());
}
  }
  return $this->classifier;
 }

 /**
  * Classify URL/input for attacks using ML model
  *
  * @param string $input The URL or string to classify
  * @return array Classification result with threat_score, blocked status, and detected attack types
  */
 public function classifyInput(string $input): array
 {
  try {
$clf = $this->getClassifier();
$out = $clf($input);

// Normalize output - can be single result or array
$results = isset($out['label']) ? [$out] : array_values($out);

$maxScore = 0.0;
$attackTypes = [];

foreach ($results as $r) {
 if (!isset($r['label'], $r['score'])) {
  continue;
 }

 if ($r['score'] > self::ALERT_THRESHOLD) {
  $attackTypes[] = [
'label' => $r['label'],
'score' => round($r['score'], 4),
  ];
 }
 $maxScore = max($maxScore, $r['score']);
}

return [
 'raw' => $results,
 'threat_score' => (int) round($maxScore * 100),
 'blocked'=> $maxScore >= self::BLOCK_THRESHOLD,
 'attack_types' => $attackTypes,
];
  } catch (Exception $e) {
throw new Exception("Classification failed: " . $e->getMessage());
  }
 }

 /**
  * Collect traffic/request data from current request
  *
  * @return array Request data including IP, User-Agent, headers, etc.
  */
 public function collectTrafficData(): array
 {
  $browserInfo = [];
  
  // Try to get browser info if function is available
  if (function_exists('get_browser')) {
try {
 $browserInfo = @get_browser(null, true);
} catch (Exception $e) {
 // Fallback if get_browser fails
 $browserInfo = $this->parseBrowserFromUserAgent($_SERVER['HTTP_USER_AGENT'] ?? '');
}
  } else {
$browserInfo = $this->parseBrowserFromUserAgent($_SERVER['HTTP_USER_AGENT'] ?? '');
  }

  return [
'timestamp'  => date('Y-m-d H:i:s'),
'request_time'  => $_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true),
'method'  => $_SERVER['REQUEST_METHOD'] ?? 'GET',
'uri'  => $_SERVER['REQUEST_URI'] ?? '',
'query_string'  => $_SERVER['QUERY_STRING'] ?? '',
'protocol'=> $_SERVER['SERVER_PROTOCOL'] ?? '',
'ip'=> $this->getClientIP(),
'port' => $_SERVER['REMOTE_PORT'] ?? '',
'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
'referer' => $_SERVER['HTTP_REFERER'] ?? '',
'accept_language'  => $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '',
'host' => $_SERVER['HTTP_HOST'] ?? '',
'server_port'=> $_SERVER['SERVER_PORT'] ?? '',
'https'=> isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
'get_params' => $_GET,
'post_params'=> $_POST,
'cookies' => $_COOKIE,
'content_type'  => $_SERVER['CONTENT_TYPE'] ?? '',
'content_length'=> $_SERVER['CONTENT_LENGTH'] ?? 0,
'x_forwarded_for'  => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '',
'browser_info'  => $browserInfo,
'headers' => getallheaders(),
  ];
 }

 /**
  * Get real client IP (handles proxies)
  */
 private function getClientIP(): string
 {
  if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
$ip = $_SERVER['HTTP_CLIENT_IP'];
  } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
// Handle multiple IPs in X-Forwarded-For
$ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
$ip = trim($ips[0]);
  } else {
$ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
  }
  return filter_var($ip, FILTER_VALIDATE_IP) ?: '127.0.0.1';
 }

 /**
  * Parse browser info from User-Agent string (fallback)
  */
 private function parseBrowserFromUserAgent(string $ua): array
 {
  $browserName = 'Unknown';
  $browserVer = '0';
  $os = 'Unknown';
  $deviceType = 'Desktop';
  $isCrawler = false;

  if (preg_match('/Chrome\/([\d.]+)/', $ua, $m)) {
$browserName = 'Chrome';
$browserVer = $m[1];
  } elseif (preg_match('/Firefox\/([\d.]+)/', $ua, $m)) {
$browserName = 'Firefox';
$browserVer = $m[1];
  } elseif (preg_match('/Safari\/([\d.]+)/', $ua, $m)) {
$browserName = 'Safari';
$browserVer = $m[1];
  } elseif (preg_match('/MSIE\s+([\d.]+)|Trident\/.*rv:([\d.]+)/', $ua, $m)) {
$browserName = 'Internet Explorer';
$browserVer = $m[1] ?? $m[2];
  }

  if (preg_match('/Windows/', $ua)) {
$os = 'Windows';
  } elseif (preg_match('/Linux/', $ua)) {
$os = 'Linux';
  } elseif (preg_match('/Macintosh/', $ua)) {
$os = 'MacOS';
  } elseif (preg_match('/iPhone|iPad|iPod/', $ua)) {
$os = 'iOS';
$deviceType = 'Mobile';
  } elseif (preg_match('/Android/', $ua)) {
$os = 'Android';
$deviceType = 'Mobile';
  }

  $isCrawler = (bool) preg_match('/bot|crawl|spider|googlebot|bingbot/i', $ua);

  return [
'browser_name' => $browserName,
'browser_version' => $browserVer,
'os'  => $os,
'device_type'  => $deviceType,
'is_crawler'=> $isCrawler,
  ];
 }

 /**
  * Insert traffic log into database
  *
  * @param array $trafficData Traffic/request data
  * @param int $threatScore Threat score (0-100)
  * @param bool $blocked Whether the request was blocked
  * @return int Last inserted traffic_log ID
  */
 public function insertTrafficLog(array $trafficData, int $threatScore = 0, bool $blocked = false): int
 {
  try {
$stmt = $this->pdo->prepare("
 INSERT INTO traffic_log
  (timestamp, source_ip, method, url, user_agent,
request_size, response_status, threat_score, blocked)
 VALUES
  (:timestamp, :source_ip, :method, :url, :user_agent,
:request_size, :response_status, :threat_score, :blocked)
");

$stmt->execute([
 ':timestamp' => $trafficData['timestamp'],
 ':source_ip' => $trafficData['ip'],
 ':method' => $trafficData['method'],
 ':url' => $trafficData['uri'],
 ':user_agent'=> $trafficData['user_agent'],
 ':request_size' => (int) ($trafficData['content_length'] ?? 0),
 ':response_status' => 200,
 ':threat_score' => $threatScore,
 ':blocked'=> (int) $blocked,
]);

return (int) $this->pdo->lastInsertId();
  } catch (Exception $e) {
throw new Exception("Failed to insert traffic log: " . $e->getMessage());
  }
 }

 /**
  * Insert browser info into database
  *
  * @param int $trafficLogId Traffic log ID (foreign key)
  * @param array $browserInfo Browser information
  * @return int Last inserted browser_info ID
  */
 public function insertBrowserInfo(int $trafficLogId, array $browserInfo): int
 {
  try {
$stmt = $this->pdo->prepare("
 INSERT INTO browser_info
  (traffic_log_id, browser_name, browser_version, os, device_type, is_crawler)
 VALUES
  (:traffic_log_id, :browser_name, :browser_version, :os, :device_type, :is_crawler)
");

$stmt->execute([
 ':traffic_log_id'  => $trafficLogId,
 ':browser_name' => $browserInfo['browser_name'] ?? 'Unknown',
 ':browser_version' => $browserInfo['browser_version'] ?? '0',
 ':os'  => $browserInfo['os'] ?? 'Unknown',
 ':device_type'  => $browserInfo['device_type'] ?? 'Desktop',
 ':is_crawler'=> (int) ($browserInfo['is_crawler'] ?? false),
]);

return (int) $this->pdo->lastInsertId();
  } catch (Exception $e) {
throw new Exception("Failed to insert browser info: " . $e->getMessage());
  }
 }

 /**
  * Insert alerts for detected attacks
  *
  * @param int $trafficLogId Traffic log ID (foreign key)
  * @param array $classification Classification result with detected attack types
  * @return array Array of inserted alert IDs
  */
 public function insertAlerts(int $trafficLogId, array $classification): array
 {
  $inserted = [];

  if (empty($classification['attack_types'])) {
return $inserted;
  }

  try {
foreach ($classification['attack_types'] as $attack) {
 $label = $attack['label'];
 $meta = self::SEVERITY_MAP[$label] ?? ['severity' => 'LOW', 'rule_id' => null];

 $stmt = $this->pdo->prepare("
  INSERT INTO alert
(traffic_log_id, rule_id, type, severity, description, status, created_at)
  VALUES
(:traffic_log_id, :rule_id, :type, :severity, :description, 'open', NOW())
 ");

 $stmt->execute([
  ':traffic_log_id' => $trafficLogId,
  ':rule_id'  => $meta['rule_id'],
  ':type'  => strtoupper(str_replace('is_', '', $label)),
  ':severity' => $meta['severity'],
  ':description' => "ML detected {$label} with score " . round($attack['score'] * 100) . "%",
 ]);

 $inserted[] = [
  'id' => (int) $this->pdo->lastInsertId(),
  'label' => $label,
  'severity' => $meta['severity'],
  'score' => $attack['score'],
 ];
}
  } catch (Exception $e) {
throw new Exception("Failed to insert alerts: " . $e->getMessage());
  }

  return $inserted;
 }

 /**
  * Block an IP address
  *
  * @param string $ip IP address to block
  * @param array $classification Classification result containing attack details
  * @param ?int $ruleId Optional rule ID for reference
  * @return bool True if IP was blocked, false if already blocked
  */
 public function blockIP(string $ip, array $classification, ?int $ruleId = 1): bool
 {
  try {
// Check if already blocked
$stmt = $this->pdo->prepare("
 SELECT id FROM blocked_ip
 WHERE ip = ? AND (is_permanent = 1 OR expires_at > NOW())
");
$stmt->execute([$ip]);

if ($stmt->fetch()) {
 return false;  // Already blocked
}

// Insert new block
$reason = 'ML-detected: ' . implode(', ', array_column($classification['attack_types'], 'label'));
$expiresAt = date('Y-m-d H:i:s', strtotime('+' . self::BLOCK_DURATION));

$stmt = $this->pdo->prepare("
 INSERT INTO blocked_ip (ip, reason, blocked_at, expires_at, is_permanent, rule_id)
 VALUES (:ip, :reason, NOW(), :expires_at, 0, :rule_id)
");

$stmt->execute([
 ':ip'=> $ip,
 ':reason'  => $reason,
 ':expires_at' => $expiresAt,
 ':rule_id' => $ruleId,
]);

return true;
  } catch (Exception $e) {
throw new Exception("Failed to block IP: " . $e->getMessage());
  }
 }

 /**
  * Check if an IP is currently blocked
  *
  * @param string $ip IP address to check
  * @return bool True if IP is blocked
  */
 public function isIPBlocked(string $ip): bool
 {
  try {
$stmt = $this->pdo->prepare("
 SELECT id FROM blocked_ip
 WHERE ip = ? AND (is_permanent = 1 OR expires_at > NOW())
 LIMIT 1
");
$stmt->execute([$ip]);
return (bool) $stmt->fetch();
  } catch (Exception $e) {
throw new Exception("Failed to check if IP is blocked: " . $e->getMessage());
  }
 }

 /**
  * Full pipeline: Classify, log, and block in one call
  *
  * @param string $input URL or input string to check
  * @param ?array $trafficData Optional pre-collected traffic data (collected if not provided)
  * @return array Result array with all details
  */
 public function processRequest(string $input, ?array $trafficData = null): array
 {
  try {
// Collect traffic data if not provided
if ($trafficData === null) {
 $trafficData = $this->collectTrafficData();
}

// Step 1: Classify
$classification = $this->classifyInput($input);
$this->lastResult = $classification;
$this->shouldBlock = $classification['blocked'];

// Step 2: Insert traffic log
$logId = $this->insertTrafficLog($trafficData, $classification['threat_score'], $classification['blocked']);

// Step 3: Insert browser info
$browserInfoId = $this->insertBrowserInfo($logId, $trafficData['browser_info'] ?? []);

// Step 4: Insert alerts if attacks detected
$alerts = $this->insertAlerts($logId, $classification);

// Step 5: Block IP if needed
$ipBlocked = false;
if ($classification['blocked']) {
 $ipBlocked = $this->blockIP($trafficData['ip'], $classification);
}

return [
 'success'=> true,
 'traffic_log_id'  => $logId,
 'browser_info_id' => $browserInfoId,
 'classification'  => $classification,
 'alerts' => $alerts,
 'ip_blocked'=> $ipBlocked,
 'ip'  => $trafficData['ip'],
 'timestamp' => $trafficData['timestamp'],
];
  } catch (Exception $e) {
return [
 'success'  => false,
 'error' => $e->getMessage(),
];
  }
 }

 /**
  * Get the last classification result
  */
 public function getLastResult(): array
 {
  return $this->lastResult;
 }

 /**
  * Check if the last result should be blocked
  */
 public function shouldBlock(): bool
 {
  return $this->shouldBlock;
 }

 /**
  * Get PDO connection for custom queries
  */
 public function getConnection(): PDO
 {
  return $this->pdo;
 }

 /**
  * Close database connection
  */
 public function __destruct()
 {
  $this->pdo = null;
 }
}

