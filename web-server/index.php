<?php
	$measureResponseStart = microtime(true);
	
	header("Cache-Control: no-cache, must-revalidate");
	header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
	header("Content-Type: text/javascript; charset=utf-8");
	
	include_once('arc/ARC2.php');
	include_once('inc_config.php');
	include_once('inc_helper.php');

//	$store = ARC2::getRemoteStore($remote_store_config_1);
	$store = ARC2::getStore($local_store_config);
//$store = ARC2::getStoreEndpoint($semantic_prefixes);


	//$position = array("lat"=>"47.7817", "lng"=>"9.606");
	$position = array("lat"=>"19.71805", "lng"=>"-103.467525");
	//$parameters = array("crawlOpeninghours"=>true, "checkAlsoOfferings"=>true, "checkAlsoBE"=>true, "checkAlsoPM"=>true, "maxDistance"=>5000000, "maxResults"=> 15, "defaultCountry"=>"Germany","baseDate"=> new DateTime("now", new DateTimeZone("Europe/Berlin")));
	
	$parameters = array("crawlOpeninghours"=>true, "checkAlsoOfferings"=>true, "checkAlsoBE"=>true, "checkAlsoPM"=>true, "maxDistance"=>5000000, "maxResults"=> 15, "defaultCountry"=>"México","baseDate"=> new DateTime("now", new DateTimeZone("America/Mexico_City")));
	
	if (isset($_GET["address"])) {
		$position = getLatLon($_GET["address"], "google");
	}

	if (isset($_GET["checkAlsoOfferings"])) {
		$parameters["checkAlsoOfferings"] = $_GET["checkAlsoOfferings"];
	}

	if (isset($_GET["checkAlsoBE"])) {
		$parameters["checkAlsoBE"] = $_GET["checkAlsoBE"];
	}

	if (isset($_GET["checkAlsoPM"])) {
		$parameters["checkAlsoPM"] = $_GET["checkAlsoPM"];
	}

	if (isset($_GET["baseDate"])) {
		$parameters["baseDate"] = $_GET["baseDate"];
	}
		
	if (isset($_GET["crawlOpeninghours"])) {
		$parameters["crawlOpeninghours"] = $_GET["crawlOpeninghours"];
	}
		
	if (isset($_GET["maxDistance"])) {
		$parameters["maxDistance"] = $_GET["maxDistance"];
	}
			
	if (isset($_GET["maxResults"])) {
		$parameters["maxResults"] = $_GET["maxResults"];
	}
	
	if (isset($_GET["baseDate"])) {
		$tmp = new DateTime();
		
		/* IF YOU USE PHP5.3 or greate:
		 * 
		 * $tmp->setTimestamp((int)$_GET["baseDate"]);
		 * 
		 * IF YOU USE SOMETHING BEFORE PHP5.3:
		 */
		$date = getdate((int)$_GET["baseDate"]);
		$tmp->setDate( $date['year'] , $date['mon'] , $date['mday'] );
        $tmp->setTime( $date['hours'] , $date['minutes'] , $date['seconds'] );

		$parameters["baseDate"] = clone $tmp;
		unset($tmp);
	}
				
	if (isset($_GET["lat"])) {
		$position["lat"] = $_GET["lat"];
	}
		
	if (isset($_GET["lng"])) {
		$position["lng"] = $_GET["lng"];
	}

	if (isset($_GET["locationuri"])) {
		if (isset($_GET["unlimited"])) {
			requestOfferings($_GET["locationuri"], "OFFSET 20");
		} else {
			requestOfferings($_GET["locationuri"], "LIMIT 20");
		}
	} else {
		requestData($position, $parameters);
	}

	function requestData ($position, $parameters) {
		$daynumber = array("sunday"=>0, "monday"=>1, "tuesday"=>2, "wednesday"=>3, "thursday"=>4, "friday"=>5, "saturday"=>6);
		$legitpaymentmethods = array("Cash", "DirectDebit", "AmericanExptress", "DinersClub", "Discover", "JCB", "MasterCard", "VISA");
		
		global $semantic_prefixes, $store, $measureResponseStart;
		
		$distancebox = relevantSquare($position["lat"],$position["lng"],$parameters["maxDistance"]);
		
		/* Due to ARCs UNION behavior, UNIONs are used as few as possible. 
		 * Multiple OPTIONALs are used instead with PHP making the final decision. */
		
		$query = $semantic_prefixes .
		'	SELECT DISTINCT * WHERE { ' . 
		'   { { ?grL a gr:LocationOfSalesOrServiceProvisioning } UNION { ?grL a gr:Location } } { ' .

		'	{ ?grL vcard:geo ?bn_loc . ' . 
		'		?bn_loc vcard:latitude ?lat . ' . 
		'		?bn_loc vcard:longitude ?lng . ' . 
		'   } FILTER ((?lat > '.$distancebox[0].' ) && (?lat < '.$distancebox[2].' ) && (?lng > '.$distancebox[1].' ) && (?lng < '.$distancebox[3].' )) . ' . 
		
		'	OPTIONAL { ?grL gr:name ?name1 } . ' . 		
		'	OPTIONAL { ' . 
		'		?grL vcard:org ?bn_o . ' . 
		'		OPTIONAL { ' . 
		'			?bn_o vcard:organization-name ?name2 . ' . 
		'		} . ' . 
		'	}  . ' .
		'	OPTIONAL { ?grL rdfs:label ?name3 } ' .
		
		'	OPTIONAL { ?grL rdfs:comment ?description1 } . ' . 
		'	OPTIONAL { ?grL gr:description ?description2 } . ' . 

		'	OPTIONAL { ?grL vcard:label ?address_label } . ' . 
		'	OPTIONAL { ' . 
		'		?grL vcard:adr ?bn_a . ' . 
		'		OPTIONAL { ' . 
		'			?bn_a vcard:street-address ?address_street_address . ' . 
		'			?bn_a vcard:postal-code ?address_postal_code . ' . 
		'			OPTIONAL { ?bn_a vcard:country-name ?address_country_name } . ' . 
		'		} . ' . 
		'	}  . ' . 

		'	OPTIONAL { ' . 
		'		?grL vcard:agent ?bn_agent .  ' . 
		'		OPTIONAL { ?bn_agent vcard:n ?agent } . ' . 
		'	}  . ' . 
		
		'	OPTIONAL { ?grL gr:category ?category1 } . ' . 
		'	OPTIONAL { ?grL vcard:tel ?telefone1 } . ' . 
		'	OPTIONAL { ?grL vcard:fax ?telefax1 } . ' . 
		'	OPTIONAL { ?grL vcard:email ?email1 } . ' . 
		
		'	OPTIONAL { ?grL vcard:logo ?logo1 } . ' . 
		'	OPTIONAL { ?grL foaf:logo ?logo2 } . ' . 
		'	OPTIONAL { ?grL foaf:depiction ?logo3 } . ' . 
		
		'	OPTIONAL { ?grL vcard:url ?homepage1 } . ' . 
		'	OPTIONAL { ?grL foaf:homepage ?homepage2 } . ' . 
		'	OPTIONAL { ?grL rdfs:seeAlso ?homepage3 } ' . 
		'	OPTIONAL { ?grL foaf:page ?homepage7 } . ' . 
				
		'   } } ORDER BY ?grL LIMIT ' . $parameters["maxResults"];

		/* Without local store you might need to remove the last brackets. */

		$result = $store->query($query);
		$returnString = '({"status": "OK", "spos": {"lat":'.$position["lat"].', "lng":'.$position["lng"].'}, "results": []})';

		if ($parameters["checkAlsoBE"]) {
			$queryBE = $semantic_prefixes .
			'	SELECT DISTINCT * WHERE { ' . 
			'   { ?grBE a gr:BusinessEntity } { ' .
			'   ?grBE gr:hasPOS ?grL . ' .
		
			'	{ ?grL vcard:geo ?bn_loc . ' . 
			'		?bn_loc vcard:latitude ?lat . ' . 
			'		?bn_loc vcard:longitude ?lng . ' . 
			'   } FILTER ((?lat > '.$distancebox[0].' ) && (?lat < '.$distancebox[2].' ) && (?lng > '.$distancebox[1].' ) && (?lng < '.$distancebox[3].' )) . ' .
			'	OPTIONAL { ?grBE gr:legalName ?name4 } ' . 
			'	OPTIONAL { ?grBE gr:name ?name5 } ' . 
			'	OPTIONAL { ' . 
			'		?grBE vcard:org ?bn_o . ' . 
			'		OPTIONAL { ' . 
			'			?bn_o vcard:organization-name ?name6 . ' . 
			'		} . ' . 
			'	}  . ' .
			'	OPTIONAL { ?grBE rdfs:label ?name7 } ' . 
			'	OPTIONAL { ?grBE rdfs:comment ?description3 } . ' .
			'	OPTIONAL { ?grBE gr:description ?description4 } . ' . 
			'	OPTIONAL { ?grBE gr:category ?category2 } . ' . 
			'	OPTIONAL { ?grBE vcard:tel ?telefone2 } . ' . 
			'	OPTIONAL { ?grBE vcard:fax ?telefax2 } . ' . 
			'	OPTIONAL { ?grBE vcard:email ?email2 } . ' . 
			'	OPTIONAL { ?grBE vcard:logo ?logo4 } . ' . 
			'	OPTIONAL { ?grBE foaf:logo ?logo5 } . ' . 
			'	OPTIONAL { ?grBE foaf:depiction ?logo6 } . ' . 
			'	OPTIONAL { ?grBE vcard:url ?homepage4 } ' . 
			'	OPTIONAL { ?grBE foaf:homepage ?homepage5 } ' . 			
			'	OPTIONAL { ?grBE rdfs:seeAlso ?homepage6 } ' . 
			'	OPTIONAL { ?grBE foaf:page ?homepage8 } ' . 
			'   } }';
			$resultBE = $store->query($queryBE);
		}

		if (!$store->getErrors()) {
			
			/* Remove duplicates through merging same grL-URIs */
			$newResults = array();
			foreach ($result['result']['rows'] as $val) {
				if (!isset($newResults["$val[grL]"])) {
					if (count($newResults)>=$parameters["maxResults"]) {
						break;
					}
					$newResults["$val[grL]"] = array();
				}
				$newResults["$val[grL]"] = array_merge($val, $newResults["$val[grL]"]);
			}
			
			if ($parameters["checkAlsoBE"]) {
				foreach ($resultBE['result']['rows'] as $val) {
					if (isset($newResults["$val[grL]"])) {
						$newResults["$val[grL]"] = array_merge($val, $newResults["$val[grL]"]);					
					}
				}
			}

			$returnString = ( '({"status": "OK", "spos": {"lat":'.$position["lat"].', "lng":'.$position["lng"].'}, "results": [' );
			$active_grL = "";
			$counter = 0;
			$venueArray = array();
			
			foreach ($newResults as $value) {
				if ($active_grL != $value["grL"]) {
					$counter++;
					$active_grL = $value["grL"];
					$distance = 0;
					
		  			$venueString =  ( "{" );
					$venueString .= ( '"locationuri": '. json_encode((string)$value["grL"]) . ', ' );

					if ( isset($value["name1"]) ) {
						$venueString .= '"name": '. json_encode((string)$value["name1"]) . ', ';
					} elseif ( isset($value["name2"]) ) {
						$venueString .= '"name": '. json_encode((string)$value["name2"]) . ', ';
					} elseif ( isset($value["name3"]) ) {
						$venueString .= '"name": '. json_encode((string)$value["name3"]) . ', ';
					} elseif ( isset($value["name4"]) ) {
						$venueString .= '"name": '. json_encode((string)$value["name4"]) . ', ';
					} elseif ( isset($value["name5"]) ) {
						$venueString .= '"name": '. json_encode((string)$value["name5"]) . ', ';
					} elseif ( isset($value["name6"]) ) {
						$venueString .= '"name": '. json_encode((string)$value["name6"]) . ', ';
					} elseif ( isset($value["name7"]) ) {
						$venueString .= '"name": '. json_encode((string)$value["name7"]) . ', ';
					}
					
					if ( isset($value["agent"]) ) {
						$venueString .= ( '"agent": '. json_encode((string)$value["agent"]) . ', ' );
					}
					
					if ( isset($value["description1"]) ) {
						$venueString .= ( '"description": '. json_encode($value["description1"]) . ', ' );
					} elseif ( isset($value["description2"]) ) {
						$venueString .= ( '"description": '. json_encode($value["description2"]) . ', ' );
					} elseif ( isset($value["description3"]) ) {
						$venueString .= ( '"description": '. json_encode($value["description3"]) . ', ' );
					} elseif ( isset($value["description4"]) ) {
						$venueString .= ( '"description": '. json_encode($value["description4"]) . ', ' );
					}
					
					if ( isset($value["category1"]) ) {
						$venueString .= ( '"category": '. json_encode((string)$value["category1"]) . ', ' );
					} elseif ( isset($value["category2"]) ) {
						$venueString .= ( '"category": '. json_encode((string)$value["category2"]) . ', ' );
					}
		
					if ( isset($value["telefone1"]) ) {
						$venueString .= ( '"formatted_phone_number": '. json_encode((string)$value["telefone1"]) . ', ' );
					} elseif ( isset($value["telefone2"]) ) {
						if ( $value["telefone2"]=="literal" ) { $venueString .= ( '"formatted_phone_number": '. json_encode((string)$value["telefone2"]) . ', ' ); }
					}
		
					if ( isset($value["telefax1"]) ) {
						$venueString .= ( '"formatted_fax_number": '. json_encode((string)$value["telefax1"]) . ', ' );
					} elseif ( isset($value["telefax2"]) ) {
						if ( $value["telefone2"]=="literal" ) { $venueString .= ( '"formatted_email": '. json_encode((string)$value["telefax2"]) . ', ' ); }
					}
		
					if ( isset($value["email1"]) ) {
						$venueString .= ( '"formatted_email": '. json_encode((string)$value["email1"]) . ', ' );
					} elseif ( isset($value["email2"]) ) {
						if ( $value["telefone2"]=="literal" ) { $venueString .= ( '"formatted_email": '. json_encode((string)$value["email2"]) . ', ' ); }
					}
		
					if ( isset($value["logo1"]) ) {
						$venueString .= ( '"logo": '. json_encode((string)$value["logo1"]) . ', ' );
					} elseif ( isset($value["logo2"]) ) {
						$venueString .= ( '"logo": '. json_encode((string)$value["logo2"]) . ', ' );
					} elseif ( isset($value["logo3"]) ) {
						$venueString .= ( '"logo": '. json_encode((string)$value["logo3"]) . ', ' );
					} elseif ( isset($value["logo4"]) ) {
						$venueString .= ( '"logo": '. json_encode((string)$value["logo4"]) . ', ' );
					} elseif ( isset($value["logo5"]) ) {
						$venueString .= ( '"logo": '. json_encode((string)$value["logo5"]) . ', ' );
					} elseif ( isset($value["logo6"]) ) {
						$venueString .= ( '"logo": '. json_encode((string)$value["logo6"]) . ', ' );
					}
					
					if ( isset($value["homepage1"]) ) {
						$venueString .= ( '"homepage": '. json_encode((string)$value["homepage1"]) . ', ' );
					} elseif ( isset($value["homepage2"]) ) {
						$venueString .= ( '"homepage": '. json_encode((string)$value["homepage2"]) . ', ' );
					} elseif ( isset($value["homepage3"]) ) {
						$venueString .= ( '"homepage": '. json_encode((string)$value["homepage3"]) . ', ' );
					} elseif ( isset($value["homepage4"]) ) {
						$venueString .= ( '"homepage": '. json_encode((string)$value["homepage4"]) . ', ' );
					} elseif ( isset($value["homepage5"]) ) {
						$venueString .= ( '"homepage": '. json_encode((string)$value["homepage5"]) . ', ' );
					} elseif ( isset($value["homepage6"]) ) {
						$venueString .= ( '"homepage": '. json_encode((string)$value["homepage6"]) . ', ' );
					} elseif ( isset($value["homepage7"]) ) {
						$venueString .= ( '"homepage": '. json_encode((string)$value["homepage7"]) . ', ' );
					} elseif ( isset($value["homepage8"]) ) {
						$venueString .= ( '"homepage": '. json_encode((string)$value["homepage8"]) . ', ' );
					}
	
					if ( isset($value["lat"] ) && isset($value["lng"])) {
						$venueString .= ( '"geometry": { "location": { "lat": '. json_encode((string)$value["lat"]) .', "lng": '. json_encode((string)$value["lng"]) .' } }, ' );
					}
					
					if (!isset($value["address_country_name"])) { $value["address_country_name"] = $parameters["defaultCountry"]; }
					
					if ( isset($value["address_label"]) ) {
						$venueString .= ( '"formatted_address": '. json_encode((string)$value["address_label"]) . ', ' );
					}  if ( (isset($value["address_street_address"])) && (isset($value["address_postal_code"])) && (isset($value["address_country_name"])) ) {
						$venueString .= ( '"formatted_address": '. json_encode((string)($value["address_street_address"] . ' - ' . $value["address_postal_code"] .' '. $value["address_country_name"])) . ', ' );
					}
		 			
					$distance = distance($position["lat"],$position["lng"],$value["lat"],$value["lng"]);

					$venueString .= ( '"distance": '. json_encode((string)$distance) . ', ' );
					
					if ( $parameters["checkAlsoOfferings"] ) {
						$o_query = $semantic_prefixes . 
							' SELECT DISTINCT count(?grO) AS ?count WHERE { ?grO gr:availableAtOrFrom <' . $value["grL"] . '> }';
						$o_query = $store->query($o_query);
						if ( isset($o_query['result']['rows'][0]) ) {
							$venueString .= ( '"offerings": ' . $o_query['result']['rows'][0]["count"] . ', ' );
						}
					}
					
					if ( $parameters["checkAlsoPM"] ) {
						$pm_query = $semantic_prefixes . 
						'	SELECT DISTINCT ?pm WHERE { ' . 
						'   { ?grO gr:availableAtOrFrom <' . $value["grL"] . '> } { ' .
						'		?grO gr:acceptedPaymentMethods ?pm . ' . 
						'	} } ';
					
						$pm_result = $store->query($pm_query);

						if ( isset($pm_result['result']['rows'][0]) ) {
							$venueString .= ( '"paymentmethods": [' );
							
							foreach ($pm_result['result']['rows'] as $paymentmethods) {
								$tmp_pm = explode("#", $paymentmethods['pm']);
								if ( in_array($tmp_pm[1], $legitpaymentmethods) ) {
									$venueString .= ( json_encode((string)$tmp_pm[1]) . ', ' );
								}
							}
							$venueString .= ( '], ' );
						}
					}

		 			/* Check if it should crawl opening hours and if they exist. If so, crawl them, check them to be valid and add them in order. */
					if ( $parameters["crawlOpeninghours"] ) {
						if ( $store->query($semantic_prefixes." ASK { <" . $active_grL . "> gr:hasOpeningHoursSpecification [] }")) {
							$oh_query = $semantic_prefixes . 
							'	SELECT * WHERE { ' . 
							'	<' . $active_grL . '> gr:hasOpeningHoursSpecification ?bn_op . ' .
							'	?bn_op gr:hasOpeningHoursDayOfWeek ?oday . ' . 
							'	?bn_op gr:opens ?oopens . ' . 
							'	?bn_op gr:closes ?ocloses . ' . 
							'	OPTIONAL { ' . 
							'		OPTIONAL { ?bn_op gr:validFrom ?validfrom } . ' . 
							'		OPTIONAL { ?bn_op gr:validThrough ?validthrough } . ' . 
							'	} . ' .
							'	}';
							
							$oh_result = $store->query($oh_query);
							
							if (isset($oh_result['result']['rows'][0])) {
							
								$venueString .= '"openinghours": [';
									$oh_days = array();
	
									foreach ($oh_result['result']['rows'] as $oh_key=>$oh_value) {
										$tmp_day = explode("#", $oh_value["oday"]);
										$reduce_problems = array (0=> 0, 1=> 0, 2=> 0);
										$tmp_opens  = array_merge ( explode(":", $oh_value["oopens"]), $reduce_problems);
										$oh_integer = $tmp_opens[0]*60*60 + $tmp_opens[1]*60 + $tmp_opens[2] + $daynumber[strtolower($tmp_day[1])]*100000;
										$tmp_relevance = 0;
										
										if (!isset($oh_value["validfrom"])) {
											$oh_value["validfrom"] = false;
										} else if (strlen($oh_value["validfrom"])==0) {
											$oh_value["validfrom"] = false;
										}

										if (!isset($oh_value["validthrough"])) {
											$oh_value["validthrough"] = false;
										} else if (strlen($oh_value["validthrough"])==0) {
											$oh_value["validthrough"] = false;
										}

										if ($oh_value["validfrom"] || $oh_value["validthrough"]) {
											if (inRelevantTimeframe($parameters["baseDate"],$oh_value["validfrom"], $oh_value["validthrough"], $daynumber[strtolower($tmp_day[1])])) {
												$tmp_relevance = timeframeRelevance($oh_value["validfrom"], $oh_value["validthrough"]);
											} else {
												continue;
											}
										}

										$oh_string = '{"day": '. json_encode((string)strtolower($tmp_day[1])) . 
											 ', "opens": '. json_encode((string)$oh_value["oopens"]) . 
											 ', "closes": '. json_encode((string)$oh_value["ocloses"]) . 
											 ' }, ';
	
										$oh_days[$oh_integer] = array("string"=>$oh_string, "relevance"=>$tmp_relevance, "daynumber"=>$daynumber[strtolower($tmp_day[1])]);
									}
									
									ksort($oh_days);
									$last_relevance = 0;
									$last_daynumber = 0;
									$last_indexkey  = 0;
									foreach ($oh_days as $oh_key=>$oh_output) {
										if (($oh_output["daynumber"] == $last_daynumber) && ($oh_output["relevance"] != $last_relevance)) {
											if (($oh_output["relevance"]==0) ^ (( $oh_output["relevance"]>$last_relevance) && $last_relevance!=0 )) {
												unset( $oh_days[$oh_key] );
												continue;
											} else {
												unset( $oh_days[$last_indexkey] );
											}
										}
										
										$last_relevance = $oh_output["relevance"];
										$last_daynumber = $oh_output["daynumber"];
										$last_indexkey = $oh_key;
									}
									unset($oh_key, $oh_output);
									
									foreach ($oh_days as $oh_output) {
										$venueString .= $oh_output["string"];
									}
									unset($oh_output);
									
								$venueString .= "], ";
							
							}

						}
					}
					$venueString .= ( "}," );
					$venueArray[(int)($distance*100)+$counter] = $venueString;
				}
			}
	 		
			ksort($venueArray);
			foreach ($venueArray as $ve_output) {
				$returnString .= $ve_output;
			}
			$returnString .=('], "responseTime": '. json_encode((string)(microtime(true)-$measureResponseStart)) .'})');
	 	}
	
		if (isset($_GET["callback"])) {
			echo $_GET["callback"] . "(". $returnString . ");";
		} else {
			echo $returnString;
		}

		/*$tm = explode(",", $returnString);
			foreach ($tm as $tt) {
				echo $tt."\n";
			} */
	}

function requestOfferings($locationuri, $limit) {
	global $semantic_prefixes;
	global $store;
		
	$query = $semantic_prefixes . 
	'	SELECT DISTINCT * WHERE { ' . 
	'   { ?grO gr:availableAtOrFrom <' . $locationuri . '> } { ' .
	'		OPTIONAL { ?grO rdfs:label ?name1 } ' . 
	'		OPTIONAL { ?grO gr:name ?name2 } ' . 
	'		OPTIONAL { ?grO rdfs:comment ?description1 } ' . 
	'		OPTIONAL { ?grO foaf:homepage ?homepage1 } ' . 
	'		OPTIONAL { ?grO foaf:page ?homepage2 } ' . 
	'		OPTIONAL { ?grO vcard:url ?homepage3 } ' . 
	'		OPTIONAL { ?grO rdfs:seeAlso ?homepage4 } ' . 			
	'		OPTIONAL { ' . 
	'			?grO gr:hasPriceSpecification ?bn_p . ' . 
	'			OPTIONAL { ' . 
	'				?bn_p gr:hasCurrency ?currency . ' . 
	'				?bn_p gr:hasCurrencyValue ?price . ' . 
	'			} . ' . 
	'		}  . ' . 
	'	} } ' . $limit;

	$result = $store->query($query);
	
	$returnString = '({"status": "OK", "number": 0})';

	if ( isset($result['result']['rows'][0]) ) {
			
		$returnString = '({"status": "OK", "locationuri": "' . $locationuri . '", "number": ' . count($result['result']['rows']) . ', "results": [';
							
		foreach ($result['result']['rows'] as $offering) {
			$returnString .= '{';

					if ( isset($offering["name1"]) ) {
						$returnString .= '"name": '. json_encode((string)$offering["name1"]) . ', ';
					} elseif ( isset($offering["name2"]) ) {
						$returnString .= '"name": '. json_encode((string)$offering["name2"]) . ', ';
					} elseif ( isset($offering["description1"]) ) {
						if (strlen($offering["description1"])>25) {
							$returnString .= '"name": '. json_encode(substr($offering["description1"],0,20) . ' [...]' ) . ', ';
						} else {
							$returnString .= '"name": '. json_encode($offering["description1"]) . ', ';
						}
					}

					if ( isset($offering["homepage1"]) ) {
						$returnString .= '"homepage": '. json_encode((string)$offering["homepage1"]) . ', ';
					} elseif ( isset($offering["homepage2"]) ) {
						$returnString .= '"homepage": '. json_encode((string)$offering["homepage2"]) . ', ';
					} elseif ( isset($offering["homepage3"]) ) {
						$returnString .= '"homepage": '. json_encode((string)$offering["homepage3"]) . ', ';
					} elseif ( isset($offering["homepage4"]) ) {
						$returnString .= '"homepage": '. json_encode((string)$offering["homepage4"]) . ', ';
					}
		
					if ( isset($offering["description1"]) ) {
						$returnString .= '"description": '. json_encode($offering["description1"]) . ', ';
					} 
					
					if ( isset($offering["currency"]) && isset($offering["price"]) ) {
						$returnString .= '"price": '. json_encode((string)($offering["price"] . ' ' . $offering["currency"])) . ', ';
					} 
					
			$returnString .= '}, ';
		}
		$returnString .= ']})';
	}

	if (isset($_GET["callback"])) {
		echo $_GET["callback"] . "(". $returnString . ");";
	} else {
		echo $returnString;
	}
}
?>