<?php
//Check credentials
function verizonCheck($db){
  $provider = 'verizon';
  $db->query("SELECT clientId, clientSecret, accessToken, accessExpire FROM tokens Where username = :username");
  $db->bind(':username', $provider);
  $row = $db->single();
  $username = $row->clientId;
  $pwd = $row->clientSecret;
  $accessToken = $row->accessToken;
  $expire = $row->accessExpire;
  $date = date("Y-m-d H:i:s", time());

  if($expire < $date){
    $url = VERIZONROOT . "ts/v1/oauth2/token?grant_type=client_credentials";
    $headers = array(
      "Content-Length: 0",
      "Authorization: Basic {Verizon API Token}"
    );
    $response = verizonCurl($url, $headers, NULL);
    $accessTime = date("Y-m-d H:i:s", time()+3600);
    $accessToken = $response['access_token'];
    $db->query('Update tokens Set accessToken = :accessToken, accessExpire = :accessExpire Where username = :username');
    $db->bind(':accessToken', $accessToken);
    $db->bind(':accessExpire', $accessTime);
    $db->bind(':username', $provider);
    $db->execute();
  }

  $url = VERIZONROOT . "m2m/v1/session/login";
  $headers = array(
    "Authorization: Bearer " . $accessToken,
    "Content-Type: application/json"
  );
  $postfields = "{\"username\": \"{Verizon Username}\", ";
  $postfields .= "\"password\": \"{Verizon Password}\"}";
  $sessionToken = verizonCurl($url, $headers, $postfields);

  $tokens = array(
    $accessToken,
    $sessionToken['sessionToken'],
    $postfields
  );
  return $tokens;
}

function verizonCurl($url, $headers, $postfields){
  $curl = curl_init();
  curl_setopt_array($curl, array(
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "POST",
    CURLOPT_POSTFIELDS => $postfields,
    CURLOPT_HTTPHEADER => $headers
  ));
  $response = curl_exec($curl);
  curl_close($curl);
  $response = json_decode($response, true);

  return $response;
}

function deviceList($db){
  $db->query("SELECT mdn FROM verizonDevices");
  $row = $db->resultSetArray();

  return $row;
}

function getDeviceUsage($tokens, $deviceList, $date){
  $url = VERIZONROOT . "m2m/v1/callbacks/0342414423-00001";
  $headers = array(
    "Authorization: Bearer " . $tokens[0],
    "VZ-M2M-Token: " . $tokens[1],
    "Content-Type: application/json"
  );
  $postfields = '{"name": "DeviceUsage","url": "{Your callback URL}"}';
  verizonCurl($url, $headers, $postfields);

  $url = VERIZONROOT . "m2m/v1/devices/usage/actions/list/aggregate";

  $postfields = "{\"deviceIds\": [";
  foreach($deviceList as $key=>$device){
    if($device === end($deviceList)){
      $postfields .= "{\"id\": \"" . $device . "\", \"kind\": \"iccId\"}";
    } else {
      $postfields .= "{\"id\": \"" . $device . "\", \"kind\": \"iccId\"}, ";
    }
  }
  $postfields .= "], \"accountName\": \"0342414423-00001\", \"endTime\": \"" . date("Y-m-d") . "T00:00:00-05:00\", \"startTime\": \"" . $date . "T00:00:00-05:00\"}";

  $doCurl = verizonCurl($url, $headers, $postfields);
}
