<?php
class Traffic {
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
    function getallowedtraffic(){
        return $this->db->query("SELECT * FROM traffic_log where blocked = 0");
    }
    function getblockedtraffic(){
        return $this->db->query("SELECT * FROM traffic_log where blocked = 1");
    }
    function Blockrate(){
        return $this->getTraffic()->rowCount()? round($this->getblockedtraffic()->rowCount()/$this->getTraffic()->rowCount(),2)*100 : 0;
    }

    function topurl()
    {
        return $this->db->query("SELECT url, COUNT(*) as count FROM traffic_log t
group by url order by count desc limit 5");
    }

    function getHourlyTraffic(){
        $query = "SELECT 
                    DATE_FORMAT(timestamp, '%H:00') as hour,
                    COUNT(*) as count
                  FROM traffic_log
                  WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                  GROUP BY DATE_FORMAT(timestamp, '%H:00')
                  ORDER BY hour ASC";
        $result = $this->db->query($query)->fetchAll(PDO::FETCH_ASSOC);
        
        $labels = array();
        $data = array();
        
        // Create 24-hour timeline
        for($i = 0; $i < 24; $i++){
            $hour = str_pad($i, 2, '0', STR_PAD_LEFT) . ":00";
            $labels[] = $hour;
            $data[$hour] = 0;
        }
        
        // Fill in actual data
        foreach($result as $row) {
            $data[$row['hour']] = (int)$row['count'];
        }
        
        return array(
            'labels' => $labels,
            'data' => array_values($data)
        );
    }
}
