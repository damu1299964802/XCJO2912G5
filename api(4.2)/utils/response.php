<?php
/**
 * API响应工具类
 */
class Response {
    /**
     * 成功响应
     * 
     * @param mixed $data 响应数据
     * @param string $message 响应消息
     * @param int $code HTTP状态码
     * @return void
     */
    public static function success($data = null, $message = "操作成功", $code = 200) {
        header("Content-Type: application/json; charset=UTF-8");
        http_response_code($code);
        
        echo json_encode([
            "status" => "success",
            "message" => $message,
            "data" => $data
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * 错误响应
     * 
     * @param string $message 错误消息
     * @param int $code HTTP状态码
     * @param mixed $errors 错误详情
     * @return void
     */
    public static function error($message = "操作失败", $code = 400, $errors = null) {
        header("Content-Type: application/json; charset=UTF-8");
        http_response_code($code);
        
        echo json_encode([
            "status" => "error",
            "message" => $message,
            "errors" => $errors
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
?>
