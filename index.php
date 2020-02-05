<?php

	require_once ('database.php');

	//create a database class object
	$db = new DB('localhost', 'root', '', 'vtapi_db');


	//-----------------handle and limit multiple user requests
	$timeWindow = '3600'; //time window in seconds (presently 1 hour)
	$maxRequests = '10'; //maximum number of requests per user
	$minutes = $timeWindow/60;
	
	// Create my users' requests array in session scope if it does not yet exist
	//this session variable will be an associative array: [userIp => [], userIp2 => [], ... ] 
	//with an assciative sub-array: [lastSessionRequest => x, requestNum => y] for each user
	session_start();
	if (!isset($_SESSION['users'])) {
		$_SESSION['users'] = array();
	}

	// shortcut for session variable 'users'
	$users = &$_SESSION['users'];

	if(!isset($users[$_SERVER['REMOTE_ADDR']])) { 
		//this is a new user, initialize their array
		$users[$_SERVER['REMOTE_ADDR']] = array();
		$users[$_SERVER['REMOTE_ADDR']]['lastSessionRequest'] = time();
		$users[$_SERVER['REMOTE_ADDR']]['requestNum'] = 1;
	}
	else if( ( $users[$_SERVER['REMOTE_ADDR']]['lastSessionRequest'] > (time() - $timeWindow) ) && 
		( $users[$_SERVER['REMOTE_ADDR']]['requestNum'] < $maxRequests) )
	{ 
		//if the user's IP already exists but the requests don't exceed the limit, update the current request number
		$users[$_SERVER['REMOTE_ADDR']]['requestNum']++;
	}
	else if( $users[$_SERVER['REMOTE_ADDR']]['lastSessionRequest'] > (time() - $timeWindow) ) {
		//more than $maxRequests made by the specific user in the specified window, kill the process
		echo "A specific user can only perform up to ".$maxRequests." requests within a ". $minutes ." minute time window.";
		die();
	}
	else{
		//the time winow for this user has elapsed, reset values
		$users[$_SERVER['REMOTE_ADDR']]['lastSessionRequest'] = time();
		$users[$_SERVER['REMOTE_ADDR']]['requestNum'] = 1;
	}
	//---------------------------------------------------------


	if($_SERVER['REQUEST_METHOD'] == 'GET'){

		//parse possible $_GET parameters
		$params = array();
		if(isset($_GET['minLat']) && !empty($_GET['minLat'])){
			$params['minLat'] = $_GET['minLat'];
		}
		else{
			$params['minLat'] = null;
		}
		if(isset($_GET['maxLat']) && !empty($_GET['maxLat'])){
			$params['maxLat'] = $_GET['maxLat'];
		}
		else{
			$params['maxLat'] = null;
		}
		if(isset($_GET['minLon']) && !empty($_GET['minLon'])){
			$params['minLon'] = $_GET['minLon'];
		}
		else{
			$params['minLon'] = null;
		}
		if(isset($_GET['maxLon']) && !empty($_GET['maxLon'])){
			$params['maxLon'] = $_GET['maxLon'];
		}
		else{
			$params['maxLon'] = null;
		}
		if(isset($_GET['mmsi']) && !empty($_GET['mmsi'])){
			//there might be more than 1 mmsi; if so, they will be separated by a comma and a space like:', '
			$params['mmsi'] = explode(', ', $_GET['mmsi']); 
		}
		else{
			$params['mmsi'] = null;
		}
		if(isset($_GET['timeInterval']) && !empty($_GET['timeInterval'])){  
			//the time interval value should be two int's separated by a comma like 123146, 73428478 (similarly to mmsi)
			$params['timeInterval'] = explode(', ', $_GET['timeInterval']);
		}
		else{
			$params['timeInterval'] = null;
		}

		//construct the initial standard part of the query, checking for non-null parameters to add
		$query = "SELECT * FROM vtapi_db.ship_positions";

		//pass the initial query and the parameters so that the query function will form the complete query and execute it; then display the results encoded in the accepted type (default is application/json)
		if($_SERVER["HTTP_ACCEPT"] == 'application/vnd.api+json') {
			header("Content-Type: application/vnd.api+json");
			echo json_encode(($db->query($query, $params)));
		}
		else if($_SERVER["HTTP_ACCEPT"] == 'application/xml') {
			header("Content-Type: application/xml");
			echo json_encode(($db->query($query, $params)));
		}
		else if($_SERVER["HTTP_ACCEPT"] == 'text/csv') {
			header("Content-Type: text/csv");
			$array = $db->query($query, $params);
			foreach($array as $item){
				echo implode(",", $item)."\n";
			}
		}
		else{ //application/json for default, if no Accept header is set, or if application/json is set as Accept
			header("Content-Type: application/json");
			echo json_encode(($db->query($query, $params)));
		}
		//log the request
		$db->logRequest($query);
		http_response_code(200); //successful method
	}
	else{
		http_response_code(405); //method not found
	}

?>