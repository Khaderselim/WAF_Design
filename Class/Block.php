<?php
class Block {
    private $db;

    public function __construct() {
        try {
            $this->db = new PDO(
                'mysql:host=localhost;dbname=waf_db;charset=utf8mb4',
                'root', '',
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
            );
        } catch (PDOException $e) {
            error_log("Connection failed: " . $e->getMessage());
        }
    }

    public function getTotalBlockedIp(): int {
        return (int) $this->db->query("SELECT COUNT(*) FROM blocked_ip")->fetchColumn();
    }

    public function getBlockedToday(): int {
        return (int) $this->db->query("
            SELECT COUNT(*) FROM blocked_ip WHERE DATE(blocked_at) = CURDATE()
        ")->fetchColumn();
    }

    public function getBlockRate(): float {
        $total   = (int) $this->db->query("SELECT COUNT(*) FROM traffic_log")->fetchColumn();
        $blocked = (int) $this->db->query("SELECT COUNT(*) FROM traffic_log WHERE blocked = 1")->fetchColumn();
        return $total > 0 ? round($blocked / $total * 100, 2) : 0;
    }

    public function getAlertToBlock(): array {
        return $this->db->query("
            SELECT tl.*, a.type, a.severity, a.status
            FROM alert a
            JOIN traffic_log tl ON tl.id = a.traffic_log_id
            ORDER BY a.created_at DESC
        ")->fetchAll();
    }

    public function blockIP(string $source_ip, int $traffic_id): bool {
        try {
            // Check if already blocked
            $check = $this->db->prepare("SELECT id FROM blocked_ip WHERE ip = ?");
            $check->execute([$source_ip]);

            if (!$check->fetchColumn()) {
                $stmt = $this->db->prepare("
                    INSERT INTO blocked_ip (ip, blocked_at, reason, is_permanent)
                    VALUES (?, NOW(), 'Manual block', 1)
                ");
                $stmt->execute([$source_ip]);
            }

            // Mark traffic as blocked
            $update = $this->db->prepare("UPDATE traffic_log SET blocked = 1 WHERE id = ?");
            return $update->execute([$traffic_id]);

        } catch (PDOException $e) {
            error_log("blockIP error: " . $e->getMessage());
            return false;
        }
    }

    public function unblockIP(string $source_ip, int $traffic_id): bool {
        try {
            $stmt = $this->db->prepare("DELETE FROM blocked_ip WHERE ip = ?");
            $stmt->execute([$source_ip]);

            $update = $this->db->prepare("UPDATE traffic_log SET blocked = 0 WHERE id = ?");
            return $update->execute([$traffic_id]);

        } catch (PDOException $e) {
            error_log("unblockIP error: " . $e->getMessage());
            return false;
        }
    }
}