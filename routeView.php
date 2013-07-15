<?php
$app->get('/RouteView', function() use ($app, $db) {
	if(isset($_SESSION['airline_id'])){
		echo $_SESSION['airline_id'];
		$query = 'SELECT sales_route.id, sales_route.departure_time, ';
		$query .= 'sales_route.arrival_time, sales_route.departure_week_date, ';
		$query .= 'user_aircraft.registration, aircraft.aircraft_icao, ';
		$query .= 'physical_route.airport_id_a_id, physical_route.airport_id_b_id, ';
		$query .= 'user_seat_config_class.seat_number, flight_number.flight_number, sales_route_index.user_route_id_id ';
		$query .= 'FROM user_route, sales_route, sales_route_index, ';
		$query .= 'physical_route, user_aircraft, user_seat_config_class, ';
		$query .= 'flight_number, aircraft, aircraft_variant ';
		$query .= 'WHERE user_route.airline_id_id = ? ';
		$query .= 'AND sales_route_index.user_route_id_id = user_route.id ';
		$query .= 'AND sales_route_index.sales_route_id_id = sales_route.id ';
		$query .= 'AND sales_route_index.flight_number_id_id = flight_number.id ';
		$query .= 'AND user_route.user_aircraft_id_id = user_aircraft.id ';
		$query .= 'AND sales_route.user_seat_class_id_id = user_seat_config_class.id ';
		$query .= 'AND sales_route.physical_route_id_id = physical_route.id ';
		$query .= 'AND user_route.user_aircraft_id_id = user_aircraft.id ';
		$query .= 'AND user_aircraft.aircraft_variant_id_id = aircraft_variant.id ';
		$query .= 'AND aircraft_variant.aircraft_id_id = aircraft.id ';
		$routes = $db->prepare($query);
		$routes->execute(array($_SESSION['airline_id']));
		
		$query = 'SELECT sales_route_index.user_route_id_id FROM sales_route_index, user_route WHERE sales_route_index.user_route_id_id = user_route.id AND user_route.airline_id_id = ? GROUP BY sales_route_index.user_route_id_id';
		
		$user_routes = $db->prepare($query);
		$user_routes->execute(array($_SESSION['airline_id']));
	
	
		$query = 'SELECT * FROM airport';
		$airports = $db->prepare($query);
		$airports->execute();
	
		$app->render('views/route/view.tpl', array('routes' => $routes->fetchAll(PDO::FETCH_ASSOC),
													'user_routes' => $user_routes->fetchAll(PDO::FETCH_ASSOC),
													'airports' => $airports->fetchAll(PDO::FETCH_ASSOC),
													'page' => 'route'));
	}else{
		$app->redirect('../SelectAirline');
	}
});

$app->get('/RouteView/:id', function($id) use ($app, $db){
	$query = 'SELECT * FROM (SELECT * FROM sales_route_history_daily WHERE sales_route_id_id = ? ORDER BY ts_created DESC LIMIT 10) as last_10 ORDER BY last_10.ts_created ASC';
	$act = $db->prepare($query);
	$act->execute(array($id));
	
	$app->render('views/route/detail.tpl', array('histories' => $act->fetchAll(PDO::FETCH_ASSOC),
							'page' => 'route'));
});

$app->post('/Route/GetAirportList/:id/:num', function($id, $num) use ($app, $db){
	//$request = $app->request();
	//$area_id = $request->post('area_id');
	$query = 'SELECT * FROM airline, airport WHERE airline.airport_id_id = airport.id AND airline.id = ?';
	$airport = $db->prepare($query);
	$airport->execute(array($_SESSION['airline_id']));
	$base_airport = $airport->fetch(PDO::FETCH_ASSOC);
	
	$query = 'SELECT airport.id, airport.airport_name, airport.airport_icao FROM airport, country ';
	$query .= 'WHERE country.area_id_id = ? AND airport.country_id_id = country.id AND airport.id != ? ORDER BY airport.airport_icao';
	$airports = $db->prepare($query);
	$airports->execute(array($id, $base_airport['id']));
	$data = $airports->fetchAll(PDO::FETCH_ASSOC);
	
	$airport_str = 'airport_'.$num;
	
	$str = '<select id="'.$airport_str.'" name="'.$airport_str.'">';
	$str .= '<option value="0">Select a airport</option>';
	foreach($data as $d){
		$str .= '<option value="'.$d['id'].'">['.$d['airport_icao'].']'.$d['airport_name'].'</option>';
	}
	$str .= '</select>';
	
	echo $str;
});

$app->post('/Route/GetAirportInfo/:aid/:bid/:num', function($aid, $bid, $num) use ($app, $db){
	$query = 'SELECT * FROM airport WHERE id = ?';
	$airport = $db->prepare($query);
	$airport->execute(array($bid));
	$data = $airport->fetch(PDO::FETCH_ASSOC);
	
	$airport_name = $data['airport_name'];
	$airport_icao = $data['airport_icao'];
	
	$query = 'SELECT physical_route.id, physical_route.distance, physical_route.demand_y, physical_route.demand_jf, route_type.type_name FROM physical_route, route_type WHERE physical_route.airport_id_a_id = ? AND physical_route.airport_id_b_id = ? AND physical_route.route_type_id_id = route_type.id';
	$physical_route = $db->prepare($query);
	$physical_route->execute(array($aid, $bid));
	$data2 = $physical_route->fetch(PDO::FETCH_ASSOC);
	
	$distance = $data2['distance'];
	$route_type_name = $data2['type_name'];
	$demand_y = $data2['demand_y'];
	$demand_jf = $data2['demand_jf'];
	
	$str = '<div id="airport_info_'.$num.'">'.$airport_name.'('.$airport_icao.') - Distance : '.$distance. ' - Type : '.$route_type_name.' - Demand : Y['.$demand_y.' JF['.$demand_jf.']</div>';
	echo $str;
});

$app->post('/Route/AddDestination/:num', function($num) use ($app, $db){
	$query = 'SELECT * FROM area';
	$areas = $db->prepare($query);
	$areas->execute();
	$data = $areas->fetchAll(PDO::FETCH_ASSOC);
	
	$str = '<div id="route_'.$num.'" class="row-fluid">';
	$str .= '<h4>Destination Airport</h4>';
	$str .= '<select id="area_'.$num.'">';
	$str .= '<option value="0">Select a area</option>';
	foreach($data as $d){
		$str .= '<option value="'.$d['id'].'">'.$d['area_name'].'</option>';
	}
	$str .= '</select></div>';
	
	echo $str;
});

$app->post('/Route/GetAircraftInfo/:id/:max_range', function($id, $max_range) use ($app, $db){
	$query = 'SELECT aircraft_variant.id, aircraft.aircraft_name, aircraft_engine.engine_name, aircraft_variant.nominal_range, ';
	$query .= '(SELECT count(*) from user_aircraft where aircraft_variant.id = user_aircraft.aircraft_variant_id_id AND user_aircraft.operator_id = ?) AS quantity ';
	$query .= 'FROM aircraft, aircraft_variant, aircraft_engine ';
	$query .= 'WHERE aircraft.id = aircraft_variant.aircraft_id_id ';
	$query .= 'AND aircraft.aircraft_family_id_id = ? ';
	$query .= 'AND aircraft_engine.id = aircraft_variant.engine_id_id ';
	$aircrafts = $db->prepare($query);
	$aircrafts->execute(array($_SESSION['airline_id'], $id));
	$data = $aircrafts->fetchAll(PDO::FETCH_ASSOC);
	
	$str = '<table class="table table-striped table-bordered table-advance table-hover"><thead><tr>';
	$str .= '<th></th><th>Aircraft Name - Engine</th><th>Nominal Range</th><th>Fulfill range</th><th>In fleet</th></tr></thead>';
	$str .= '<tbody>';
	foreach($data as $d){
		$str .= '<tr>';
		if($d['quantity'] > 0){
			$str .= '<td><input type="radio" id="aircraft_id" name="aircraft_id" value='.$d['id'].' /></td>';
		}else{
			$str .= '<td><input type="radio" id="aircraft_id" name="aircraft_id" disabled value='.$d['id'].' /></td>';
		}
		
		$str .= '<td>'. $d['aircraft_name'] .' - '. $d['engine_name'] .'</td>';
		$str .= '<td>'. $d['nominal_range'] .'</td>';
		if($d['nominal_range'] >= $max_range){
			$str .='<td><i class="icon-check"></i></td>';
		}else{
			$str .='<td><i class="icon-check-empty"></i></td>';
		}
		$str .= '<td>'. $d['quantity'] .'</td></tr>';
	}
	$str .= '</tbody></table>';
	
	echo $str;
});

$app->post('/Route/GetUserAircraftInfo/:id', function($id) use ($app, $db){
	$query = 'SELECT * FROM user_aircraft WHERE operator_id = ? AND aircraft_variant_id_id = ?';
	$aircrafts = $db->prepare($query);
	$aircrafts->execute(array($_SESSION['airline_id'], $id));
	$data = $aircrafts->fetchAll(PDO::FETCH_ASSOC);
	
	$str = '<table class="table table-striped table-bordered table-advance table-hover"><thead><tr>';
	$str .= '<th></th><th>Aircraft Registration</th></tr></thead>';
	$str .= '<tbody>';
	foreach($data as $d){
		$str .= '<tr><td><input type="radio" id="user_aircraft_id" name="user_aircraft_id" value='.$d['id'].' /></td>';

		$str .= '<td>'. $d['registration'] .'</td>';
		$str .= '</tr>';
	}
	$str .= '</tbody></table>';
	
	echo $str;
});

$app->post('/Route/GetAircraftSpeed/:id', function($id) use ($app, $db){
	$query = 'SELECT speed FROM aircraft_variant WHERE id = ?';
	$speed = $db->prepare($query);
	$speed->execute(array($id));
	$data = $speed->fetch(PDO::FETCH_ASSOC);
	echo $data['speed'];
});

$app->post('/Route/GetAircraftTurnaroundTime/:id/:num', function($id, $num) use ($app, $db){
	$query = 'SELECT min_short_ta, min_long_ta FROM aircraft WHERE id = ?';
	$ta = $db->prepare($query);
	$ta->execute(array($id));
	$data = $ta->fetch(PDO::FETCH_ASSOC);
	
	$str = '<select id="turnaround_time_'.$num.'" name="turnaround_time_'.$num.'">';
	
	for($i = 5;$i < 800;$i+= 5){
		if($i == $data['min_short_ta']){
			$str .= '<option value="'. $i .'">'. $i . ' Min (Minimum Short TA)' .'</option>';
		}else if($i == $data['min_long_ta']){
			$str .= '<option value="'. $i .'">'. $i . ' Min (Minimum Long TA)' .'</option>';
		}else{
			$str .= '<option value="'. $i .'">'. $i . ' Min' .'</option>';
		}
	}
	$str .= '</select>';
	echo $str;
});

$app->post('/Route/GetAirportSlot/:id/:hour/:dep_arr/:weekdate', function($id, $hour, $dep_arr, $weekdate) use ($app, $db){
	$query = 'select slot.dep_slot_number - ( ';
	$query .= 'select COUNT(slot_allocation.id) AS slot from slot, slot_allocation ';
	$query .= 'where slot.airport_id_id = ? AND  slot.hour = ? ';
	$query .= 'AND slot.id = slot_allocation.slot_id_id ';
	$query .= 'AND slot_allocation.dep_arr = ? ';
	$query .= 'AND slot_allocation.day_of_week = ?';
	$query .= ' ) AS slot FROM slot where slot.airport_id_id = ? and hour = ?';
	
	$slot = $db->prepare($query);
	$slot->execute(array($id, $hour, $dep_arr, $weekdate, $id, $hour));
	$data = $slot->fetch(PDO::FETCH_ASSOC);
	echo $data['slot'];

});

$app->get('/Route/Create', function() use ($app, $db){
	if(isset($_SESSION['airline_id'])){
		$query = 'SELECT airport.* FROM airline, airport WHERE airline.airport_id_id = airport.id AND airline.id = ?';
		$airport = $db->prepare($query);
		$airport->execute(array($_SESSION['airline_id']));
	
		$query = 'SELECT id, area_name FROM area';
		$areas = $db->prepare($query);
		$areas->execute();
	
		$query = 'SELECT * FROM airport';
		$airports = $db->prepare($query);
		$airports->execute();
	
		$app->render('views/route/createIndex.tpl', array('page' => 'route',
													'base_airport' => $airport->fetch(PDO::FETCH_ASSOC),
													'areas' => $areas->fetchAll(PDO::FETCH_ASSOC),
													'airports' => $airport->fetchAll(PDO::FETCH_ASSOC)));
	}else{
		$app->redirect('../SelectAirline');
	}
});

$app->post('/Route/Create', function() use ($app, $db){
	$request = $app->request();
	$leg = $request->post('num');
	$base_airport_id = $request->post('base_airport_id');
	$leg += 1;

	$airports = array();
	$airports[0] = $base_airport_id;
	for($i = 1; $i <= $leg; $i++){
		$airports[$i] = $request->post('airport_'.($i-1));
	}
	$airports[$leg+1] = $base_airport_id;
	
	$airport_seq = $airports;
	
	//Query for get the physical routes
	$query = 'SELECT * FROM physical_route WHERE ';
	for($i = 0; $i <= $leg; $i++){
		$query .= '(airport_id_a_id = ' . $airports[$i] . ' AND airport_id_b_id = ' . $airports[$i + 1] . ')';
		if($i < $leg){
			$query .= ' OR ';
		}
	}
	
	$physical_routes = $db->prepare($query);
	$physical_routes->execute();
	
	//Query for max distance
	$query = 'SELECT MAX(distance) AS max_distance FROM physical_route WHERE ';
	for($i = 0; $i <= $leg; $i++){
		$query .= '(airport_id_a_id = ' . $airports[$i] . ' AND airport_id_b_id = ' . $airports[$i + 1] . ')';
		if($i < $leg){
			$query .= ' OR ';
		}
	}
	
	$distance = $db->prepare($query);
	$distance->execute();
	$max_distance = $distance->fetch(PDO::FETCH_ASSOC);
	//print_r($distance);

	//Query for airports
	$query = 'SELECT * FROM airport WHERE ';
	for($i = 0; $i <= $leg; $i++){
		$query .= 'id = ' . $airports[$i];
		if($i < $leg){
			$query .= ' OR ';
		}
	}
	
	$airports = $db->prepare($query);
	$airports->execute();
	
	
	//Query for aircraft families
	$query = 'SELECT manufacturer.manufacturer_name, aircraft_family.id, aircraft_family.family_name FROM aircraft_family, manufacturer ';
	$query .= 'WHERE manufacturer.id = aircraft_family.manufacturer_id_id ';
	$query .= "AND manufacturer.manufacturer_type = 'Aircraft'";
	$families = $db->prepare($query);
	$families->execute();
	//echo $query . '[' . $max_distance['max_distance'] . ']';

		
	//Query for user aircraft
	$query = 'SELECT * FROM user_aircraft WHERE operator_id = ?';
	$user_aircrafts = $db->prepare($query);
	$user_aircrafts->execute(array($_SESSION['airline_id']));


	$app->render('views/route/createInfo.tpl', array('page' => 'route',
													'leg' => $leg,
													'airports'=> $airports->fetchAll(PDO::FETCH_ASSOC),
													'families' => $families->fetchAll(PDO::FETCH_ASSOC),
													'base_airport_id' => $base_airport_id,
													'airport_seq' => $airport_seq,
													'max_distance' => $max_distance['max_distance'],
													'physical_routes' => $physical_routes->fetchAll(PDO::FETCH_ASSOC),
													'user_aircrafts' => $user_aircrafts->fetchAll(PDO::FETCH_ASSOC)));

});

$app->post('/Route/CreateRoute', function() use ($app, $db){
	$request = $app->request();
	$leg = $request->post('leg');
	$user_aircraft_id = $request->post('user_aircraft_id');
	$flight_number = $request->post('flight_number');
	$dep_airport_id = $request->post('dep_airport_id');

	//Local Time
	$dep_hour = $request->post('hour');
	$dep_min = $request->post('minute');
	
	
	$dep_week_date = array();
	$turnaround_time = array();
	$physical_route_id = array();
	$airport = array();
	$distance = array();
	$flight_time = array();
	$dep_time = array();
	$arr_time = array();
	$dep_weekdate = array();
	$arr_weekdate = array();
	$price_y = array();
	$price_yp = array();
	$price_j = array();
	$price_f = array();
	$weekdate_adjust = 0;
	$aircraft_speed = 0.0;
	$aircraft_variant_id = 0;
	$user_seat_class_id = 0;
	$temp_ops_route_id = 0;
	$error = 0;
	$nm = 661.4714137982;
	$km = 1.85200;

	for($i = 0;$i < 7; $i++){
		$dep_week_date[$i] = $request->post('weekdate_'. $i);
	}
	
	for($i = 0;$i < $leg+1; $i++){
		$turnaround_time[$i] = $request->post('turnaround_time_' . $i);
		$physical_route_id[$i] = $request->post('physical_route_id_' . $i);	
		$distance[$i] = $request->post('distance_' . $i);
		$airport[$i] = $request->post('airport_id_' . $i);
		$price_y[$i] = $request->post('price_y_' . $i);
		$price_yp[$i] = $request->post('price_yp_' . $i);
		$price_j[$i] = $request->post('price_j_' . $i);
		$price_f[$i] = $request->post('price_f_' . $i);
	}

	//Get aircraft speed
	$query = 'SELECT aircraft_variant.id, aircraft_variant.speed FROM aircraft_variant, user_aircraft ';
	$query .= 'WHERE user_aircraft.aircraft_variant_id_id = aircraft_variant.id AND user_aircraft.id = ?';
	$aircraft = $db->prepare($query);
	$aircraft->execute(array($user_aircraft_id));
	$data = $aircraft->fetch(PDO::FETCH_ASSOC);
	$aircraft_speed = $data['speed'];
	$aircraft_variant_id = $data['id'];

	//Get departure airport timezone
	$query = 'SELECT timezone FROM airport WHERE id = ?';
	$timezone = $db->prepare($query);
	$timezone->execute(array($dep_airport_id));
	$tz = $timezone->fetch(PDO::FETCH_ASSOC);

	//Get user seat class id
	$query = 'SELECT user_seat_config_class.id, user_seat_config_class.seat_class_type_id_id FROM user_aircraft, user_seat_config_class ';
	$query .= 'WHERE user_aircraft.id = ? AND user_aircraft.user_seat_id = user_seat_config_class.user_seat_id_id ';
	$id = $db->prepare($query);
	$id->execute(array($user_aircraft_id));
	$data = $id->fetchAll(PDO::FETCH_ASSOC);
	$user_seat_class_id = $data;
	
	
	//Timezone Adjust
	if(($dep_hour - $tz['timezone']) >= 24){
		$dep_hour = $dep_hour % 24;
		$weekdate_adjust += 1;
	}else if(($dep_hour - $tz['timezone']) < 0){
		$dep_hour =  ($dep_hour - $tz['timezone']) * -1;
		$weekdate_adjust -= 1; 
	}else{
		$dep_hour -= $tz['timezone'];	
	}

	echo "Speed : " . $aircraft_speed . " distance : " . $distance[0] . "</br>";

	//Get each flight time
	for($i = 0; $i < $leg+1; $i++){
		$temp = floor(($distance[$i] / ($aircraft_speed * $nm)) * 60);
		if(($temp % 5) != 0){
			$temp -= ($temp % 5);
			$temp += 5;
		}
		$flight_time[$i] = $temp;
		echo "Distance :" . $distance[$i] . " Speed : " . $aircraft_speed ."</br>";
		echo "Leg[" . $i . "] Flight Time - " . $flight_time[$i] . "</br>";
	}

	//Get first dep & arr time
	$temp_min = 0;
	$dep_time[0] = ($dep_hour * 100) + $dep_min;
	$dep_weekdate[0] = 0;
	$arr_weekdate[0] = 0;
	$arr_time[0] = (round($dep_time[0] / 100) * 100) + round(($flight_time[0] / 60)) * 100;
	$temp_min = ($flight_time[0] % 60);
	$temp_min += ($dep_time[0] - (round($dep_time[0] / 100) * 100));
	if($temp_min >= 60){
		$arr_time[0] += (round($temp_min % 60) * 100);
		$arr_time[0] += ($temp_min % 60);	
	}else{
		$arr_time[0] += $temp_min;
	}
	
	if($arr_time[0] >= 2400){
		$arr_weekdate[0] = round($arr_time[0] / 2400);
		echo "Round : " . round($arr_time[0] / 2400) . "</br>";
		$arr_time[0] = ($arr_time[0] % 2400);
	}

	//Get rest dep & arr time
	
	for($i = 1; $i < $leg+1; $i++){
		$temp_min = 0;
		$dep_weekdate[$i] = $arr_weekdate[$i-1];
		$dep_time[$i] = (round($arr_time[$i - 1] / 100) * 100) + (round(($turnaround_time[$i - 1] / 60)) * 100);

		$temp_min = ($turnaround_time[$i - 1] % 60);
		$temp_min += ($arr_time[$i - 1] - (round($arr_time[$i - 1] / 100) * 100));
		if($temp_min >= 60){
			$dep_time[$i] += (round($temp_min / 60) * 100);
			$dep_time[$i] += ($temp_min % 60);	
		}else{
			$dep_time[$i] += $temp_min;
		}
		
		if($dep_time[$i] > 2400){
			$dep_weekdate[$i] += round($dep_time[$i] / 2400);
			$dep_time[$i] = ($dep_time[$i] % 2400);
		}

		$temp_min = 0;
		$arr_weekdate[$i] = $dep_weekdate[$i];
		$arr_time[$i] = (round($dep_time[$i] / 100) * 100) + (intval(($flight_time[$i] / 60)) * 100);
		echo "DEP : " . $dep_time[$i] . " - ARR : " . $arr_time[$i] . "</br>";

		$temp_min = ($flight_time[$i] % 60);
		$temp_min += ($dep_time[$i] - (round($dep_time[$i] / 100) * 100));
		echo "TEMP MIN : " . $temp_min . " - HOUR : " . (round($temp_min / 60) * 100) ." - MIN : " .($temp_min % 60)."</br>";
		if($temp_min >= 60){
			$arr_time[$i] += (round($temp_min / 60) * 100);
			$arr_time[$i] += ($temp_min % 60);	
		}else{
			$arr_time[$i] += $temp_min;
		}

		if($arr_time[$i] > 2400){
			$arr_weekdate[$i] += round($arr_time[$i] / 2400);
			$arr_time[$i] = ($arr_time[$i] % 2400);
		}
	}
	
	try{
	
	//Create the flight number 
	$query = 'INSERT INTO flight_number VALUES (NULL, ?, ?, NOW(), NULL)';
	$data = $db->prepare($query);
	$data->execute(array($_SESSION['airline_id'], $flight_number));
	$flight_number_id = $db->lastInsertId();
	if($flight_number_id < 1){
		//$error += 1;
	}

	$query = 'INSERT INTO user_route VALUES(NULL, ?, ?, NOW(), NULL)';
	$data = $db->prepare($query);
	$data->execute(array($_SESSION['airline_id'], $user_aircraft_id));
	$user_route_id = $db->lastInsertId();
	if($user_route_id < 1){
		//$error += 1;
	}
	
	for($i = 0;$i < $leg+1; $i++){
		for($j = 0;$j < 7; $j++){
			if($dep_week_date[$j] != NULL){
				if($physical_route_id[$i] > 0){
				
					$query = 'INSERT INTO ops_route VALUES(NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NULL)';
					$data = $db->prepare($query);
					$data->execute(array($user_route_id, $physical_route_id[$i], $aircraft_variant_id, 1, 50, 50, 8000, 300, $dep_time[$i], $arr_time[$i], ($j + $dep_weekdate[$i]), ($j + $arr_weekdate[$i])));
					$ops_route_id = $db->lastInsertId();
					if($ops_route_id < 1){
						//$error += 1;				
					}
				

					$query = 'INSERT INTO ops_route_index VALUES(NULL, ?, ?, ?, ?)';
					$data = $db->prepare($query);
					$data->execute(array($user_route_id, $ops_route_id, $flight_number_id, ($i + 1)));
					if($db->lastInsertId() < 1){
						//$error += 1;				
					}
				
					//echo "OpsRoute : [" . $dep_time[$i] . "][" . $arr_time[$i] . "][" . ($j + $dep_weekdate[$i]) . "][" . ($j + $arr_weekdate[$i]) . "]</br>";

					if($turnaround_time[$i] > 0){
					if($temp_ops_route_id > 0){
						$query = 'INSERT INTO aircraft_turnaround VALUES(NULL, ?, ?, ?, ?, ?, NOW(), NULL)';
						$data = $db->prepare($query);
						$data->execute(array($user_aircraft_id, $temp_ops_route_id, $ops_route_id, $arr_time[$i], $turnaround_time[$i]));
						//echo "UA : ". $user_aircraft_id ." OPS_ID - [" . $temp_ops_route_id . "] [" . $ops_route_id . "] - TIME [".$arr_time[$i]."] TA [".$turnaround_time[$i]."]</br>";
						//echo "MYSQL[" . $db->errorInfo()[2] . "]</br>";						
					}
					}
					$temp_ops_route_id = $ops_route_id;

					foreach($user_seat_class_id as $usc){
						$price = 0;
						switch($usc['seat_class_type_id_id']){
							//1 => Y
							//2 => Y+
							//3 => J
							//4 => F
							case 1:
								$price = $price_y[$i];
								break;
							case 2:
								$price = $price_yp[$i];
								break;
							case 3:
								$price = $price_j[$i];
								break;
							case 4:
								$price = $price_f[$i];
								break;
							default:
								$price = 1;
								break;
						}
					
						$query = 'INSERT INTO sales_route VALUES(NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ? ,? ,? ,? ,? ,?, NOW(), NULL)';
						$data = $db->prepare($query);
						$data->execute(array($_SESSION['airline_id'], $user_route_id, $physical_route_id[$i], $usc['id'], 0, 40, 0, 0, 0, 0, 0, $price, 0, $dep_time[$i], $arr_time[$i], $flight_time[$i], ($j + $dep_weekdate[$i]), ($j + $arr_weekdate[$i])));
						$sales_route_id = $db->lastInsertId();
						if($sales_route_id < 1){
							//$error += 1;
						}

						echo "SalesRoute : [" .$price. "]</br>";

						$query = 'INSERT INTO sales_route_index VALUES(NULL, ?, ?, ?, ?)';
						$data = $db->prepare($query);
						$data->execute(array($user_route_id, $sales_route_id, $flight_number_id, ($i + 1)));
						if($db->lastInsertId()){
							//$error += 1;
						}
					
					}
				}
			}
		}
	}

	if(!$error){
		
		$query = 'SELECT * FROM ops_route_index WHERE user_route_id_id = ? ORDER BY leg ASC';
		$data = $db->prepare($query);
		$data->execute(array($user_route_id));
		$index = $data->fetchAll(PDO::FETCH_ASSOC);
		
		for($i = 0;$i < $leg+1; $i++){
			$query = 'SELECT COUNT(airline_popularity.id) AS count FROM airline_popularity, airport ';
			$query .= 'WHERE airline_popularity.country_id_id = airport.country_id_id AND airport.id = ? ';
			$query .= 'AND airline_popularity.airline_id_id = ?';
			$data = $db->prepare($query);
			$data->execute(array($airport[$i], $_SESSION['airline_id']));
			$count = $data->fetch(PDO::FETCH_ASSOC);
			echo "POP => [".$count['count']."] </br>";
			echo "AIRPORT [".$airport[$i]."] AIRLINE [".$_SESSION['airline_id']."]</br>";

			if($count['count'] == 0){

				$query = 'SELECT country_id_id FROM airport WHERE id = ?';
				$datas = $db->prepare($query);
				$datas->execute(array($airport[$i]));
				$airport_data = $datas->fetch(PDO::FETCH_ASSOC);
				echo $airport_data['country_id_id'] . "</br>";
				$country_id = $airport_data['country_id_id'];
				
				$query = 'INSERT INTO airline_popularity VALUES (NULL, ?, ?, 1, NOW(), NULL)';
				$data = $db->prepare($query);
				$data->execute(array($_SESSION['airline_id'], $country_id));
				echo "POP INSERT => [".$_SESSION['airline_id']."] [".$country_id."]</br>";
			}
		}

		for($i = 0;$i < $leg+1;$i++){
			$query = 'INSERT INTO route_process_query VALUES(NULL, ?, NOW(), NULL)';
			$data = $db->prepare($query);
			$data->execute(array($physical_route_id[$i]));
			echo "PR [ " . $physical_route_id[$i] . "]</br>";
		}
		
		
	}
	}catch(Exception $e){
    		throw $e;
	}
	
	echo "ERROR [".$error."]</br>";
	
	print_r($dep_week_date);
	echo "</br>";
	print_r($turnaround_time);
	echo "</br>";
	print_r($physical_route_id);
	echo "</br>";
	echo $tz['timezone'];
	echo "</br>";
	echo $leg;
	echo "</br>";
	echo $user_aircraft_id;
	echo "</br>";
	echo $flight_number;
	echo "</br>";
	echo $dep_hour . ":" . $dep_min;
	echo "</br>";
	echo $weekdate_adjust;

});

?>

