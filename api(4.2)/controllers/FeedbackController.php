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
        
        // 获取用户ID
        $user_id = $_REQUEST['user_id'];
        
        // 创建反馈对象
        $feedback = new Feedback($this->conn);
        
        // 设置反馈属性
        $feedback->user_id = $user_id;
        $feedback->scooter_id = isset($data['scooter_id']) ? $data['scooter_id'] : null;
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
