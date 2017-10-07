<?php
//Taking data from xml and adding it to database

	class DBFromXML {

		private static $host = "localhost";
		private static $db_name = "users_db";
		private static $user_name = "artem";
		private static $user_password = "artem";

		public static $xmls = array('vw/VW_stat_05.09.2017.xml', 'vw/VW_stat_06.09.2017.xml', 'vw/VW_stat_07.09.2017.xml',
									'vw/VW_stat_08.09.2017.xml', 'vw/VW_stat_09.09.2017.xml', 'vw/VW_stat_10.09.2017.xml', 
									'vw/VW_stat_11.09.2017.xml');
			
		function __construct(){
			$this->pdo = $this->db_connection(self::$host, self::$db_name, self::$user_name, self::$user_password);
		}

		function db_connection($host, $db, $user, $password){
			try {
				$pdo = new PDO('mysql:host=' . $host . ';dbname=' . $db, $user, $password);
				$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				$pdo->exec('SET NAMES "utf8"') ;
			}
			catch (PDOException $e) {
				self::db_error("Couldn't make connection to database: ", $e);
			}
			return $pdo;
		}

		function create_table_vw(){
			try {
				$sql = 'DROP TABLE IF EXISTS vw;
						CREATE TABLE IF NOT EXISTS vw (
							id_stat INT AUTO_INCREMENT PRIMARY KEY,
							apply_time datetime DEFAULT NULL,
							ip VARCHAR(20) DEFAULT NULL,
							country VARCHAR(30) DEFAULT NULL,
							city VARCHAR(30) DEFAULT NULL,
							ad_id INT NOT NULL,
							camp_id INT NOT NULL,
							hsite2 VARCHAR(50) DEFAULT NULL,
							cost DECIMAL(10,4) DEFAULT NULL 
						) ENGINE=InnoDB DEFAULT CHARSET=utf8';
				$s = $this->pdo->prepare($sql);
				$s->execute();
			}
			catch (PDOException $e) {
				self::db_error('Error of creating table: ', $e);
			}
		}

		function parsing_xml_insert(){
			
			foreach (self::$xmls as $xml_file){ 

				if (file_exists($xml_file)) {
					$vw_stats = simplexml_load_file($xml_file);
					
					foreach ($vw_stats->stat as $data) {
						
						$this->insert_data_vw(self::get_date_from_filename($xml_file) . ' ' . $data->time,
												$data->ip,
												$data->country,
												$data->city,
												$data->ad_id,
												$data->camp_id,
												$data->hsite2,
												$data->cost);	
					}
				}

				else {
					exit('Failed to open xml file');
				}
			}
		}

		function insert_data_vw($apply_time, $ip, $country, $city, $ad_id, $camp_id, $hsite2, $cost){
			try {
				$sql = 'INSERT INTO vw SET 
						apply_time = :apply_time,
						ip = :ip,
						country = :country,
						city = :city,
						ad_id = :ad_id,
						camp_id = :camp_id,
						hsite2 = :hsite2,
						cost = :cost';
				
				$s = $this->pdo->prepare($sql);
				$s->bindValue(':apply_time', $apply_time);
				$s->bindValue(':ip', $ip);
				$s->bindValue(':country', $country);
				$s->bindValue(':city', $city);
				$s->bindValue(':ad_id', $ad_id);
				$s->bindValue(':camp_id', $camp_id);
				$s->bindValue(':hsite2', $hsite2);
				$s->bindValue(':cost', $cost);
				$s->execute();
			}
			catch (PDOException $e) {
				self::db_error('Insert data error: ', $e);	
			}
		}

		function create_table_trafficcost(){
			try {
				$sql = 'DROP TABLE IF EXISTS trafficcost;
						CREATE TABLE IF NOT EXISTS trafficcost (
							id bigint(20) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
    						apply_date date DEFAULT NULL,
    						network varchar(100) DEFAULT NULL,
    						campaign varchar(100) DEFAULT NULL,
    						tizer varchar(100) DEFAULT NULL,
    						site varchar(100) DEFAULT NULL,
    						clicks int(11) NOT NULL DEFAULT 0,
    						sum_cost decimal(10,4) DEFAULT NULL,
    						UNIQUE KEY DateTizerSite (apply_date, tizer, site),
    						KEY date(apply_date)
						)ENGINE=InnoDB AUTO_INCREMENT=21174976 DEFAULT CHARSET=utf8'; // UNIQUE KEY through phpMyAdmin
				
				$s = $this->pdo->prepare($sql);
				$s->execute();
			}
			catch (PDOException $e) {
				self::db_error('Error of deleting user: ', $e);
			}	
		}

		function select_group_insert(){
			try {
				$sql = 'SELECT date(apply_time), ip, ad_id, camp_id, hsite2, sum(cost), count(*) 
							FROM vw 
							GROUP BY date(apply_time), ad_id, hsite2';
				$result = $this->pdo->query($sql);
			}
			catch (PDOException $e) {
				self::db_error('Select data error: ', $e);
			}

			while ($row = $result->fetch()){
				$this->insert_data_trafficcost($row['date(apply_time)'],
												$row['ip'],
												$row['camp_id'],
												$row['ad_id'],
												$row['hsite2'],
												$row['count(*)'],
												$row['sum(cost)']);
			}
		}

		function insert_data_trafficcost($apply_date, $network, $campaign, $tizer, $site, $clicks, $sum_cost){
			try {
				$sql = 'INSERT INTO trafficcost SET 
						apply_date = :apply_date,
						network = :network,
						campaign = :campaign,
						tizer = :tizer,
						site = :site,
						clicks = :clicks,
						sum_cost = :sum_cost
						ON DUPLICATE KEY UPDATE
						clicks = clicks + clicks,
						sum_cost = sum_cost + sum_cost'; //carefully
				
				$s = $this->pdo->prepare($sql);
				$s->bindValue(':apply_date', $apply_date);
				$s->bindValue(':network', $network);
				$s->bindValue(':campaign', $campaign);
				$s->bindValue(':tizer', $tizer);
				$s->bindValue(':site', $site);
				$s->bindValue(':clicks', $clicks);
				$s->bindValue(':sum_cost', $sum_cost);
				$s->execute();
			}
			catch (PDOException $e) {
				self::db_error('Insert data error: ', $e);	
			}
		}

		function destroy_connection(){
			$this->pdo = null;
		}

		static function db_error($string, $exception){
			$output = $string . $exception->getMessage();
			include 'output.html.php';
			exit();
		}

		static function get_date_from_filename($file){
			$match = array();
			preg_match('/^.*([0-9]{2})\.([0-9]+)\.([0-9]+).*$/', $file, $match);
			return $match[3]. '-' . $match[2] . '-' . $match[1];
		}
	

	}
?>