<?php
	require 'creating_db.php';
	
	$vw = new DBFromXML();
	
	$vw->create_table_vw();
	$vw->parsing_xml_insert();
	$vw->create_table_trafficcost();
	$vw->select_group_insert();
	$vw->destroy_connection();
	
	echo '<br>';
	
	echo 'Everything is ok';
?>