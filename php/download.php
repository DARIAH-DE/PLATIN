<?php
/*
* download.php
*
* Copyright (c) 2013, Sebastian Kruse. All rights reserved.
*
* This library is free software; you can redistribute it and/or
* modify it under the terms of the GNU Lesser General Public
* License as published by the Free Software Foundation; either
* version 3 of the License, or (at your option) any later version.
*
* This library is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
* Lesser General Public License for more details.
*
* You should have received a copy of the GNU Lesser General Public
* License along with this library; if not, write to the Free Software
* Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
* MA 02110-1301  USA
*/

if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
	http_response_code(405);
	header('Allow: POST');
	header('Content-Type: text/plain; charset=utf-8');
	echo "Method Not Allowed\n";
	exit;
}

$file = isset($_POST['file']) ? $_POST['file'] : null;
$filename = isset($_POST['filename']) ? $_POST['filename'] : 'export';

if (!is_string($file) || $file === '') {
	http_response_code(400);
	header('Content-Type: text/plain; charset=utf-8');
	echo "Missing file\n";
	exit;
}

if (!is_string($filename)) {
	$filename = 'export';
}

$maxFileSize = 5 * 1024 * 1024;
if (strlen($file) > $maxFileSize) {
	http_response_code(413);
	header('Content-Type: text/plain; charset=utf-8');
	echo "File too large\n";
	exit;
}

$trimmedFile = ltrim($file, "\xEF\xBB\xBF \t\n\r");
if (strpos($trimmedFile, '<?xml') !== 0 && strpos($trimmedFile, '<kml') !== 0) {
	http_response_code(400);
	header('Content-Type: text/plain; charset=utf-8');
	echo "Invalid KML\n";
	exit;
}

$filename = preg_replace('/[^A-Za-z0-9._-]+/', '_', $filename);
$filename = trim($filename, '._-');
if ($filename === '') {
	$filename = 'export';
}
$filename = substr($filename, 0, 100) . '.kml';

header('Content-Type: application/vnd.google-earth.kml+xml; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . strlen($file));
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Content-Security-Policy: default-src \'none\'; sandbox');
header('Cache-Control: no-store');

echo $file;
?>
