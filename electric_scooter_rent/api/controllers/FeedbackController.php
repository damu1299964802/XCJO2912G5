<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Feedback.php';
require_once __DIR__ . '/../utils/response.php';

/**
 * 反馈控制器
 */
class FeedbackController {
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
     * 创建反馈
     */
    public function create() {
        // 检查请求方法
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::error('Method not allowed', 405);
        }
        
        // 获取请求数据
        $data = json_decode(file_get_contents("php://input"), true);
        
        // 验证必填字段
        if (!isset($data['type']) || !isset($data['content'])) {
            Response::error('Missing required fields', 400);
        }
        
        // 验证类型值是否有效
        $valid_types = ['scooter_fault', 'app_issue', 'suggestion', 'location_error', 'other'];
        if (!in_array($data['type'], $valid_types)) {
            Response::error('Invalid type value. Must be one of: ' . implode(', ', $valid_types), 400);
        }
        
        // 获取用户ID
        $user_id = $_REQUEST['user_id'];
        
        // 如果是设备故障类型且提供了滑板车ID，则验证滑板车是否存在
        $scooter_id = null;
        if ($data['type'] === 'scooter_fault' && isset($data['scooter_id'])) {
            require_once __DIR__ . '/../models/Scooter.php';
            $scooter = new Scooter($this->conn);
            
            if (!$scooter->findById($data['scooter_id'])) {
                Response::error('Scooter not found', 404);
            }
            
            $scooter_id = $data['scooter_id'];
        }
        
        // 创建反馈对象
        $feedback = new Feedback($this->conn);
        
        // 设置反馈属性
        $feedback->user_id = $user_id;
        $feedback->scooter_id = $scooter_id;
        $feedback->type = $data['type'];
        $feedback->content = $data['content'];
        $feedback->status = 'pending';
        
        // 创建反馈
        if ($feedback->create()) {
            Response::success([
                'id' => $feedback->id,
                'user_id' => $feedback->user_id,
                'scooter_id' => $feedback->scooter_id,
                'type' => $feedback->type,
                'content' => $feedback->content,
                'status' => $feedback->status
            ], 'Feedback submitted successfully', 201);
        } else {
            Response::error('Failed to submit feedback', 500);
        }
    }
    
    /**
     * 获取用户的反馈
     */
    public function getByUser() {
        // 检查请求方法
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            Response::error('Method not allowed', 405);
        }
        
        // 获取用户ID
        $user_id = $_REQUEST['user_id'];
        
        // 创建反馈对象
        $feedback = new Feedback($this->conn);
        
        // 获取用户的所有反馈
        $stmt = $feedback->getAllByUser($user_id);
        $feedbacks = [];
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $feedbacks[] = $row;
        }
        
        Response::success($feedbacks, 'Feedbacks retrieved successfully');
    }
    
    /**
     * 获取所有反馈（管理员）
     */
    public function getAll() {
        // 检查请求方法
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            Response::error('Method not allowed', 405);
        }
        
        // 创建反馈对象
        $feedback = new Feedback($this->conn);
        
        // 获取状态过滤参数
        $status = isset($_GET['status']) ? $_GET['status'] : null;
        
        // 获取所有反馈
        $stmt = $feedback->getAll($status);
        $feedbacks = [];
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $feedbacks[] = $row;
        }
        
        Response::success($feedbacks, 'Feedbacks retrieved successfully');
    }

    /**
     * 获取单个反馈详情
     * 
     * @param int $id 反馈ID
     */
    public function getOne($id) {
        // 检查请求方法
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            Response::error('Method not allowed', 405);
        }
        
        // 创建反馈对象
        $feedback = new Feedback($this->conn);
        
        // 查找反馈
        if (!$feedback->findById($id)) {
            Response::error('Feedback not found', 404);
        }
        
        // 获取滑板车信息（如果有）
        $scooter_info = null;
        if ($feedback->scooter_id) {
            require_once __DIR__ . '/../models/Scooter.php';
            $scooter = new Scooter($this->conn);
            if ($scooter->findById($feedback->scooter_id)) {
                $scooter_info = [
                    'id' => $scooter->id,
                    'scooter_code' => $scooter->scooter_code,
                    'status' => $scooter->status,
                    'battery_level' => $scooter->battery_level
                ];
            }
        }
        
        // 获取用户信息
        $user_info = null;
        $query = "SELECT username, email, phone FROM users WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $feedback->user_id);
        $stmt->execute();
        
        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $user_info = [
                'username' => $row['username'],
                'email' => $row['email'],
                'phone' => $row['phone']
            ];
        }
        
        Response::success([
            'id' => $feedback->id,
            'user_id' => $feedback->user_id,
            'user_info' => $user_info,
            'scooter_id' => $feedback->scooter_id,
            'scooter_info' => $scooter_info,
            'type' => $feedback->type,
            'content' => $feedback->content,
            'status' => $feedback->status,
            'admin_reply' => $feedback->admin_reply,
            'created_at' => $feedback->created_at,
            'updated_at' => $feedback->updated_at
        ], 'Feedback details retrieved successfully');
    }
    
    /**
     * 更新反馈（管理员）
     */
    public function update() {
        // 检查请求方法
        if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
            Response::error('Method not allowed', 405);
        }
        
        // 获取请求数据
        $data = json_decode(file_get_contents("php://input"), true);
        
        // 验证必填字段
        if (!isset($data['id']) || !isset($data['status'])) {
            Response::error('Missing required fields', 400);
        }
        
        // 验证状态值是否有效
        $valid_statuses = ['pending', 'processing', 'resolved', 'invalid'];
        if (!in_array($data['status'], $valid_statuses)) {
            Response::error('Invalid status value. Must be one of: ' . implode(', ', $valid_statuses), 400);
        }
        
        // 创建反馈对象
        $feedback = new Feedback($this->conn);
        
        // 查找反馈
        if (!$feedback->findById($data['id'])) {
            Response::error('Feedback not found', 404);
        }
        
        // 更新反馈属性
        $feedback->status = $data['status'];
        
        if (isset($data['admin_reply'])) {
            $feedback->admin_reply = $data['admin_reply'];
        }
        
        // 更新反馈
        if ($feedback->update()) {
            Response::success([
                'id' => $feedback->id,
                'user_id' => $feedback->user_id,
                'scooter_id' => $feedback->scooter_id,
                'type' => $feedback->type,
                'content' => $feedback->content,
                'status' => $feedback->status,
                'admin_reply' => $feedback->admin_reply
            ], 'Feedback updated successfully');
        } else {
            Response::error('Failed to update feedback', 500);
        }
    }
}
?>
