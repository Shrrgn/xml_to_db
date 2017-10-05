<?php
	require 'creating_db.php';
	
	$vw = new DBFromXML();
	//$vw->create_table_vw();
	//$vw->parsing_xml_insert();

	echo '<br>';
	
	$vw->destroy_connection();
	echo 'Everything is ok';
?>