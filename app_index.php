<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type");

$prefix = '/~alexxu/sched/';
$path = @parse_url($_SERVER['REQUEST_URI'])['path'] ?? '/';
$path = substr($path, strlen($prefix) - 1);

switch ($path) {
    case '/':
        echo "Welcome to the Alex's PHP Web App!";
        break;
    case '/info':
        header('Content-Type: application/json');
        include_once(__DIR__ . "/utils.php");
        $tz_res = getUserTimezone();
        $tz_res['php_version'] = phpversion();
        echo json_encode($tz_res, JSON_PRETTY_PRINT);
        break;
    case '/schedule':
        include_once(__DIR__ . "/scheduleInTimezone.php");
        break;
    default:
        http_response_code(404);
        exit('Not Found');
}
?>