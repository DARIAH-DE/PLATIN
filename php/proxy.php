<?php
/*
* proxy.php
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

// Hosts that are allowed to download from in RegEx form. (e.g. "/.*dropbox\.com/")
// An empty array denies every request. Do not enable localhost in production.
// Patterns must be anchored (^...$ or ^...\z). Bare patterns are accepted
// for compatibility and wrapped automatically.
	$validHosts = array(
//		"/localhost/",
		"/^([a-z0-9-]+\.)*dariah\.eu$/i",
	);

	if (!defined('GEOBROWSER_PROXY_LIBRARY')) {
		define('GEOBROWSER_PROXY_LIBRARY', true);
	}
	require_once __DIR__ . '/proxy_lib.php';

	geoBrowserProxyRun($validHosts);
?>
