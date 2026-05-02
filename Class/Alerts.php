<?php
class Alerts{
    private $db;
    public function __construct(){
        try{
            $this->db = new PDO('mysql:host=localhost;dbname=waf_db', 'root', '');

        }catch (PDOException $e){
            echo("Connection failed: " . $e->getMessage());
        }
    }
    function getCritical(){
        return $this->db->query("SELECT * FROM alert where severity = 'critical'");
    }
    function getHigh(){
        return $this->db->query("SELECT * FROM alert where severity = 'high'");
    }
    function getMedium(){
        return $this->db->query("SELECT * FROM alert where severity = 'medium'");
    }
    function getResolved(){
        return $this->db->query("SELECT * FROM alert where status = 'resolved'");
    }
    function getallalerts(){
        return $this->db->query("SELECT *  FROM alert A JOIN waf_db.traffic_log tl on tl.id = A.traffic_log_id");
    }
    
    function updateAlertStatus($alert_id, $status){
        try {
            $stmt = $this->db->prepare("UPDATE alert SET status = :status WHERE id = :id");
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':id', $alert_id);
            return $stmt->execute();
        } catch (PDOException $e) {
            echo("Error: " . $e->getMessage());
            return false;
        }
    }
}