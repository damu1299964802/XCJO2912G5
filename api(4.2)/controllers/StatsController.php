<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/response.php';

/**
 * 统计控制器
 */
class StatsController {
    // 数据库连接
    private $conn;
    
    /**
     * 构造函数
     */
    public function __construct() {
        // 获取数据库连接
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    /**
     * 获取滑板车使用统计
     */
    public function getScooterStats() {
        // 检查请求方法
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            Response::error('Method not allowed', 405);
        }
        
        // 滑板车使用次数统计
        $query = "SELECT s.id, s.scooter_code, s.status, s.battery_level, 
                COUNT(o.id) as rental_count, 
                SUM(o.rental_duration) as total_hours
                FROM scooters s
                LEFT JOIN orders o ON s.id = o.scooter_id AND o.status IN ('ongoing', 'completed')
                GROUP BY s.id
                ORDER BY rental_count DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        $scooter_stats = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $scooter_stats[] = $row;
        }
        
        // 滑板车状态统计
        $query = "SELECT status, COUNT(*) as count
                FROM scooters
                GROUP BY status";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        $status_stats = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $status_stats[] = $row;
        }
        
        Response::success([
            'scooter_usage' => $scooter_stats,
            'status_distribution' => $status_stats
        ], 'Scooter statistics retrieved successfully');
    }
    
    /**
     * 获取订单统计
     */
    public function getOrderStats() {
        // 检查请求方法
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            Response::error('Method not allowed', 405);
        }
        
        // 获取时间范围参数
        $period = isset($_GET['period']) ? $_GET['period'] : 'month';
        
        // 订单状态统计
        $query = "SELECT status, COUNT(*) as count
                FROM orders
                GROUP BY status";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        $status_stats = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $status_stats[] = $row;
        }
        
        // 按时间段统计订单数量
        $date_format = '';
        $group_by = '';
        
        switch ($period) {
            case 'day':
                $date_format = '%Y-%m-%d';
                $group_by = 'DATE(created_at)';
                $limit = 30; // 最近30天
                break;
            case 'week':
                $date_format = '%Y-%u';
                $group_by = 'YEARWEEK(created_at)';
                $limit = 12; // 最近12周
                break;
            case 'month':
            default:
                $date_format = '%Y-%m';
                $group_by = 'DATE_FORMAT(created_at, "%Y-%m")';
                $limit = 12; // 最近12个月
                break;
        }
        
        $query = "SELECT DATE_FORMAT(created_at, '$date_format') as time_period, 
                COUNT(*) as order_count
                FROM orders
                GROUP BY $group_by
                ORDER BY time_period DESC
                LIMIT $limit";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        $time_stats = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $time_stats[] = $row;
        }
        
        // 按租赁时长统计
        $query = "SELECT 
                CASE 
                    WHEN rental_duration <= 1 THEN 'Within 1 hour'
                    WHEN rental_duration <= 4 THEN '1-4 hours'
                    WHEN rental_duration <= 24 THEN '4-24 hours'
                    ELSE 'Over 24 hours'
                END as duration_range,
                COUNT(*) as count
                FROM orders
                GROUP BY duration_range";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        $duration_stats = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $duration_stats[] = $row;
        }
        
        Response::success([
            'status_distribution' => $status_stats,
            'time_distribution' => $time_stats,
            'duration_distribution' => $duration_stats
        ], 'Order statistics retrieved successfully');
    }
}
?>
