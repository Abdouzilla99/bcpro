<?php
define('BLOCKED_REDIRECT_URL', "https://www.google.com");
define('IP_API_KEY', "UO8wl6MQD2zPxmf");
define('ALLOWED_COUNTRIES', ["DE", "MA"]);
define('MAX_REQUESTS_PER_HOUR', 100000);
define('ALLOW_LOCALHOST', false); 

header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
?>