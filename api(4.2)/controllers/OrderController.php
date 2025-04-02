<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../models/Scooter.php';
require_once __DIR__ . '/../utils/response.php';

/**
 * 订单控制器
 */
class OrderController {
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
     * 创建订单
     */
    public function create() {
        // 检查请求方法
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::error('Method not allowed', 405);
        }
        
        // 获取请求数据
        $data = json_decode(file_get_contents("php://input"), true);
        
        // 验证必填字段
        if (!isset($data['scooter_id']) || !isset($data['rental_duration'])) {
            Response::error('Missing required fields', 400);
        }
        
        // 获取用户ID
        $user_id = $_REQUEST['user_id'];
        
        // 检查滑板车是否可用
        $scooter = new Scooter($this->conn);
        if (!$scooter->findById($data['scooter_id'])) {
            Response::error('Scooter not found', 404);
        }
        
        if ($scooter->status !== 'available') {
            Response::error('Scooter is not available', 400);
        }

        // 创建订单对象
        $order = new Order($this->conn);
        
        // 设置订单属性
        $order->user_id = $user_id;
        $order->scooter_id = $data['scooter_id'];
        $order->rental_duration = $data['rental_duration'];
        $order->status = 'pending';
        
        // 如果立即开始
        if (isset($data['start_now']) && $data['start_now']) {
            $order->start_time = date('Y-m-d H:i:s');
            $order->status = 'ongoing';
        } else {
            $order->start_time = null;
        }
        
        $order->end_time = null;
        
        // 创建订单
        if ($order->create()) {
            Response::success([
                'id' => $order->id,
                'user_id' => $order->user_id,
                'scooter_id' => $order->scooter_id,
                'scooter_code' => $scooter->scooter_code,
                'rental_duration' => $order->rental_duration,
                'start_time' => $order->start_time,
                'end_time' => $order->end_time,
                'status' => $order->status
            ], 'Order created successfully', 201);
        } else {
            Response::error('Failed to create order', 500);
        }
    }
    
    /**
     * 获取用户当前订单
     */
    public function getCurrentByUser() {
        // 检查请求方法
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            Response::error('Method not allowed', 405);
        }
        
        // 获取用户ID
        $user_id = $_REQUEST['user_id'];
        
        // 创建订单对象
        $order = new Order($this->conn);
        
        // 获取用户当前订单
        $stmt = $order->getCurrentByUser($user_id);
        $orders = [];
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $orders[] = $row;
        }
        
        Response::success($orders, 'Current orders retrieved successfully');
    }
    
    /**
     * 获取用户历史订单
     */
    public function getHistoryByUser() {
        // 检查请求方法
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            Response::error('Method not allowed', 405);
        }
        
        // 获取用户ID
        $user_id = $_REQUEST['user_id'];
        
        // 创建订单对象
        $order = new Order($this->conn);
        
        // 获取用户所有订单
        $stmt = $order->getAllByUser($user_id);
        $orders = [];
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // 只返回已完成或已取消的订单
            if ($row['status'] === 'completed' || $row['status'] === 'cancelled') {
                $orders[] = $row;
            }
        }
        
        Response::success($orders, 'Order history retrieved successfully');
    }
    
    /**
     * 更新订单（用户）
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
            Response::error('Missing order ID', 400);
        }
        
        // 获取用户ID
        $user_id = $_REQUEST['user_id'];
        
        // 创建订单对象
        $order = new Order($this->conn);
        
        // 查找订单
        if (!$order->findById($data['id'])) {
            Response::error('Order not found', 404);
        }
        
        // 检查订单是否属于当前用户
        if ($order->user_id != $user_id) {
            Response::error('Unauthorized to operate this order', 403);
        }
        
        // 检查订单状态
        if ($order->status === 'completed' || $order->status === 'cancelled') {
            Response::error('Cannot modify completed or cancelled orders', 400);
        }
        
        // 更新订单属性
        if (isset($data['rental_duration'])) {
            $order->rental_duration = $data['rental_duration'];
        }
        
        // 开始租赁
        if (isset($data['start']) && $data['start'] && $order->status === 'pending') {
            $order->start_time = date('Y-m-d H:i:s');
            $order->status = 'ongoing';
        }
        
        // 结束租赁
        if (isset($data['end']) && $data['end'] && $order->status === 'ongoing') {
            $order->end_time = date('Y-m-d H:i:s');
            $order->status = 'completed';
        }
        
        // 更新订单
        if ($order->update()) {
            Response::success([
                'id' => $order->id,
                'user_id' => $order->user_id,
                'scooter_id' => $order->scooter_id,
                'rental_duration' => $order->rental_duration,
                'start_time' => $order->start_time,
                'end_time' => $order->end_time,
                'status' => $order->status
            ], 'Order updated successfully');
        } else {
            Response::error('Failed to update order', 500);
        }
    }
    
    /**
     * 取消订单（用户）
     */
    public function cancel() {
        // 检查请求方法
        if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
            Response::error('Method not allowed', 405);
        }
        
        // 获取请求数据
        $data = json_decode(file_get_contents("php://input"), true);
        
        // 验证必填字段
        if (!isset($data['id'])) {
            Response::error('Missing order ID', 400);
        }
        
        // 获取用户ID
        $user_id = $_REQUEST['user_id'];
        
        // 创建订单对象
        $order = new Order($this->conn);
        
        // 查找订单
        if (!$order->findById($data['id'])) {
            Response::error('Order not found', 404);
        }
        
        // 检查订单是否属于当前用户
        if ($order->user_id != $user_id) {
            Response::error('Unauthorized to operate this order', 403);
        }
        
        // 检查订单状态
        if ($order->status !== 'pending') {
            Response::error('Can only cancel pending orders', 400);
        }
        
        // 更新订单状态
        $order->status = 'cancelled';
        
        // 更新订单
        if ($order->update()) {
            Response::success(null, 'Order cancelled successfully');
        } else {
            Response::error('Failed to cancel order', 500);
        }
    }
    
    /**
     * 获取所有订单（管理员）
     */
    public function getAll() {
        // 检查请求方法
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            Response::error('Method not allowed', 405);
        }
        
        // 创建订单对象
        $order = new Order($this->conn);
        
        // 获取状态过滤参数
        $status = isset($_GET['status']) ? $_GET['status'] : null;
        
        // 获取所有订单
        $stmt = $order->getAll($status);
        $orders = [];
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $orders[] = $row;
        }
        
        Response::success($orders, 'Orders retrieved successfully');
    }
    
    /**
     * 更新订单（管理员）
     */
    public function adminUpdate() {
        // 检查请求方法
        if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
            Response::error('Method not allowed', 405);
        }
        
        // 获取请求数据
        $data = json_decode(file_get_contents("php://input"), true);
        
        // 验证必填字段
        if (!isset($data['id'])) {
            Response::error('Missing order ID', 400);
        }
        
        // 创建订单对象
        $order = new Order($this->conn);
        
        // 查找订单
        if (!$order->findById($data['id'])) {
            Response::error('Order not found', 404);
        }
        
        // 更新订单属性
        if (isset($data['rental_duration'])) {
            $order->rental_duration = $data['rental_duration'];
        }
        
        if (isset($data['status'])) {
            $order->status = $data['status'];
        }
        
        if (isset($data['start_time'])) {
            $order->start_time = $data['start_time'];
        }
        
        if (isset($data['end_time'])) {
            $order->end_time = $data['end_time'];
        }
        
        // 更新订单
        if ($order->update()) {
            Response::success([
                'id' => $order->id,
                'user_id' => $order->user_id,
                'scooter_id' => $order->scooter_id,
                'rental_duration' => $order->rental_duration,
                'start_time' => $order->start_time,
                'end_time' => $order->end_time,
                'status' => $order->status
            ], 'Order updated successfully');
        } else {
            Response::error('Failed to update order', 500);
        }
    }
    
    /**
     * 删除订单（管理员）
     */
    public function delete() {
        // 检查请求方法
        if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
            Response::error('Method not allowed', 405);
        }
        
        // 验证参数
        if (!isset($_GET['id'])) {
            Response::error('Missing order ID', 400);
        }
        
        // 创建订单对象
        $order = new Order($this->conn);
        
        // 查找订单
        if (!$order->findById($_GET['id'])) {
            Response::error('Order not found', 404);
        }
        
        // 删除订单
        if ($order->delete()) {
            Response::success(null, 'Order deleted successfully');
        } else {
            Response::error('Failed to delete order', 500);
        }
    }
    
    /**
     * 检查用户是否有待处理订单
     */
    public function checkPending() {
        // 检查请求方法
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            Response::error('Method not allowed', 405);
        }
        
        // 获取用户ID
        $user_id = $_REQUEST['user_id'];
        
        // 创建订单对象
        $order = new Order($this->conn);
        
        // 检查待处理订单
        $pendingOrder = $order->checkPendingByUser($user_id);
        
        if ($pendingOrder) {
            // 有待处理订单
            Response::success([
                'hasPending' => true,
                'order' => $pendingOrder
            ], 'User has a pending order');
        } else {
            // 没有待处理订单
            Response::success([
                'hasPending' => false,
                'order' => null
            ], 'User has no pending orders');
        }
    }
}
?>
