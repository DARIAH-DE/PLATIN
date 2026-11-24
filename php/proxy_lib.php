<?php
/*
* proxy_lib.php
*
* Hardened HTTP proxy used by proxy.php.
*
* The proxy accepts one target URL in the "address" query parameter, checks
* it against the deployment allowlist, resolves it, rejects non-public
* addresses, and fetches it with cURL. cURL is required: the former stream
* wrapper fallback was removed because it could not pin the checked DNS
* address and therefore left a DNS-rebinding window.
*
* This file is part of PLATIN and is distributed under the same terms as
* proxy.php.
*/

if (!defined('GEOBROWSER_PROXY_LIBRARY')) {
	http_response_code(404);
	exit;
}

if (!defined('GEOBROWSER_PROXY_MAX_RESPONSE_BYTES')) {
	define('GEOBROWSER_PROXY_MAX_RESPONSE_BYTES', 25 * 1024 * 1024);
}
if (!defined('GEOBROWSER_PROXY_MAX_POST_BYTES')) {
	define('GEOBROWSER_PROXY_MAX_POST_BYTES', 2 * 1024 * 1024);
}

/**
 * Validate and execute one proxy request.
 *
 * The flow is: method and cURL checks, address parsing, scheme/host/port
 * allowlisting, DNS resolution with public-IP validation, POST-body
 * validation, cURL fetch with DNS pinning, and safe response headers.
 *
 * @param array $validHosts Allowlist patterns supplied by proxy.php.
 * @return void
 */
function geoBrowserProxyRun($validHosts)
{
	if (!function_exists('curl_init') || !function_exists('curl_exec')) {
		geoBrowserProxyError(503, 'Proxy requires the PHP cURL extension');
		return;
	}

	$method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper($_SERVER['REQUEST_METHOD']) : '';
	if (!in_array($method, array('GET', 'POST'), true)) {
		geoBrowserProxyError(405, 'Method Not Allowed', array('Allow: GET, POST'));
		return;
	}

	$rawAddress = isset($_GET['address']) ? $_GET['address'] : null;
	if (!is_string($rawAddress) || trim($rawAddress) === '') {
		geoBrowserProxyError(400, 'Missing address');
		return;
	}
	if (strlen($rawAddress) > 4096) {
		geoBrowserProxyError(400, 'Address too long');
		return;
	}
	if (preg_match('/[\x00-\x1F\x7F]/', $rawAddress) === 1) {
		geoBrowserProxyError(400, 'Invalid address');
		return;
	}

	$parts = parse_url($rawAddress);
	if (!is_array($parts) || !isset($parts['scheme'], $parts['host'])) {
		geoBrowserProxyError(400, 'Invalid address');
		return;
	}

	$scheme = strtolower($parts['scheme']);
	if (!in_array($scheme, array('http', 'https'), true)) {
		geoBrowserProxyError(403, 'Scheme not allowed');
		return;
	}
	if (isset($parts['user']) || isset($parts['pass'])) {
		geoBrowserProxyError(403, 'Credentials are not allowed');
		return;
	}

	$host = strtolower($parts['host']);
	$hostForMatch = $host;
	if (strlen($host) > 1 && $host[0] === '[' && substr($host, -1) === ']') {
		$hostForMatch = substr($host, 1, -1);
	}

	if (!geoBrowserProxyHostAllowed($hostForMatch, $validHosts)) {
		geoBrowserProxyError(403, 'Host not allowed');
		return;
	}

	$port = isset($parts['port']) ? (int) $parts['port'] : ($scheme === 'https' ? 443 : 80);
	if (!in_array($port, array(80, 443), true)) {
		geoBrowserProxyError(403, 'Port not allowed');
		return;
	}

	$ips = geoBrowserProxyResolveHost($hostForMatch);
	if (count($ips) === 0) {
		geoBrowserProxyError(502, 'Upstream host could not be resolved');
		return;
	}
	foreach ($ips as $ip) {
		if (!geoBrowserProxyIsPublicIp($ip)) {
			geoBrowserProxyError(403, 'Upstream host is not public');
			return;
		}
	}

	$targetUrl = geoBrowserProxyBuildTargetUrl($parts);
	if ($targetUrl === null) {
		geoBrowserProxyError(400, 'Invalid address');
		return;
	}

	$postBody = '';
	if ($method === 'POST') {
		$length = isset($_SERVER['CONTENT_LENGTH']) ? (int) $_SERVER['CONTENT_LENGTH'] : 0;
		if ($length > GEOBROWSER_PROXY_MAX_POST_BYTES) {
			geoBrowserProxyError(413, 'Request body too large');
			return;
		}
		$contentType = geoBrowserProxyMediaType(isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '');
		if (!in_array($contentType, array('application/xml', 'text/xml', 'application/gml+xml'), true)) {
			geoBrowserProxyError(415, 'Unsupported request content type');
			return;
		}
		$stream = fopen('php://input', 'rb');
		if ($stream === false) {
			geoBrowserProxyError(400, 'Could not read request body');
			return;
		}
		$postBody = stream_get_contents($stream, GEOBROWSER_PROXY_MAX_POST_BYTES + 1);
		fclose($stream);
		if (!is_string($postBody)) {
			geoBrowserProxyError(400, 'Could not read request body');
			return;
		}
		if (strlen($postBody) > GEOBROWSER_PROXY_MAX_POST_BYTES) {
			geoBrowserProxyError(413, 'Request body too large');
			return;
		}
	}

	$response = geoBrowserProxyFetchCurl($targetUrl, $method, $postBody, $hostForMatch, $port, $ips);
	if ($response === null) {
		geoBrowserProxyError(502, 'Upstream request failed');
		return;
	}

	geoBrowserProxySendResponse($response);
}

/**
 * Check a hostname against normalized allowlist patterns.
 * Invalid or unanchored patterns are skipped, so an empty or misconfigured
 * list denies every request.
 *
 * @param string $host Hostname to check, without IPv6 brackets.
 * @param array $validHosts Allowlist patterns from proxy.php.
 * @return bool True only if at least one valid pattern matches.
 */
function geoBrowserProxyHostAllowed($host, $validHosts)
{
	if (!is_array($validHosts)) {
		return false;
	}
	foreach ($validHosts as $pattern) {
		$pattern = geoBrowserProxyNormalizePattern($pattern);
		if ($pattern === null) {
			continue;
		}
		if (preg_match($pattern, $host) === 1) {
			return true;
		}
	}
	return false;
}

/**
 * Normalize and validate one allowlist pattern.
 * Delimited patterns are accepted if valid. Bare values are wrapped as
 * /.../i for compatibility; the result must be anchored at both ends, and
 * invalid or unanchored values are rejected so they fail closed.
 *
 * @param mixed $pattern Candidate allowlist pattern.
 * @return string|null Normalized PCRE pattern, or null if unusable.
 */
function geoBrowserProxyNormalizePattern($pattern)
{
	if (!is_string($pattern)) {
		return null;
	}
	$pattern = trim($pattern);
	if ($pattern === '') {
		return null;
	}
	if (@preg_match($pattern, '') === false) {
		$pattern = '/' . str_replace('/', '\\/', $pattern) . '/i';
	}
	if (@preg_match($pattern, '') === false) {
		return null;
	}
	$delimiter = $pattern[0];
	$end = strrpos($pattern, $delimiter);
	if ($end === false || $end === 0) {
		return null;
	}
	$body = substr($pattern, 1, $end - 1);
	if ($body === '' || $body[0] !== '^') {
		return null;
	}
	if (substr($body, -2) !== '\\z' && substr($body, -1) !== '$') {
		return null;
	}
	return $pattern;
}

/**
 * Rebuild the upstream URL from parsed address parts.
 * The original query string is preserved. Extra query parameters from the
 * proxy request (except "address") are appended with RFC 3986 encoding;
 * array values are rejected because they cannot be represented safely.
 *
 * @param array $parts Result of parse_url() for the address parameter.
 * @return string|null Target URL, or null for invalid/array parameters.
 */
function geoBrowserProxyBuildTargetUrl($parts)
{
	$scheme = strtolower($parts['scheme']);
	$url = $scheme . '://' . $parts['host'];
	if (isset($parts['port'])) {
		$url .= ':' . (int) $parts['port'];
	}
	$url .= isset($parts['path']) && $parts['path'] !== '' ? $parts['path'] : '/';

	$extra = array();
	foreach ($_GET as $key => $value) {
		if ($key === 'address') {
			continue;
		}
		if (!is_string($key) || !is_string($value)) {
			return null;
		}
		$extra[] = rawurlencode($key) . '=' . rawurlencode($value);
	}

	$hasQuery = isset($parts['query']) && $parts['query'] !== '';
	if ($hasQuery) {
		$url .= '?' . $parts['query'];
	}
	if (count($extra) > 0) {
		$url .= ($hasQuery ? '&' : '?') . implode('&', $extra);
	}
	return $url;
}

/**
 * Resolve a hostname to unique IPv4/IPv6 addresses.
 * IP literals are returned directly. DNS A/AAAA records are preferred;
 * gethostbynamel() is an IPv4 fallback. IPv4 addresses are sorted first so
 * the cURL resolver pin uses a predictable family when both exist.
 *
 * @param string $host Hostname or IP literal.
 * @return array Unique IP address strings.
 */
function geoBrowserProxyResolveHost($host)
{
	if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
		return array($host);
	}

	$ips = array();
	if (function_exists('dns_get_record')) {
		$records = @dns_get_record($host, DNS_A | DNS_AAAA);
		if (is_array($records)) {
			foreach ($records as $record) {
				if (isset($record['ip'])) {
					$ips[] = $record['ip'];
				}
				if (isset($record['ipv6'])) {
					$ips[] = $record['ipv6'];
				}
			}
		}
	}
	if (count($ips) === 0) {
		$v4 = @gethostbynamel($host);
		if (is_array($v4)) {
			$ips = $v4;
		}
	}
	$ips = array_values(array_unique($ips));
	usort($ips, function ($a, $b) {
		$aIsV4 = filter_var($a, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false;
		$bIsV4 = filter_var($b, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false;
		if ($aIsV4 === $bIsV4) {
			return 0;
		}
		return $aIsV4 ? -1 : 1;
	});
	return $ips;
}

/**
 * Check whether an IP address is outside blocked non-public ranges.
 * This is a denylist, not a complete IANA special-purpose registry: it
 * rejects private, loopback, link-local, CGNAT, multicast, and reserved
 * IPv4 ranges plus loopback/unspecified/ULA/link-local IPv6 addresses.
 * IPv4-mapped IPv6 addresses are recursively checked as IPv4.
 *
 * @param mixed $ip Candidate IP address.
 * @return bool True if the address is not in a blocked range.
 */
function geoBrowserProxyIsPublicIp($ip)
{
	if (!is_string($ip) || filter_var($ip, FILTER_VALIDATE_IP) === false) {
		return false;
	}
	if (stripos($ip, '::ffff:') === 0) {
		return geoBrowserProxyIsPublicIp(substr($ip, 7));
	}
	if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
		return false;
	}
	if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) {
		$long = ip2long($ip);
		if ($long === false) {
			return false;
		}
		if (($long & 0xFF000000) === 0x00000000) {
			return false;
		}
		if (($long & 0xFFFF0000) === 0xA9FE0000) {
			return false;
		}
		if (($long & 0xFFC00000) === 0x64400000) {
			return false;
		}
		if (($long & 0xF0000000) === 0xE0000000) {
			return false;
		}
		if (($long & 0xF0000000) === 0xF0000000) {
			return false;
		}
	}
	return true;
}

/**
 * Extract and normalize the media type from a Content-Type header.
 * Parameters such as charset are discarded and the result is lowercased.
 *
 * @param mixed $value Raw Content-Type value.
 * @return string Normalized media type, or an empty string.
 */
function geoBrowserProxyMediaType($value)
{
	if (!is_string($value)) {
		return '';
	}
	$parts = explode(';', $value, 2);
	return strtolower(trim($parts[0]));
}

/**
 * Fetch an upstream URL with cURL using the validated DNS address.
 * The resolved address is pinned through CURLOPT_RESOLVE so cURL cannot be
 * redirected by a second DNS lookup. Redirects are disabled, protocols are
 * limited to HTTP(S), TLS is verified, and the response body is capped.
 *
 * @param string $url Validated upstream URL.
 * @param string $method GET or POST.
 * @param string $body Request body for POST.
 * @param string $host Hostname used for TLS/SNI and the Host header.
 * @param int $port Upstream port (80 or 443).
 * @param array $ips Resolved IPs; the first address is pinned.
 * @return array|null Response array with body/content_type, or null on failure.
 */
function geoBrowserProxyFetchCurl($url, $method, $body, $host, $port, $ips)
{
	$ip = $ips[0];
	$resolve = $host . ':' . $port . ':' . (strpos($ip, ':') !== false ? '[' . $ip . ']' : $ip);
	$responseBody = '';
	$tooLarge = false;
	$headers = array('Accept: */*', 'Expect:');

	$ch = curl_init($url);
	if ($ch === false) {
		return null;
	}

	$options = array(
		CURLOPT_RETURNTRANSFER => false,
		CURLOPT_FOLLOWLOCATION => false,
		CURLOPT_MAXREDIRS => 0,
		CURLOPT_CONNECTTIMEOUT => 5,
		CURLOPT_TIMEOUT => 15,
		CURLOPT_SSL_VERIFYPEER => true,
		CURLOPT_SSL_VERIFYHOST => 2,
		CURLOPT_USERAGENT => 'GeoBrowser proxy/1.0',
		CURLOPT_HTTPHEADER => $headers,
		CURLOPT_RESOLVE => array($resolve),
		CURLOPT_ENCODING => '',
		CURLOPT_WRITEFUNCTION => function ($ch, $chunk) use (&$responseBody, &$tooLarge) {
			if (strlen($responseBody) + strlen($chunk) > GEOBROWSER_PROXY_MAX_RESPONSE_BYTES) {
				$tooLarge = true;
				return 0;
			}
			$responseBody .= $chunk;
			return strlen($chunk);
		},
	);
	if (defined('CURLOPT_PROTOCOLS_STR')) {
		$options[CURLOPT_PROTOCOLS_STR] = 'http,https';
	} else {
		$options[CURLOPT_PROTOCOLS] = CURLPROTO_HTTP | CURLPROTO_HTTPS;
	}
	if ($method === 'POST') {
		$headers[] = 'Content-Type: ' . $_SERVER['CONTENT_TYPE'];
		$headers[] = 'Content-Length: ' . strlen($body);
		$options[CURLOPT_HTTPHEADER] = $headers;
		$options[CURLOPT_POST] = true;
		$options[CURLOPT_POSTFIELDS] = $body;
	}

	curl_setopt_array($ch, $options);
	$ok = curl_exec($ch);
	$errno = curl_errno($ch);
	$status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
	$contentType = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

	if ($tooLarge || $ok === false || $errno !== 0 || $status < 200 || $status >= 300) {
		return null;
	}

	return array('body' => $responseBody, 'content_type' => $contentType);
}

/**
 * Send the upstream response with a safe content type and security headers.
 * HTML/XHTML/SVG responses are rejected. Binary download types are sent as
 * attachments; other allowed types are rendered inline with nosniff and a
 * restrictive CSP.
 *
 * @param array $response Response array from the cURL fetch.
 * @return void
 */
function geoBrowserProxySendResponse($response)
{
	$contentType = geoBrowserProxySafeContentType($response['content_type']);
	if ($contentType === null) {
		geoBrowserProxyError(502, 'Unsupported upstream content type');
		return;
	}

	$body = $response['body'];
	header('Content-Type: ' . $contentType);
	header('Content-Length: ' . strlen($body));
	header('X-Content-Type-Options: nosniff');
	header('X-Frame-Options: DENY');
	header('Content-Security-Policy: default-src \'none\'; frame-ancestors \'none\'');
	if (in_array($contentType, array(
		'application/octet-stream',
		'application/zip',
		'application/x-zip-compressed',
		'application/vnd.google-earth.kmz',
		'application/vnd.ms-excel',
		'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
	), true)) {
		header('Content-Disposition: attachment');
	}
	echo $body;
}

/**
 * Map an upstream Content-Type to a safely handled proxy content type.
 * Known safe types are preserved, empty or unknown types become
 * application/octet-stream, and active HTML/XHTML/SVG types return null.
 *
 * @param mixed $value Upstream Content-Type header.
 * @return string|null Safe media type, or null for blocked active content.
 */
function geoBrowserProxySafeContentType($value)
{
	$mediaType = geoBrowserProxyMediaType($value);
	$allowed = array(
		'image/png',
		'image/jpeg',
		'image/gif',
		'image/webp',
		'application/xml',
		'text/xml',
		'application/gml+xml',
		'text/csv',
		'application/csv',
		'text/plain',
		'application/json',
		'application/geo+json',
		'application/vnd.google-earth.kml+xml',
		'application/vnd.google-earth.kmz',
		'application/zip',
		'application/x-zip-compressed',
		'application/octet-stream',
		'application/vnd.ms-excel',
		'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
	);
	if (in_array($mediaType, $allowed, true)) {
		return $mediaType;
	}
	if ($mediaType === '') {
		return 'application/octet-stream';
	}
	if (in_array($mediaType, array('text/html', 'application/xhtml+xml', 'image/svg+xml'), true)) {
		return null;
	}
	return 'application/octet-stream';
}

/**
 * Emit a plain-text proxy error response.
 * The caller must pass a fixed message and must not include user input,
 * because the message is written directly to the response body.
 *
 * @param int $status HTTP status code.
 * @param string $message Safe error message.
 * @param array $headers Additional response headers.
 * @return void
 */
function geoBrowserProxyError($status, $message, $headers = array())
{
	http_response_code($status);
	header('Content-Type: text/plain; charset=utf-8');
	header('X-Content-Type-Options: nosniff');
	header('Cache-Control: no-store');
	foreach ($headers as $header) {
		header($header);
	}
	echo $message . "\n";
}
