
<?php
	$remote_store_config_1 = array(
	  'remote_store_endpoint' => 'nothing/included',
	);

	$local_store_config = array(
	  'db_host' => 'localhost',
	  'db_name' => 'arc2database',
	  'db_user' => 'root',
	  'db_pwd' => '',
	  'store_name' => 'my_store',
	);

	$semantic_prefixes = ' PREFIX gr: <http://purl.org/goodrelations/v1#> '.
						 ' PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#> '.
						 ' PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#> '.
						 ' PREFIX vcard: <http://www.w3.org/2006/vcard/ns#> ' .
						 ' PREFIX foaf: <http://xmlns.com/foaf/0.1/> ' . 
						 ' PREFIX xsd: <http://www.w3.org/2001/XMLSchema#> ';
?>