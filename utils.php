<?php
/**
 * Get the user's IP address from App Engine header or fallback.
 * @return string
 */
function getUserIp(): string {
    return $_SERVER['HTTP_X_APPENGINE_USER_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
}

/**
 * Request timezone info from ip-api.com
 * @param string $ip
 * @return array|null
 */
function requestTimeInfo(string $ip): ?array {
    if ($ip === '' || $ip === 'Unknown') {
        return null;
    }
    $url = "http://ip-api.com/json/{$ip}?fields=33603840";
    $response = @file_get_contents($url);
    if ($response === false) {
        return null;
    }
    return json_decode($response, true);
}

/**
 * Get user's timezone info as array.
 * @return array
 */
function getUserTimezone(): array {
    $ip = getUserIp();
    $res = ["ip" => $ip];

    $data = requestTimeInfo($ip);

    if ($data && ($data['status'] ?? null) === 'success') {
        $offsetSeconds = $data['offset'];
        $offsetHours = $offsetSeconds / 3600;
        $res['tz_offset_hours'] = $offsetHours;
        $res['tz_offset_seconds'] = $offsetSeconds;
        $res['tz_description'] = $data['timezone'];
    }
    return $res;
}
?>