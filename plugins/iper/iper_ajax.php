<?php

/*
  +-------------------------------------------------------------------------+
  | Copyright (C) 2007 Susanin                                          |
  |                                                                         |
  | This program is free software; you can redistribute it and/or           |
  | modify it under the terms of the GNU General Public License             |
  | as published by the Free Software Foundation; either version 2          |
  | of the License, or (at your option) any later version.                  |
  |                                                                         |
  | This program is distributed in the hope that it will be useful,         |
  | but WITHOUT ANY WARRANTY; without even the implied warranty of          |
  | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the           |
  | GNU General Public License for more details.                            |
  +-------------------------------------------------------------------------+
 */

chdir('../../');
include("./include/auth.php");

//***********************************************************

$task = $_REQUEST['task'];

if (function_exists($task)) {
    call_user_func($task);
} else {
    echo "Unsupported command [" . $task . "].";
}

function iper_get_snmptt_traps() {
    $table = 'plugin_iper_snmptt';
    $rowStart = (integer) ($_REQUEST['start']);
    $rowLimit = (integer) ($_REQUEST['length']);

    $query_string = "SELECT * FROM `$table` ORDER BY `id` DESC LIMIT $rowStart, $rowLimit;";
    $total_rows = db_fetch_cell("SELECT count(*) FROM `$table`;");

    if ($total_rows > 0) {
        $rows = db_fetch_assoc($query_string);
        die(json_encode(array('success' => true, 'total' => $total_rows, "results" => $rows)));
    } else {
        echo json_encode(array('success' => true, 'total' => "0", "results" => ""));
    }
}

function iper_get_snmptt_unks() {
    $table = 'plugin_iper_snmptt_unk';
    $rowStart = (integer) ($_REQUEST['start']);
    $rowLimit = (integer) ($_REQUEST['length']);

    $query_string = "SELECT * FROM `$table` ORDER BY `id` DESC LIMIT $rowStart, $rowLimit;";
    $total_rows = db_fetch_cell("SELECT count(*) FROM `$table`;");

    if ($total_rows > 0) {
        $rows = db_fetch_assoc($query_string);
        die(json_encode(array('success' => true, 'total' => $total_rows, "results" => $rows)));
    } else {
        echo json_encode(array('success' => true, 'total' => "0", "results" => ""));
    }
}

function iper_get_syslogs() {
    $table = 'plugin_iper_syslog';

    //business logic
    $rowStart = (integer) $_REQUEST['start'];
    $rowLimit = (integer) $_REQUEST['length'];

    $draw = array_key_exists('draw', $_POST) ? $_POST['draw'] : -1;

    $query_string = "SELECT * FROM `$table` ORDER BY id DESC LIMIT $rowStart, $rowLimit;";

    $total_rows = db_fetch_cell("SELECT count(*) FROM `$table`;");

    //output
    if ($total_rows > 0) {
        $rows = db_fetch_assoc($query_string);
        $responseData = array('success' => true,
            'total' => $total_rows,
            'recordsTotal' => $total_rows,
            'recordsFiltered' => $total_rows,
            "results" => $rows);
    } else {
        $responseData = array('success' => true,
            'total' => "0",
            'recordsTotal' => 0,
            "results" => ""
        );
    }
    if ($draw >= 0)
        $responseData['draw'] = $draw;
    die(json_encode($responseData));
}
