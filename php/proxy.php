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

	//Hosts that are allowed to download from in RegEx form. (e.g. "/.*dropbox\.com/")
	//If this array is empty, all hosts are allowed.
	$validHosts = array();
	$validHosts[] = '/localhost/';
	$validHosts[] = '/b-dev20211109.pk.de/';

/*	$strFaenger = print_r($validHosts, true);
$strConsole = "<script>console.log('console: ".$strFaenger."');</script>";
echo $strConsole;*/
//print_r($validHosts);

	if (isset($_REQUEST['address'])){

		$parsedAddress = parse_url($_REQUEST['address']);

		$found = 0;

		foreach ($validHosts as $host){
			if (preg_match($host, $parsedAddress["host"])){
				$found = 1;
				break;
			}
		}

		if ((count($validHosts)==0) || ($found==1)){
			$address = $_REQUEST['address'];
			$isFirst = 1;
			foreach($_REQUEST as $key => $value){
				if ($key == 'address')
					continue;
				if ($isFirst == 1){
					$isFirst = 0;
					$address .= "?";
				} else {
					$address .= "&";
				}
				$address .= $key . "=" . $value;
			}

			$request_body = file_get_contents('php://input');

			if (empty($request_body)){
				echo file_get_contents($address);
			} else {
				$opts = array('http' =>
						array(
								'method'  => 'POST',
								'header'  => 'Content-type: '.$_SERVER["CONTENT_TYPE"],
								'content' => $request_body
						)
				);

				$context = stream_context_create($opts);

				echo file_get_contents($address, false, $context);
			}
		}
	}
?>
