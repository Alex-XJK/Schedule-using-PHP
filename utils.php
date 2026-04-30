<?php
/**
 * Normalize an IP address candidate from a server/proxy header.
 * @param string|null $value
 * @return string|null
 */
function normalizeIpCandidate(?string $value): ?string {
    if ($value === null) {
        return null;
    }

    $value = trim($value, " \t\n\r\0\x0B\"'");
    if ($value === '' || strtolower($value) === 'unknown') {
        return null;
    }

    // Some proxies include a port, for example "203.0.113.10:12345".
    if (preg_match('/^\[([^\]]+)\](?::\d+)?$/', $value, $matches)) {
        $value = $matches[1];
    } elseif (substr_count($value, ':') === 1 && strpos($value, '.') !== false) {
        $value = preg_replace('/:\d+$/', '', $value);
    }

    return filter_var($value, FILTER_VALIDATE_IP) ? $value : null;
}

/**
 * Get the first useful IP address from a comma-separated proxy header.
 * @param string|null $value
 * @return string|null
 */
function getIpFromProxyHeader(?string $value): ?string {
    if ($value === null) {
        return null;
    }

    $firstValidIp = null;
    foreach (explode(',', $value) as $candidate) {
        $ip = normalizeIpCandidate($candidate);
        if (!$ip) {
            continue;
        }

        $firstValidIp ??= $ip;
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return $ip;
        }
    }

    return $firstValidIp;
}

/**
 * Get the user's IP address.
 * @return string
 */
function getUserIp(): string {
    $serverSoftware = $_SERVER['SERVER_SOFTWARE'] ?? '';
    $isAppEngine = isset($_SERVER['GAE_ENV']) || strpos($serverSoftware, 'Google App Engine') !== false;

    $appEngineIp = normalizeIpCandidate($_SERVER['HTTP_X_APPENGINE_USER_IP'] ?? null);
    if ($isAppEngine && $appEngineIp) {
        return $appEngineIp;
    }

    $proxyHeaders = [
        $_SERVER['HTTP_CF_CONNECTING_IP'] ?? null,
        $_SERVER['HTTP_TRUE_CLIENT_IP'] ?? null,
        $_SERVER['HTTP_X_REAL_IP'] ?? null,
        $_SERVER['HTTP_X_FORWARDED_FOR'] ?? null,
        $_SERVER['HTTP_CLIENT_IP'] ?? null,
    ];

    foreach ($proxyHeaders as $headerValue) {
        $ip = getIpFromProxyHeader($headerValue);
        if ($ip) {
            return $ip;
        }
    }

    $remoteAddr = normalizeIpCandidate($_SERVER['REMOTE_ADDR'] ?? null);
    if ($remoteAddr) {
        return $remoteAddr;
    }

    return 'Unknown';
}

/**
 * Explain IP-related server variables for debugging proxy/network behavior.
 * @return array
 */
function getIpDebugInfo(): array {
    $headers = [
        'HTTP_CF_CONNECTING_IP' => [
            'value' => $_SERVER['HTTP_CF_CONNECTING_IP'] ?? null,
            'description' => 'Cloudflare client IP header. Useful only when Cloudflare is in front of this server.',
        ],
        'HTTP_TRUE_CLIENT_IP' => [
            'value' => $_SERVER['HTTP_TRUE_CLIENT_IP'] ?? null,
            'description' => 'Enterprise proxy/CDN client IP header. Useful only when a trusted proxy sets it.',
        ],
        'HTTP_X_REAL_IP' => [
            'value' => $_SERVER['HTTP_X_REAL_IP'] ?? null,
            'description' => 'Common Nginx/reverse-proxy header for the original client IP.',
        ],
        'HTTP_X_FORWARDED_FOR' => [
            'value' => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? null,
            'description' => 'Comma-separated chain: original client first, then each proxy that forwarded the request.',
        ],
        'HTTP_CLIENT_IP' => [
            'value' => $_SERVER['HTTP_CLIENT_IP'] ?? null,
            'description' => 'Older proxy header. Less common and easy to spoof from direct client requests.',
        ],
        'HTTP_X_APPENGINE_USER_IP' => [
            'value' => $_SERVER['HTTP_X_APPENGINE_USER_IP'] ?? null,
            'description' => 'Legacy Google App Engine header. This app now trusts it only when running on App Engine.',
        ],
        'REMOTE_ADDR' => [
            'value' => $_SERVER['REMOTE_ADDR'] ?? null,
            'description' => 'The immediate peer that connected to PHP/Apache. This may be a proxy, NAT, or load balancer.',
        ],
    ];

    $parsed = [];
    foreach ($headers as $name => $details) {
        $value = $details['value'];
        $parts = $value === null ? [] : explode(',', $value);
        $candidates = [];

        foreach ($parts as $part) {
            $ip = normalizeIpCandidate($part);
            $candidates[] = [
                'raw' => trim($part),
                'normalized_ip' => $ip,
                'is_public_ip' => $ip
                    ? (bool)filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)
                    : false,
            ];
        }

        $parsed[$name] = [
            'raw_value' => $value,
            'chosen_candidate' => getIpFromProxyHeader($value),
            'candidates' => $candidates,
            'description' => $details['description'],
        ];
    }

    return [
        'selected_ip' => getUserIp(),
        'selection_order' => [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_TRUE_CLIENT_IP',
            'HTTP_X_REAL_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_CLIENT_IP',
            'REMOTE_ADDR',
        ],
        'app_engine_header_rule' => 'HTTP_X_APPENGINE_USER_IP is checked before proxy headers only when GAE_ENV is set or SERVER_SOFTWARE contains "Google App Engine".',
        'server_context' => [
            'GAE_ENV' => $_SERVER['GAE_ENV'] ?? null,
            'SERVER_SOFTWARE' => $_SERVER['SERVER_SOFTWARE'] ?? null,
            'REQUEST_URI' => $_SERVER['REQUEST_URI'] ?? null,
        ],
        'ip_headers' => $parsed,
        'notes' => [
            'REMOTE_ADDR shows the immediate network peer, not always the browser user.',
            'X-Forwarded-For can contain multiple IPs; the leftmost public IP is usually the original client.',
            'Proxy headers are trustworthy only if they are set by your own trusted proxy/CDN and not accepted directly from arbitrary clients.',
        ],
    ];
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
