<?php
class Dashboard{
    private $db;
    public function __construct(){
        try{
            $this->db = new PDO('mysql:host=localhost;dbname=waf_db', 'root', '');

        }catch (PDOException $e){
            echo("Connection failed: " . $e->getMessage());
        }
    }
    function getTraffic()
    {
        return $this->db->query("SELECT * FROM traffic_log");

    }

    function getAlerts()
    {
        return $this->db->query("SELECT * FROM alert");
    }

    function getBlocked()
    {
        return $this->db->query("SELECT * FROM blocked_ip");
    }

    function getTopThreat(){
        return $this->db->query("select  name_threat from (select  type as name_threat , count(type) as number_attack FROM traffic_log t
                                                    JOIN alert a ON a.traffic_log_id = t.id
    group by type
    order by number_attack desc) as t limit 1")->fetchColumn();
    }
    function getrecenttraffic(){
        return $this->db->query("SELECT * FROM traffic_log order by id desc limit 5");
    }

    function getTrafficTypeStats(){
        $query = "SELECT 'Normal' as type, COUNT(*) as count 
                  FROM traffic_log 
                  WHERE id NOT IN (SELECT traffic_log_id FROM alert)
                  UNION
                  SELECT type, COUNT(*) as count 
                  FROM traffic_log t
                  JOIN alert a ON a.traffic_log_id = t.id
                  GROUP BY type
                  ORDER BY type";
        $result = $this->db->query($query)->fetchAll(PDO::FETCH_ASSOC);
        
        $labels = array();
        $data = array();
        $colors = array("#2dce89", "#1d7af3", "#f3545d", "#fdaf4b", "#ffaa00");
        
        foreach($result as $index => $row) {
            $labels[] = strtoupper($row['type']);
            $data[] = (int)$row['count'];
        }
        
        return array(
            'labels' => $labels,
            'data' => $data,
            'colors' => array_slice($colors, 0, count($data))
        );
    }

}
