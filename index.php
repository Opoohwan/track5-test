<?php
	$zips        = getZips(2077);
	$reducedZips = reduceZipRedundancy($zips);
	$coordToMap  = getLatLongJson($reducedZips);
?>
<!DOCTYPE html>
<html>
  <head>
    <style>
       #map {
       	height: 400px;
        width: 100%;
       }
    </style>
  </head>
  <body>
    <h3>My Google Maps Demo</h3>
    <div id="map"></div>
    <script>
      function initMap() {
      	var listCoord = <?php echo $coordToMap; ?>;
        var map = new google.maps.Map(document.getElementById('map'), {
          zoom: 7,
          center: listCoord[0]
        });

        for (var key in listCoord) {
        	var marker = new google.maps.Marker({
          		position: listCoord[key],
          		map: map
        	});
        	var cityCircle = new google.maps.Circle({
            	strokeColor: '#FF0000',
            	strokeOpacity: 0.8,
            	strokeWeight: 2,
            	fillColor: '#FF0000',
            	fillOpacity: 0.35,
            	map: map,
            	center: listCoord[key],
            	radius: 60350
          	});
    	}
      }
    </script>
    <script async defer
    src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCW0zbxk9-aqW8mbfrY6miND8JMq6e4Zkc&callback=initMap">
    </script>
  </body>
</html>

<?php

function distance($lat1, $lon1, $lat2, $lon2) {

  $theta = $lon1 - $lon2;
  $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
  $dist = acos($dist);
  $dist = rad2deg($dist);
  $miles = $dist * 60 * 1.1515;
  
  return $miles;
}

function pr($var) {
	echo '<pre>';
	print_r($var);
	echo '</pre>';
	exit;
}
function getLatLongJson($zipArray) {
	$coordArray = array();
	foreach ($zipArray as $key => $zip) {
		$coords = getLatLong($zip);
		$coordArray[] = array('lat' => $coords[0], 'lng' => $coords[1]);
	}
	return json_encode($coordArray);
}

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

function getZips($campaign) {

	$query   = "SELECT zip FROM job_campaign_locations WHERE campaign = " . $campaign . " ORDER BY zip ASC;";
	$result = simpleConnection($query);
	$return  = array();

	while ($row = $result->fetch_array(MYSQLI_NUM)) {
		foreach ($row as $r) {
			$return[] = $r;
		}
	}

	return $return;

}

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