<?php
  //Get required files
  require_once 'bootstrap.php';

  //Connect to database
  $db = new Database;
  //Get access token for API
  $tokens = verizonCheck($db);

  $url = VERIZONROOT . "m2m/v1/devices/actions/list";
  $headers = array(
    "Authorization: Bearer " . $tokens[0],
    "VZ-M2M-Token: " . $tokens[1],
    "Content-Type: application/json"
  );
  $deviceList = verizonCurl($url, $headers, $tokens[2]);

  $list = [];
  $i = 0;
  foreach($deviceList['devices'] as $device){
    foreach($device['deviceIds'] as $id){
      if($id['kind'] == "iccId"){
        array_push($list, $id['id']);
      }
      $deviceId[$i][$id['kind']] = $id['id'];
    }
    $i++;
  }
  $query = "INSERT INTO verizonDevices (imsi, imei, mdn, iccId, msisdn, min) VALUES";
  $last = end(array_keys($deviceId));
  foreach($deviceId as $key => $insert){
    if($insert['imsi'] == ""){
      $insert['imsi'] = "";
    }
    if($insert['imei'] == ""){
      $insert['imei'] = "";
    }
    if($insert['mdn'] == ""){
      $insert['mdn'] = "";
    }
    if($insert['msisdn'] == ""){
      $insert['msisdn'] = "";
    }
    if($insert['min'] == ""){
      $insert['min'] = "";
    }
    if($key == $last){
      $query .= "('" . $insert['imsi'] . "','" . $insert['imei'] . "','" . $insert['mdn'] . "','" . $insert['iccId'] . "','" . $insert['msisdn'] . "','" . $insert['min'] . "')";
    } else {
      $query .= "('" . $insert['imsi'] . "','" . $insert['imei'] . "','" . $insert['mdn'] . "','" . $insert['iccId'] . "','" . $insert['msisdn'] . "','" . $insert['min'] . "'),";
    }
  }
  $query .= " ON DUPLICATE KEY UPDATE imsi=values(imsi),imei=values(imei),mdn=values(mdn),msisdn=values(msisdn),min=values(min)";

  $db->query($query);
  //Execute
  $db->execute();

  $date = date('Y-m-d',strtotime("-1 days"));
  getDeviceUsage($tokens, $list, $date);
?>
