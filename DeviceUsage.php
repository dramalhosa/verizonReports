<?php
  //Get required files
  require_once 'bootstrap.php';
  //Connect to database
  $db = new Database;
  //Parse verizon data
  $entityBody = file_get_contents('php://input');
  $data = json_decode($entityBody);

  $db->query("SELECT imsi, imei, mdn, iccId, msisdn, min FROM verizonDevices");
  $row = $db->resultSetArray();
  unlink("Daily Verizon_" . date('Y-m-d',strtotime("-2 days")) . ".csv");
  $file = "Daily Verizon_" . date('Y-m-d',strtotime("-1 days")) . ".csv";
  if(!file_exists($file)){
    $fp = fopen($file, 'a');
    $headers = ['id', 'imsi', 'imei', 'mdn', 'iccId', 'msisdn', 'min', 'Data Usage', 'date'];
    fputcsv($fp, $headers);
  } else {
    $fp = fopen($file, 'a');
  }

  foreach($data as $deviceResponse){
    foreach($deviceResponse as $usageResponse){
      foreach($usageResponse as $usage){

        $iccId = $usage->deviceIds[0]->id;
        $dataUsage = $usage->dataUsage;
        $key = array_search($iccId, array_column($row, 'iccId'));

        if($key === false) {
          echo "not found";
        } else {
          $date = date('Y-m-d',strtotime("-1 days"));
          $dataImport = [uniqid($mdn), $row[$key]['imsi'], $row[$key]['imei'], $row[$key]['mdn'], $row[$key]['iccId'], $row[$key]['msisdn'], $row[$key]['min'], $dataUsage, $date];
          fputcsv($fp, $dataImport);
        }
      }
    }
  }

  fclose($fp);
  $fp = fopen($file, 'r');

  // set up basic connection
  $conn_id = ftp_connect({"{Your FTP Site}"});
  // login with username and password
  $login_result = ftp_login($conn_id, "{FTP Username}", "{FTP Password}");

  // check connection
  if ((!$conn_id) || (!$login_result)) {
      die("FTP connection has failed !");
  }
  // try to change the directory to somedir
  ftp_chdir($conn_id, "Verizon");
  //turn passive mode on
  ftp_pasv($conn_id, true);
  // try to upload $file
  ftp_fput($conn_id, $file, $fp, FTP_BINARY);

  ftp_close($conn_id);
  fclose($fp);
