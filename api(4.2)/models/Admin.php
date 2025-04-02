<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/auth.php';

/**
 * 管理员模型类
 */
class Admin {
    // 数据库连接和表名
    private $conn;
    private $table_name = "admins";
    
    // 对象属性
    public $id;
    public $username;
    public $password;
    public $email;
    public $created_at;
    public $updated_at;
    
    /**
     * 构造函数
     */
    public function __construct($db) {
        $this->conn = $db;
    }
    
    /**
     * 通过用户名查询管理员
     * 
     * @param string $username 用户名
     * @return bool 是否找到管理员
     */
    public function findByUsername($username) {
        // 查询语句
        $query = "SELECT * FROM " . $this->table_name . "
                WHERE username = :username
                LIMIT 0,1";
        
        // 准备查询
        $stmt = $this->conn->prepare($query);
        
        // 绑定参数
        $stmt->bindParam(":username", $username);
        
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
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            
            return true;
        }
        
        return false;
    }
    
    /**
     * 通过ID查询管理员
     * 
     * @param int $id 管理员ID
     * @return bool 是否找到管理员
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
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            
            return true;
        }
        
        return false;
    }
    
    /**
     * 更新管理员密码
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
}
?>
