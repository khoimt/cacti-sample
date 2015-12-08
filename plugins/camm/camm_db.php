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


include_once($config["base_path"] . "/plugins/camm/lib/camm_functions.php");
include_once($config["base_path"] . "/plugins/camm/lib/json_sql.php");

//***********************************************************

/* ================= input validation ================= */

camm_input_validate_input_regex(get_request_var_request("task"), "camm_[a-zA-Z_]*", 'Unrecognised command [' . get_request_var_request("task") . ']');
//camm_input_validate_input_regex(get_request_var_request("task"), "view_traps|view_unktraps|delete_traps|delete_unktraps|view_rules|get_menu_tree|get_eventname|save_Rule|delete_rule|view_stats|get_graphurl|view_rezults|count_rezults|recreate_tree|get_eventnames|get_severites|test_rule|get_full_trap|get_full_unk_trap|get_full_rule|convert_json_to_sql_string|get_user_functions|view_syslog|get_facilitys|get_prioritys|get_full_sys_mes",'Unrecognised command [' . get_request_var_request("task") . ']');

/* ==================================================== */


if (is_error_message()) {
    echo "Validation error.";
    exit;
}

$task = '';
if (isset($_POST['task'])) {
    $task = $_POST['task'];
} elseif (isset($_GET['task'])) {
    $task = $_GET['task'];
}

$sql_where = '';


if (camm_user_func_exists($task)) {
    call_user_func($task);
} else {
    echo "Unsupported command [" . $task . "].";
}

function camm_view_rezults() {
    /* ================= input validation ================= */


    /* ==================================================== */

    $rezult = array();

    if (isset($_SESSION["camm_output_messages"])) {
        $i = -1;
        if (is_array($_SESSION["camm_output_messages"])) {


            foreach ($_SESSION["camm_output_messages"] as $current_message) {
                $i = $i + 1;
                $rezult[$i]["id"] = $i;
                $rezult[$i]["group"] = ((isset($current_message["object"])) ? $current_message["object"] : "");
                $rezult[$i]["title"] = $current_message["device_descr"];
                $rezult[$i]["message"] = $current_message["message"];
                if ($current_message["type"] == "update_db") {
                    $rezult[$i]["step_rezult"] = ((isset($current_message["step_rezult"])) ? $current_message["step_rezult"] : "");
                    $rezult[$i]["step_data"] = ((isset($current_message["step_data"])) ? $current_message["step_data"] : "");
                    $rezult[$i]["check_rezult"] = ((isset($current_message["step_rezult"])) ? $current_message["step_rezult"] : "");
                    $rezult[$i]["check_data"] = ((isset($current_message["step_data"])) ? $current_message["step_data"] : "");
                } else {
                    $rezult[$i]["step_rezult"] = ((isset($current_message["step_rezult"])) ? $current_message["step_rezult"] : "");
                    $rezult[$i]["step_data"] = ((isset($current_message["step_data"])) ? $current_message["step_data"] : "");
                    $rezult[$i]["check"] = ((isset($current_message["check_rezult"])) ? $current_message["check_rezult"] : "no access");
                    $rezult[$i]["check_data"] = ((isset($current_message["check_data"])) ? $current_message["check_data"] : "");
                }
            }
        }
    }

    //kill_session_var("camm_output_messages");
    $jsonresult = camm_JEncode($rezult);
    echo '({"rezults":' . $jsonresult . '})';

    kill_session_var("camm_output_messages");
}

function camm_get_start_variable() {
    global $config, $cacti_camm_components;

    $out_array = array();

    /* ================= input validation ================= */


    /* ==================================================== */

    $int_count_output_mess = 0;

    if (isset($_SESSION["camm_output_messages"])) {
        if (is_array($_SESSION["camm_output_messages"])) {
            $int_count_output_mess = sizeof($_SESSION["camm_output_messages"]);
        }
    }

    $out_array['success'] = true;
    $out_array['count_output_mess'] = $int_count_output_mess;
    $out_array['is_camm_admin'] = is_camm_admin();
    $out_array['cacti_path'] = $config['url_path'];
    $out_array['graph_camm_url_big'] = get_graph_camm_url('%camm%poller%big%stat%');
    $out_array['graph_camm_url_row'] = get_graph_camm_url('%camm%poller rows%stat%');
    $out_array['graph_camm_url_time'] = get_graph_camm_url('%camm%poller%time%stat%');
    $out_array['camm_num_rows'] = read_config_option("camm_num_rows", true);
    $out_array['camm_startup_tab'] = read_config_option("camm_startup_tab", true);
    $out_array['camm_unktrap_tab_update'] = read_config_option("camm_snmptt_unktrap_tab_update", true);
    $out_array['camm_trap_tab_update'] = read_config_option("camm_snmptt_trap_tab_update", true);
    $out_array['camm_sys_tab_update'] = read_config_option("camm_sys_tab_update", true);
    $out_array['camm_use_snmptt'] = $cacti_camm_components["snmptt"];
    $out_array['camm_use_syslog'] = $cacti_camm_components["syslog"];
    $out_array['camm_date'] = date("Y-m-d  H:i:s");




    echo camm_JEncode($out_array);
}

function camm_get_traps_records() {
    global $cacti_camm_components;

    $rezult = 1;

    /* ================= input validation ================= */

    camm_input_validate_input_regex(get_request_var_request("start", "0"), "^[0-9]{0,10}$");
    camm_input_validate_input_regex(get_request_var_request("limit", "50"), "^[0-9]{1,4}$");
    camm_input_validate_input_regex(get_request_var_request("tree_id"), "^((typ|evn|host)-[0-9]{0,4})|root$");

    /* ==================================================== */

    //error checking
    if (is_error_message()) {
        $rezult = "Input validation error.";
    }

    //business logic
    if ($rezult == 1) {
        if ($cacti_camm_components["snmptt"]) {
            $row_start = (integer) (isset($_POST['start']) ? $_POST['start'] : $_POST['start']);
            $row_limit = (integer) (isset($_POST['limit']) ? $_POST['limit'] : $_POST['limit']);
            $tree_id = (string) (isset($_POST['tree_id']) ? $_POST['tree_id'] : $_POST['tree_id']);
            $raw_json_where = (string) (isset($_POST['filter']) ? $_POST['filter'] : $_POST['filter']);

            $query_string = "";
            $sql_where = getSQL(camm_JDecode(stripslashes($raw_json_where)));

            if (eregi("([a-z]{3,4})-([0-9]{1,6})", $tree_id, $regs)) {
                $tree_row = db_fetch_row("select SQL_CALC_FOUND_ROWS * from `plugin_camm_tree` where `id`=" . $regs[2] . "; ");
                switch ($regs[1]) {
                    case "host":
                        $tree_sql = " and  (`hostname` = '" . $tree_row["hostname"] . "') ";
                        break;
                    case "evn":
                        $tree_sql = " and (`eventname` = '" . $tree_row["eventname"] . "' and  `hostname` = '" . $tree_row["hostname"] . "') ";
                        break;
                    case "typ":
                        $search_hostnames = db_fetch_assoc("SELECT `hostname` FROM plugin_camm_tree where `host_template_id`='" . $tree_row["host_template_id"] . "' AND `type`='camm'  group by hostname;");
                        $search_hostname = '';
                        for ($i = 0; ($i < count($search_hostnames)); $i++) {
                            $search_hostname = $search_hostname . "'" . $search_hostnames[$i]["hostname"] . "', ";
                        }
                        $search_hostname = substr($search_hostname, 0, strlen($search_hostname) - 2);

                        $tree_sql = " and (hostname IN (" . $search_hostname . ")) ";
                        break;
                }
            } else {
                $tree_sql = "";
            }

            if (read_config_option("camm_show_all_records") == "0") {
                $tree_sql = $tree_sql . " and `status`=2 ";
            }

            if (read_config_option("camm_join_field") == "sourceip") {
                $join_field = "agentip";
            } elseif (read_config_option("camm_join_field") == "hostname") {
                $join_field = "hostname";
            } else {
                $join_field = "agentip";
            }
            $query_string = " SELECT  temp_unk.*, host.description, host.host_template_id, host.id as device_id " .
                    "from (SELECT  plugin_camm_snmptt.* FROM plugin_camm_snmptt WHERE $sql_where $tree_sql order by traptime desc ";
            $query_string .= " LIMIT " . $row_start . "," . $row_limit;
            //group by id because cacti hosts table may have more than one device with one hostname
            $query_string .= ") as temp_unk Left join host on (temp_unk." . $join_field . "=host.hostname) group by id";

            $total_rows = db_fetch_cell("SELECT count(*) FROM plugin_camm_snmptt WHERE " . $sql_where . " " . $tree_sql . ";");
        } else {
            $rezult = " SNMPTT component disabled.";
        }
    }

    //output
    if ($rezult == 1) {
        if ($total_rows > 0) {
            $rows = db_fetch_assoc($query_string);
            //$jsonresult = camm_JEncode($rows);
            //echo '({"total":"'.$total_rows.'","results":'.$jsonresult.'})';
            echo camm_JEncode(array('success' => true, 'total' => $total_rows, "results" => $rows));
        } else {
            echo camm_JEncode(array('success' => true, 'total' => "0", "results" => ""));
        }
    } else {
        echo camm_JEncode(array('failure' => true, 'error' => $rezult));
    }
}

function camm_get_unktraps_records() {
    global $cacti_camm_components;

    $rezult = 1;

    /* ================= input validation ================= */

    camm_input_validate_input_regex(get_request_var_request("start", "0"), "^[0-9]{0,10}$");
    camm_input_validate_input_regex(get_request_var_request("limit", "50"), "^[0-9]{1,4}$");

    /* ==================================================== */

    //error checking
    if (is_error_message()) {
        $rezult = "Input validation error.";
    }

    //business logic
    if ($rezult == 1) {
        if ($cacti_camm_components["snmptt"]) {
            $sql_where = "";

            $row_start = (integer) (isset($_POST['start']) ? $_POST['start'] : $_POST['start']);
            $row_limit = (integer) (isset($_POST['limit']) ? $_POST['limit'] : $_POST['limit']);
            $raw_json_where = (string) (isset($_POST['filter']) ? $_POST['filter'] : $_POST['filter']);

            $query_string = "";
            $sql_where = getSQL(camm_JDecode(stripslashes($raw_json_where)));


            $query_string = " SELECT temp_unk.*, host.description, host.host_template_id, host.id as device_id " .
                    "from (SELECT plugin_camm_snmptt_unk.* FROM plugin_camm_snmptt_unk WHERE $sql_where ";

            $query_string .= " LIMIT " . $row_start . "," . $row_limit;

            $query_string .= ") as temp_unk Left join host on (temp_unk.hostname=host.hostname)";


            $total_rows = db_fetch_cell("SELECT count(plugin_camm_snmptt_unk.id) FROM plugin_camm_snmptt_unk WHERE $sql_where;");
        } else {
            $rezult = " SNMPTT component disabled.";
        }
    }

    //output
    if ($rezult == 1) {
        if ($total_rows > 0) {
            $rows = db_fetch_assoc($query_string);
            //$jsonresult = camm_JEncode($rows);
            //echo '({"total":"'.$total_rows.'","results":'.$jsonresult.'})';
            echo camm_JEncode(array('success' => true, 'total' => $total_rows, "results" => $rows));
        } else {
            echo camm_JEncode(array('success' => true, 'total' => "0", "results" => ""));
        }
    } else {
        echo camm_JEncode(array('failure' => true, 'error' => $rezult));
    }
}

function camm_get_graphurl() {
    global $config;
    $rezult = '';

    if (strlen(trim(read_config_option("camm_general_graphs_ids"))) > 0) {

        $graph_ids = explode(',', read_config_option("camm_general_graphs_ids"));

        if (sizeof($graph_ids) > 0) {
            foreach ($graph_ids as $graph_id) {
                $graph_id = trim($graph_id);
                if ((is_numeric($graph_id)) && ($graph_id != "")) {
                    $graph_row = db_fetch_row("SELECT * FROM `graph_templates_graph` where `local_graph_id`='" . $graph_id . "'; ");
                    if (sizeof($graph_row) > 0) {
                        $rezult = $rezult . '<img border="0" alt="' . $graph_row['title_cache'] . '" src="' . $config['url_path'] . 'graph_image.php?local_graph_id=' . $graph_id . '">';
                    }
                }
            }
        }

        echo $rezult;
    }
}

function camm_get_stats() {
    global $cacti_camm_components;

    $rezult = array();
    $i = 0;

    $tables_info = db_fetch_assoc("show table status where name in ('plugin_camm_snmptt','plugin_camm_tree','plugin_camm_snmptt_unk') ");

    if ($cacti_camm_components["syslog"]) {
        $table_syslog = db_fetch_row("show table status FROM `" . read_config_option("camm_syslog_db_name") . "` where name='plugin_camm_syslog'");
        if (sizeof($table_syslog) > 0) {
            $tables_info[] = $table_syslog;
        }
    }

    if (sizeof($tables_info) > 0) {
        foreach ($tables_info as $table) {
            $group_name = "Table " . $table["Name"] . " detailed info";
            $rezult[$i]["type"] = $group_name;
            $rezult[$i]["name"] = "Rows";
            $rezult[$i]["value"] = number_format($table["Rows"], 0, ' ', ' ');
            $i = $i + 1;
            $rezult[$i]["type"] = $group_name;
            $rezult[$i]["name"] = "Data Length";
            $rezult[$i]["value"] = camm_format_filesize($table["Data_length"]);
            $i = $i + 1;
            $rezult[$i]["type"] = $group_name;
            $rezult[$i]["name"] = "Index Length";
            $rezult[$i]["value"] = camm_format_filesize($table["Index_length"]);
            $i = $i + 1;
            $rezult[$i]["type"] = $group_name;
            $rezult[$i]["name"] = "Auto increment";
            $rezult[$i]["value"] = $table["Auto_increment"];
            $i = $i + 1;
            $rezult[$i]["type"] = $group_name;
            $rezult[$i]["name"] = "Update time";
            $rezult[$i]["value"] = $table["Update_time"] . "  (" . camm_format_datediff(strtotime($table["Update_time"])) . ")";
            $i = $i + 1;
            $rezult[$i]["type"] = $group_name;
            $rezult[$i]["name"] = "Check time";
            $rezult[$i]["value"] = $table["Check_time"];
            $i = $i + 1;
        }
    }

    #info from snmptt stats table
    if ($cacti_camm_components["snmptt"]) {
        $table_snmptt_stats = db_fetch_row("show table status where name='plugin_camm_snmptt_stat'");
        $group_name = "1. Stats from SMPTT stat table (since last snmptt service restart)";
        if (sizeof($table_snmptt_stats) > 0) {
            $snmptt_last_stats = db_fetch_assoc("SELECT * FROM `plugin_camm_snmptt_stat` order by `stat_time` desc limit 2;");
            if (sizeof($snmptt_last_stats) > 0) {
                if (sizeof($snmptt_last_stats) > 0) {
                    $group_name = $group_name . " and [from last stat period]";
                    $snmptt_last_stat2 = $snmptt_last_stats[0];
                }


                $snmptt_last_stat1 = $snmptt_last_stats[1];

                $rezult[$i]["type"] = $group_name;
                $rezult[$i]["name"] = "Last stat date";
                $rezult[$i]["value"] = $snmptt_last_stat1["stat_time"];
                $i = $i + 1;
                $rezult[$i]["type"] = $group_name;
                $rezult[$i]["name"] = "Total traps received";
                $rezult[$i]["value"] = ((isset($snmptt_last_stat2["total_received"])) ? number_format($snmptt_last_stat1["total_received"], 0, ' ', ' ') . "    [" . number_format(($snmptt_last_stat2["total_received"] - $snmptt_last_stat1["total_received"]), 0, ' ', ' ') . "]" : number_format($snmptt_last_stat1["total_received"], 0, ' ', ' '));
                $i = $i + 1;
                $rezult[$i]["type"] = $group_name;
                $rezult[$i]["name"] = "Total traps translated";
                $rezult[$i]["value"] = ((isset($snmptt_last_stat2["total_translated"])) ? number_format($snmptt_last_stat1["total_translated"], 0, ' ', ' ') . "    [" . number_format(($snmptt_last_stat2["total_translated"] - $snmptt_last_stat1["total_translated"]), 0, ' ', ' ') . "]" : number_format($snmptt_last_stat1["total_translated"], 0, ' ', ' '));
                $i = $i + 1;
                $rezult[$i]["type"] = $group_name;
                $rezult[$i]["name"] = "Total traps ignored";
                $rezult[$i]["value"] = ((isset($snmptt_last_stat2["total_ignored"])) ? number_format($snmptt_last_stat1["total_ignored"], 0, ' ', ' ') . "    [" . number_format(($snmptt_last_stat2["total_ignored"] - $snmptt_last_stat1["total_ignored"]), 0, ' ', ' ') . "]" : number_format($snmptt_last_stat1["total_ignored"], 0, ' ', ' '));
                $i = $i + 1;
                $rezult[$i]["type"] = $group_name;
                $rezult[$i]["name"] = "Total unknown traps";
                $rezult[$i]["value"] = ((isset($snmptt_last_stat2["total_unknown"])) ? number_format($snmptt_last_stat1["total_unknown"], 0, ' ', ' ') . "    [" . number_format(($snmptt_last_stat2["total_unknown"] - $snmptt_last_stat1["total_unknown"]), 0, ' ', ' ') . "]" : number_format($snmptt_last_stat1["total_unknown"], 0, ' ', ' '));
                $i = $i + 1;
            } else {
                $rezult[$i]["type"] = $group_name;
                $rezult[$i]["name"] = "General";
                $rezult[$i]["value"] = "Table [plugin_camm_snmptt_stat] empty";
                $i = $i + 1;
            }
        } else {
            $rezult[$i]["type"] = $group_name;
            $rezult[$i]["name"] = "General";
            $rezult[$i]["value"] = "Table [plugin_camm_snmptt_stat] not exist";
            $i = $i + 1;
        }
    }

    #info from camm poller output
    $poller_camm_stats = db_fetch_assoc("SELECT * FROM settings where name like 'camm_%';");
    foreach ($poller_camm_stats as $row) {
        $poller_camm_stats_new[$row['name']] = $row['value'];
    }
    $group_name = "2. Stats from CaMM Poller output";
    if (sizeof($poller_camm_stats_new) > 0) {
        //$snmptt_last_stats = db_fetch_assoc("SELECT * FROM `plugin_camm_snmptt_stat` order by `stat_time` desc limit 2;");

        $rezult[$i]["type"] = $group_name;
        $rezult[$i]["name"] = "Last poller run time";
        $rezult[$i]["value"] = date('Y-m-d h:i:s', $poller_camm_stats_new["camm_last_run_time"]) . "  (" . camm_format_datediff(($poller_camm_stats_new["camm_last_run_time"])) . ")";
        $i = $i + 1;
        if ($cacti_camm_components["snmptt"] && (isset($poller_camm_stats_new["camm_last_snmptttreedb_time"]) && isset($poller_camm_stats_new["camm_stats_snmptt_tree"]))) {
            $rezult[$i]["type"] = $group_name;
            $rezult[$i]["name"] = "Last snmptt tree menu recreate time";
            $rezult[$i]["value"] = date('Y-m-d h:i:s', $poller_camm_stats_new["camm_last_snmptttreedb_time"]) . "  (" . camm_format_datediff(($poller_camm_stats_new["camm_last_snmptttreedb_time"])) . ")";
            $i = $i + 1;
            $poller_camm_stats_new["camm_stats_snmptt_tree"] = substr(stristr($poller_camm_stats_new["camm_stats_snmptt_tree"], ":"), 1);
            $rezult[$i]["type"] = $group_name;
            $rezult[$i]["name"] = "Last snmptt tree menu recreate duration";
            $rezult[$i]["value"] = $poller_camm_stats_new["camm_stats_snmptt_tree"] . " sec.";
            $i = $i + 1;
        }

        if ($cacti_camm_components["syslog"] && (isset($poller_camm_stats_new["camm_last_syslogtreedb_time"]) && isset($poller_camm_stats_new["camm_stats_syslog_tree"]))) {
            $rezult[$i]["type"] = $group_name;
            $rezult[$i]["name"] = "Last syslog tree menu recreate time";
            $rezult[$i]["value"] = date('Y-m-d h:i:s', $poller_camm_stats_new["camm_last_syslogtreedb_time"]) . "  (" . camm_format_datediff(($poller_camm_stats_new["camm_last_syslogtreedb_time"])) . ")";
            $i = $i + 1;
            $poller_camm_stats_new["camm_stats_syslog_tree"] = substr(stristr($poller_camm_stats_new["camm_stats_syslog_tree"], ":"), 1);
            $rezult[$i]["type"] = $group_name;
            $rezult[$i]["name"] = "Last syslog tree menu recreate duration";
            $rezult[$i]["value"] = $poller_camm_stats_new["camm_stats_syslog_tree"] . " sec.";
            $i = $i + 1;
        }

        if (isset($poller_camm_stats_new["camm_stats"])) {
            list($del_traps, $del_unk_traps, $del_sys_messages) = sscanf($poller_camm_stats_new["camm_stats"], "del_traps:%s del_unk_traps:%s del_sys_messages:%s");
            if ($cacti_camm_components["snmptt"]) {
                $rezult[$i]["type"] = $group_name;
                $rezult[$i]["name"] = "Count snmptt traps deleted";
                $rezult[$i]["value"] = number_format($del_traps, 0, ' ', ' ');
                $i = $i + 1;
                $rezult[$i]["type"] = $group_name;
                $rezult[$i]["name"] = "Count unk. snmptt traps deleted";
                $rezult[$i]["value"] = number_format($del_unk_traps, 0, ' ', ' ');
                $i = $i + 1;
            }
            if ($cacti_camm_components["syslog"]) {
                $rezult[$i]["type"] = $group_name;
                $rezult[$i]["name"] = "Count syslog message deleted";
                $rezult[$i]["value"] = number_format($del_sys_messages, 0, ' ', ' ');
                $i = $i + 1;
            }
        }
    } else {
        $rezult[$i]["type"] = $group_name;
        $rezult[$i]["name"] = "General";
        $rezult[$i]["value"] = "CaMM Poller output data not exist";
        $i = $i + 1;
    }

    echo camm_JEncode(array('success' => true, 'stats' => $rezult));
}

function camm_get_rules_records() {
    /* ================= input validation ================= */

    camm_input_validate_input_regex(get_request_var_request("start", "0"), "^[0-9]{0,10}$");
    camm_input_validate_input_regex(get_request_var_request("limit", "50"), "^[0-9]{1,4}$");

    /* ==================================================== */

    if (is_error_message()) {
        echo "Validation error.";
        exit;
    }

    $sql_where = "";

    $row_start = (integer) (isset($_POST['start']) ? $_POST['start'] : $_POST['start']);
    $row_limit = (integer) (isset($_POST['limit']) ? $_POST['limit'] : $_POST['limit']);
    $raw_json_where = (string) (isset($_POST['filter']) ? $_POST['filter'] : $_POST['filter']);

    $query_string = "";
    $sql_where = getSQL(camm_JDecode(stripslashes($raw_json_where)));


    $query_string = " SELECT plugin_camm_rule.*,user_auth.username FROM plugin_camm_rule left join user_auth on (plugin_camm_rule.user_id=user_auth.id) WHERE $sql_where";

    $query_string .= " LIMIT " . $row_start . "," . $row_limit;



    $total_rows = db_fetch_cell("SELECT count(*) FROM plugin_camm_rule WHERE $sql_where;");
    if ($total_rows > 0) {
        $rows = db_fetch_assoc($query_string);
        $jsonresult = camm_JEncode($rows);
        echo '({"total":"' . $total_rows . '","results":' . $jsonresult . '})';
    } else {
        echo '({"total":"0", "results":""})';
    }
}

function camm_get_syslog_records() {
    global $cacti_camm_components;

    $rezult = 1;

    /* ================= input validation ================= */

    camm_input_validate_input_regex(get_request_var_request("start", "0"), "^[0-9]{0,10}$", 'Uncorrect input data');
    camm_input_validate_input_regex(get_request_var_request("limit", "50"), "^[0-9]{1,4}$", 'Uncorrect input data');
    camm_input_validate_input_regex(get_request_var_request("tree_id"), "^((typ|evn|host)-[0-9]{0,4})|root$", 'Uncorrect input data [tree_id]');

    /* ==================================================== */

    //error checking
    if (is_error_message()) {
        $rezult = "Input validation error.";
    }

    //business logic
    if ($rezult == 1) {
        if ($cacti_camm_components["syslog"]) {
            $row_start = (integer) (isset($_POST['start']) ? $_POST['start'] : $_POST['start']);
            $row_limit = (integer) (isset($_POST['limit']) ? $_POST['limit'] : $_POST['limit']);
            $tree_id = (string) (isset($_POST['tree_id']) ? $_POST['tree_id'] : $_POST['tree_id']);
            $raw_json_where = (string) (isset($_POST['filter']) ? $_POST['filter'] : $_POST['filter']);

            $query_string = "";
            $sql_where = getSQL(camm_JDecode(stripslashes($raw_json_where)));

            if (eregi("([a-z]{3,4})-([0-9]{1,6})", $tree_id, $regs)) {
                $tree_row = db_fetch_row("select * from `plugin_camm_tree` where `id`=" . $regs[2] . "; ");
                switch ($regs[1]) {
                    case "host":
                        $tree_sql = " and  (`host` = '" . $tree_row["hostname"] . "') ";
                        break;
                    case "evn":
                        $tree_sql = " and (`facility` = '" . $tree_row["eventname"] . "' and  `host` = '" . $tree_row["hostname"] . "') ";
                        break;
                    case "typ":
                        $search_hostnames = db_fetch_assoc("SELECT `hostname` FROM plugin_camm_tree where `host_template_id`='" . $tree_row["host_template_id"] . "' AND `type`='syslog'  group by hostname;");
                        $search_hostname = '';
                        for ($i = 0; ($i < count($search_hostnames)); $i++) {
                            $search_hostname = $search_hostname . "'" . $search_hostnames[$i]["hostname"] . "', ";
                        }
                        $search_hostname = substr($search_hostname, 0, strlen($search_hostname) - 2);

                        $tree_sql = " and (`host` IN (" . $search_hostname . ")) ";
                        break;
                }
            } else {
                $tree_sql = "";
            }
            if (read_config_option("camm_show_all_records") == "0") {
                $tree_sql = $tree_sql . " and `status`=2 ";
            }

            if (read_config_option("camm_join_field") == "sourceip") {
                $join_field = "sourceip";
            } elseif (read_config_option("camm_join_field") == "hostname") {
                $join_field = "host";
            } else {
                $join_field = "sourceip";
            }

            $query_string = " SELECT temp_sys.*, host.description, host.host_template_id, host.id as device_id " .
                    "from (SELECT id, `facility`, `priority`, `sys_date`, `host`, `message`,`sourceip` FROM `" . read_config_option("camm_syslog_db_name") . "`.`plugin_camm_syslog` WHERE $sql_where $tree_sql order by sys_date desc ";


            $query_string .= " LIMIT " . $row_start . "," . $row_limit;
//            $query_string .= " LIMIT 1000";

            //group by id because cacti hosts table may have more than one device with one hostname
            $query_string .= ") as temp_sys Left join host on (temp_sys." . $join_field . "=host.hostname) group by id";

            $total_rows = db_fetch_cell("SELECT count(*) FROM `" . read_config_option("camm_syslog_db_name") . "`.`plugin_camm_syslog` WHERE " . $sql_where . " " . $tree_sql . " ;");
        } else {
            $rezult = " SYSLOG component disabled.";
        }
    }

    //output
    if ($rezult == 1) {
        if ($total_rows > 0) {
            $rows = db_fetch_assoc($query_string);
            echo camm_JEncode(array('success' => true, 'total' => $total_rows, "results" => $rows));
        } else {
            echo camm_JEncode(array('success' => true, 'total' => "0", "results" => ""));
        }
    } else {
        echo camm_JEncode(array('failure' => true, 'error' => $rezult));
    }
}

function camm_get_full_trap() {
    global $cacti_camm_components;

    $rezult = 1;

    /* ================= input validation ================= */

    camm_input_validate_input_regex(stripslashes(get_request_var_request("id")), "^\\[([0-9]+,?)+\\]\$", "Uncorrect input value");

    /* ==================================================== */

    //error checking
    if (is_error_message()) {
        $rezult = "Input validation error.";
    }

    //business logic
    if ($rezult == 1) {
        if ($cacti_camm_components["snmptt"]) {
            if (isset($_POST['id'])) {
                $id = $_POST['id']; // Get our array back and translate it :
                $id = camm_JDecode(stripslashes($id));

                if (sizeof($id) < 1) {
                    $rezult = " no ID.";
                } else if (sizeof($id) == 1) {
                    $query = "SELECT * FROM `plugin_camm_snmptt` WHERE `id` = '" . $id[0] . "';";
                    $row_rezult = db_fetch_row($query);
                } else {
                    $rezult = " Incorrect ID.";
                }
            } else {
                $rezult = " no ID.";
            }
        } else {
            $rezult = " SNMPTT component disabled.";
        }
    }

    //output
    if ($rezult == 1) {
        echo camm_JEncode(array('success' => true, 'data' => $row_rezult));
    } else {
        echo camm_JEncode(array('failure' => true, 'error' => $rezult));
    }
}

function camm_get_full_unk_trap() {
    global $cacti_camm_components;

    $rezult = 1;

    /* ================= input validation ================= */

    camm_input_validate_input_regex(stripslashes(get_request_var_request("id")), "^\\[([0-9]+,?)+\\]\$", "Uncorrect input value");

    /* ==================================================== */

    //error checking
    if (is_error_message()) {
        $rezult = "Input validation error.";
    }

    //business logic
    if ($rezult == 1) {
        if ($cacti_camm_components["snmptt"]) {
            if (isset($_POST['id'])) {
                $id = $_POST['id']; // Get our array back and translate it :
                $id = camm_JDecode(stripslashes($id));

                if (sizeof($id) < 1) {
                    $rezult = " no ID.";
                } else if (sizeof($id) == 1) {
                    $query = "SELECT * FROM `plugin_camm_snmptt_unk` WHERE `id` = '" . $id[0] . "';";
                    $row_rezult = db_fetch_row($query);
                } else {
                    $rezult = " Incorrect ID.";
                }
            } else {
                $rezult = " no ID.";
            }
        } else {
            $rezult = " SNMPTT component disabled.";
        }
    }

    //output
    if ($rezult == 1) {
        echo camm_JEncode(array('success' => true, 'data' => $row_rezult));
    } else {
        echo camm_JEncode(array('failure' => true, 'error' => $rezult));
    }
}

function camm_get_full_sys_mes() {
    global $cacti_camm_components;

    /* ================= input validation ================= */

    camm_input_validate_input_regex(stripslashes(get_request_var_request("id")), "^\\[([0-9]+,?)+\\]\$", "Uncorrect input value");

    /* ==================================================== */



    if (is_error_message()) {
        echo "Validation error.";
        exit;
    } else {
        if ($cacti_camm_components["syslog"]) {
            if (isset($_POST['id'])) {
                $id = $_POST['id']; // Get our array back and translate it :
                $id = camm_JDecode(stripslashes($id));
                // You could do some checkups here and return '0' or other error consts.
                // Make a single query to delete all of the presidents at the same time :
                if (sizeof($id) < 1) {
                    echo camm_JEncode(array('failure' => true, 'error' => "Zerro count Input data"));
                } else if (sizeof($id) == 1) {
                    $query = "SELECT * FROM `" . read_config_option("camm_syslog_db_name") . "`.`plugin_camm_syslog` WHERE `id` = '" . $id[0] . "';";
                    $row_rezult = db_fetch_row($query);
                    echo camm_JEncode(array('success' => true, 'data' => $row_rezult));
                } else {
                    echo camm_JEncode(array('failure' => true, 'error' => "Uncorrect count Input data"));
                }
                // echo $query;  This helps me find out what the heck is going on in Firebug...
            } else {
                echo camm_JEncode(array('failure' => true, 'error' => "No Input data"));
            }
        } else {
            echo camm_JEncode(array('failure' => true, 'error' => "SYSLOG NOT USED"));
        }
    }
}

function camm_get_eventnames() {
    global $cacti_camm_components;

    if ($cacti_camm_components["snmptt"]) {
        $rows = db_fetch_assoc("SELECT DISTINCT `eventname` as value,  `eventname` as label FROM `plugin_camm_snmptt`;");
        echo camm_JEncode(array('success' => true, 'names' => $rows));
    } else {
        echo camm_JEncode(array('failure' => true, 'error' => "SNMPTT component disabled"));
    };
}

function camm_get_severites() {
    global $cacti_camm_components;

    if ($cacti_camm_components["snmptt"]) {
        $rows = db_fetch_assoc("SELECT DISTINCT `severity` as value,  `severity` as label FROM `plugin_camm_snmptt`;");
        echo camm_JEncode(array('success' => true, 'names' => $rows));
    } else {
        echo camm_JEncode(array('failure' => true, 'error' => "SNMPTT component disabled"));
    };
}

function camm_get_facilitys() {
    global $cacti_camm_components;

    if ($cacti_camm_components["syslog"]) {
        $rows = db_fetch_assoc("SELECT DISTINCT `facility` as value,  `facility` as label FROM `" . read_config_option("camm_syslog_db_name") . "`.`plugin_camm_syslog`;");

        echo camm_JEncode(array('success' => true, 'names' => $rows));
    } else {
        echo camm_JEncode(array('failure' => true, 'error' => "SYSLOG component disabled"));
    }
}

function camm_get_prioritys() {
    global $cacti_camm_components;

    if ($cacti_camm_components["syslog"]) {
        $rows = db_fetch_assoc("SELECT DISTINCT `priority` as value,  `priority` as label FROM `" . read_config_option("camm_syslog_db_name") . "`.`plugin_camm_syslog`;");

        echo camm_JEncode(array('success' => true, 'names' => $rows));
    } else {
        echo camm_JEncode(array('failure' => true, 'error' => "SYSLOG component disabled"));
    }
}

function camm_get_menutree() {
    global $cacti_camm_components;

    $rezult = 1;

    /* ================= input validation ================= */

    camm_input_validate_input_regex(stripslashes(get_request_var_request("type")), "^snmptt|syslog$");

    /* ==================================================== */

    //error checking
    if (is_error_message()) {
        $rezult = "Input validation error.";
    }

    $type_tree = (string) (isset($_POST['type']) ? $_POST['type'] : "snmptt");

    //business logic
    if ($rezult == 1) {
        if ($cacti_camm_components[$type_tree]) {

            $test_tree = array();
            $lv_2_id = 0;
            $lv_3_id = 0;
            $j = 0;

            $tree_lists = db_fetch_assoc("SELECT * FROM plugin_camm_tree WHERE `type`='" . $type_tree . "' order by device_type_name asc, description, eventname ");

            $test_tree[0] = array("text" => $type_tree, "id" => "root", "expanded" => "true", "cls" => "folder", "children" => array());
            if (sizeof($tree_lists) > 0) {
                foreach ($tree_lists as $tree_list) {
                    $tree_list = $tree_lists[$j];
                    if (isset($tree_list["description"])) {
                        $name_leaf = "Host: " . addslashes($tree_list["description"]);
                    } else {
                        $name_leaf = "IP:" . addslashes($tree_list["hostname"]);
                    }

                    if (isset($tree_list["device_type_name"])) {
                        //use old device_type_name leaf
                        if ((isset($tree_lists[$j - 1]["device_type_name"])) && ($tree_lists[$j - 1]["device_type_name"] == $tree_list["device_type_name"])) {
                            if ($tree_lists[$j - 1]["hostname"] == $tree_list["hostname"]) {
                                $test_tree[0]["children"][$lv_2_id - 1]["children"][$lv_3_id - 1]["children"][] = array("text" => addslashes($tree_list["eventname"]), "id" => "evn-" . $tree_list["id"], "leaf" => true);
                            } else {
                                $test_tree[0]["children"][$lv_2_id - 1]["children"][$lv_3_id] = array("text" => $name_leaf, "id" => "host-" . $tree_list["id"], "cls" => "folder", "children" => array());
                                $lv_3_id++;
                                $test_tree[0]["children"][$lv_2_id - 1]["children"][$lv_3_id - 1]["children"][] = array("text" => addslashes($tree_list["eventname"]), "id" => "evn-" . $tree_list["id"], "leaf" => true);
                            }
                        } else {
                            //create new device_type_name leaf
                            $test_tree[0]["children"][$lv_2_id] = array("text" => "Type: " . addslashes($tree_list["device_type_name"]), "id" => "typ-" . $tree_list["id"], "cls" => "folder", "children" => array());
                            $lv_3_id = 0;
                            $test_tree[0]["children"][$lv_2_id]["children"][$lv_3_id] = array("text" => $name_leaf, "id" => "host-" . $tree_list["id"], "cls" => "folder", "children" => array());

                            $test_tree[0]["children"][$lv_2_id]["children"][$lv_3_id]["children"][] = array("text" => addslashes($tree_list["eventname"]), "id" => "evn-" . $tree_list["id"], "leaf" => true);
                            $lv_2_id++;
                            $lv_3_id++;
                        }
                    } else {
                        if ((isset($tree_lists[$j - 1]["hostname"])) && ($tree_lists[$j - 1]["hostname"] == $tree_list["hostname"])) {
                            $test_tree[0]["children"][$lv_2_id - 1]["children"][$lv_3_id] = array("text" => "Event: " . addslashes($tree_list["eventname"]), "id" => "evn-" . $tree_list["id"], "leaf" => "true");
                            $lv_3_id++;
                        } else {
                            //создание заголовка для ИП
                            $test_tree[0]["children"][$lv_2_id] = array("text" => $name_leaf, "id" => "host-" . $tree_list["id"], "cls" => "folder", "children" => array());
                            $lv_3_id = 0;
                            $test_tree[0]["children"][$lv_2_id]["children"][$lv_3_id] = array("text" => "Event: " . addslashes($tree_list["eventname"]), "id" => "evn-" . $tree_list["id"], "leaf" => "true");
                            $lv_2_id++;
                        }
                    }
                    $j++;
                }
            }
        } else {
            $rezult = $type_tree . " component disabled.";
        }
    }

    //output
    if ($rezult == 1) {
        echo camm_JEncode(array('success' => true, 'data' => $test_tree));
    } else {
        echo camm_JEncode(array('failure' => true, 'error' => $rezult));
    }
}

?>
