<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/auth.php';

/**
 * 用户模型类
 */
class User {
    // 数据库连接和表名
    private $conn;
    private $table_name = "users";
    
    // 对象属性
    public $id;
    public $username;
    public $password;
    public $email;
    public $phone;
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
     * 创建新用户
     * 
     * @return bool 是否成功
     */
    public function create() {
        // 查询语句
        $query = "INSERT INTO " . $this->table_name . "
                SET
                    username = :username,
                    password = :password,
                    email = :email,
                    phone = :phone,
                    status = :status";
        
        // 准备查询
        $stmt = $this->conn->prepare($query);
        
        // 清理数据
        $this->username = htmlspecialchars(strip_tags($this->username));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->phone = htmlspecialchars(strip_tags($this->phone));
        $this->status = htmlspecialchars(strip_tags($this->status));
        
        // 哈希密码
        $this->password = Auth::hashPassword($this->password);
        
        // 绑定参数
        $stmt->bindParam(":username", $this->username);
        $stmt->bindParam(":password", $this->password);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":phone", $this->phone);
        $stmt->bindParam(":status", $this->status);
        
        // 执行查询
        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        
        return false;
    }
    
    /**
     * 通过邮箱或手机号查询用户
     * 
     * @param string $email_or_phone 邮箱或手机号
     * @return bool 是否找到用户
     */
    public function findByEmailOrPhone($email_or_phone) {
        // 查询语句
        $query = "SELECT * FROM " . $this->table_name . "
                WHERE email = :email_or_phone OR phone = :email_or_phone
                LIMIT 0,1";
        
        // 准备查询
        $stmt = $this->conn->prepare($query);
        
        // 绑定参数
        $stmt->bindParam(":email_or_phone", $email_or_phone);
        
        // 执行查询
        $stmt->execute();
        
        // 获取结果
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row) {
            // 设置属性
            $this->id = $row['id'];
            $this->username = $row['username'];
            $this->password = $row['password'];
            $this->email = $row['email'];
            $this->phone = $row['phone'];
            $this->status = $row['status'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            
            return true;
        }
        
        return false;
    }
    
    /**
     * 通过ID查询用户
     * 
     * @param int $id 用户ID
     * @return bool 是否找到用户
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
            $this->username = $row['username'];
            $this->password = $row['password'];
            $this->email = $row['email'];
            $this->phone = $row['phone'];
            $this->status = $row['status'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            
            return true;
        }
        
        return false;
    }
    
    /**
     * 更新用户信息
     * 
     * @return bool 是否成功
     */
    public function update() {
        // 查询语句
        $query = "UPDATE " . $this->table_name . "
                SET
                    username = :username,
                    email = :email,
                    phone = :phone,
                    status = :status
                WHERE
                    id = :id";
        
        // 准备查询
        $stmt = $this->conn->prepare($query);
        
        // 清理数据
        $this->username = htmlspecialchars(strip_tags($this->username));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->phone = htmlspecialchars(strip_tags($this->phone));
        $this->status = htmlspecialchars(strip_tags($this->status));
        $this->id = htmlspecialchars(strip_tags($this->id));
        
        // 绑定参数
        $stmt->bindParam(":username", $this->username);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":phone", $this->phone);
        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":id", $this->id);
        
        // 执行查询
        if ($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    /**
     * 更新用户密码
     * 
     * @param string $new_password 新密码
     * @return bool 是否成功
     */
    public function updatePassword($new_password) {
        // 查询语句
        $query = "UPDATE " . $this->table_name . "
                SET
                    password = :password
                WHERE
                    id = :id";
        
        // 准备查询
        $stmt = $this->conn->prepare($query);
        
        // 哈希密码
        $hashed_password = Auth::hashPassword($new_password);
        
        // 绑定参数
        $stmt->bindParam(":password", $hashed_password);
        $stmt->bindParam(":id", $this->id);
        
        // 执行查询
        if ($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    /**
     * 获取所有用户
     * 
     * @return PDOStatement 查询结果
     */
    public function getAll() {
        // 查询语句
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY id DESC";
        
        // 准备查询
        $stmt = $this->conn->prepare($query);
        
        // 执行查询
        $stmt->execute();
        
        return $stmt;
    }
}
?>
