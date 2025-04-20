<?php
require_once __DIR__ . '/../config/database.php';

/**
 * 滑板车模型类
 */
class Scooter {
    // 数据库连接和表名
    private $conn;
    public $table_name = "scooters";
    
    // 对象属性
    public $id;
    public $scooter_code;
    public $status;
    public $battery_level;
    public $latitude;
    public $longitude;
    public $location;
    public $created_at;
    public $updated_at;
    public $hourly_rate;
    
    /**
     * 构造函数
     */
    public function __construct($db = null) {
        $this->conn = $db;
    }
    
    /**
     * 创建新滑板车
     * 
     * @return bool 是否成功
     */
    public function create() {
        // 查询语句
        $query = "INSERT INTO " . $this->table_name . "
                SET
                    scooter_code = :scooter_code,
                    status = :status,
                    battery_level = :battery_level,
                    latitude = :latitude,
                    longitude = :longitude,
                    location = :location";
        
        // 准备查询
        $stmt = $this->conn->prepare($query);
        
        // 清理数据
        $this->scooter_code = htmlspecialchars(strip_tags($this->scooter_code));
        $this->status = htmlspecialchars(strip_tags($this->status));
        $this->battery_level = htmlspecialchars(strip_tags($this->battery_level));
        $this->latitude = htmlspecialchars(strip_tags($this->latitude));
        $this->longitude = htmlspecialchars(strip_tags($this->longitude));
        $this->location = !empty($this->location) ? htmlspecialchars(strip_tags($this->location)) : null;
        
        // 如果设置了经纬度但没有设置位置描述，则自动生成
        if (empty($this->location) && (!empty($this->latitude) || !empty($this->longitude))) {
            $this->location = "Lat: " . $this->latitude . ", Lng: " . $this->longitude;
        }
        
        // 绑定参数
        $stmt->bindParam(":scooter_code", $this->scooter_code);
        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":battery_level", $this->battery_level);
        $stmt->bindParam(":latitude", $this->latitude);
        $stmt->bindParam(":longitude", $this->longitude);
        $stmt->bindParam(":location", $this->location);
        
        // 执行查询
        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        
        return false;
    }
    
    /**
     * 通过ID查询滑板车
     * 
     * @param int $id 滑板车ID
     * @return bool 是否找到滑板车
     */
    public function findById($id) {
        // 查询语句
        $query = "SELECT * FROM " . $this->table_name . "
                WHERE id = :id
                LIMIT 0,1";
        
        // 准备查询
        $stmt = $this->conn->prepare($query);
        
        // 绑定参数
        $stmt->bindParam(":id", $id);
        
        // 执行查询
        $stmt->execute();
        
        // 获取结果
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row) {
            // 设置属性
            $this->id = $row['id'];
            $this->scooter_code = $row['scooter_code'];
            $this->status = $row['status'];
            $this->battery_level = $row['battery_level'];
            $this->latitude = $row['latitude'];
            $this->longitude = $row['longitude'];
            $this->location = $row['location'] ?? null;
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            $this->hourly_rate = $row['hourly_rate'];
            
            return true;
        }
        
        return false;
    }
    
    /**
     * 通过编号查询滑板车
     * 
     * @param string $code 滑板车编号
     * @return bool 是否找到滑板车
     */
    public function findByCode($code) {
        // 查询语句
        $query = "SELECT * FROM " . $this->table_name . "
                WHERE scooter_code = :code
                LIMIT 0,1";
        
        // 准备查询
        $stmt = $this->conn->prepare($query);
        
        // 绑定参数
        $stmt->bindParam(":code", $code);
        
        // 执行查询
        $stmt->execute();
        
        // 获取结果
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row) {
            // 设置属性
            $this->id = $row['id'];
            $this->scooter_code = $row['scooter_code'];
            $this->status = $row['status'];
            $this->battery_level = $row['battery_level'];
            $this->latitude = $row['latitude'];
            $this->longitude = $row['longitude'];
            $this->location = $row['location'] ?? null;
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            $this->hourly_rate = $row['hourly_rate'];
            
            return true;
        }
        
        return false;
    }

    public function setDisabled($scooterId) {
        $query = "UPDATE " . $this->table_name . "
        SET
            status = 'disabled'
        WHERE
            id = :id";

        // 准备查询
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":id", $scooterId);
        
        // 执行查询
        if ($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    /**
     * 更新滑板车信息
     * 
     * @return bool 是否成功
     */
    public function update() {
        // 查询语句
        $query = "UPDATE " . $this->table_name . "
                SET
                    scooter_code = :scooter_code,
                    status = :status,
                    hourly_rate = :hourly_rate,
                    battery_level = :battery_level,
                    latitude = :latitude,
                    longitude = :longitude,
                    location = :location
                WHERE
                    id = :id";
        
        // 准备查询
        $stmt = $this->conn->prepare($query);
        
        // 清理数据
        $this->scooter_code = htmlspecialchars(strip_tags($this->scooter_code));
        $this->status = htmlspecialchars(strip_tags($this->status));
        $this->battery_level = htmlspecialchars(strip_tags($this->battery_level));
        $this->latitude = htmlspecialchars(strip_tags($this->latitude));
        $this->longitude = htmlspecialchars(strip_tags($this->longitude));
        $this->location = !empty($this->location) ? htmlspecialchars(strip_tags($this->location)) : null;
        $this->id = htmlspecialchars(strip_tags($this->id));
        $this->hourly_rate = htmlspecialchars(strip_tags($this->hourly_rate));
        
        // 如果设置了经纬度但没有设置位置描述，则自动生成
        if (empty($this->location) && (!empty($this->latitude) || !empty($this->longitude))) {
            $this->location = "Lat: " . $this->latitude . ", Lng: " . $this->longitude;
        }
        
        // 绑定参数
        $stmt->bindParam(":scooter_code", $this->scooter_code);
        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":battery_level", $this->battery_level);
        $stmt->bindParam(":latitude", $this->latitude);
        $stmt->bindParam(":longitude", $this->longitude);
        $stmt->bindParam(":location", $this->location);
        $stmt->bindParam(":hourly_rate", $this->hourly_rate);
        $stmt->bindParam(":id", $this->id);
        
        // 执行查询
        if ($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    /**
     * 删除滑板车
     * 
     * @return bool 是否成功
     */
    public function delete() {
        // 查询语句
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        
        // 准备查询
        $stmt = $this->conn->prepare($query);
        
        // 清理数据
        $this->id = htmlspecialchars(strip_tags($this->id));
        
        // 绑定参数
        $stmt->bindParam(":id", $this->id);
        
        // 执行查询
        if ($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    /**
     * 获取所有可用滑板车
     * 
     * @return PDOStatement 查询结果
     */
    public function getAllAvailable() {
        // 查询语句
        $query = "SELECT * FROM " . $this->table_name . " WHERE status = 'available' ORDER BY id DESC";
        
        // 准备查询
        $stmt = $this->conn->prepare($query);
        
        // 执行查询
        $stmt->execute();
        
        return $stmt;
    }
    
    /**
     * 获取所有滑板车
     * 
     * @return PDOStatement 查询结果
     */
    public function getAll($status) {
        $where  = ' where 1=1 ';
        if ($status) {
            $where .= " AND status = '{$status}'";
        }
        // 查询语句
        $query = "SELECT * FROM " . $this->table_name . " {$where} ORDER BY id DESC";

        // 准备查询
        $stmt = $this->conn->prepare($query);
        
        // 执行查询
        $stmt->execute();
        
        return $stmt;
    }
}
?>
