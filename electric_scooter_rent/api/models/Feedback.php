<?php
require_once __DIR__ . '/../config/database.php';

/**
 * 反馈模型类
 */
class Feedback {
    // 数据库连接和表名
    private $conn;
    private $table_name = "feedbacks";
    
    // 对象属性
    public $id;
    public $user_id;
    public $scooter_id;
    public $type;
    public $content;
    public $status;
    public $admin_reply;
    public $created_at;
    public $updated_at;
    
    /**
     * 构造函数
     */
    public function __construct($db) {
        $this->conn = $db;
    }
    
    /**
     * 创建新反馈
     * 
     * @return bool 是否成功
     */
    public function create() {
        // 查询语句
        $query = "INSERT INTO " . $this->table_name . "
                SET
                    user_id = :user_id,
                    scooter_id = :scooter_id,
                    type = :type,
                    content = :content,
                    status = :status";
        
        // 准备查询
        $stmt = $this->conn->prepare($query);
        
        // 清理数据
        $this->user_id = htmlspecialchars(strip_tags($this->user_id));
        $this->type = htmlspecialchars(strip_tags($this->type));
        $this->content = htmlspecialchars(strip_tags($this->content));
        $this->status = htmlspecialchars(strip_tags($this->status));
        
        // 绑定参数
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->bindParam(":scooter_id", $this->scooter_id);
        $stmt->bindParam(":type", $this->type);
        $stmt->bindParam(":content", $this->content);
        $stmt->bindParam(":status", $this->status);
        
        // 执行查询
        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        
        return false;
    }
    
    /**
     * 通过ID查询反馈
     * 
     * @param int $id 反馈ID
     * @return bool 是否找到反馈
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
            $this->type = $row['type'];
            $this->content = $row['content'];
            $this->status = $row['status'];
            $this->admin_reply = $row['admin_reply'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            
            return true;
        }
        
        return false;
    }
    
    /**
     * 更新反馈信息
     * 
     * @return bool 是否成功
     */
    public function update() {
        // 查询语句
        $query = "UPDATE " . $this->table_name . "
                SET
                    status = :status,
                    admin_reply = :admin_reply
                WHERE
                    id = :id";
        
        // 准备查询
        $stmt = $this->conn->prepare($query);
        
        // 清理数据
        $this->status = htmlspecialchars(strip_tags($this->status));
        $this->admin_reply = htmlspecialchars(strip_tags($this->admin_reply));
        $this->id = htmlspecialchars(strip_tags($this->id));
        
        // 绑定参数
        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":admin_reply", $this->admin_reply);
        $stmt->bindParam(":id", $this->id);
        
        // 执行查询
        if ($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    /**
     * 获取用户的所有反馈
     * 
     * @param int $user_id 用户ID
     * @return PDOStatement 查询结果
     */
    public function getAllByUser($user_id) {
        // 查询语句
        $query = "SELECT f.*, s.scooter_code 
                FROM " . $this->table_name . " f
                LEFT JOIN scooters s ON f.scooter_id = s.id
                WHERE f.user_id = :user_id 
                ORDER BY f.created_at DESC";
        
        // 准备查询
        $stmt = $this->conn->prepare($query);
        
        // 绑定参数
        $stmt->bindParam(":user_id", $user_id);
        
        // 执行查询
        $stmt->execute();
        
        return $stmt;
    }
    
    /**
     * 获取所有反馈
     * 
     * @param string $status 反馈状态（可选）
     * @return PDOStatement 查询结果
     */
    public function getAll($status = null) {
        // 查询语句
        $query = "SELECT f.*, u.username, s.scooter_code 
                FROM " . $this->table_name . " f
                JOIN users u ON f.user_id = u.id
                LEFT JOIN scooters s ON f.scooter_id = s.id";
        
        if ($status) {
            $query .= " WHERE f.status = :status";
        }
        
        $query .= " ORDER BY 
                    CASE 
                        WHEN f.status = 'pending' THEN 1
                        WHEN f.status = 'processing' THEN 2
                        ELSE 3
                    END,
                    f.created_at DESC";
        
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
}
?>
