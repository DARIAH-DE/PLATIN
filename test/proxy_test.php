<?php
define('GEOBROWSER_PROXY_LIBRARY', true);
require __DIR__ . '/../php/proxy_lib.php';

$failures = array();

function checkValue($label, $actual, $expected)
{
	global $failures;
	if ($actual !== $expected) {
		$failures[] = $label . ': expected ' . var_export($expected, true) . ', got ' . var_export($actual, true);
	}
}

$validHosts = array(
	'/^geoserver\\.de\\.dariah\\.eu\\z/i',
	'/^([a-z0-9-]+\\.)*steinheim-institut\\.de\\z/i',
);

checkValue('normalizes delimited pattern', geoBrowserProxyNormalizePattern('/^([a-z0-9-]+\\.)*dariah\\.eu$/i'), '/^([a-z0-9-]+\\.)*dariah\\.eu$/i');
checkValue('normalizes \\z pattern', geoBrowserProxyNormalizePattern('/^([a-z0-9-]+\\.)*dariah\\.eu\\z/i'), '/^([a-z0-9-]+\\.)*dariah\\.eu\\z/i');
checkValue('wraps bare pattern', geoBrowserProxyNormalizePattern('^([a-z0-9-]+\\.)*dariah\\.eu$'), '/^([a-z0-9-]+\\.)*dariah\\.eu$/i');
checkValue('rejects unanchored pattern', geoBrowserProxyNormalizePattern('/.*dariah\\.eu/'), null);
checkValue('rejects empty pattern', geoBrowserProxyNormalizePattern(''), null);

$hostCases = array(
    array('accepts GeoServer host', 'geoserver.de.dariah.eu', true),
    array('accepts Steinheim apex', 'steinheim-institut.de', true),
    array('accepts Steinheim subdomain', 'www.steinheim-institut.de', true),
    array('accepts nested Steinheim subdomain', 'data.www.steinheim-institut.de', true),
    array('rejects old GeoServer host', 'geoserver.dariah.eu', false),
    array('rejects CDstar host', 'cdstar.de.dariah.eu', false),
    array('rejects GeoServer subdomain', 'foo.geoserver.de.dariah.eu', false),
    array('rejects GeoServer lookalike', 'geoserver.de.dariah.eu.evil', false),
    array('rejects Steinheim prefix', 'evilsteinheim-institut.de', false),
    array('rejects Steinheim suffix', 'steinheim-institut.de.evil', false),
    array('rejects Steinheim trailing dot', 'steinheim-institut.de.', false),
    array('rejects unrelated host', 'example.org', false),
    array('rejects localhost', 'localhost', false),
);
foreach ($hostCases as $case) {
    $allowed = geoBrowserProxyHostAllowed($case[1], $validHosts);
    checkValue($case[0], $allowed, $case[2]);
}
checkValue('denies empty allowlist',
    geoBrowserProxyHostAllowed('geoserver.de.dariah.eu', array()), false);

$_GET = array(
	'address' => 'https://geoserver.de.dariah.eu/geoserver/wfs?service=WFS&request=GetFeature',
	'outputFormat' => 'application/json',
	'label' => 'a b',
);
$parts = parse_url($_GET['address']);
checkValue(
	'builds target with encoded extra parameters',
	geoBrowserProxyBuildTargetUrl($parts),
	'https://geoserver.de.dariah.eu/geoserver/wfs?service=WFS&request=GetFeature&outputFormat=application%2Fjson&label=a%20b'
);

$_GET = array(
	'address' => 'https://geoserver.de.dariah.eu/geoserver/wfs?service=WFS',
	'request' => 'GetFeature',
);
$parts = parse_url($_GET['address']);
checkValue(
	'builds target for legacy unencoded query parameters',
	geoBrowserProxyBuildTargetUrl($parts),
	'https://geoserver.de.dariah.eu/geoserver/wfs?service=WFS&request=GetFeature'
);

$_GET = array('address' => 'https://geoserver.de.dariah.eu/', 'array' => array('x'));
$parts = parse_url($_GET['address']);
checkValue('rejects array parameters', geoBrowserProxyBuildTargetUrl($parts), null);

checkValue('accepts public IPv4', geoBrowserProxyIsPublicIp('8.8.8.8'), true);
checkValue('accepts public IPv6', geoBrowserProxyIsPublicIp('2606:4700:4700::1111'), true);
checkValue('rejects IPv4 loopback', geoBrowserProxyIsPublicIp('127.0.0.1'), false);
checkValue('rejects IPv4 private', geoBrowserProxyIsPublicIp('10.0.0.1'), false);
checkValue('rejects IPv4 link-local', geoBrowserProxyIsPublicIp('169.254.1.1'), false);
checkValue('rejects IPv4 CGNAT', geoBrowserProxyIsPublicIp('100.64.0.1'), false);
checkValue('rejects IPv4 multicast', geoBrowserProxyIsPublicIp('224.0.0.1'), false);
checkValue('rejects IPv6 loopback', geoBrowserProxyIsPublicIp('::1'), false);
checkValue('rejects IPv6 unique-local', geoBrowserProxyIsPublicIp('fc00::1'), false);
checkValue('rejects IPv6 link-local', geoBrowserProxyIsPublicIp('fe80::1'), false);
checkValue('rejects IPv4-mapped loopback', geoBrowserProxyIsPublicIp('::ffff:127.0.0.1'), false);

checkValue('normalizes media type', geoBrowserProxyMediaType('Application/XML; charset=UTF-8'), 'application/xml');
checkValue('allows png', geoBrowserProxySafeContentType('image/png; charset=binary'), 'image/png');
checkValue('defaults empty media type to octet-stream', geoBrowserProxySafeContentType(''), 'application/octet-stream');
checkValue('maps unknown media type to octet-stream', geoBrowserProxySafeContentType('application/pdf'), 'application/octet-stream');
checkValue('blocks html', geoBrowserProxySafeContentType('text/html'), null);
checkValue('blocks svg', geoBrowserProxySafeContentType('image/svg+xml'), null);

$wfsBodies = array(
    array('allows WFS 1.1 GetFeature',
        '<wfs:GetFeature xmlns:wfs="http://www.opengis.net/wfs"/>', true),
    array('allows WFS 2.0 GetFeature',
        '<wfs:GetFeature xmlns:wfs="http://www.opengis.net/wfs/2.0"/>', true),
    array('rejects WFS Transaction',
        '<wfs:Transaction xmlns:wfs="http://www.opengis.net/wfs"/>', false),
    array('rejects plain text', 'not xml', false),
    array('rejects empty body', '', false),
    array('rejects DOCTYPE',
        '<!DOCTYPE wfs:GetFeature>'
        . '<wfs:GetFeature xmlns:wfs="http://www.opengis.net/wfs"/>', false),
);
foreach ($wfsBodies as $case) {
    $actual = geoBrowserProxyIsWfsGetFeature($case[1]);
    checkValue($case[0], $actual, $case[2]);
}

checkValue('requires the cURL extension', extension_loaded('curl'), true);
checkValue('removes the stream fallback', function_exists('geoBrowserProxyFetchStream'), false);
checkValue('removes the fetch dispatcher', function_exists('geoBrowserProxyFetch'), false);

if (count($failures) > 0) {
	fwrite(STDERR, "FAILED\n" . implode("\n", $failures) . "\n");
	exit(1);
}

echo "OK\n";
