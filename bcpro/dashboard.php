<?php
$logFile = 'visitors.json';
$visitors = [];
if (file_exists($logFile)) {
    $visitors = json_decode(file_get_contents($logFile), true) ?? [];
}

// Filters
$filterStatus = $_GET['status'] ?? '';
$filterDevice = $_GET['device'] ?? '';
$filterOS = $_GET['os'] ?? '';
$filterCompany = $_GET['company'] ?? '';
$searchIP = $_GET['search'] ?? '';

// Filter visitors
$filteredVisitors = $visitors;
if ($filterStatus) {
    $filteredVisitors = array_filter($filteredVisitors, function($v) use ($filterStatus) {
        return ($v['status'] ?? '') === $filterStatus;
    });
}
if ($filterDevice) {
    $filteredVisitors = array_filter($filteredVisitors, function($v) use ($filterDevice) {
        return ($v['device_type'] ?? '') === $filterDevice;
    });
}
if ($filterOS) {
    $filteredVisitors = array_filter($filteredVisitors, function($v) use ($filterOS) {
        return stripos($v['os'] ?? '', $filterOS) !== false;
    });
}
if ($filterCompany) {
    $filteredVisitors = array_filter($filteredVisitors, function($v) use ($filterCompany) {
        return stripos($v['company'] ?? '', $filterCompany) !== false;
    });
}
if ($searchIP) {
    $filteredVisitors = array_filter($filteredVisitors, function($v) use ($searchIP) {
        return stripos($v['ip'] ?? '', $searchIP) !== false;
    });
}

// Calculate stats
$totalVisits = count($visitors);
$allowedVisits = count(array_filter($visitors, function($v) { return ($v['status'] ?? '') === 'allowed'; }));
$blockedVisits = count(array_filter($visitors, function($v) { return ($v['status'] ?? '') === 'blocked'; }));

// Enhanced stats
$deviceStats = $osStats = $browserStats = $companyStats = $countryStats = [];
$todayVisits = 0;
$today = date('Y-m-d');

foreach ($visitors as $visitor) {
    @$deviceStats[$visitor['device_type'] ?? 'Unknown']++;
    @$osStats[$visitor['os'] ?? 'Unknown']++;
    @$browserStats[$visitor['browser'] ?? 'Unknown']++;
    @$companyStats[$visitor['company'] ?? 'Unknown']++;
    @$countryStats[$visitor['country'] ?? 'Unknown']++;
    
    if (strpos($visitor['timestamp'] ?? '', $today) === 0) {
        $todayVisits++;
    }
}

arsort($deviceStats);
arsort($osStats);
arsort($browserStats);
arsort($companyStats);
arsort($countryStats);

$topCompanies = array_slice($companyStats, 0, 8, true);
$topOS = array_slice($osStats, 0, 6, true);
$topDevices = array_slice($deviceStats, 0, 5, true);
$topCountries = array_slice($countryStats, 0, 10, true);

$countryCoordinates = [
    'US' => [37.0902, -95.7129], 'MA' => [31.7917, -7.0926], 'DE' => [51.1657, 10.4515],
    'FR' => [46.2276, 2.2137], 'GB' => [55.3781, -3.4360], 'CA' => [56.1304, -106.3468],
    'CN' => [35.8617, 104.1954], 'RU' => [61.5240, 105.3188], 'JP' => [36.2048, 138.2529],
    'BR' => [-14.2350, -51.9253], 'IN' => [20.5937, 78.9629], 'AU' => [-25.2744, 133.7751],
    'IT' => [41.8719, 12.5674], 'ES' => [40.4637, -3.7492], 'CH' => [46.8182, 8.2275],
    'Local' => [0, 0]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HAARBv1 Analytics Dashboard</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/leaflet.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/leaflet.css" />
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', system-ui, sans-serif; 
            background: #0a0a16; 
            color: white; 
            padding: 12px;
            font-size: 14px;
            line-height: 1.4;
        }
        
        .container { max-width: 1800px; margin: 0 auto; }
        
        /* Header */
        .header { 
            background: rgba(16, 18, 37, 0.95); 
            padding: 20px; 
            border-radius: 12px; 
            border: 1px solid rgba(0, 240, 255, 0.3);
            margin-bottom: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
        .logo { display: flex; align-items: center; gap: 12px; }
        .logo h1 { font-size: 1.4em; color: #00f0ff; font-weight: 600; }
        .header-stats { display: flex; gap: 20px; font-size: 0.9em; }
        .header-stat { text-align: center; }
        .stat-label { display: block; color: #00f0ff; font-size: 0.8em; }
        .stat-value { font-weight: 600; }
        
        /* Quick Stats */
        .quick-stats { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); 
            gap: 10px; 
            margin-bottom: 16px; 
        }
        .stat-card { 
            background: rgba(16, 18, 37, 0.95); 
            padding: 14px; 
            border-radius: 8px; 
            border: 1px solid rgba(255,255,255,0.1);
            text-align: center;
            transition: transform 0.2s;
        }
        .stat-card:hover { transform: translateY(-2px); }
        .stat-number { font-size: 1.6em; font-weight: bold; display: block; margin-bottom: 4px; }
        .stat-label { font-size: 0.8em; opacity: 0.8; }
        
        /* Filters */
        .filters { 
            background: rgba(16, 18, 37, 0.95); 
            padding: 16px; 
            border-radius: 10px; 
            margin-bottom: 16px;
            border: 1px solid rgba(0, 240, 255, 0.2);
        }
        .search-box { display: flex; gap: 10px; margin-bottom: 12px; flex-wrap: wrap; }
        .search-box input { 
            flex: 1; 
            min-width: 250px;
            padding: 10px 14px; 
            border: 1px solid rgba(0, 240, 255, 0.3); 
            border-radius: 6px; 
            background: rgba(255,255,255,0.1); 
            color: white; 
            font-size: 0.9em;
        }
        .search-btn { 
            padding: 10px 20px; 
            background: #00f0ff; 
            border: none; 
            border-radius: 6px; 
            color: black; 
            font-weight: 600; 
            cursor: pointer;
        }
        .filter-buttons { display: flex; gap: 8px; flex-wrap: wrap; }
        .filter-btn { 
            padding: 6px 12px; 
            border: 1px solid rgba(255,255,255,0.2); 
            border-radius: 15px; 
            background: rgba(255,255,255,0.05); 
            color: white; 
            text-decoration: none; 
            font-size: 0.8em; 
            cursor: pointer;
            transition: all 0.2s;
        }
        .filter-btn:hover, .filter-btn.active { 
            background: #00f0ff; 
            color: black; 
            transform: translateY(-1px);
        }
        
        /* Main Content */
        .main-content { display: grid; grid-template-columns: 3fr 1fr; gap: 16px; }
        
        /* Table */
        .table-container { 
            background: rgba(16, 18, 37, 0.95); 
            border-radius: 10px; 
            overflow: hidden;
            border: 1px solid rgba(0, 240, 255, 0.2);
        }
        .table-header { 
            padding: 16px 20px; 
            background: rgba(0, 240, 255, 0.1); 
            border-bottom: 1px solid rgba(0, 240, 255, 0.3);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .table-title { font-weight: 600; color: #00f0ff; }
        .table-count { background: #00f0ff; color: black; padding: 4px 10px; border-radius: 10px; font-size: 0.8em; }
        
        .table-wrapper { max-height: 500px; overflow: auto; }
        .table { width: 100%; border-collapse: collapse; font-size: 0.82em; }
        .table th { 
            padding: 12px 8px; 
            text-align: left; 
            background: rgba(0, 240, 255, 0.05); 
            border-bottom: 2px solid #00f0ff;
            font-weight: 600;
            position: sticky;
            top: 0;
        }
        .table td { 
            padding: 10px 8px; 
            border-bottom: 1px solid rgba(255,255,255,0.05); 
            vertical-align: top;
        }
        .table tr:hover td { background: rgba(0, 240, 255, 0.03); }
        
        .status { 
            padding: 4px 8px; 
            border-radius: 10px; 
            font-size: 0.75em; 
            font-weight: 600; 
            display: inline-block;
        }
        .status.allowed { background: rgba(0, 255, 136, 0.2); color: #00ff88; }
        .status.blocked { background: rgba(255, 42, 109, 0.2); color: #ff2a6d; }
        
        /* Sidebar */
        .sidebar { display: flex; flex-direction: column; gap: 12px; }
        .card { 
            background: rgba(16, 18, 37, 0.95); 
            border-radius: 8px; 
            padding: 16px;
            border: 1px solid rgba(0, 240, 255, 0.2);
        }
        .card h3 { 
            color: #00f0ff; 
            margin-bottom: 12px; 
            font-size: 0.95em; 
            border-bottom: 1px solid rgba(0, 240, 255, 0.3);
            padding-bottom: 6px;
            font-weight: 600;
        }
        
        /* Map */
        #visitorMap { 
            height: 160px; 
            border-radius: 6px; 
            margin-bottom: 8px;
            border: 1px solid rgba(0, 240, 255, 0.3);
        }
        
        .stats-item { 
            display: flex; 
            justify-content: space-between; 
            padding: 6px 0; 
            border-bottom: 1px solid rgba(255,255,255,0.05);
            font-size: 0.8em;
            align-items: center;
        }
        .stats-bar { 
            height: 4px; 
            background: #00f0ff; 
            border-radius: 2px; 
            margin-top: 2px;
            flex-shrink: 0;
        }
        .stats-content { flex: 1; min-width: 0; }
        
        /* Footer */
        .footer { 
            text-align: center; 
            margin-top: 20px; 
            padding: 14px; 
            color: rgba(255,255,255,0.6); 
            font-size: 0.8em;
            background: rgba(16, 18, 37, 0.95);
            border-radius: 8px;
            border: 1px solid rgba(0, 240, 255, 0.2);
        }
        
        /* Icons */
        .device-icon { font-size: 1.1em; margin-right: 4px; }
        .company-icon { font-size: 1em; margin-right: 4px; }
        
        /* Responsive */
        @media (max-width: 1200px) {
            .main-content { grid-template-columns: 1fr; }
        }
        
        @media (max-width: 768px) {
            .container { padding: 8px; }
            .header { flex-direction: column; gap: 12px; text-align: center; }
            .header-stats { justify-content: center; }
            .search-box input { min-width: 200px; }
            .table { font-size: 0.78em; }
            .table th, .table td { padding: 8px 6px; }
            #visitorMap { height: 140px; }
        }
        
        @media (max-width: 480px) {
            .quick-stats { grid-template-columns: repeat(2, 1fr); }
            .filter-buttons { justify-content: center; }
            .table { font-size: 0.75em; }
        }
        
        /* Scrollbar */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: rgba(255,255,255,0.05); border-radius: 3px; }
        ::-webkit-scrollbar-thumb { background: #00f0ff; border-radius: 3px; }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="logo">
                <h1>🛡️ HAARBv1 Analytics Dashboard</h1>
            </div>
            <div class="header-stats">
                <div class="header-stat">
                    <span class="stat-label">TOTAL VISITS</span>
                    <span class="stat-value"><?php echo number_format($totalVisits); ?></span>
                </div>
                <div class="header-stat">
                    <span class="stat-label">COUNTRIES</span>
                    <span class="stat-value"><?php echo count($countryStats); ?></span>
                </div>
                <div class="header-stat">
                    <span class="stat-label">LAST UPDATE</span>
                    <span class="stat-value"><?php echo date('H:i:s'); ?></span>
                </div>
            </div>
        </div>
        
        <!-- Quick Stats -->
        <div class="quick-stats">
            <div class="stat-card">
                <span class="stat-number"><?php echo number_format($totalVisits); ?></span>
                <span class="stat-label">Total Visits</span>
            </div>
            <div class="stat-card">
                <span class="stat-number"><?php echo number_format($allowedVisits); ?></span>
                <span class="stat-label">Allowed</span>
            </div>
            <div class="stat-card">
                <span class="stat-number"><?php echo number_format($blockedVisits); ?></span>
                <span class="stat-label">Blocked</span>
            </div>
            <div class="stat-card">
                <span class="stat-number"><?php echo number_format($todayVisits); ?></span>
                <span class="stat-label">Today</span>
            </div>
            <div class="stat-card">
                <span class="stat-number"><?php echo count($countryStats); ?></span>
                <span class="stat-label">Countries</span>
            </div>
            <div class="stat-card">
                <span class="stat-number"><?php echo count($companyStats); ?></span>
                <span class="stat-label">Companies</span>
            </div>
        </div>
        
        <!-- Filters -->
        <div class="filters">
            <form method="GET">
                <div class="search-box">
                    <input type="text" name="search" placeholder="🔍 Search IP, City, Company, ISP..." value="<?php echo htmlspecialchars($searchIP); ?>">
                    <button type="submit" class="search-btn">Search</button>
                </div>
                <div class="filter-buttons">
                    <!-- Status -->
                    <button type="submit" name="status" value="" class="filter-btn <?php echo !$filterStatus ? 'active' : ''; ?>">All Status</button>
                    <button type="submit" name="status" value="allowed" class="filter-btn <?php echo $filterStatus === 'allowed' ? 'active' : ''; ?>">✅ Allowed</button>
                    <button type="submit" name="status" value="blocked" class="filter-btn <?php echo $filterStatus === 'blocked' ? 'active' : ''; ?>">🚫 Blocked</button>
                    
                    <!-- Devices -->
                    <button type="submit" name="device" value="Mobile" class="filter-btn <?php echo $filterDevice === 'Mobile' ? 'active' : ''; ?>">📱 Mobile</button>
                    <button type="submit" name="device" value="Desktop" class="filter-btn <?php echo $filterDevice === 'Desktop' ? 'active' : ''; ?>">💻 Desktop</button>
                    <button type="submit" name="device" value="Tablet" class="filter-btn <?php echo $filterDevice === 'Tablet' ? 'active' : ''; ?>">📟 Tablet</button>
                    <button type="submit" name="device" value="Server" class="filter-btn <?php echo $filterDevice === 'Server' ? 'active' : ''; ?>">🖥️ Server</button>
                    
                    <!-- OS -->
                    <button type="submit" name="os" value="Windows" class="filter-btn <?php echo $filterOS === 'Windows' ? 'active' : ''; ?>">🪟 Windows</button>
                    <button type="submit" name="os" value="Android" class="filter-btn <?php echo $filterOS === 'Android' ? 'active' : ''; ?>">🤖 Android</button>
                    <button type="submit" name="os" value="iOS" class="filter-btn <?php echo $filterOS === 'iOS' ? 'active' : ''; ?>">📱 iOS</button>
                    <button type="submit" name="os" value="Linux" class="filter-btn <?php echo $filterOS === 'Linux' ? 'active' : ''; ?>">🐧 Linux</button>
                    <button type="submit" name="os" value="macOS" class="filter-btn <?php echo $filterOS === 'macOS' ? 'active' : ''; ?>">🍎 macOS</button>
                    
                    <a href="dashboard.php" class="filter-btn">🔄 Clear Filters</a>
                </div>
            </form>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <!-- Table -->
            <div class="table-container">
                <div class="table-header">
                    <div class="table-title">Detailed Visitor Analytics</div>
                    <div class="table-count"><?php echo number_format(count($filteredVisitors)); ?> records</div>
                </div>
                <div class="table-wrapper">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>IP Address</th>
                                <th>Location</th>
                                <th>Company/ISP</th>
                                <th>Device</th>
                                <th>OS/Browser</th>
                                <th>Status</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($filteredVisitors)): ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 30px; color: rgba(255,255,255,0.5);">
                                        📊 No visitor data found for current filters
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach (array_slice($filteredVisitors, 0, 100) as $visitor): ?>
                                    <tr>
                                        <td>
                                            <strong style="color: #00f0ff;"><?php echo htmlspecialchars($visitor['ip']); ?></strong>
                                        </td>
                                        <td>
                                            🏴 <strong><?php echo htmlspecialchars($visitor['country']); ?></strong><br>
                                            <small>📍 <?php echo htmlspecialchars($visitor['city']); ?>, <?php echo htmlspecialchars($visitor['region']); ?></small><br>
                                            <small>📮 <?php echo htmlspecialchars($visitor['zip']); ?></small>
                                        </td>
                                        <td>
                                            <span class="company-icon">🏢</span>
                                            <strong><?php echo htmlspecialchars($visitor['company']); ?></strong><br>
                                            <small>📡 <?php echo htmlspecialchars($visitor['isp']); ?></small>
                                        </td>
                                        <td>
                                            <?php 
                                            $deviceIcon = '💻';
                                            if ($visitor['device_type'] === 'Mobile') $deviceIcon = '📱';
                                            elseif ($visitor['device_type'] === 'Tablet') $deviceIcon = '📟';
                                            elseif ($visitor['device_type'] === 'Server') $deviceIcon = '🖥️';
                                            ?>
                                            <span class="device-icon"><?php echo $deviceIcon; ?></span>
                                            <?php echo htmlspecialchars($visitor['device_brand']); ?><br>
                                            <small><?php echo htmlspecialchars($visitor['device_type']); ?></small>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($visitor['os']); ?></strong><br>
                                            <small>🌐 <?php echo htmlspecialchars($visitor['browser']); ?></small>
                                        </td>
                                        <td>
                                            <span class="status <?php echo $visitor['status']; ?>">
                                                <?php echo strtoupper($visitor['status']); ?>
                                            </span><br>
                                            <small><?php echo htmlspecialchars($visitor['reason']); ?></small>
                                        </td>
                                        <td>
                                            <small><?php echo htmlspecialchars($visitor['timestamp']); ?></small>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Sidebar -->
            <div class="sidebar">
                <!-- World Map -->
                <div class="card">
                    <h3>🌍 Visitor Map</h3>
                    <div id="visitorMap"></div>
                    <div style="text-align: center; font-size: 0.8em; opacity: 0.7; margin-top: 5px;">
                        Visitors from <?php echo count($countryStats); ?> countries
                    </div>
                </div>
                
                <!-- Top Companies -->
                <div class="card">
                    <h3>🏢 Top Companies</h3>
                    <?php if (empty($topCompanies)): ?>
                        <div style="text-align: center; opacity: 0.6; font-size: 0.8em;">No company data</div>
                    <?php else: ?>
                        <?php $maxCompany = max($topCompanies); ?>
                        <?php foreach ($topCompanies as $company => $count): ?>
                            <div class="stats-item">
                                <div class="stats-content">
                                    <span title="<?php echo htmlspecialchars($company); ?>">
                                        🏢 <?php echo htmlspecialchars($company); ?>
                                    </span>
                                    <div class="stats-bar" style="width: <?php echo ($count/$maxCompany)*100; ?>%"></div>
                                </div>
                                <span style="font-weight: 600;"><?php echo $count; ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <!-- Top OS -->
                <div class="card">
                    <h3>⚙️ Operating Systems</h3>
                    <?php if (empty($topOS)): ?>
                        <div style="text-align: center; opacity: 0.6; font-size: 0.8em;">No OS data</div>
                    <?php else: ?>
                        <?php foreach ($topOS as $os => $count): ?>
                            <div class="stats-item">
                                <span>
                                    <?php 
                                    $osIcon = '💻';
                                    if (strpos($os, 'Windows') !== false) $osIcon = '🪟';
                                    elseif (strpos($os, 'Android') !== false) $osIcon = '🤖';
                                    elseif (strpos($os, 'iOS') !== false) $osIcon = '📱';
                                    elseif (strpos($os, 'macOS') !== false) $osIcon = '🍎';
                                    elseif (strpos($os, 'Linux') !== false) $osIcon = '🐧';
                                    echo $osIcon . ' ' . htmlspecialchars($os);
                                    ?>
                                </span>
                                <span style="font-weight: 600;"><?php echo $count; ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <!-- Device Types -->
                <div class="card">
                    <h3>📊 Device Types</h3>
                    <?php foreach ($topDevices as $device => $count): ?>
                        <div class="stats-item">
                            <span>
                                <?php 
                                $deviceIcon = '💻';
                                if ($device === 'Mobile') $deviceIcon = '📱';
                                elseif ($device === 'Tablet') $deviceIcon = '📟';
                                elseif ($device === 'Server') $deviceIcon = '🖥️';
                                echo $deviceIcon . ' ' . htmlspecialchars($device);
                                ?>
                            </span>
                            <span style="font-weight: 600;"><?php echo $count; ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            🛡️ HAARBv1 Protection System • Last Update: <?php echo date('Y-m-d H:i:s'); ?> • 
            Auto-refresh: <span id="countdown">30</span>s • 
            Total Records: <?php echo number_format($totalVisits); ?>
        </div>
    </div>

    <script>
        // Initialize Map
        function initMap() {
            var map = L.map('visitorMap').setView([20, 0], 2);
            
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap'
            }).addTo(map);
            
            <?php foreach ($countryStats as $countryCode => $count): ?>
                <?php if (isset($countryCoordinates[$countryCode])): ?>
                    var coord = <?php echo json_encode($countryCoordinates[$countryCode]); ?>;
                    L.circleMarker(coord, {
                        radius: Math.min(<?php echo $count; ?> / 2 + 5, 20),
                        color: '#00f0ff',
                        fillColor: '#00f0ff',
                        fillOpacity: 0.3
                    }).addTo(map)
                    .bindPopup('<?php echo $countryCode . ": " . $count . " visits"; ?>');
                <?php endif; ?>
            <?php endforeach; ?>
        }
        
        // Auto-refresh every 30 seconds
        let time = 30;
        const countdownElement = document.getElementById('countdown');
        
        const countdown = setInterval(() => {
            time--;
            countdownElement.textContent = time;
            if (time <= 0) {
                clearInterval(countdown);
                window.location.reload();
            }
        }, 1000);
        
        // Initialize map when page loads
        document.addEventListener('DOMContentLoaded', initMap);
        
        // Add hover effects to cards
        document.querySelectorAll('.stat-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-3px)';
                this.style.boxShadow = '0 4px 12px rgba(0, 240, 255, 0.2)';
            });
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
                this.style.boxShadow = 'none';
            });
        });
    </script>
</body>
</html>