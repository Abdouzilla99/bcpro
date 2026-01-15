<?php

if (basename($_SERVER['SCRIPT_FILENAME']) === 'security.php') {
    header('HTTP/1.1 503 Service Unavailable');
    exit('Service Unavailable');
}

$config_file = __DIR__ . '/config.php';
if (!file_exists($config_file)) {
    file_put_contents($config_file, '<?php
    define("BLOCKED_REDIRECT_URL", "https://www.google.com");
    define("IP_API_KEY", "UO8wl6MQD2zPxmf");
    define("ALLOWED_COUNTRIES", ["DE", "MA"]);
    define("MAX_REQUESTS_PER_HOUR", 100000);
    define("ALLOW_LOCALHOST", false);
    ?>');
}

require_once $config_file;

class SecuritySystem {
    
    public static function getVisitorIP() {
        $headers = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];
        
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = trim(explode(',', $_SERVER[$header])[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        return "UNKNOWN";
    }
    
    public static function isLocalhost() {
        $ip = self::getVisitorIP();
        $localhost_ips = ['127.0.0.1', '::1', 'localhost'];
        return in_array($ip, $localhost_ips);
    }
    
    public static function validateRequest() {
        // Skip all checks for localhost
        if (self::isLocalhost() && ALLOW_LOCALHOST) {
            return true;
        }
        
        $ip = self::getVisitorIP();
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        // Simple rate limiting
        if (!self::checkRateLimit($ip)) {
            self::blockAccess('Rate limit exceeded');
        }
        
        // Basic User Agent check
        if (self::detectMaliciousUA($userAgent)) {
            self::blockAccess('Suspicious User Agent');
        }
        
        return true;
    }
    
    public static function checkRateLimit($ip) {
        if (self::isLocalhost() && ALLOW_LOCALHOST) {
            return true;
        }
        
        $logFile = __DIR__ . '/../data/rate_limit.json';
        $currentTime = time();
        $window = 3600;
        
        if (!is_dir(dirname($logFile))) {
            @mkdir(dirname($logFile), 0755, true);
        }
        
        $data = [];
        if (file_exists($logFile)) {
            $content = file_get_contents($logFile);
            $data = json_decode($content, true) ?: [];
        }
        
        // Clean old entries
        foreach ($data as $clientIP => $times) {
            $data[$clientIP] = array_filter($times, function($time) use ($currentTime, $window) {
                return ($currentTime - $time) < $window;
            });
            if (empty($data[$clientIP])) {
                unset($data[$clientIP]);
            }
        }
        
        if (!isset($data[$ip])) {
            $data[$ip] = [];
        }
        
        $data[$ip][] = $currentTime;
        
        if (count($data[$ip]) > MAX_REQUESTS_PER_HOUR) {
            return false;
        }
        
        @file_put_contents($logFile, json_encode($data));
        return true;
    }
    
    public static function detectMaliciousUA($userAgent) {
        if (self::isLocalhost() && ALLOW_LOCALHOST) {
            return false;
        }
        
        if (empty($userAgent) || strlen($userAgent) < 10) {
            return true;
        }
        
        $blocked_patterns = [
            '/wget|curl|bot|spider|crawler|scanner/i',
            '/headless|phantom|selenium|puppeteer/i',
            '/postman|insomnia|http-client/i',
            '/python-requests|go-http|java\/[0-9]/i'
        ];
        
        foreach ($blocked_patterns as $pattern) {
            if (preg_match($pattern, $userAgent)) {
                return true;
            }
        }
        
        return false;
    }
    
    public static function blockAccess($reason = '') {
        self::create503Page();
    }
    
    public static function create503Page() {
        header('HTTP/1.1 503 Service Temporarily Unavailable');
        header('Retry-After: 3600');
        header('Content-Type: text/html; charset=utf-8');
        
        echo '<!DOCTYPE html>
        <html lang="de">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Wartungsarbeiten </title>
            <style>
                body { 
                    font-family: Arial, sans-serif; 
                    background: #f5f5f5;
                    margin: 0;
                    padding: 20px;
                    min-height: 100vh;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }
                .container { 
                    text-align: center;
                    padding: 40px;
                    background: white;
                    border-radius: 10px;
                    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                    max-width: 500px;
                }
                .logo {
                    color: #005ea8;
                    margin-bottom: 30px;
                    font-size: 28px;
                    font-weight: bold;
                }
                h1 { 
                    color: #d32f2f;
                    font-size: 24px;
                    margin-bottom: 20px;
                }
                p {
                    line-height: 1.6;
                    margin-bottom: 15px;
                    color: #666;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="logo"></div>
                <h1>⚠ Wartungsarbeiten</h1>
                <p>Wir führen derzeit Wartungsarbeiten durch. Der Service steht in Kürze wieder zur Verfügung.</p>
                <p>Bitte versuchen Sie es in einigen Minuten erneut.</p>
            </div>
        </body>
        </html>';
        exit;
    }
}

try {
    SecuritySystem::validateRequest();
} catch (Exception $e) {

}
?>