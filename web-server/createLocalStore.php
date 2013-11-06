<html>
<body>
<?php


	include_once('arc/ARC2.php');
	include_once('inc_config.php');
	include_once('inc_helper.php');

	$store = ARC2::getStore($local_store_config);
	

	/* Create database for local store if needed. */
	if (!$store->isSetUp()) {
	  $store->setUp();
	}

	/* Always start fresh. */
	$store->reset();
	
	//$loadBase  = "http://" . $_SERVER["HTTP_HOST"] . substr($_SERVER["PHP_SELF"],0,-20) . "local/";
	$loadBase  = "http://" . $_SERVER["HTTP_HOST"]  . "/swc/rdf/";
	// $loadInto  = "http://localhost/swc/web-server/local/";
	$loadInto  = "http://localhost/rdf";
	$loadArray = array (
		"semanticweb1.rdf",  //
		"semanticweb2.rdf",	 //
		"semanticweb3.rdf",  // 
		"semanticweb4.rdf",  // No tiene Geo
		"semanticweb5.rdf",  //
		"semanticweb1.owl",
		"bestbuy.rdf",		//
		"elmartogr.rdf",	// No tiene Geo
		"peekundcloppenburg.htm", //No tiene Geo
		"richsnippetgenerator.htm",
		"semanticweb.rdf",
		"semanticweb6.rdf",
		"semanticweb7.rdf",
		"semanticweb8.rdf",
		"semanticweb9.rdf",
	);
	
	foreach ($loadArray as $value) {
		print_r($loadBase.$value."\n");
    	$store->query('LOAD <'.$loadBase.$value.'> INTO <'.$loadInto.'>');
	}
			
	$query1 = $semantic_prefixes .
		'	SELECT * WHERE { { ?grL a gr:LocationOfSalesOrServiceProvisioning } UNION { ?grL a gr:Location } ' .
		'	OPTIONAL { ?grL vcard:geo ?bn_loc . ' . 
		'		OPTIONAL { ?bn_loc vcard:latitude ?lat } . ' . 
		'		OPTIONAL { ?bn_loc vcard:longitude ?lng } . ' . 
		'   } ' .
		'	OPTIONAL { ?grL vcard:label ?address_label } . ' . 
		'	OPTIONAL { ' . 
		'		?grL vcard:adr ?bn_a . ' . 
		'		OPTIONAL { ' . 
		'			?bn_a vcard:street-address ?address_street_address . ' . 
		'			?bn_a vcard:postal-code ?address_postal_code . ' . 
		'			OPTIONAL { ?bn_a vcard:country-name ?address_country_name } . ' . 
		'		} . ' . 
		'	}  . ' . 		
		' } ORDER BY ?grL ';

	$result1 = $store->query($query1);
	$counter = 0;
	
	if (!$store->getErrors()) {
		foreach ($result1['result']['rows'] as $venue) {
			if ( (!isset($venue["lat"])) && (!isset($venue["lng"])) ){
				if (!isset($venue["address_country_name"])) { $venue["address_country_name"]="Mexico"; }
				if (isset($venue["address_label"])) {
					$adr = $venue["address_label"];
				} elseif ( (isset($venue["address_street_address"])) && (isset($venue["address_postal_code"])) && (isset($venue["address_country_name"])) ) {
					$adr = $venue["address_street_address"] . ' - ' . $venue["address_postal_code"] .' '. $venue["address_country_name"];
				} else {
					continue;
				}
				$coord = getLatLon($adr, "google");
				$base = explode("#", $venue["grL"]);
				
				$counter++;
				$query2 = $semantic_prefixes .
				'INSERT INTO <http://localhost/swc/web-server/local/> { ' . 
    			'    <'. $venue["grL"] .'> vcard:geo _:b'.$counter.'node1 . ' . 		
			    '		_:b'.$counter.'node1 a vcard:location .' . 
			    '		_:b'.$counter.'node1 vcard:latitude "'. $coord["lat"] .'" .' . 
			    '		_:b'.$counter.'node1 vcard:longitude "'. $coord["lng"] .'" .' . 
				'}';

				$r = $store->query($query2);
				echo "\n=======================================================================================================================\n" . 
					 "Added Coordinates (Lat: $coord[lat], Lng: $coord[lng]) to the URI \n" .
					 "$venue[grL] \n" .
					 "Generated via $coord[service] from the address \n " . 
					 "$adr ." . 
					 "\n=======================================================================================================================\n";
				unset($query2,$base,$coord,$adr);
			}
		}
	}

	$query3 = $semantic_prefixes .
		'	SELECT * WHERE { { ?grL a gr:LocationOfSalesOrServiceProvisioning } UNION { ?grL a gr:Location } ' .
		'	OPTIONAL { ?grL vcard:geo ?bn_loc . ' . 
		'		OPTIONAL { ?bn_loc vcard:latitude ?lat } . ' . 
		'		OPTIONAL { ?bn_loc vcard:longitude ?lng } . ' . 
		'   } ' .
		'	OPTIONAL { ?grL vcard:label ?address_label } . ' . 
		'	OPTIONAL { ' . 
		'		?grL vcard:adr ?bn_a . ' . 
		'		OPTIONAL { ' . 
		'			?bn_a vcard:street-address ?address_street_address . ' . 
		'			?bn_a vcard:postal-code ?address_postal_code . ' . 
		'			OPTIONAL { ?bn_a vcard:country-name ?address_country_name } . ' . 
		'		} . ' . 
		'	}  . ' . 		
		' } ORDER BY ?grL ';

	$result3 = $store->query($query3);
	 print_r($result3);
?>
</body>
</html>