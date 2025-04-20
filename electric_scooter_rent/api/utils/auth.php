<?php
/**
 * 认证工具类
 */
class Auth {
    /**
     * 生成JWT令牌
     * 
     * @param array $payload 载荷数据
     * @param int $expiry 过期时间（秒）
     * @return string JWT令牌
     */
    public static function generateToken($payload, $expiry = 86400) {
        $header = [
            'alg' => 'HS256',
            'typ' => 'JWT'
        ];
        
        $payload['exp'] = time() + $expiry;
        $payload['iat'] = time();
        
        $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(json_encode($header)));
        $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(json_encode($payload)));
        
        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, self::getSecretKey(), true);
        $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
        
        return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    }
    
    /**
     * 验证JWT令牌
     * 
     * @param string $token JWT令牌
     * @return array|bool 成功返回载荷数据，失败返回false
     */
    public static function validateToken($token) {
        $parts = explode('.', $token);
        if (count($parts) != 3) {
            return false;
        }
        
        list($base64UrlHeader, $base64UrlPayload, $base64UrlSignature) = $parts;
        
        $signature = base64_decode(str_replace(['-', '_'], ['+', '/'], $base64UrlSignature));
        $expectedSignature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, self::getSecretKey(), true);
        
        if (!hash_equals($signature, $expectedSignature)) {
            return false;
        }
        
        $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $base64UrlPayload)), true);
        
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return false;
        }
        return $payload;
    }
    
    /**
     * 获取密钥
     * 
     * @return string 密钥
     */
    private static function getSecretKey() {
        return 'electric_scooter_rent_secret_key_2025';
    }
    
    /**
     * 哈希密码
     * 
     * @param string $password 明文密码
     * @return string 哈希密码
     */
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_BCRYPT);
    }
    
    /**
     * 验证密码
     * 
     * @param string $password 明文密码
     * @param string $hash 哈希密码
     * @return bool 是否匹配
     */
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
}
?>
