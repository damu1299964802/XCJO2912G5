<?php
/**
 * API入口文件
 */

// 设置响应头
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, PUT, DELETE, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// 处理OPTIONS请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// 引入工具类
require_once __DIR__ . '/utils/response.php';
require_once __DIR__ . '/utils/auth.php';

// 引入控制器
require_once __DIR__ . '/controllers/UserController.php';
require_once __DIR__ . '/controllers/AdminController.php';
require_once __DIR__ . '/controllers/ScooterController.php';
require_once __DIR__ . '/controllers/OrderController.php';
require_once __DIR__ . '/controllers/FeedbackController.php';
require_once __DIR__ . '/controllers/StatsController.php';
require_once __DIR__ . '/controllers/PaymentMethodController.php';

// 获取请求URI
$request_uri = $_SERVER['REQUEST_URI'];
$base_path = '/api';
$path = str_replace($base_path, '', $request_uri);
$path = parse_url($path, PHP_URL_PATH);

// 创建控制器实例
$userController = new UserController();
$adminController = new AdminController();
$scooterController = new ScooterController();
$orderController = new OrderController();
$feedbackController = new FeedbackController();
$statsController = new StatsController();
$paymentController = new PaymentMethodController();

// 路由配置
$routes = [
    // 用户相关路由 - 无需认证
    '/user/register' => ['handler' => [$userController, 'register'], 'auth' => false],
    '/user/login' => ['handler' => [$userController, 'login'], 'auth' => false],
    '/admin/login' => ['handler' => [$adminController, 'login'], 'auth' => false],
    
    // 统计路由 - 无需认证
    '/stats/orders' => ['handler' => [$statsController, 'getOrderStats'], 'auth' => false],
    '/stats/scooters' => ['handler' => [$statsController, 'getScooterStats'], 'auth' => false],
    
    // 用户相关路由 - 需要用户认证
    '/user/profile' => ['handler' => [$userController, 'getProfile'], 'auth' => 'user'],
    '/user/update' => ['handler' => [$userController, 'update'], 'auth' => 'user'],
    '/user/password' => ['handler' => [$userController, 'updatePassword'], 'auth' => 'user'],
    
    // 滑板车相关路由 - 需要用户认证
    '/scooters' => ['handler' => [$scooterController, 'getAll'], 'auth' => 'user'],
    '/scooters/available' => ['handler' => [$scooterController, 'getAvailable'], 'auth' => 'user'],
    '/scooters/([0-9]+)' => ['handler' => [$scooterController, 'getOne'], 'auth' => 'user'],
    
    // 订单相关路由 - 需要用户认证
    '/orders/create' => ['handler' => [$orderController, 'create'], 'auth' => 'user'],
    '/orders/current' => ['handler' => [$orderController, 'getCurrentByUser'], 'auth' => 'user'],
    '/orders/history' => ['handler' => [$orderController, 'getHistoryByUser'], 'auth' => 'user'],
    '/orders/update' => ['handler' => [$orderController, 'update'], 'auth' => 'user'],
    '/orders/cancel' => ['handler' => [$orderController, 'cancel'], 'auth' => 'user'],
    '/orders/check-pending' => ['handler' => [$orderController, 'checkPending'], 'auth' => 'user'],
    '/orders/([0-9]+)' => ['handler' => [$orderController, 'getOne'], 'auth' => 'user'],
    
    // 反馈相关路由 - 需要用户认证
    '/feedbacks/create' => ['handler' => [$feedbackController, 'create'], 'auth' => 'user'],
    '/feedbacks' => ['handler' => [$feedbackController, 'getByUser'], 'auth' => 'user'],
    '/feedbacks/([0-9]+)' => ['handler' => [$feedbackController, 'getOne'], 'auth' => 'user'],

    '/payment-methods' => ['handler' => [$paymentController, 'getPaymentMethods'], 'auth' => 'user'],
    '/payment-methods/add' => ['handler' => [$paymentController, 'addPaymentMethod'], 'auth' => 'user'],
    '/payment-methods/set-default' => ['handler' => [$paymentController, 'setDefaultPaymentMethod'], 'auth' => 'user'],
    '/payment-methods/delete' => ['handler' => [$paymentController, 'deletePaymentMethod'], 'auth' => 'user'],
    '/payment/process' => ['handler' => [$paymentController, 'processPayment'], 'auth' => 'user'],

    
    // 管理员相关路由 - 需要管理员认证
    '/admin/users' => ['handler' => [$adminController, 'getUsers'], 'auth' => 'admin'],
    '/admin/users/([0-9]+)' => ['handler' => [$adminController, 'getUser'], 'auth' => 'admin'],
    '/admin/users/update' => ['handler' => [$adminController, 'updateUser'], 'auth' => 'admin'],
    '/admin/users/reset-password' => ['handler' => [$adminController, 'resetPassword'], 'auth' => 'admin'],
    
    // 管理员滑板车管理路由
    '/admin/scooters/create' => ['handler' => [$scooterController, 'create'], 'auth' => 'admin'],
    '/admin/scooters/update' => ['handler' => [$scooterController, 'update'], 'auth' => 'admin'],
    '/admin/scooters/delete' => ['handler' => [$scooterController, 'delete'], 'auth' => 'admin'],
    
    // 管理员订单管理路由
    '/admin/orders' => ['handler' => [$orderController, 'getAll'], 'auth' => 'admin'],
    '/admin/orders/create-guest' => ['handler' => [$orderController, 'createGuestOrder'], 'auth' => 'admin'],
    '/admin/orders/([0-9]+)' => ['handler' => [$orderController, 'getOne'], 'auth' => 'admin'],
    '/admin/orders/update' => ['handler' => [$orderController, 'adminUpdate'], 'auth' => 'admin'],
    '/admin/orders/delete' => ['handler' => [$orderController, 'delete'], 'auth' => 'admin'],
    
    // 管理员反馈管理路由
    '/admin/feedbacks' => ['handler' => [$feedbackController, 'getAll'], 'auth' => 'admin'],
    '/admin/feedbacks/([0-9]+)' => ['handler' => [$feedbackController, 'getOne'], 'auth' => 'admin'],
    '/admin/feedbacks/update' => ['handler' => [$feedbackController, 'update'], 'auth' => 'admin'],
    
    // 管理员统计路由
    '/admin/stats/orders' => ['handler' => [$statsController, 'getOrderStats'], 'auth' => 'admin'],
    '/admin/stats/scooters' => ['handler' => [$statsController, 'getScooterStats'], 'auth' => 'admin'],

    // Revenue Statistics Routes
    '/admin/stats/revenue/rental-options' => ['handler' => [$statsController, 'getRentalOptionsRevenue'], 'auth' => false],
    '/admin/stats/revenue/daily' => ['handler' => [$statsController, 'getDailyRevenue'], 'auth' => false],
    '/admin/stats/revenue/weekly' => ['handler' => [$statsController, 'getWeeklyRevenue'], 'auth' => false]
];

// 路由匹配和处理
$matched = false;

foreach ($routes as $route_pattern => $route_config) {
    $pattern = '#^' . $route_pattern . '$#';
    if (preg_match($pattern, $path, $matches)) {
        array_shift($matches); // 移除完整匹配
        
        // 身份验证检查
        if ($route_config['auth'] !== false) {
            $headers = getallheaders();
            $auth_header = isset($headers['authorization']) ? $headers['authorization'] : '';
            $auth_header = !$auth_header ? $headers['Authorization'] : $auth_header;
            $token = null;
            // 从头部提取令牌
            if (!empty($auth_header) && preg_match('/Bearer\s(\S+)/', $auth_header, $token_matches)) {
                $token = $token_matches[1];
            }

            if (!$token) {
                Response::error('Authentication token not provided', 401);
            }
            
            // 验证令牌
            $payload = Auth::validateToken($token);
            if (!$payload) {
                Response::error('Invalid or expired authentication token', 401);
            }
            
            // 检查权限
            $is_admin = isset($payload['role']) && $payload['role'] === 'admin';
            
            if ($route_config['auth'] === 'admin' && !$is_admin) {
                Response::error('No permission to access this resource', 403);
            }
            
            // 将用户ID添加到请求中
            $_REQUEST['user_id'] = $payload['id'];
            $_REQUEST['user_role'] = $payload['role'];
        }
        
        // 调用处理函数
        call_user_func_array($route_config['handler'], $matches);
        $matched = true;
        break;
    }
}

// 如果没有匹配的路由
if (!$matched) {
    Response::error('Requested resource not found', 404);
}
?>
