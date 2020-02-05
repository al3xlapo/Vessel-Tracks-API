<?php

	class DB{

		private $dbo;

		//connect to database and init the database object
		public function __construct($host, $user, $pass, $db){

			$dbo = new PDO('mysql:host'.$host.';dbname='.$db.';charset=utf8', $user, $pass) or die('Unable to connect to databse.');
			$dbo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$this->dbo = $dbo;
		}


		//perform a query on the database
		public function query($query, $params) {

			//construct the full query, checking for non-null parameters to add, so
			//parse the $params array
			$i=0; //auxiliary index, used to add AND operators starting from the second non-null parameter
			foreach($params as $key => $value){
				if($value !== null){ //I only care for non-null values
					if($i > 0) //add a comma only if the parameter to be added isn't the first one
						$query .= " AND ";
					else
						$query .= " WHERE "; //if it's the first non-null parameter add the WHERE operator
					if($key == 'mmsi'){ //it's an array of mmsi id's, so I'll parse them and add them to the query, using the 'IN' operator
						//the $paramArray variable will be of the form (134346, 131236, 43567) or (13453)
						$paramArray = "(";
						$j=0;
						foreach($value as $mmsiValue){
							if($j > 0)
								$paramArray .= ", ";
							$paramArray .= $mmsiValue;

							$j++;
						}
						$paramArray .= ")";
						$query .= 'mmsi'." IN ".$paramArray;
					}
					else if($key == 'timeInterval'){ //the value is an array of two int's between which two values the timestamps should be
						$query .= 'timestamp >= '.$value[0].' AND timestamp <= '.$value[1];
					}
					else{ //parsing single value parameters now
						if($key == 'minLat') //minimum latitude parameter
							$query .= 'lat'." >= ".$value;	
						else if($key == 'minLon'){ //minimum longitude parameter
							$query .= 'lon'." >= ".$value;
						}
						else if($key == 'maxLat'){ //maximum latitude parameter
							$query .= 'lat'." <= ".$value;
						}
						else if($key == 'maxLon'){ //maximum longitude parameter
							$query .= 'lon'." <= ".$value;
						}
						else{ //time interval
							$query .= $key." = ".$value;
						}
					}

					$i++;
				}
			}

			//prepare and execute the query
			$statement = $this->dbo->prepare($query);
			if( !($statement->execute()) ) { //handle query execution by echoing an error message
				echo "Invalid query: ".$query;
				return;
			}

			if( explode(' ', $query)[0] == "SELECT" ){ //it's a select query, so it expects the data fetched as output
				$data = $statement->fetchAll(PDO::FETCH_ASSOC); //fetch results while omiting numerical indexes
				return $data;
			}
		}

		//create an entry to the 'log' database table
		public function logRequest($request){
			$statement = $this->dbo->prepare("INSERT INTO vtapi_db.log (incoming_requests) VALUES ('".$request."')");
			$statement->execute();
		}		
	}

?>