<?php
require_once __DIR__ . '/../config/database.php';

/**
 * 订单模型类
 */
class Order {
    // 数据库连接和表名
    private $conn;
    private $table_name = "orders";
    
    // 对象属性
    public $id;
    public $user_id;
    public $scooter_id;
    public $rental_duration;
    public $start_time;
    public $end_time;
    public $status;
    public $created_at;
    public $updated_at;
    
    /**
     * 构造函数
     */
    public function __construct($db) {
        $this->conn = $db;
    }
    
    /**
     * 创建新订单
     * 
     * @return bool 是否成功
     */
    public function create() {
        // 查询语句
        $query = "INSERT INTO " . $this->table_name . "
                SET
                    user_id = :user_id,
                    scooter_id = :scooter_id,
                    rental_duration = :rental_duration,
                    start_time = :start_time,
                    end_time = :end_time,
                    status = :status";
        
        // 准备查询
        $stmt = $this->conn->prepare($query);
        
        // 清理数据
        $this->user_id = htmlspecialchars(strip_tags($this->user_id));
        $this->scooter_id = htmlspecialchars(strip_tags($this->scooter_id));
        $this->rental_duration = htmlspecialchars(strip_tags($this->rental_duration));
        $this->status = htmlspecialchars(strip_tags($this->status));
        
        // 绑定参数
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->bindParam(":scooter_id", $this->scooter_id);
        $stmt->bindParam(":rental_duration", $this->rental_duration);
        $stmt->bindParam(":start_time", $this->start_time);
        $stmt->bindParam(":end_time", $this->end_time);
        $stmt->bindParam(":status", $this->status);
        
        // 执行查询
        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        
        return false;
    }
    
    /**
     * 通过ID查询订单
     * 
     * @param int $id 订单ID
     * @return bool 是否找到订单
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
            $this->user_id = $row['user_id'];
            $this->scooter_id = $row['scooter_id'];
            $this->rental_duration = $row['rental_duration'];
            $this->start_time = $row['start_time'];
            $this->end_time = $row['end_time'];
            $this->status = $row['status'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            
            return true;
        }
        
        return false;
    }
    
    /**
     * 更新订单信息
     * 
     * @return bool 是否成功
     */
    public function update() {
        // 查询语句
        $query = "UPDATE " . $this->table_name . "
                SET
                    rental_duration = :rental_duration,
                    start_time = :start_time,
                    end_time = :end_time,
                    status = :status
                WHERE
                    id = :id";
        
        // 准备查询
        $stmt = $this->conn->prepare($query);
        
        // 清理数据
        $this->rental_duration = htmlspecialchars(strip_tags($this->rental_duration));
        $this->status = htmlspecialchars(strip_tags($this->status));
        $this->id = htmlspecialchars(strip_tags($this->id));
        
        // 绑定参数
        $stmt->bindParam(":rental_duration", $this->rental_duration);
        $stmt->bindParam(":start_time", $this->start_time);
        $stmt->bindParam(":end_time", $this->end_time);
        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":id", $this->id);
        
        // 执行查询
        if ($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    /**
     * 删除订单
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
     * 获取用户的所有订单
     * 
     * @param int $user_id 用户ID
     * @return PDOStatement 查询结果
     */
    public function getAllByUser($user_id) {
        // 查询语句
        $query = "SELECT o.*, s.scooter_code 
                FROM " . $this->table_name . " o
                JOIN scooters s ON o.scooter_id = s.id
                WHERE o.user_id = :user_id 
                ORDER BY o.created_at DESC";
        
        // 准备查询
        $stmt = $this->conn->prepare($query);
        
        // 绑定参数
        $stmt->bindParam(":user_id", $user_id);
        
        // 执行查询
        $stmt->execute();
        
        return $stmt;
    }
    
    /**
     * 获取用户的当前订单
     * 
     * @param int $user_id 用户ID
     * @return PDOStatement 查询结果
     */
    public function getCurrentByUser($user_id) {
        // 查询语句
        $query = "SELECT o.*, s.scooter_code 
                FROM " . $this->table_name . " o
                JOIN scooters s ON o.scooter_id = s.id
                WHERE o.user_id = :user_id 
                AND (o.status = 'pending' OR o.status = 'ongoing')
                ORDER BY o.created_at DESC";
        
        // 准备查询
        $stmt = $this->conn->prepare($query);
        
        // 绑定参数
        $stmt->bindParam(":user_id", $user_id);
        
        // 执行查询
        $stmt->execute();
        
        return $stmt;
    }
    
    /**
     * 获取所有订单
     * 
     * @param string $status 订单状态（可选）
     * @return PDOStatement 查询结果
     */
    public function getAll($status = null) {
        // 查询语句
        $query = "SELECT o.*, u.username, s.scooter_code 
                FROM " . $this->table_name . " o
                JOIN users u ON o.user_id = u.id
                JOIN scooters s ON o.scooter_id = s.id";
        
        if ($status) {
            $query .= " WHERE o.status = :status";
        }
        
        $query .= " ORDER BY o.created_at DESC";
        
        // 准备查询
        $stmt = $this->conn->prepare($query);
        
        // 绑定参数
        if ($status) {
            $stmt->bindParam(":status", $status);
        }
        
        // 执行查询
        $stmt->execute();
        
        return $stmt;
    }
    
    /**
     * 检查用户是否有pending状态的订单
     * 
     * @param int $user_id 用户ID
     * @return array|null 包含pending订单信息的数组，如果没有则返回null
     */
    public function checkPendingByUser($user_id) {
        // 查询语句
        $query = "SELECT o.*, s.scooter_code 
                FROM " . $this->table_name . " o
                JOIN scooters s ON o.scooter_id = s.id
                WHERE o.user_id = :user_id 
                AND o.status = 'pending'
                LIMIT 0,1";
        
        // 准备查询
        $stmt = $this->conn->prepare($query);
        
        // 绑定参数
        $stmt->bindParam(":user_id", $user_id);
        
        // 执行查询
        $stmt->execute();
        
        // 获取结果
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row ? $row : null;
    }
}
?>
