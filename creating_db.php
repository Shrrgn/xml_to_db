<?php
	
	class DBFromXML {

		function __construct(){
			try {
				$pdo = new PDO('mysql:host=localhost;dbname=users_db', 'artem', 'artem');
				$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				$pdo->exec('SET NAMES "utf8"') ;
			}
			catch (PDOException $e) {
				self::db_error("Couldn't make connection to database: ", $e);
			}
			$this->pdo = $pdo;
		}

		static function db_error($string, $exception){
			$output = $string . $exception->getMessage();
			include 'output.html.php';
			exit();
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
				self::db_error('Error of deleting user: ', $e);
			}
		}

		function parsing_xml_insert(){
			$xmls = array('vw/VW_stat_05.09.2017.xml', 'vw/VW_stat_06.09.2017.xml', 'vw/VW_stat_07.09.2017.xml',
							'vw/VW_stat_08.09.2017.xml', 'vw/VW_stat_09.09.2017.xml', 'vw/VW_stat_10.09.2017.xml', 'vw/VW_stat_11.09.2017.xml');

			
			foreach ($xmls as $xml_file){ 

				if (file_exists($xml_file)) {
					$vw_stats = simplexml_load_file($xmls[0]);
					
					foreach ($vw_stats->stat as $data) {
						
						$this->insert_data_vw(self::get_data_from_filename($xmls[0]) . ' ' . $data->time,
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

		function destroy_connection(){
			$this->pdo = null;
		}

		static function get_data_from_filename($file){
			$match = array();
			preg_match('/^.*([0-9]{2})\.([0-9]+)\.([0-9]+).*$/', $file, $match);
			return $match[3]. '-' . $match[2] . '-' . $match[1];
		}
	

	}
?>