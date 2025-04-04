<?php
    switch (@parse_url($_SERVER['REQUEST_URI'])['path']) {
        case '/':
            echo "Welcome to the Alex's PHP Web App!";
            break;
        case '/info':
            echo "PHP Version: " . phpversion() . "<br>";
            echo "User's IP: " . $_SERVER['HTTP_X_APPENGINE_USER_IP'] ?? 'Unknown' . "<br>";
            break;
        case '/schedule':
            include_once("scheduleInTimezone.php");
            break;
        default:
            http_response_code(404);
            exit('Not Found');
    }
?>