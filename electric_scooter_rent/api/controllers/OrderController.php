<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../models/User.php';
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
        if (!isset($data['scooter_id']) || !isset($data['rental_duration']) || !isset($data['payment_method_id'])) {
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

        // 检查支付方式是否存在
        require_once __DIR__ . '/../models/PaymentMethod.php';
        $paymentMethod = new PaymentMethod($this->conn);
        if (!$paymentMethod->findById($data['payment_method_id'])) {
            Response::error('Payment method not found', 404);
        }

        // 检查支付方式是否属于当前用户
        if ($paymentMethod->user_id != $user_id) {
            Response::error('Unauthorized to use this payment method', 403);
        }

        // 创建订单对象
        $order = new Order($this->conn);
        
        // 设置订单属性
        $order->user_id = $user_id;
        $order->scooter_id = $data['scooter_id'];
        $order->rental_duration = $data['rental_duration'];
        $order->status = 'pending';
        $order->payment_status = 'unpaid';
        
        // 计算订单价格
        $order->price = $scooter->hourly_rate * $data['rental_duration'];
        

        // 如果立即开始
        if (isset($data['start_now']) && $data['start_now'] == true) {
            $order->start_time = date('Y-m-d H:i:s');
            $order->status = 'ongoing';
        } else {
            $order->start_time = null;
        }
        
        $order->end_time = null;
        
        // 开始事务
        $this->conn->beginTransaction();
        
        try {
            // 创建订单
            if (!$order->create()) {
                throw new Exception('Failed to create order');
            }

            // 模拟支付处理
            $payment_success = $this->processPayment($order->id, $paymentMethod->token, $order->price);
            
            if (!$payment_success) {
                throw new Exception('Payment failed');
            }
            
            // 更新订单支付状态
            $order->payment_status = 'paid';
            if (!$order->update()) {
                throw new Exception('Failed to update order payment status');
            }

            // 如果立即开始，设置滑板车状态为禁用
            if (isset($data['start_now']) && $data['start_now'] == true) {
                if (!$scooter->setDisabled($data['scooter_id'])) {
                    throw new Exception('Failed to update scooter status');
                }
            }

            // 提交事务
            $this->conn->commit();

            // 发送邮件
            $user = new User($this->conn);
            $user->findById($order->user_id);

            $this->postmail($user->email, 'order success', 'Congratulations on your successful booking');

            Response::success([
                'id' => $order->id,
                'user_id' => $order->user_id,
                'scooter_id' => $order->scooter_id,
                'scooter_code' => $scooter->scooter_code,
                'rental_duration' => $order->rental_duration,
                'price' => $order->price,
                'start_time' => $order->start_time,
                'end_time' => $order->end_time,
                'status' => $order->status,
                'payment_status' => $order->payment_status
            ], 'Order created successfully', 201);
        } catch (Exception $e) {
            // 回滚事务
            $this->conn->rollBack();
            Response::error($e->getMessage(), 500);
        }
    }


    public function postmail($to,$subject = '',$body = ''){
        error_reporting(E_ALL);
        ini_set('display_errors', true);
        $path = dirname(__FILE__);

        require_once __DIR__ . '/../utils/mail/Exception.php';
        require_once __DIR__ . '/../utils/mail/PHPMailer.php';
        require_once __DIR__ . '/../utils/mail/SMTP.php';
        $mail = new \PHPMailer\PHPMailer\PHPMailer();
        $mail->CharSet    = 'UTF-8';
        $mail->IsSMTP();
        $mail->SMTPDebug  = 0;
        $mail->SMTPAuth   = true;
        $mail->SMTPSecure = 'ssl';
        $mail->Host       = 'smtp.163.com';
        $mail->Port       = '465';
        $mail->Username   = 'develop_demo@163.com';
        $mail->Password   = 'RKCIIOSWONAYOQIV';
//        $mail->Password   = 'code@1024.';
        $mail->SetFrom('develop_demo@163.com', 'Admin');
        $mail->Subject    = $subject;
        $mail->MsgHTML($body);
        $address = $to;
        $mail->AddAddress($address, '');
        if(!$mail->Send()) {
            echo  'error info:'.$mail->ErrorInfo;exit;
        } else {
            return true;
        }
    }
    
    /**
     * 模拟支付处理
     */
    private function processPayment($order_id, $payment_token, $amount) {
        // 这里应该调用实际的支付网关API
        // 为了演示，我们模拟支付成功
        return true;
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
            if (isset($data['start']) && $data['start'] && $order->status === 'pending') {
                (new Scooter($this->conn))->setDisabled($order->scooter_id);
            }
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
            $order->start_time = $data['start_time'] ?: null;
        }
        
        if (isset($data['end_time'])) {
            $order->end_time = $data['end_time']  ?: null;
        }
        
        // 更新订单
        if ($order->update()) {
            Response::success([
                'id' => $order->id,
                'user_id' => $order->user_id,
                'scooter_id' => $order->scooter_id,
                'rental_duration' => $order->rental_duration,
                'start_time' => $order->start_time ,
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
    
    /**
     * 获取单个订单详情
     * 
     * @param int $id 订单ID
     */
    public function getOne($id) {
        // 检查请求方法
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            Response::error('Method not allowed', 405);
        }
        
        // 创建订单对象
        $order = new Order($this->conn);
        
        // 查找订单
        if (!$order->findById($id)) {
            Response::error('Order not found', 404);
        }
        
        // 获取用户角色
        $user_role = isset($_REQUEST['user_role']) ? $_REQUEST['user_role'] : null;
        
        // // 如果不是管理员，检查是否是该用户的订单
        // if ($user_role !== 'admin' && $order->user_id != $_REQUEST['user_id']) {
        //     Response::error('Unauthorized to view this order', 403);
        // }
        
        // 获取滑板车信息
        $scooter = new Scooter($this->conn);
        $scooter_code = '';
        $scooter_info = null;
        
        if ($scooter->findById($order->scooter_id)) {
            $scooter_code = $scooter->scooter_code;
            $scooter_info = [
                'id' => $scooter->id,
                'scooter_code' => $scooter->scooter_code,
                'status' => $scooter->status,
                'battery_level' => $scooter->battery_level
            ];
        }
        
        // 获取用户名
        $username = '';
        if ($user_role === 'admin') {
            $query = "SELECT username FROM users WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $order->user_id);
            $stmt->execute();
            
            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $username = $row['username'];
            }
        }
        
        // 计算总价（如有需要）
        $total_amount = null;
        if ($order->status === 'completed' && $order->start_time && $order->end_time) {
            // 简化计算，每小时10元
            $start = new DateTime($order->start_time);
            $end = new DateTime($order->end_time);
            $hours = $end->diff($start)->h + ($end->diff($start)->days * 24);
            $total_amount = $hours > 0 ? $hours * 10 : 10; // 最低收费10元
        }
        
        // 返回响应
        $orderData = [
            'id' => $order->id,
            'user_id' => $order->user_id,
            'scooter_id' => $order->scooter_id,
            'scooter_code' => $scooter_code,
            'rental_duration' => $order->rental_duration,
            'start_time' => $order->start_time,
            'end_time' => $order->end_time,
            'status' => $order->status,
            'created_at' => $order->created_at,
            'updated_at' => $order->updated_at
        ];
        
        // 添加管理员可见的额外信息
        if ($user_role === 'admin') {
            $orderData['username'] = $username;
            $orderData['scooter_info'] = $scooter_info;
        }
        
        // 添加已完成订单的费用信息
        if ($total_amount !== null) {
            $orderData['total_amount'] = $total_amount;
        }
        
        Response::success($orderData, 'Order details retrieved successfully');
    }

    /**
     * Create a guest order by admin
     */
    public function createGuestOrder()
    {
        try {
            // 验证请求数据
            $data = $this->validateGuestOrderData();
            if (!$data['success']) {
                return Response::error($data['message']);
            }
            $orderData = $data['data'];

            // 开始事务
            $this->conn->beginTransaction();

            // 创建订单
            $orderQuery = "INSERT INTO orders (scooter_id, rental_duration, price, start_time, status, payment_status) 
                          VALUES (:scooter_id, :rental_duration, :price, :start_time, 'pending', 'unpaid')";
            
            $stmt = $this->conn->prepare($orderQuery);
            $stmt->execute([
                'scooter_id' => $orderData['scooter_id'],
                'rental_duration' => $orderData['rental_duration'],
                'price' => $orderData['price'],
                'start_time' => $orderData['start_time']
            ]);
            
            $orderId = $this->conn->lastInsertId();

            // 创建访客订单信息
            $guestQuery = "INSERT INTO guest_orders (name, phone, email, order_id) 
                          VALUES (:name, :phone, :email, :order_id)";
            
            $stmt = $this->conn->prepare($guestQuery);
            $stmt->execute([
                'name' => $orderData['guest_name'],
                'phone' => $orderData['guest_phone'],
                'email' => $orderData['guest_email'] ?? null,
                'order_id' => $orderId,
            ]);

            $scooter = new Scooter($this->conn);
            $scooter->setDisabled($orderData['scooter_id']);

            // 提交事务
            $this->conn->commit();

            return Response::success([
                'order_id' => $orderId
            ], 'Guest order created successfully');

        } catch (Exception $e) {
            // 回滚事务
            $this->conn->rollBack();
            return Response::error('Failed to create guest order: ' . $e->getMessage());
        }
    }

    /**
     * Validate guest order data
     */
    private function validateGuestOrderData()
    {
        $requiredFields = [
            'scooter_id',
            'rental_duration',
            'price',
            'start_time',
            'guest_name',
            'guest_phone'
        ];

        $data = json_decode(file_get_contents('php://input'), true);

        // 检查必填字段
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                return [
                    'success' => false,
                    'message' => "Missing required field: {$field}"
                ];
            }
        }

        // 验证滑板车是否可用
        $scooterQuery = "SELECT status FROM scooters WHERE id = ? AND status = 'available'";
        $stmt = $this->conn->prepare($scooterQuery);
        $stmt->execute([$data['scooter_id']]);
        if (!$stmt->fetch()) {
            return [
                'success' => false,
                'message' => 'Selected scooter is not available'
            ];
        }

        // 验证租赁时长
        if (!is_numeric($data['rental_duration']) || $data['rental_duration'] <= 0) {
            return [
                'success' => false,
                'message' => 'Invalid rental duration'
            ];
        }

        // 验证开始时间
        $startTime = strtotime($data['start_time']);
        if (!$startTime || $startTime < time()) {
            return [
                'success' => false,
                'message' => 'Invalid start time'
            ];
        }

        // 验证手机号格式
        if (!preg_match('/^[0-9]{11}$/', $data['guest_phone'])) {
            return [
                'success' => false,
                'message' => 'Invalid phone number format'
            ];
        }

        // 验证邮箱格式（如果提供）
        if (isset($data['guest_email']) && !filter_var($data['guest_email'], FILTER_VALIDATE_EMAIL)) {
            return [
                'success' => false,
                'message' => 'Invalid email format'
            ];
        }

        return [
            'success' => true,
            'data' => $data
        ];
    }
}
?>
