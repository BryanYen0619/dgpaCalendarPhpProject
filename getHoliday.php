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
        getHoliday();
        break;
    case 'PUT':
        #$sql = "update `$table` set $set where id=$key"; break;
        echo 'Not Support PUT' . "\n";
        break;
    case 'POST':
        echo 'Not Support POST' . "\n";
        break;
    case 'DELETE':
        #$sql = "delete `$table` where id=$key"; break;
        echo 'Not Support DELETE' . "\n";
        break;
}

/** input參數說明
 * 
 * */
function getHoliday() {

    // SQL init
    require_once('Connections/link.php');
    // SQL Select table
    $db_table_name = 'calendar_table';

    
    // 取得資料
        $selectSql = "SELECT * FROM $db_table_name "
                . "WHERE isWorkerHoliday IS true " ;
    if ($isHightPhpVersion) {
        $selectSqlQuery = mysqli_query($mysql, $selectSql);
    } else {
        $selectSqlQuery = mysql_query($selectSql);
    }

    if ($selectSqlQuery != null) {
        http_response_code(200);

        if ($isHightPhpVersion) {
            while ($row = mysqli_fetch_assoc($selectSqlQuery)) {
                $rows[] = $row;
            }
        } else {
            while ($row = mysql_fetch_assoc($selectSqlQuery)) {
                $rows[] = $row;
            }
        }

        $data = array('errorcode' => 0, 'data' => $rows);
        echo json_encode($data);
    } else {
        http_response_code(404);
        echo mysql_error() . "\n";
    }
}