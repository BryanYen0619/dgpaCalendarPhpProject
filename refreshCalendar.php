<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

date_default_timezone_set("Asia/Taipei");

echo "====== " . date("Y-m-d h:i:s A") . " BEGIN. ======\n";

$response = getDpgaGovCalendarFromServer();
$responseArray = json_decode($response, true);
//var_dump($responseArray);

$dataList = $responseArray;
//if ($successStatus == 1) {
//    $infoList = $responseArray['result'];
//    if(isset($infoList)) {
//        $version = $infoList['resource_id'];
//        echo "resource_id:".$version."\n";
    
//        $dataList = $infoList['records'];
//        if(isset($dataList)) {
            for ($i = 0; $i < sizeof($dataList); $i++){
                $itemList = $dataList[$i];
//                var_dump($itemList);

                $date = $itemList['date'];
                $name = $itemList['name'];
                $isHoliday = $itemList['isHoliday'];
                $holidayCategory = $itemList['holidayCategory'];
                $description = $itemList['description'];
                
                $isWorkerHoliday = false;
                if ($isHoliday == "是") {
                    // 軍人節判斷，勞工沒放假
                    if (substr($date, -3) == "9/3") {
                        $isWorkerHoliday = false;
                    } else {
                        $isWorkerHoliday = true;
                    }
                }
                
                require_once('Connections/link.php');
                $db_table_name = 'calendar_table';

                // 取得資料
                $selectSql = "SELECT id FROM $db_table_name "
                        . "WHERE date LIKE '$date' "
                        . "AND isWorkerHoliday IS true "
                        . "LIMIT 1";
                $rows = null;
                if ($isHightPhpVersion) {
                    $selectSqlQuery = mysqli_query($mysql, $selectSql);
                    $selectSqlQueryNum = mysqli_num_rows($selectSqlQuery);
                    while ($row = mysqli_fetch_assoc($selectSqlQuery)) {
                            $rows[] = $row;
                        }
                } else {
                    $selectSqlQuery = mysql_query($selectSql);
                    $selectSqlQueryNum = mysql_num_rows($selectSqlQuery);
                    while ($row = mysql_fetch_assoc($selectSqlQuery)) {
                            $rows[] = $row;
                        }
                }

                $insertCount = 0;
                $updateCount = 0;
                if ($selectSqlQueryNum == 0) {
                    // 新增
                    $instertSql = "INSERT INTO $db_table_name (date "
                            . ",name "
                            . ",isHoliday "
                            . ",holidayCategory "
                            . ",description "
                            . ",isWorkerHoliday) "
                            . "VALUES ('$date' "
                            . ",'$name' "
                            . ",'$isHoliday' "
                            . ",'$holidayCategory' "
                            . ",'$description' "
                            . ",'$isWorkerHoliday') ";
//                    echo "$instertSql\n";
                    if ($isHightPhpVersion) {
                        $insertResult = mysqli_query($mysql, $instertSql);
                    } else {
                        $insertResult = mysql_query($instertSql);
                    }
                    
                    $insertCount++;
                } else {
                    if ($rows != null) {
                        foreach ($rows as $key => $value) {
                            $id = $value['id'];
                        }
                    
                        // 更新
                        $updateSql = "UPDATE $db_table_name SET "
                                . "date = '$date' "
                                . ",name = '$name' "
                                . ",isHoliday = '$isHoliday' "
                                . ",holidayCategory = '$holidayCategory' "
                                . ",description = '$description' "
                                . ",isWorkerHoliday = '$isWorkerHoliday' "
                                . ",updateAt = NOW() "
                                . "WHERE id LIKE '$id' ";
                        if ($isHightPhpVersion) {
                            $updateSqlQuery = mysqli_query($mysql, $updateSql);
                        } else {
                            $updateSqlQuery = mysql_query($updateSql);
                        }
                        
                        $updateCount++;
                    }
                }
            }
//        }
//    }
//}
echo "refresh DGPA Gov Calendar Success.\n";
echo "refresh DGPA Gov Calendar Insert:$insertCount.\n";
echo "refresh DGPA Gov Calendar Update:$updateCount.\n";

echo "====== " . date("Y-m-d h:i:s A") . " END.   ======\n";

function getDpgaGovCalendarFromServer() {
    $ServiceURL = "https://data.ntpc.gov.tw/api/datasets/308DCD75-6434-45BC-A95F-584DA4FED251/json?page=10&size=200";

    $headers = array(
        'Content-Type:application/json'
    );

    return useCurlGet($ServiceURL, $headers, null);
}

// Curl 
function useCurlGet($url, $headers, $fields) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // Disabling SSL Certificate support temporarly
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        if ($fields) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
        }

        // Execute post
        $result = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($result === FALSE) {
            die('Curl failed: ' . curl_error($ch));
        }
        // Close connection
        curl_close($ch);
        //checking the response code we get from fcm for debugging purposes
//        echo "http response " . $httpcode . "\n";
        //checking the status/result of the push notif for debugging purposes
//        echo $result . "\n";
        return $result;
    }
