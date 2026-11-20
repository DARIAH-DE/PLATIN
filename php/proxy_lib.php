<?php
/*
* proxy_lib.php
*
* Hardened HTTP proxy used by proxy.php.
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

function geoBrowserProxyRun($validHosts)
{
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

	$response = geoBrowserProxyFetch($targetUrl, $method, $postBody, $hostForMatch, $port, $ips);
	if ($response === null) {
		geoBrowserProxyError(502, 'Upstream request failed');
		return;
	}

	geoBrowserProxySendResponse($response);
}

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

function geoBrowserProxyMediaType($value)
{
	if (!is_string($value)) {
		return '';
	}
	$parts = explode(';', $value, 2);
	return strtolower(trim($parts[0]));
}

function geoBrowserProxyFetch($url, $method, $body, $host, $port, $ips)
{
	if (function_exists('curl_init')) {
		return geoBrowserProxyFetchCurl($url, $method, $body, $host, $port, $ips);
	}
	return geoBrowserProxyFetchStream($url, $method, $body);
}

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

function geoBrowserProxyFetchStream($url, $method, $body)
{
	$headers = "Accept: */*\r\nConnection: close\r\n";
	if ($method === 'POST') {
		$headers .= 'Content-Type: ' . $_SERVER['CONTENT_TYPE'] . "\r\n";
		$headers .= 'Content-Length: ' . strlen($body) . "\r\n";
	}
	$options = array(
		'http' => array(
			'method' => $method,
			'header' => $headers,
			'content' => $method === 'POST' ? $body : '',
			'follow_location' => 0,
			'max_redirects' => 0,
			'timeout' => 15,
			'ignore_errors' => true,
			'protocol_version' => 1.1,
		),
		'ssl' => array(
			'verify_peer' => true,
			'verify_peer_name' => true,
		),
	);
	$context = stream_context_create($options);
	$responseBody = @file_get_contents($url, false, $context, 0, GEOBROWSER_PROXY_MAX_RESPONSE_BYTES + 1);
	if (!is_string($responseBody) || strlen($responseBody) > GEOBROWSER_PROXY_MAX_RESPONSE_BYTES) {
		return null;
	}

	$status = 0;
	$contentType = '';
	$responseHeaders = function_exists('http_get_last_response_headers')
		? http_get_last_response_headers()
		: null;
	if (!is_array($responseHeaders)) {
		$responseHeaderName = 'http_response_header';
		$responseHeaders = isset($$responseHeaderName) ? $$responseHeaderName : array();
	}
	if (is_array($responseHeaders)) {
		foreach ($responseHeaders as $line) {
			if (preg_match('#^HTTP/\S+\s+(\d{3})#', $line, $matches) === 1) {
				$status = (int) $matches[1];
			} elseif (stripos($line, 'Content-Type:') === 0) {
				$contentType = trim(substr($line, strlen('Content-Type:')));
			}
		}
	}
	if ($status < 200 || $status >= 300) {
		return null;
	}
	return array('body' => $responseBody, 'content_type' => $contentType);
}

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
