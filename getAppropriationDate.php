<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

date_default_timezone_set("Asia/Taipei");
// get the HTTP method, path and body of the request
$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

//echo var_dump($input);
// create SQL based on HTTP method
switch ($method) {
    case 'GET':
        #$sql = "select * from `$table`".($key?" WHERE id=$key":''); break;
        echo 'Not Support GET' . "\n";
        break;
    case 'PUT':
        #$sql = "update `$table` set $set where id=$key"; break;
        echo 'Not Support PUT' . "\n";
        break;
    case 'POST':
        getAppropriationDate($input);
        break;
    case 'DELETE':
        #$sql = "delete `$table` where id=$key"; break;
        echo 'Not Support DELETE' . "\n";
        break;
}

function getAppropriationDate($input) {
    $inputDate = $input['date'];

    if (!isset($inputDate)) {
        $error = array('errorcode' => 502, 'errormessage' => '參數不足');
        echo json_encode($error);
        return;
    }

    // 判斷通路
    if (isset($input['type']) == "store") {
        $isStore = true;
    } else {
        $isStore = false;
    }
    
    // 取得休假列表
    $holidayList = getHolidayList($inputDate);
    $date = date_create($inputDate);
    
    $dateYearMonth = date_format($date, 'Ym');
    $yearCount = date_format($date, 'Y');
    $monthCount = date_format($date, 'm');
    $dayCount = date_format($date, 'd');

    // 超商下下期撥款
    // 其他下期撥款
    if ($dayCount >= 1 && $dayCount <= 10) {
        if($isStore) { 
            $dateYearMonth = date("Ym", mktime(0, 0, 0, $monthCount + 1, 1, $yearCount));
            $new_date = date_create($dateYearMonth . "02");
        } else {
            $new_date = date_create($dateYearMonth . "22"); 
        }
    } else if ($dayCount >= 11 && $dayCount <= 20) { 
        if($isStore) { 
            $dateYearMonth = date("Ym", mktime(0, 0, 0, $monthCount + 1, 1, $yearCount));
            $new_date = date_create($dateYearMonth . "12");
        } else {       
            $dateYearMonth = date("Ym", mktime(0, 0, 0, $monthCount + 1, 1, $yearCount));
            $new_date = date_create($dateYearMonth . "02");  
        }
    } else if ($dayCount >= 21 && $dayCount <= 31) {
        if($isStore) { 
            $dateYearMonth = date("Ym", mktime(0, 0, 0, $monthCount + 1, 1, $yearCount));
            $new_date = date_create($dateYearMonth . "22");
        } else {
            $dateYearMonth = date("Ym", mktime(0, 0, 0, $monthCount + 1, 1, $yearCount));
            $new_date = date_create($dateYearMonth . "12");
        }
    } else {
        $new_date = -1;
    }
    
    $appropriationDate = date_format($new_date, 'Y-m-d');
    
    // 判斷請求日期是否為假日
    $checkCurrentDateIsChtHoliday = isChtHoliday($holidayList, $appropriationDate);
    // 找下一個工作日
    if ($checkCurrentDateIsChtHoliday) {
        while (1) {
            $appropriationDate = date('Y-m-d', strtotime("$appropriationDate +1 Days"));
            $check = isChtHoliday($holidayList, $appropriationDate);
            if (!$check) {
                break;
            }
        }
    }

    $data = array('errorcode' => 0, 'data' => $appropriationDate);
    http_response_code(200);
    echo json_encode($data);
}

function getHolidayList($inputDate) {
     // 日期建立
    $beginDate = date_create($inputDate);
    $endDate = date_create($inputDate)->modify('last day of +1 month');
    
    // 日期時區轉換 Asia -> UTC  
    $beginDate->setTimezone(new DateTimeZone('UTC'));
    $endDate->setTimezone(new DateTimeZone('UTC'));
    
    // 日期取Timestamp
    $beginDateTimestamp = $beginDate->getTimestamp();
    $endDateTimestamp = $endDate->getTimestamp();
    
    // SQL init
    require_once('Connections/link.php');
    // SQL Select table
    $db_table_name = 'calendar_table';


    // 取得資料
    $selectSql = "SELECT date FROM $db_table_name "
            . "WHERE UNIX_TIMESTAMP(STR_TO_DATE(date, '%Y/%m/%d')) >= $beginDateTimestamp "
            . "AND UNIX_TIMESTAMP(STR_TO_DATE(date, '%Y/%m/%d')) < $endDateTimestamp ";
    
    if ($isHightPhpVersion) {
        $selectSqlQuery = mysqli_query($mysql, $selectSql);
    } else {
        $selectSqlQuery = mysql_query($selectSql);
    }

    if ($selectSqlQuery) {
        $rows = array();
        if ($isHightPhpVersion) {
            $selectSqlQuery = mysqli_query($mysql, $selectSql);
            while ($row = mysqli_fetch_assoc($selectSqlQuery)) {
                    $rows[] = $row;
                }
        } else {
            $selectSqlQuery = mysql_query($selectSql);
            while ($row = mysql_fetch_assoc($selectSqlQuery)) {
                    $rows[] = $row;
                }
        }
    } 
    
    return $rows;
}

function isChtHoliday($holidayList, $inputdate) {
    for ($i = 0; $i < sizeof($holidayList); $i++) {
        $holidayDate = date_create($holidayList[$i]['date'])->format('Y-m-d');
        
//        echo $inputdate." VS ".$holidayDate."\n";
        if ($inputdate == $holidayDate) {
            return true;
        }
    }

    return false;
}