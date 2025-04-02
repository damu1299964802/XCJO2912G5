<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../utils/auth.php';

/**
 * 用户控制器
 */
class UserController {
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
     * 用户注册
     */
    public function register() {
        // 检查请求方法
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::error('Method not allowed', 405);
        }
        
        // 获取请求数据
        $data = json_decode(file_get_contents("php://input"), true);
        
        // 验证必填字段
        if (!isset($data['username']) || !isset($data['password']) || (!isset($data['email']) && !isset($data['phone']))) {
            Response::error('Missing required fields', 400);
        }
        
        // 创建用户对象
        $user = new User($this->conn);
        
        // 检查邮箱或手机号是否已存在
        if (isset($data['email']) && $user->findByEmailOrPhone($data['email'])) {
            Response::error('Email already registered', 400);
        }
        
        if (isset($data['phone']) && $user->findByEmailOrPhone($data['phone'])) {
            Response::error('Phone number already registered', 400);
        }
        
        // 设置用户属性
        $user->username = $data['username'];
        $user->password = $data['password'];
        $user->email = isset($data['email']) ? $data['email'] : null;
        $user->phone = isset($data['phone']) ? $data['phone'] : null;
        $user->status = 'normal';
        
        // 创建用户
        if ($user->create()) {
            // 生成令牌
            $token = Auth::generateToken([
                'id' => $user->id,
                'username' => $user->username
            ]);
            
            Response::success([
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'status' => $user->status
                ]
            ], 'Registration successful', 201);
        } else {
            Response::error('Registration failed', 500);
        }
    }
    
    /**
     * 用户登录
     */
    public function login() {
        // 检查请求方法
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::error('Method not allowed', 405);
        }
        
        // 获取请求数据
        $data = json_decode(file_get_contents("php://input"), true);
        
        // 验证必填字段
        if (!isset($data['email']) || !isset($data['password'])) {
            Response::error('Missing required fields', 400);
        }
        
        // 创建用户对象
        $user = new User($this->conn);
        
        // 查找用户
        if (!$user->findByEmailOrPhone($data['email'])) {
            Response::error('User not found', 404);
        }
        
        // 检查用户状态
        if ($user->status !== 'normal') {
            Response::error('Account has been disabled', 403);
        }
        
        // 验证密码
        if (!Auth::verifyPassword($data['password'], $user->password)) {
            Response::error('Invalid password', 401);
        }
        
        // 生成令牌
        $token = Auth::generateToken([
            'id' => $user->id,
            'username' => $user->username
        ]);
        
        Response::success([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'phone' => $user->phone,
                'status' => $user->status
            ]
        ], 'Login successful');
    }
    
    /**
     * 获取用户个人资料
     */
    public function getProfile() {
        // 检查请求方法
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            Response::error('Method not allowed', 405);
        }
        
        // 获取用户ID
        $user_id = $_REQUEST['user_id'];
        
        // 创建用户对象
        $user = new User($this->conn);
        
        // 查找用户
        if (!$user->findById($user_id)) {
            Response::error('User not found', 404);
        }
        
        Response::success([
            'id' => $user->id,
            'username' => $user->username,
            'email' => $user->email,
            'phone' => $user->phone,
            'status' => $user->status,
            'created_at' => $user->created_at
        ], 'Profile retrieved successfully');
    }
    
    /**
     * 更新用户密码
     */
    public function updatePassword() {
        // 检查请求方法
        if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
            Response::error('Method not allowed', 405);
        }
        
        // 获取请求数据
        $data = json_decode(file_get_contents("php://input"), true);
        
        // 验证必填字段
        if (!isset($data['old_password']) || !isset($data['new_password'])) {
            Response::error('Missing required fields', 400);
        }
        
        // 获取用户ID
        $user_id = $_REQUEST['user_id'];
        
        // 创建用户对象
        $user = new User($this->conn);
        
        // 查找用户
        if (!$user->findById($user_id)) {
            Response::error('User not found', 404);
        }
        
        // 验证旧密码
        if (!Auth::verifyPassword($data['old_password'], $user->password)) {
            Response::error('Invalid old password', 401);
        }
        
        // 更新密码
        if ($user->updatePassword($data['new_password'])) {
            Response::success(null, 'Password updated successfully');
        } else {
            Response::error('Failed to update password', 500);
        }
    }
}
?>
