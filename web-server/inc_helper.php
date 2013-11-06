
<?php
	function getLatLon($searchstring, $service="google") {
		if ($service=="yahoo") {
			$appID="YOU NEED AN APP ID TO USE YAHOO";
			$request = "http://where.yahooapis.com/geocode?$appID&flags=P&gflags=&location=".urlencode($searchstring);
			$session = curl_init($request);
			curl_setopt($session, CURLOPT_HEADER, false);
			curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
			$response = unserialize(curl_exec($session));
			curl_close($session);
	
			if (isset($response["ResultSet"]["Result"][0]["offsetlat"]) && isset($response["ResultSet"]["Result"][0]["offsetlon"])) {
				return array("lat"=>$response["ResultSet"]["Result"][0]["offsetlat"], "lng"=>$response["ResultSet"]["Result"][0]["offsetlon"], "service"=>$service);
			} else {
				return array("lat"=>$response["ResultSet"]["Result"][0]["latitude"], "lng"=>$response["ResultSet"]["Result"][0]["longitude"], "service"=>$service);
			}
		} else if ($service=="google") {
			$request = "http://maps.googleapis.com/maps/api/geocode/json?sensor=false&address='".urlencode($searchstring)."'";
			$session = curl_init($request);
			curl_setopt($session, CURLOPT_HEADER, false);
			curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
			$response = json_decode(curl_exec($session));
			curl_close($session);
			
			if (isset($response->results[0]->geometry->location->lat) && isset($response->results[0]->geometry->location->lng)) {
				return array("lat"=>$response->results[0]->geometry->location->lat, "lng"=>$response->results[0]->geometry->location->lng, "service"=>$service);
			} 
		}

		return array("lat"=>"47.7817", "lng"=>"9.606", "service"=>"error");
	}
	
	/* Computes distance between two decimal coordinate.
	 * Uses the the formula for distances bewtween points on the surface of a sphere. 
	 * 678388 is the radius of the earth in meters. */
	function distance($lat1, $lng1, $lat2, $lng2) {
		return ( 6378388 * acos(sin( deg2rad($lat1) )*sin( deg2rad($lat2) )+ cos( deg2rad($lat1) )*cos( deg2rad($lat2) )*cos( deg2rad($lng1) - deg2rad($lng2) )) );
	}
	
	/* Creates a rectangle with roughly the length defined as distance.
	 * Input as decimal coordinates and distance m. Output in decimal coordinates. */
	function relevantSquare($lat, $lng, $distance=1000) {
		$lng1 = $lng - $distance /abs(cos(deg2rad($lat))*111320);
		$lng2 = $lng + $distance /abs(cos(deg2rad($lat))*111320);
		$lat1 = $lat - ($distance/111320); 
		$lat2 = $lat + ($distance/111320);
		return array($lat1, $lng1, $lat2, $lng2);
	}
	
	/* $baseDate: The date delivered by the client.
	 * $validFrom, $validThrough: taken from the meta-data-
	 * $weekday: The number-code (0=sunday, 1=monday, ...) which should be tested for falling within the timeframe. 
	 * The function only tests days within the coming week. */
	function inRelevantTimeframe($baseDate, $validFrom=false, $validThrough=false, $weekday=0) {
		if (is_string($baseDate)) {
			$tmp = new DateTime();
			$tmp->setTimestamp(strtotime($baseDate));
			$baseDate = clone $tmp;
			unset($tmp);
		} elseif  (is_int($baseDate)) {
			$tmp = new DateTime();
			$tmp->setTimestamp($baseDate);
			$baseDate = clone $tmp;
			unset($tmp);
		}
		
		if (is_string($validFrom)) {
			$tmp = new DateTime();
			$tmp->setTimestamp(strtotime($validFrom));
			$validFrom = clone $tmp;
			unset($tmp);
		} elseif  (is_int($validFrom)) {
			$tmp = new DateTime();
			$tmp->setTimestamp($validFrom);
			$validFrom = clone $tmp;
			unset($tmp);
		} elseif (!($validFrom)) {
			$tmp = new DateTime();
			$tmp->setTimestamp(0);
			$validFrom = clone $tmp;
			unset($tmp);
		}
		
		if (is_string($validThrough)) {
			$tmp = new DateTime();
			$tmp->setTimestamp(strtotime($validThrough));
			$validThrough = clone $tmp;
			unset($tmp);
		} elseif  (is_int($validThrough)) {
			$tmp = new DateTime();
			$tmp->setTimestamp($validThrough);
			$validThrough = clone $tmp;
			unset($tmp);
		} elseif (!($validThrough)) {
			$tmp = new DateTime();
			$tmp->setTimestamp(2147483647);
			$validFrom = clone $tmp;
			unset($tmp);
		}
				
		$baseWeekday = $baseDate->format("w");
		
		$relevantDate = clone $baseDate;
		$relevantDate->add(new DateInterval('P' . (7 + $weekday - $baseWeekday) .'D'));
		
		if (( $validFrom->format("U") <= $relevantDate->format("U") ) && ( $relevantDate->format("U") <= $validThrough->format("U") )) {
			return true;
		} else {
			return false;
		}
	}
	
	/* Returns the length of an timeframe in seconds. If no timeframe is given, it will return false.
	 * As longer as the timeframe is as less important is it. Normalizing it between 0 for unimportant/no timeframe
	 * and 1 for extremely important/valid for one second could be done by using the inverse, but results in 
	 * extremely small number which is not practicable. (E.G., a timeframe of one week would have the value of
	 * 1/604800 = 0.00000165)
	 * The function only gives roughly the exact seconds due to a bug in PHP 5.3, months will be counted as 30 days
	 * and years as 365 days. */
	
	function timeframeRelevance($validFrom=false, $validThrough=false) {
		if (is_string($validFrom)) {
			$tmp = new DateTime();
			$tmp->setTimestamp(strtotime($validFrom));
			$validFrom = clone $tmp;
			unset($tmp);
		} elseif  (is_int($validFrom)) {
			$tmp = new DateTime();
			$tmp->setTimestamp($validFrom);
			$validFrom = clone $tmp;
			unset($tmp);
		}
		
		if (is_string($validThrough)) {
			$tmp = new DateTime();
			$tmp->setTimestamp(strtotime($validThrough));
			$validThrough = clone $tmp;
			unset($tmp);
		} elseif  (is_int($validThrough)) {
			$tmp = new DateTime();
			$tmp->setTimestamp($validThrough);
			$validThrough = clone $tmp;
			unset($tmp);
		}
		
		if (($validFrom) && ($validThrough)) {
			$interval = date_diff($validFrom, $validThrough, true);
			return (($interval->y)*365*24*60*60) + (($interval->m)*30*24*60*60) + (($interval->d)*24*60*60) + (($interval->h)*60*60) + (($interval->i)*60) + ($interval->s);
		} else if (($validFrom) || ($validThrough)) {
			return (2147483647);
		} else {
			return (int)0;
		}
	}
						 
?>