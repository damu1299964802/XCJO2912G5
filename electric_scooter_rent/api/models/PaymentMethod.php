<?php
/**
 * 支付方式模型
 */
require_once __DIR__ . '/../config/database.php';

class PaymentMethod {
    // 数据库连接
    private $conn;
    
    // 表名
    private $table_name = "payment_methods";
    
    // 对象属性
    public $id;
    public $user_id;
    public $card_holder_name;
    public $card_number_last4;
    public $card_type;
    public $expiration_month;
    public $expiration_year;
    public $token;
    public $is_default;
    public $created_at;
    public $updated_at;
    
    /**
     * 构造函数
     */
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    /**
     * 获取用户的支付方式列表
     */
    public function getByUserId($user_id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE user_id = :user_id ORDER BY is_default DESC, created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * 创建支付方式
     */
    public function create($data) {
        $query = "INSERT INTO " . $this->table_name . " 
                (user_id, card_holder_name, card_number_last4, card_type, expiration_month, expiration_year, token, is_default, created_at, updated_at) 
                VALUES 
                (:user_id, :card_holder_name, :card_number_last4, :card_type, :expiration_month, :expiration_year, :token, :is_default, NOW(), NOW())";
        
        $stmt = $this->conn->prepare($query);
    
        
        // 从卡号中提取后四位
        $card_number_last4 = substr($data['card_number'], -4);
        
        // 生成随机token (实际应用中应使用支付处理系统的token)
        $token = md5($data['card_number'] . time());
        
        // 判断卡类型 (简单实现)
        $card_type = 'Unknown';
        $first_digit = substr($data['card_number'], 0, 1);
        if ($first_digit == '4') {
            $card_type = 'Visa';
        } else if ($first_digit == '5') {
            $card_type = 'MasterCard';
        } else if ($first_digit == '3') {
            $card_type = 'American Express';
        }
        
        // 如果是第一张卡，默认设为默认卡
        $is_default = $data['is_default'] ?? false;
        if ($this->isFirstCard($data['user_id'])) {
            $is_default = true;
        }
        // 绑定参数
        $stmt->bindParam(":user_id", $data['user_id']);
        $stmt->bindParam(":card_holder_name", $data['card_holder_name']);
        $stmt->bindParam(":card_number_last4", $card_number_last4);
        $stmt->bindParam(":card_type", $card_type);
        $stmt->bindParam(":expiration_month", $data['expiration_month']);
        $stmt->bindParam(":expiration_year", $data['expiration_year']);
        $stmt->bindParam(":token", $token);
        $stmt->bindParam(":is_default", $is_default, PDO::PARAM_BOOL);
        
        if ($stmt->execute()) {
            $lastId = $this->conn->lastInsertId();
            // 如果是默认卡，需要将其他卡设为非默认
            if ($is_default) {
                $this->setAllOthersNotDefault($data['user_id'], $lastId);
            }
            return $lastId;
        }
        
        return false;
    }
    
    /**
     * 检查是否为用户的第一张卡
     */
    private function isFirstCard($user_id) {
        $query = "SELECT COUNT(*) FROM " . $this->table_name . " WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();
        return $stmt->fetchColumn() == 0;
    }
    
    /**
     * 将用户的其他卡设为非默认
     */
    private function setAllOthersNotDefault($user_id, $except_id) {
        $query = "UPDATE " . $this->table_name . " SET is_default = 0 WHERE user_id = :user_id AND id != :except_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":except_id", $except_id);
        return $stmt->execute();
    }
    
    /**
     * 根据ID查找支付方式
     */
    public function findById($id) {
        // 查询语句
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id";
        
        // 准备查询语句
        $stmt = $this->conn->prepare($query);
        
        // 绑定参数
        $stmt->bindParam(":id", $id);
        
        // 执行查询
        $stmt->execute();
        
        // 获取结果
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row) {
            $this->id = $row['id'];
            $this->user_id = $row['user_id'];
            $this->card_holder_name = $row['card_holder_name'];
            $this->card_number_last4 = $row['card_number_last4'];
            $this->card_type = $row['card_type'];
            $this->expiration_month = $row['expiration_month'];
            $this->expiration_year = $row['expiration_year'];
            $this->token = $row['token'];
            $this->is_default = $row['is_default'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            return true;
        }
        
        return false;
    }
    
    /**
     * 设置默认支付方式
     */
    public function setDefault($user_id, $payment_method_id) {
        // 开始事务
        $this->conn->beginTransaction();
        
        try {
            // 先将用户的所有支付方式设置为非默认
            $query = "UPDATE " . $this->table_name . " SET is_default = 0 WHERE user_id = :user_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":user_id", $user_id);
            $stmt->execute();
            
            // 设置指定的支付方式为默认
            $query = "UPDATE " . $this->table_name . " SET is_default = 1 WHERE id = :id AND user_id = :user_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id", $payment_method_id);
            $stmt->bindParam(":user_id", $user_id);
            $stmt->execute();
            
            // 提交事务
            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            // 回滚事务
            $this->conn->rollBack();
            throw $e;
        }
    }
    
    /**
     * 删除支付方式
     */
    public function delete($user_id, $payment_method_id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id AND user_id = :user_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $payment_method_id);
        $stmt->bindParam(":user_id", $user_id);
        
        return $stmt->execute();
    }
}
?> 