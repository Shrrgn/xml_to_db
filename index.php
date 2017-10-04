<?php
	require 'creating_db.php';
	$vw = new DBFromXML();
	$vw->create_table_vw();
	$vw->parsing_xml_insert();

	echo '<br>';
	//$vw::get_data_from_filename('vw/VW_stat_05.09.2017.xml');
	
	echo 'every thing is ok';
?>