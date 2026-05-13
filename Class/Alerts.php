<?php
class Alerts{
    private $db;
    public function __construct(){
        try{
            $this->db = new PDO('mysql:host=localhost;dbname=waf_db', 'root', '', [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
        }catch (PDOException $e){
            error_log("Connection failed: " . $e->getMessage());
            throw new Exception("Database connection failed");
        }
    }

    public function getCritical(){
        return $this->db->query("SELECT * FROM alert WHERE severity = 'CRITICAL'");
    }

    public function getHigh(){
        return $this->db->query("SELECT * FROM alert WHERE severity = 'HIGH'");
    }

    public function getMedium(){
        return $this->db->query("SELECT * FROM alert WHERE severity = 'MEDIUM'");
    }

    public function getResolved(){
        return $this->db->query("SELECT * FROM alert WHERE status = 'resolved'");
    }

    public function getAllAlerts(){
        return $this->db->query("SELECT a.*, tl.source_ip FROM alert a JOIN traffic_log tl ON tl.id = a.traffic_log_id ORDER BY a.created_at DESC");
    }
    
    public function updateAlertStatus($alert_id, $status){
        try {
            $stmt = $this->db->prepare("UPDATE alert SET status = :status WHERE id = :id");
            $stmt->bindParam(':status', $status, PDO::PARAM_STR);
            $stmt->bindParam(':id', $alert_id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error updating alert: " . $e->getMessage());
            return false;
        }
    }
}