<?php

	/*
	 * Get Latitude and Longitude Json String
	 *
	 * Takes an array of zip codes and returns a json formated array of coordinates
	 *
	 * @param  array  $zipArray  array of zip codes
	 * @return string $coordArray array of geocoordinates in json format
	 */
	function getLatLongJson($zipArray) {
		$coordArray = array();
		foreach ($zipArray as $key => $zip) {
			$coords = getLatLong($zip);
			$coordArray[] = array('lat' => $coords[0], 'lng' => $coords[1]);
		}
		return json_encode($coordArray);
	}

	/*
	 * Reduce zip codes for coverage redundancy
	 *
	 * Takes an array of zip codes and an optional coverage level modifier. 
	 * The function reduce the zip codes to include only the codes that are
	 * (defaulted) 37.5 miles apart. coverage level modifier will divide 75 miles
	 * as the distance filter. Example: the default level of 2 will set the
	 * distance at 37.5 (75/2)
	 *
	 * @param  array  $zipArray  array of zip codes
	 * @return string $coordArray array of geocoordinates in json format
	 */
	function reduceZipRedundancy($zipArray, $coverageLevel = 2) {
		$zipcount = count($zipArray);
		$count    = 0;

		$filteredzips = array();
		$coordList    = array();

		while ($count < $zipcount) {
			$newcoord = getLatLong($zipArray[$count]);
			if($newcoord != array(0,0)) {
				$secondaryCount = count($coordList);
				if($secondaryCount > 0) {
					$newCount = 0;
					$continue = true;
					while ($continue && ($newCount < $secondaryCount)) {
						$distance = distance(
							$coordList[$newCount][0], 
							$coordList[$newCount][1], 
							$newcoord[0], 
							$newcoord[1]
						);

						if ($distance < 75/$coverageLevel) {
							$continue = false;
						}
						$newCount++;
					}

					if ($continue == true) {
						$filteredzips[] = $zipArray[$count];
						$coordList[] = $newcoord;
					}
				} else {
					$filteredzips[]  = $zipArray[$count];
					$coordList[] = $newcoord;
					$coord = $newcoord;
				}
			}
			$count++;
		}

		return $filteredzips;
	}

	/*
	 * Distance
	 *
	 * Takes two geocoordinates and returns distance between in miles
	 *
	 * @param  int $lat1  latitude for first coordinate set
	 * @param  int $lon1  longitude for first coordinate set
	 * @param  int $lat2  latitude for second coordinate set
	 * @param  int $lon2  longitude for second coordinate set
	 * @return int $miles Distance between coordinates in miles
	 */
	function distance($lat1, $lon1, $lat2, $lon2) {
	  $theta = $lon1 - $lon2;
	  $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
	  $dist = acos($dist);
	  $dist = rad2deg($dist);
	  $miles = $dist * 60 * 1.1515;
	  
	  return $miles;
	}

	/*
	 * Simple Database Connection
	 *
	 * Bare minimum database connection. Takes a query and returns results
	 *
	 * @param  string $query  query to run
	 * @return array  $result query result
	 */
	function simpleConnection($query) {
		$mysqli = new mysqli("localhost", "root", "June2000", "track5test");

		/* check connection */
		if (mysqli_connect_errno()) {
			printf("Connect failed: %s\n", mysqli_connect_error());
			exit();
		}

		if ($stmt = $mysqli->prepare($query)) {
			/* execute statement */
			$stmt->execute();
			$result = $stmt->get_result();
			/* close statement */
			$stmt->close();
		}

		/* close connection */
		$mysqli->close();

		return $result;
	}

	/*
	 * Get Latitude and Longitude
	 *
	 * Retrieves Geocoordinates from the database provided zip code
	 *
	 * @param  string $zip zipcode
	 * @return array  $return coordinates
	 */
	function getLatLong($zip) {

		$query   = "SELECT latitude, longitude FROM zip_codes WHERE zip = " . $zip . ";";
		$result  = simpleConnection($query);
		$return  = array(0,0);

		while ($row = $result->fetch_array(MYSQLI_NUM)) {
			$return[0] = (float) $row[0];
			$return[1] = (float) $row[1];
		}

		return $return;

	}
?>