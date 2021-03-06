<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

$response = getDpgaGovCalendarFromServer();
$responseArray = json_decode($response, true);
//var_dump($responseArray);
        
$successStatus = $responseArray['success'];
if ($successStatus == 1) {
    $infoList = $responseArray['result'];
    if(isset($infoList)) {
        $version = $infoList['resource_id'];
//        echo "resource_id:".$version."\n";
    
        $dataList = $infoList['records'];
        if(isset($dataList)) {
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
                $db_table_name = 'dgpa_gov_calendar_table';

                // 取得電子發票資料
                $selectSql = "SELECT id FROM $db_table_name "
                        . "WHERE date LIKE '$date' "
                        . "AND isWorkerHoliday LIKE 1 "
                        . "LIMIT 1";
                if ($isHightPhpVersion) {
                    $selectSqlQuery = mysqli_query($mysql, $selectSql);
                    $selectSqlQueryNum = mysqli_num_rows($selectSqlQuery);
                } else {
                    $selectSqlQuery = mysql_query($selectSql);
                    $selectSqlQueryNum = mysql_num_rows($selectSqlQuery);
                }

                if ($selectSqlQueryNum == 0) {
                    // 新增
                    $instertSql = "INSERT INTO $db_table_name (date "
                            . ",name "
                            . ",isHoliday "
                            . ",holidayCategory "
                            . ",description "
                            . ",isWorkerHoliday) "
                            . "VALUES ('$date' "
                            . ",'$isHoliday' "
                            . ",'$holidayCategory' "
                            . ",'$description' "
                            . ",'$isWorkerHoliday') ";
                    if ($isHightPhpVersion) {
                        $insertResult = mysqli_query($mysql, $instertSql);
                    } else {
                        $insertResult = mysql_query($instertSql);
                    }
                } else {
                    $rows = null;
                    if ($isHightPhpVersion) {
                        while ($row = mysqli_fetch_assoc($selectSqlQuery)) {
                            $rows[] = $row;
                        }
                    } else {
                        while ($row = mysql_fetch_assoc($selectSqlQuery)) {
                            $rows[] = $row;
                        }
                    }
                    
                    if ($rows != null) {
                        foreach ($rows as $key => $value) {
                            $id = $value['id'];
                        }
                    
                        // 更新
                        $updateSql = "UPDATE $db_table_name SET "
                                . "date = '$date' "
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
                    }
                }
                
                

            }
        }
    }
}

function getDpgaGovCalendarFromServer() {
    $ServiceURL = "https://data.ntpc.gov.tw/api/v1/rest/datastore/382000000A-000077-002";

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
