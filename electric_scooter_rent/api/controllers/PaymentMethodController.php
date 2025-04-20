<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/PaymentMethod.php';
require_once __DIR__ . '/../utils/response.php';

/**
 * 支付方式控制器
 */
class PaymentMethodController {
    private $paymentMethodModel;
    
    /**
     * 构造函数
     */
    public function __construct() {
        $this->paymentMethodModel = new PaymentMethod();
    }
    
    /**
     * 获取用户的支付方式列表
     */
    public function getPaymentMethods() {
        try {
            $user_id = $_REQUEST['user_id'];
            $paymentMethods = $this->paymentMethodModel->getByUserId($user_id);
            
            Response::success($paymentMethods);
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }
    
    /**
     * 添加支付方式
     */
    public function addPaymentMethod() {
        try {
            $user_id = $_REQUEST['user_id'];
            $data = json_decode(file_get_contents('php://input'), true);
            
            // 验证必要字段
            if (empty($data['card_holder_name']) || empty($data['card_number']) || 
                empty($data['expiration_month']) || empty($data['expiration_year']) || 
                empty($data['cvv'])) {
                Response::error('Missing required fields');
                return;
            }
            
            // 添加用户ID
            $data['user_id'] = $user_id;
            
            // 创建支付方式
            $paymentId = $this->paymentMethodModel->create($data);
            if ($paymentId) {
                // 获取新创建的支付方式
                $this->paymentMethodModel->findById($paymentId);
                
                // 格式化响应数据
                $response = [
                    'id' => $paymentId,
                    'card_holder_name' => $data['card_holder_name'],
                    'card_number_last4' => substr($data['card_number'], -4),
                    'card_type' => $this->detectCardType($data['card_number']),
                    'expiration_month' => $data['expiration_month'],
                    'expiration_year' => $data['expiration_year'],
                    'is_default' => isset($data['is_default']) ? (bool)$data['is_default'] : false,
                    'created_at' => date('Y-m-d H:i:s')
                ];
                
                Response::success($response);
            } else {
                Response::error('Failed to add payment method');
            }
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }
    
    /**
     * 检测卡类型
     */
    private function detectCardType($cardNumber) {
        $first_digit = substr($cardNumber, 0, 1);
        if ($first_digit == '4') {
            return 'Visa';
        } else if ($first_digit == '5') {
            return 'MasterCard';
        } else if ($first_digit == '3') {
            return 'American Express';
        }
        return 'Unknown';
    }
    
    /**
     * 设置默认支付方式
     */
    public function setDefaultPaymentMethod() {
        try {
            $user_id = $_REQUEST['user_id'];
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['payment_method_id'])) {
                Response::error('Payment method ID is required');
            }
            
            $result = $this->paymentMethodModel->setDefault($user_id, $data['payment_method_id']);
            
            if ($result) {
                Response::success(['message' => 'Default payment method updated successfully']);
            } else {
                Response::error('Failed to update default payment method');
            }
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }
    
    /**
     * 删除支付方式
     */
    public function deletePaymentMethod() {
        try {
            $user_id = $_REQUEST['user_id'];
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['payment_method_id'])) {
                Response::error('Payment method ID is required');
            }
            
            $result = $this->paymentMethodModel->delete($user_id, $data['payment_method_id']);
            
            if ($result) {
                Response::success(['message' => 'Payment method deleted successfully']);
            } else {
                Response::error('Failed to delete payment method');
            }
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }
    
    /**
     * 处理支付
     */
    public function processPayment() {
        try {
            $user_id = $_REQUEST['user_id'];
            $data = json_decode(file_get_contents('php://input'), true);
            
            // 验证必要字段
            if (empty($data['order_id']) || empty($data['payment_method_id'])) {
                Response::error('Missing required fields');
                return;
            }
            
            // 模拟支付处理
            // 在实际应用中，这里应该调用支付网关API
            sleep(1); // 模拟处理延迟
            
            // 更新订单状态
            require_once __DIR__ . '/../models/Order.php';
            require_once __DIR__ . '/../config/database.php';
            
            // 获取数据库连接
            $database = new Database();
            $db = $database->getConnection();
            
            // 创建订单模型实例
            $orderModel = new Order($db);
            
            // 查找并更新订单
            if ($orderModel->findById($data['order_id'])) {
                $orderModel->payment_status = 'paid';
                if ($orderModel->update()) {
                    Response::success(['message' => 'Payment processed successfully']);
                } else {
                    Response::error('Failed to update order payment status');
                }
            } else {
                Response::error('Order not found');
            }
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }
}
?> 