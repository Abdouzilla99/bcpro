<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../security.php';

// Authentication
if (!isset($_SERVER['PHP_AUTH_USER']) || 
    $_SERVER['PHP_AUTH_USER'] !== ADMIN_USERNAME || 
    $_SERVER['PHP_AUTH_PASSWORD'] !== ADMIN_PASSWORD) {
    header('WWW-Authenticate: Basic realm="Admin Panel"');
    header('HTTP/1.0 401 Unauthorized');
    die('Access Denied');
}

$stats = SecuritySystem::getStats();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Admin Panel</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="admin-container">
        <header>
            <h1>🔒 Security Admin Panel</h1>
            <div class="user-info">Angemeldet als: <?php echo ADMIN_USERNAME; ?></div>
        </header>

        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Blocked</h3>
                <div class="stat-number"><?php echo $stats['total_blocked']; ?></div>
            </div>
            <div class="stat-card">
                <h3>Active IPs</h3>
                <div class="stat-number"><?php echo count($stats['rate_limits']); ?></div>
            </div>
            <div class="stat-card">
                <h3>Allowed Countries</h3>
                <div class="stat-number"><?php echo implode(', ', ALLOWED_COUNTRIES); ?></div>
            </div>
        </div>

        <div class="sections">
            <div class="section">
                <h2>Recent Blocked Attempts</h2>
                <div class="log-container">
                    <?php foreach(array_reverse($stats['recent_blocks']) as $log): ?>
                        <?php $data = json_decode($log, true); if($data): ?>
                        <div class="log-entry">
                            <div class="log-time"><?php echo $data['timestamp']; ?></div>
                            <div class="log-ip"><?php echo $data['ip']; ?></div>
                            <div class="log-reason"><?php echo $data['reason']; ?></div>
                            <div class="log-ua"><?php echo substr($data['user_agent'], 0, 50); ?>...</div>
                        </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="section">
                <h2>Active Rate Limits</h2>
                <div class="log-container">
                    <?php foreach($stats['rate_limits'] as $ip => $times): ?>
                        <div class="log-entry">
                            <div class="log-ip"><?php echo $ip; ?></div>
                            <div class="log-requests">Requests: <?php echo count($times); ?></div>
                            <div class="log-time">Last: <?php echo date('H:i:s', end($times)); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="system-info">
            <h2>System Information</h2>
            <table>
                <tr><th>Server Time:</th><td><?php echo date('Y-m-d H:i:s'); ?></td></tr>
                <tr><th>Your IP:</th><td><?php echo SecuritySystem::getVisitorIP(); ?></td></tr>
                <tr><th>Max Requests/Hour:</th><td><?php echo MAX_REQUESTS_PER_HOUR; ?></td></tr>
                <tr><th>Allowed Countries:</th><td><?php echo implode(', ', ALLOWED_COUNTRIES); ?></td></tr>
            </table>
        </div>
    </div>
</body>
</html>