<?php
/**
 * 数据库配置文件
 */
class Database {
    // 数据库连接参数
    private $host = "localhost";
    private $db_name = "electric_scooter_rent";
    private $username = "root";
    private $password = "123456";
    private $port = "3306"; 
    public $conn;
    
    // 获取数据库连接
    public function getConnection() {
        $this->conn = null;
        
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";port=" . $this->port,
                $this->username,
                $this->password,
                array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8")
            );
            $this->conn->exec("set names utf8");
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $e) {
            echo "数据库连接失败: " . $e->getMessage();
        }
        
        return $this->conn;
    }
}
?>
