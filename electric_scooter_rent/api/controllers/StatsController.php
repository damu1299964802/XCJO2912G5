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
        
        // 获取时间范围参数
        $period = isset($_GET['period']) ? $_GET['period'] : 'month';
        
        // 滑板车使用次数统计
        $query = "SELECT s.id, s.scooter_code, s.status, s.battery_level, 
                COUNT(o.id) as usage_count, 
                IFNULL(SUM(TIMESTAMPDIFF(HOUR, 
                    CASE WHEN o.start_time IS NULL THEN o.created_at ELSE o.start_time END, 
                    CASE WHEN o.end_time IS NULL THEN NOW() ELSE o.end_time END)), 0) as total_usage_time
                FROM scooters s
                LEFT JOIN orders o ON s.id = o.scooter_id AND o.status IN ('ongoing', 'completed')
                GROUP BY s.id
                ORDER BY usage_count DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        $scooter_usage = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $scooter_usage[] = [
                'id' => $row['id'],
                'scooter_code' => $row['scooter_code'],
                'status' => $row['status'],
                'battery_level' => (int)$row['battery_level'],
                'usage_count' => (int)$row['usage_count'],
                'total_usage_time' => (int)$row['total_usage_time']
            ];
        }
        
        // 滑板车状态统计
        $query = "SELECT status, COUNT(*) as count
                FROM scooters
                GROUP BY status";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        $status_distribution = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $status_distribution[$row['status']] = (int)$row['count'];
        }
        
        // 获取使用频率统计（按小时段分布）
        $query = "SELECT 
                HOUR(CASE WHEN o.start_time IS NULL THEN o.created_at ELSE o.start_time END) as hour_of_day,
                COUNT(*) as order_count
                FROM orders o
                WHERE o.status IN ('ongoing', 'completed')
                GROUP BY hour_of_day
                ORDER BY hour_of_day";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        $hourly_usage = array_fill(0, 24, 0); // 初始化24小时的数组
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $hour = (int)$row['hour_of_day'];
            $hourly_usage[$hour] = (int)$row['order_count'];
        }
        
        // 获取热门区域统计（通过经纬度聚类）
        $query = "SELECT 
                CONCAT(ROUND(latitude, 2), ',', ROUND(longitude, 2)) as location_group,
                COUNT(*) as order_count
                FROM orders o
                JOIN scooters s ON o.scooter_id = s.id
                WHERE s.latitude IS NOT NULL AND s.longitude IS NOT NULL
                GROUP BY location_group
                ORDER BY order_count DESC
                LIMIT 5";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        $popular_locations = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $popular_locations[] = [
                'location' => $row['location_group'],
                'order_count' => (int)$row['order_count']
            ];
        }
        
        Response::success([
            'scooter_usage' => $scooter_usage,
            'status_distribution' => $status_distribution,
            'hourly_usage' => $hourly_usage,
            'popular_locations' => $popular_locations
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
        
        $status_distribution = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $status_distribution[$row['status']] = (int)$row['count'];
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
        
        // 获取最近的订单时间范围
        $query = "SELECT MIN(created_at) as min_date FROM orders";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $min_date = $stmt->fetch(PDO::FETCH_ASSOC)['min_date'];
        
        // 生成完整的时间序列（包括没有订单的日期）
        $time_periods = [];
        $end_date = new DateTime();
        
        if ($period == 'day') {
            $start_date = new DateTime($min_date);
            $interval = new DateInterval('P1D');
            $date_format_php = 'Y-m-d';
            
            // 如果时间跨度大于30天，只取最近30天
            $diff = $start_date->diff($end_date);
            if ($diff->days > 30) {
                $start_date = clone $end_date;
                $start_date->sub(new DateInterval('P29D'));
            }
        } elseif ($period == 'week') {
            $start_date = new DateTime($min_date);
            $start_date->setISODate($start_date->format('Y'), $start_date->format('W'));
            $interval = new DateInterval('P7D');
            $date_format_php = 'Y-W';
            
            // 如果时间跨度大于12周，只取最近12周
            $diff = $start_date->diff($end_date);
            if ($diff->days > 84) {
                $start_date = clone $end_date;
                $start_date->sub(new DateInterval('P84D'));
                $start_date->setISODate($start_date->format('Y'), $start_date->format('W'));
            }
        } else { // month
            $start_date = new DateTime($min_date);
            $start_date->setDate($start_date->format('Y'), $start_date->format('m'), 1);
            $interval = new DateInterval('P1M');
            $date_format_php = 'Y-m';
            
            // 如果时间跨度大于12个月，只取最近12个月
            $diff = $end_date->diff($start_date);
            if (($diff->y * 12 + $diff->m) > 12) {
                $start_date = clone $end_date;
                $start_date->sub(new DateInterval('P11M'));
                $start_date->setDate($start_date->format('Y'), $start_date->format('m'), 1);
            }
        }
        
        $period_obj = new DatePeriod($start_date, $interval, $end_date);
        
        foreach ($period_obj as $date) {
            $time_periods[$date->format($date_format_php)] = 0;
        }
        
        // 查询实际订单数据
        $query = "SELECT DATE_FORMAT(created_at, '$date_format') as time_period, 
                COUNT(*) as order_count
                FROM orders
                GROUP BY $group_by
                ORDER BY time_period";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (isset($time_periods[$row['time_period']])) {
                $time_periods[$row['time_period']] = (int)$row['order_count'];
            }
        }
        
        // 格式化为前端期望的格式
        $time_distribution = [];
        foreach ($time_periods as $period_key => $count) {
            $time_distribution[] = [
                'time_period' => $period_key,
                'order_count' => $count
            ];
        }
        
        // 按租赁时长统计
        $query = "SELECT 
                CASE 
                    WHEN rental_duration = 0 THEN '0'
                    WHEN rental_duration = 1 THEN '1'
                    WHEN rental_duration = 2 THEN '2'
                    WHEN rental_duration <= 4 THEN '3-4'
                    WHEN rental_duration <= 8 THEN '5-8'
                    WHEN rental_duration <= 24 THEN '9-24'
                    ELSE '24+'
                END as duration_range,
                COUNT(*) as count
                FROM orders
                GROUP BY duration_range
                ORDER BY FIELD(duration_range, '0', '1', '2', '3-4', '5-8', '9-24', '24+')";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        $duration_distribution = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $duration_distribution[$row['duration_range']] = (int)$row['count'];
        }
        
        // 获取周天使用频率
        $query = "SELECT 
                DAYOFWEEK(created_at) as day_of_week,
                COUNT(*) as order_count
                FROM orders
                GROUP BY day_of_week
                ORDER BY day_of_week";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        $weekly_distribution = array_fill_keys($days, 0);
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // MySQL的DAYOFWEEK从1开始，1代表周日
            $day_index = (int)$row['day_of_week'] - 1;
            $day_name = $days[$day_index];
            $weekly_distribution[$day_name] = (int)$row['order_count'];
        }
        
        Response::success([
            'status_distribution' => $status_distribution,
            'time_distribution' => $time_distribution,
            'duration_distribution' => $duration_distribution,
            'weekly_distribution' => $weekly_distribution
        ], 'Order statistics retrieved successfully');
    }

    /**
     * Get revenue statistics by rental duration options
     */
    public function getRentalOptionsRevenue()
    {
        try {
            $query = "SELECT 
                CASE 
                    WHEN rental_duration <= 1 THEN '1hr'
                    WHEN rental_duration <= 8 THEN '8hr'
                    ELSE '1day'
                END as duration_category,
                COUNT(*) as order_count,
                SUM(price) as total_revenue
                FROM orders 
                WHERE status = 'completed' 
                AND payment_status = 'paid'
                AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                GROUP BY duration_category
                ORDER BY duration_category";
                
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            $data = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $data[] = [
                    'duration_category' => $row['duration_category'],
                    'order_count' => (int)$row['order_count'],
                    'total_revenue' => (float)$row['total_revenue']
                ];
            }
            
            return Response::success(['data' => $data], 'Rental options revenue retrieved successfully');
        } catch (Exception $e) {
            return Response::error('Failed to fetch rental options revenue: ' . $e->getMessage());
        }
    }

    /**
     * Get daily revenue for the past week
     */
    public function getDailyRevenue()
    {
        try {
            $query = "SELECT 
                DATE(created_at) as date,
                COUNT(*) as order_count,
                SUM(price) as total_revenue,
                AVG(rental_duration) as avg_duration
                FROM orders 
                WHERE status = 'completed' 
                AND payment_status = 'paid'
                AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                GROUP BY DATE(created_at)
                ORDER BY date";
                
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            $data = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $data[] = [
                    'date' => $row['date'],
                    'order_count' => (int)$row['order_count'],
                    'total_revenue' => (float)$row['total_revenue'],
                    'avg_duration' => (float)$row['avg_duration']
                ];
            }
            
            return Response::success(['data' => $data], 'Daily revenue retrieved successfully');
        } catch (Exception $e) {
            return Response::error('Failed to fetch daily revenue: ' . $e->getMessage());
        }
    }

    /**
     * Get weekly revenue summary
     */
    public function getWeeklyRevenue()
    {
        try {
            $query = "SELECT 
                YEARWEEK(created_at) as week,
                COUNT(*) as order_count,
                SUM(price) as total_revenue,
                AVG(rental_duration) as avg_duration
                FROM orders 
                WHERE status = 'completed' 
                AND payment_status = 'paid'
                AND created_at >= DATE_SUB(NOW(), INTERVAL 4 WEEK)
                GROUP BY YEARWEEK(created_at)
                ORDER BY week DESC";
                
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            $data = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $data[] = [
                    'week' => $row['week'],
                    'order_count' => (int)$row['order_count'],
                    'total_revenue' => (float)$row['total_revenue'],
                    'avg_duration' => (float)$row['avg_duration']
                ];
            }
            
            return Response::success(['data' => $data], 'Weekly revenue retrieved successfully');
        } catch (Exception $e) {
            return Response::error('Failed to fetch weekly revenue: ' . $e->getMessage());
        }
    }
}
?>
