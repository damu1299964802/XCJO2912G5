<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Admin.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../utils/auth.php';

/**
 * 管理员控制器
 */
class AdminController {
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
     * 管理员登录
     */
    public function login() {
        // 检查请求方法
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::error('Method not allowed', 405);
        }
        
        // 获取请求数据
        $data = json_decode(file_get_contents("php://input"), true);
        
        // 验证必填字段
        if (!isset($data['username']) || !isset($data['password'])) {
            Response::error('Missing required fields', 400);
        }
        
        // 创建管理员对象
        $admin = new Admin($this->conn);
        
        // 查找管理员
        if (!$admin->findByUsername($data['username'])) {
            Response::error('Admin not found', 404);
        }
        
        // 验证密码
        if (!Auth::verifyPassword($data['password'], $admin->password)) {
            Response::error('Invalid password', 401);
        }
        
        // 生成令牌
        $token = Auth::generateToken([
            'id' => $admin->id,
            'username' => $admin->username,
            'role' => 'admin'
        ]);
        
        Response::success([
            'token' => $token,
            'admin' => [
                'id' => $admin->id,
                'username' => $admin->username,
                'email' => $admin->email
            ]
        ], 'Login successful');
    }
    
    /**
     * 获取所有用户
     */
    public function getUsers() {
        // 检查请求方法
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            Response::error('Method not allowed', 405);
        }
        
        // 创建用户对象
        $user = new User($this->conn);
        
        // 获取所有用户
        $stmt = $user->getAll();
        $users = [];
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // 移除密码
            unset($row['password']);
            $users[] = $row;
        }
        
        Response::success($users, 'Users retrieved successfully');
    }
    
    /**
     * 更新用户信息
     */
    public function updateUser() {
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
        
        // 创建用户对象
        $user = new User($this->conn);
        
        // 查找用户
        if (!$user->findById($data['id'])) {
            Response::error('User not found', 404);
        }
        
        // 更新用户状态
        $user->status = $data['status'];
        
        if ($user->update()) {
            Response::success(null, 'User information updated successfully');
        } else {
            Response::error('Failed to update user information', 500);
        }
    }
    
    /**
     * 重置用户密码
     */
    public function resetUserPassword() {
        // 检查请求方法
        if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
            Response::error('Method not allowed', 405);
        }
        
        // 获取请求数据
        $data = json_decode(file_get_contents("php://input"), true);
        
        // 验证必填字段
        if (!isset($data['id']) || !isset($data['new_password'])) {
            Response::error('Missing required fields', 400);
        }
        
        // 创建用户对象
        $user = new User($this->conn);
        
        // 查找用户
        if (!$user->findById($data['id'])) {
            Response::error('User not found', 404);
        }
        
        // 更新密码
        if ($user->updatePassword($data['new_password'])) {
            Response::success(null, 'Password reset successfully');
        } else {
            Response::error('Failed to reset password', 500);
        }
    }
}
?>
