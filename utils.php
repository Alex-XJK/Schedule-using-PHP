<?php
function getUserIp() {
    return $_SERVER['HTTP_X_APPENGINE_USER_IP'] ?? 'Unknown';
}

function requestTimeInfo($ip) {
    $url = "http://ip-api.com/json/{$ip}?fields=33603840";
    $response = file_get_contents($url);
    return json_decode($response, true);
}

function getUserTimezone() {
    $ip = getUserIp();
    $res["ip"] = $ip;

    if ($ip === 'Unknown') {
        $ip = "";
    }

    $data = requestTimeInfo($ip);

    if ($data && $data['status'] === 'success') {
        $offsetSeconds = $data['offset'];
        $offsetHours = $offsetSeconds / 3600;
        $res = [
            'tz_offset_hours'   => $offsetHours,
            'tz_offset_seconds' => $offsetSeconds,
            'tz_description'    => $data['timezone'],
        ];
    }
    return $res;
}
?>