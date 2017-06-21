<?php

function address_validate($address1,$address2,$city,$state,$zip) {
	$url = 'https://tools.usps.com/go/ZipLookupResultsAction!input.action?resultMode=0&companyName=&';

	$retval = array();

	$params = array();
	$params[] = "address1=". urlencode($address1 ? $address1 : "");
	$params[] = "address2=". urlencode($address2 ? $address2 : "");
	$params[] = "city=". urlencode($city ? $city : "");
	$params[] = "state=". urlencode($state ? $state : "");
	$params[] = "zip=". urlencode($zip ? $zip : "");
	$qstr = join("&",$params);

	$ch = curl_init($url . $qstr);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Host: tools.usps.com',
			'User-Agent: Mozilla/5.0 (Windows NT 6.1; WOW64; rv:18.0) Gecko/20100101 Firefox/18.0',
			'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
			'Accept-Language: en-US,en;q=0.5'
	));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	$result = curl_exec($ch);
	$error = curl_error($ch);

	if (!empty($error)) {
		//print_r($error);
		return "ERROR-CONNECT";
	}
	/*
	$result = file_get_contents($url . $qstr);
	if (!$result) {
		return "ERROR-CONNECT";
	}
	*/

	$lines = explode("\n",$result);
	foreach ($lines as $line) {
		$matches = array();

		$x = preg_match('<li class="error">',$line,$matches);
		if ($x) {
			return false;
		}

		$x = preg_match('|<span class="address1 range">(.*)</span>|',$line,$matches);
 		if ($x) {
 			$retval["address"] = trim($matches[1]);
 			continue;
 		}
 		$x = preg_match('|<span class="address2 range">(.*)</span>|',$line,$matches);
 		if ($x) {
 			$retval["address2"] = trim($matches[1]);
 			continue;
 		}
 		$x = preg_match('|<span class="city range">(.*)</span> <span class="state range">(.*)</span> <span class="zip" style="">(.*)</span><span class="hyphen">&#45;</span><span class="zip4">(.*)</span>|',$line,$matches);
 		if ($x) {
 			$retval["city"] = trim($matches[1]);
 			$retval["state"] = trim($matches[2]);
 			$retval["zip"] = trim($matches[3]);
 			$retval["zip4"] = trim($matches[4]);
 			break;
 		}
 		$x = preg_match('|<span class="city range">(.*)</span> <span class="state range">(.*)</span> <span class="zip" style="">(.*)</span><span class="zip4"></span>|',$line,$matches);
 		if ($x) {
 			$retval["city"] = trim($matches[1]);
 			$retval["state"] = trim($matches[2]);
 			$retval["zip"] = trim($matches[3]);
 			break;
 		}
 		$x = preg_match('|The address you provided is not recognized|',$line,$matches);
 		if ($x) {
 			$retval["no_service"] = true;
 		}
	}

 	if (count($retval)) return $retval;
 	return "ERROR-NODATA";
}

?>