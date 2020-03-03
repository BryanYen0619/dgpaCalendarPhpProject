<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

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
        checkHoliday($input);
        break;
    case 'DELETE':
        #$sql = "delete `$table` where id=$key"; break;
        echo 'Not Support DELETE' . "\n";
        break;
}

/** input參數說明
 * 
 * */
function checkHoliday($input) {
    $member_date = $input['date'];

    if (!isset($member_date)) {
        $error = array('errorcode' => 502, 'errormessage' => '參數不足');
        echo json_encode($error);
        return;
    }

    // 日期建立
    $beginDate = date_create($member_date);
   
    // 日期時區轉換 Asia -> UTC  
    $beginDate->setTimezone(new DateTimeZone('UTC'));
    
    // 日期取Timestamp
    $beginDateTimestamp = $beginDate->getTimestamp();

    // SQL init
    require_once('Connections/link.php');
    // SQL Select table
    $db_table_name = 'calendar_table';


    // 取得資料
    $selectSql = "SELECT * FROM $db_table_name "
            . "WHERE UNIX_TIMESTAMP(STR_TO_DATE(date, '%Y/%m/%d')) LIKE '$beginDateTimestamp' "
            . "LIMIT 1 ";
    if ($isHightPhpVersion) {
        $selectSqlQuery = mysqli_query($mysql, $selectSql);
        $selectSqlQueryNum = mysqli_num_rows($selectSqlQuery);
//        while ($row = mysqli_fetch_assoc($selectSqlQuery)) {
//                $rows[] = $row;
//            }
    } else {
        $selectSqlQuery = mysql_query($selectSql);
        $selectSqlQueryNum = mysql_num_rows($selectSqlQuery);
//        while ($row = mysql_fetch_assoc($selectSqlQuery)) {
//                $rows[] = $row;
//            }
    }

    if ($selectSqlQuery != null) {
        http_response_code(200);
        if ($selectSqlQueryNum != 0) {
            $data = array('errorcode' => 0, 'isHoliday' => true);
        } else {
            $data = array('errorcode' => 0, 'isHoliday' => false);
        }

        echo json_encode($data);
    } else {
        http_response_code(404);
        echo mysql_error() . "\n";
    }
}
