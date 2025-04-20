<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Scooter.php';
require_once __DIR__ . '/../utils/response.php';

/**
 * 滑板车控制器
 */
class ScooterController {
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
     * 获取所有滑板车
     */
    public function getAll() {
        // 检查请求方法
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            Response::error('Method not allowed', 405);
        }
        
        // 创建滑板车对象
        $scooter = new Scooter($this->conn);
        
        // 获取所有可用滑板车（用户端只能看到可用的）
        if (isset($_REQUEST['user_role']) && $_REQUEST['user_role'] === 'admin') {
            $status = $_GET['status'] ?? '';
            $stmt = $scooter->getAll($status);
        } else {
            $stmt = $scooter->getAllAvailable();
        }
        
        $scooters = [];
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $scooters[] = $row;
        }
        
        Response::success($scooters, 'Scooters retrieved successfully');
    }
    
    /**
     * 获取滑板车详情
     */
    public function getDetail() {
        // 检查请求方法
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            Response::error('Method not allowed', 405);
        }
        
        // 验证参数
        if (!isset($_GET['id'])) {
            Response::error('Missing scooter ID', 400);
        }
        
        // 创建滑板车对象
        $scooter = new Scooter($this->conn);
        
        // 查找滑板车
        if (!$scooter->findById($_GET['id'])) {
            Response::error('Scooter not found', 404);
        }
      
        // 用户端只能看到可用的滑板车
        if ($_REQUEST['user_role'] !== 'admin' && $scooter->status !== 'available') {
            Response::error('Scooter is not available', 403);
        }
        
        Response::success([
            'id' => $scooter->id,
            'scooter_code' => $scooter->scooter_code,
            'status' => $scooter->status,
            'battery_level' => $scooter->battery_level,
            'latitude' => $scooter->latitude,
            'longitude' => $scooter->longitude,
            'created_at' => $scooter->created_at,
            'updated_at' => $scooter->updated_at
        ], 'Scooter details retrieved successfully');
    }
    
    /**
     * 获取单个滑板车详情（通过ID）
     * 
     * @param int $id 滑板车ID
     */
    public function getOne($id) {
        // 检查请求方法
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            Response::error('Method not allowed', 405);
        }
        
        // 创建滑板车对象
        $scooter = new Scooter($this->conn);
        
        // 查找滑板车
        if (!$scooter->findById($id)) {
            Response::error('Scooter not found', 404);
        }
     
        // 用户端只能看到可用的滑板车
        if ($_REQUEST['user_role'] !== 'admin' && $scooter->status !== 'available') {
            Response::error('Scooter is not available', 403);
        }
        
        Response::success([
            'id' => $scooter->id,
            'scooter_code' => $scooter->scooter_code,
            'hourly_rate' => $scooter->hourly_rate,
            'status' => $scooter->status,
            'battery_level' => $scooter->battery_level,
            'latitude' => $scooter->latitude,
            'longitude' => $scooter->longitude,
            'location' => $scooter->location,
            'created_at' => $scooter->created_at,
            'updated_at' => $scooter->updated_at
        ], 'Scooter details retrieved successfully');
    }
    
    /**
     * 创建滑板车（管理员）
     */
    public function create() {
        // 检查请求方法
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::error('Method not allowed', 405);
        }
        
        // 获取请求数据
        $data = json_decode(file_get_contents("php://input"), true);
        
        // 验证必填字段
        if (!isset($data['scooter_code']) || !isset($data['status']) || !isset($data['battery_level'])) {
            Response::error('Missing required fields', 400);
        }
        
        // 创建滑板车对象
        $scooter = new Scooter($this->conn);
        
        // 检查编号是否已存在
        if ($scooter->findByCode($data['scooter_code'])) {
            Response::error('Scooter code already exists', 400);
        }
        
        // 设置滑板车属性
        $scooter->scooter_code = $data['scooter_code'];
        $scooter->status = $data['status'];
        $scooter->battery_level = $data['battery_level'];
        $scooter->latitude = isset($data['latitude']) ? $data['latitude'] : null;
        $scooter->longitude = isset($data['longitude']) ? $data['longitude'] : null;
        $scooter->location = isset($data['location']) ? $data['location'] : null;
        
        // 创建滑板车
        if ($scooter->create()) {
            Response::success([
                'id' => $scooter->id,
                'scooter_code' => $scooter->scooter_code,
                'status' => $scooter->status,
                'battery_level' => $scooter->battery_level,
                'latitude' => $scooter->latitude,
                'longitude' => $scooter->longitude,
                'location' => $scooter->location
            ], 'Scooter created successfully', 201);
        } else {
            Response::error('Failed to create scooter', 500);
        }
    }
    
    /**
     * 更新滑板车（管理员）
     */
    public function update() {
        // 检查请求方法
        if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
            Response::error('Method not allowed', 405);
        }
        
        // 获取请求数据
        $data = json_decode(file_get_contents("php://input"), true);
        
        // 验证必填字段
        if (!isset($data['id'])) {
            Response::error('Missing scooter ID', 400);
        }
        
        // 创建滑板车对象
        $scooter = new Scooter($this->conn);
        
        // 查找滑板车
        if (!$scooter->findById($data['id'])) {
            Response::error('Scooter not found', 404);
        }
        
        // 更新滑板车属性
        if (isset($data['scooter_code'])) {
            $scooter->scooter_code = $data['scooter_code'];
        }
        
        if (isset($data['status'])) {
            $scooter->status = $data['status'];
        }
        
        if (isset($data['battery_level'])) {
            $scooter->battery_level = $data['battery_level'];
        }
        
        if (isset($data['latitude'])) {
            $scooter->latitude = $data['latitude'];
        }
        
        if (isset($data['longitude'])) {
            $scooter->longitude = $data['longitude'];
        }
        
        if (isset($data['location'])) {
            $scooter->location = $data['location'];
        }
        
        if (isset($data['hourly_rate'])) {
            $scooter->hourly_rate = $data['hourly_rate'];
        }

        // 更新滑板车
        if ($scooter->update()) {
            Response::success([
                'id' => $scooter->id,
                'scooter_code' => $scooter->scooter_code,
                'status' => $scooter->status,
                'battery_level' => $scooter->battery_level,
                'latitude' => $scooter->latitude,
                'longitude' => $scooter->longitude,
                'location' => $scooter->location,
                'hourly_rate' => $scooter->hourly_rate
            ], 'Scooter updated successfully');
        } else {
            Response::error('Failed to update scooter', 500);
        }
    }
    
    /**
     * 删除滑板车（管理员）
     */
    public function delete() {
        // 检查请求方法
        if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
            Response::error('Method not allowed', 405);
        }
        
        // 验证参数
        if (!isset($_GET['id'])) {
            Response::error('Missing scooter ID', 400);
        }
        
        // 创建滑板车对象
        $scooter = new Scooter($this->conn);
        
        // 查找滑板车
        if (!$scooter->findById($_GET['id'])) {
            Response::error('Scooter not found', 404);
        }
        
        // 删除滑板车
        if ($scooter->delete()) {
            Response::success(null, 'Scooter deleted successfully');
        } else {
            Response::error('Failed to delete scooter', 500);
        }
    }
}
?>
