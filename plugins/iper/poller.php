<?php
 /*
  +-------------------------------------------------------------------------+
  | Copyright (C) 2008 Susanin                                          |
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
 
 /*******************************************************************************
 
     Author ......... Susanin (gthe in forum.cacti.net)
     Program ........ camm viewer for cacti
     Version ........ 0.0.08b
 
 *******************************************************************************/
 
 /* do NOT run this script through a web browser */
 if (!isset($_SERVER["argv"][0])) {
 //	die("<br><strong>This script is only meant to run at the command line.</strong>");
 }
 
 /* We are not talking to the browser */
 $no_http_headers = true;
 
 $dir = dirname(__FILE__);
 chdir($dir);
 
 if (strpos($dir, 'camm') !== false) {
 	chdir('../../');
 }
 
 /* Start Initialization Section */
 include("./include/global.php"); 
 include_once($config["base_path"] . "/plugins/iper/lib/iper_functions.php");
 include_once($config["base_path"] . "/plugins/iper/lib/json_sql.php");
 include_once($config["base_path"] . "/plugins/iper/lib/iper_user_func.php");  
 
 //$Regtt = new RegEx;
 // $trez = $Regtt->isValid("/\\[FORM12\\]\\+/");
 // $trez = camm_check_regexp("[FORM12+ +");
 // $trez = camm_check_regexp("FORM: This trap indicates the MAC addresses variation in address table swL2MgmtMIB.100.1.2.1.1:<br>   [type=");
 
 	/* initialize variables */
 
 	/* process calling arguments */
 	$parms = $_SERVER["argv"];
 	array_shift($parms);
 
 	//$camm_debug = FALSE;
 	//$forcerun = FALSE;
 	$forcerun = TRUE;
     
 	foreach($parms as $parameter) {
 		@list($arg, $value) = @explode("=", $parameter);
 
 		switch ($arg) {
 		case "-d":
 			$camm_debug = TRUE;
 			break;
 		case "-h":
 			display_help();
 			exit;
 		case "-f":
 			$forcerun = TRUE;
 			break;
 		case "-v":
 			display_help();
 			exit;
 		case "--version":
 			display_help();
 			exit;
 		case "--help":
 			display_help();
 			exit;
 		default:
 			print "ERROR: Invalid Parameter " . $parameter . "\n\n";
 			display_help();
 			exit;
 		}
 	}
 	if ($camm_debug)  {
 		camm_debug("S0. Found enabled DEBUG mode. Output will be verbose");
 	}
 	camm_debug("S0. About to enter camm poller processing");
 	$seconds_offset = read_config_option("camm_autopurge_timing");
 	
 	process_alerts();
 	
 	if (($seconds_offset <> "disabled") || $forcerun) {
 		camm_debug("S2. Checking to determine if it's time to run AutoPurge process.");
 		$seconds_offset = $seconds_offset * 60;
 		/* find out if it's time to collect device information */
 		//$base_start_time = read_config_option("camm_base_time");
 		$last_run_time = read_config_option("camm_last_run_time", true);
 		//$previous_base_start_time = read_config_option("camm_prev_base_time");
 		
 
 		/* determine the next start time */
 		$current_time = strtotime("now");
 		$next_run_time = $last_run_time + $seconds_offset;
 		$time_till_next_run = $next_run_time - $current_time;
 
 		if ($time_till_next_run < 0) {
 			camm_debug("S2.1 The next AutoPurge process run time has been determined to be NOW");
 		}else{
 			camm_debug("S2.1 The next AutoPurge process run time has been determined to be at '" . date("Y-m-d G:i:s", $next_run_time) . "'. Last run time was '" . date("Y-m-d G:i:s", $last_run_time) . "'");
 		}		
 		
 		if ($time_till_next_run < 0 || $forcerun == TRUE) {
 			camm_debug("S2.2 Either a scan has been forced, or it's time for AutoPurge process");
 			/* take time and log performance data */
 			list($micro,$seconds) = split(" ", microtime());
 			$start = $seconds + $micro;
 
 			purge_camm_records($start);
 			db_execute("REPLACE INTO settings (name, value) VALUES ('camm_last_run_time', '$current_time')");
 				//log_mactrack_statistics("collect");
 		}
 	}
 	
 	camm_debug("S3. Checking to determine if it's time to run AutoCreate Tree Menu.");
 	$seconds_tree_offset = read_config_option("camm_tree_update");
 	$arr_type = array("snmptt", "syslog");
 
 	foreach ($arr_type as $tree_type) {
 		$tree_to_recreate[$tree_type]=false;
 		//process only used components
 		if ($cacti_camm_components[$tree_type]){
 			$last_treedb_run_time = 0;
 			$last_treedb_run_time = read_config_option("camm_last_" . $tree_type . "treedb_time", true);
 			$last_treedb_run_time = ((isset($last_treedb_run_time)) ? $last_treedb_run_time : 0);
 			/* determine the next start time */
 			$next_treedb_run_time = $last_treedb_run_time + $seconds_tree_offset;
 			$time_till_next_treedb_run = $next_treedb_run_time - $current_time;	
 				if ($time_till_next_treedb_run < 0) {
 					camm_debug("S3.1 The next " . $tree_type . " Tree to DB run time has been determined to be NOW");
 				}else{
 					camm_debug("S3.1 The next " . $tree_type . " Tree to DB run time has been determined to be at '" . date("Y-m-d G:i:s", $next_run_time) . "'. Last run time was '" . date("Y-m-d G:i:s", $last_run_time) . "'");
 				}		
 			if ($time_till_next_treedb_run < 0 || $forcerun == TRUE) {
 				$tree_to_recreate[$tree_type]=true;
 			}
 		}
 	}
 	
 	if ($tree_to_recreate["snmptt"] && $tree_to_recreate["syslog"]) {
 		//if need update all trees - may be truncate tree table if they index too mach big
 		$max_tree_key = db_fetch_cell("SELECT max(`id`) FROM `plugin_camm_tree`;");
 		if ($max_tree_key > 1000000) {
 			db_execute("TRUNCATE table `plugin_camm_tree`;");
 			camm_debug("S4. We are update all trees - may be truncate tree table if they index too mach big");
 		}
 	}
 	//and now real recreating tree
 	if ($tree_to_recreate["snmptt"]) {
 		camm_poller_recreate_tree("snmptt");
 	}	
 	if ($tree_to_recreate["syslog"]) {
 		camm_poller_recreate_tree("syslog");
 	}	
 	
 	
 	
 /*	display_help - displays the usage of the function */
 function display_help () {
 	print "camm Process Control Version 1.0, Copyright 2005 - Susanin\n\n";
 	print "usage: poller_camm.php [-d] [-h] [--help] [-v] [--version]\n\n";
 	print "-f            - Force the execution of a purge process\n";
 	print "-d            - Display verbose output during execution\n";
 	print "-v --version  - Display this help message\n";
 	print "-h --help     - display this help message\n";
 }
 
 function purge_camm_records($start) {
 	global $config, $camm_debug, $cacti_camm_components;
 
 	$camm_snmptt_delay_purge_day = read_config_option("camm_snmptt_delay_purge_day");
 	$delete_older_data = db_fetch_cell("SELECT ADDDATE(date(NOW()), INTERVAL -" . $camm_snmptt_delay_purge_day . " DAY);");
 	
 	$purge_sys_delay_days = read_config_option("camm_sys_delay_purge_day");
 	$delete_sys_older_data = db_fetch_cell("SELECT ADDDATE(date(NOW()), INTERVAL -" . $purge_sys_delay_days . " DAY);");
 	
 	$camm_use_syslog = read_config_option("camm_use_syslog");
 	$camm_syslog_db_name = read_config_option("camm_syslog_db_name");
 	
 	$count_del_rows_traps = 0;
 	$count_del_rows_unk_traps = 0;
 	$count_sys_del_rows_traps = 0;
 	
 	if ((read_config_option("camm_snmptt_tables", true) == 1) || (read_config_option("camm_snmptt_tables") == 3)) {
 		$use_traps_table = true;
 	}else{
 		$use_traps_table = false;
 	}
 	
 	if ((read_config_option("camm_snmptt_tables") == 2) || (read_config_option("camm_snmptt_tables") == 3)) {
 		$use_unk_traps_table = true;
 	}else{
 		$use_unk_traps_table = false;
 	}
 	
 	
 	//1.1 Process SNMPTT max per day /per host messages;
 	if ($cacti_camm_components["snmptt"]) {
 		$max_rows_per_dev = read_config_option("camm_snmptt_max_row_per_device",true);
 		if ($max_rows_per_dev > 0) {
 			camm_debug("S2.3 Max row per device in day for snmptt is set to [" . $max_rows_per_dev . "]");
 			if ($use_traps_table) {
 				camm_debug("S2.4 Check SNMPTT table ..");
 				$trap_devices_days=db_fetch_assoc("SELECT hostname, date(`traptime`) as day_noumber, count(*) as count_rows FROM plugin_camm_snmptt
 					where date(`traptime`) < date('" . $delete_older_data . "') 
 					group by hostname, date(`traptime`)
 					HAVING count_rows > " . $max_rows_per_dev . "
 					order by count_rows");
 				if (sizeof($trap_devices_days)) {
 					foreach ($trap_devices_days as $trap_devices_day) {
 						camm_debug("S2.4  - The next count of rows will be deleted from table plugin_camm_snmptt = [" . ($trap_devices_day["count_rows"] - $max_rows_per_dev) . "] for hostname=[" . $trap_devices_day["hostname"]  . "]");
 						db_execute("DELETE FROM `plugin_camm_snmptt`  where (date(`traptime`) = date('" . $trap_devices_day["day_noumber"] . "')) and `hostname`='" . $trap_devices_day["hostname"] . "'  order by `traptime` desc limit " . ($trap_devices_day["count_rows"] - $max_rows_per_dev) . "");				
 						$count_del_rows_traps = $count_del_rows_traps + ($trap_devices_day["count_rows"] - $max_rows_per_dev);
 					}
 				}else{
 					camm_debug("S2.4 No bigger rows found in SNMPTT table ..");
 				}
 			}
 			if ($use_unk_traps_table) {
 				camm_debug("S2.5 Check SNMPTT_UNK table ..");
 				$trap_devices_days=db_fetch_assoc("SELECT hostname, date(`traptime`) as day_noumber, count(*) as count_rows FROM plugin_camm_snmptt_unk
 					where date(`traptime`) < date('" . $delete_older_data . "') 
 					group by hostname, date(`traptime`)
 					HAVING count_rows > " . $max_rows_per_dev . "
 					order by count_rows");
 				if (sizeof($trap_devices_days)) {
 					foreach ($trap_devices_days as $trap_devices_day) {
 						camm_debug("S2.5  - The next count of rows will be deleted from table plugin_camm_snmptt_unk = [" . ($trap_devices_day["count_rows"] - $max_rows_per_dev) . "] for hostname=[" . $trap_devices_day["hostname"]  . "]");
 						db_execute("DELETE FROM `plugin_camm_snmptt_unk`  where (date(`traptime`) = date('" . $trap_devices_day["day_noumber"] . "')) and `hostname`='" . $trap_devices_day["hostname"] . "'  order by `traptime` desc limit " . ($trap_devices_day["count_rows"] - $max_rows_per_dev) . "");				
 						$count_del_rows_unk_traps = $count_del_rows_unk_traps + ($trap_devices_day["count_rows"] - $max_rows_per_dev);
 					}
 				}else{
 					camm_debug("S2.5 No bigger rows found in SNMPTT_UNK table ..");
 				}
 			}		
 		}
 	}
 	
 	//1.2 Process Syslog max per day /per host messages;
 	if ($cacti_camm_components["syslog"]) {
 		$max_sys_rows_per_dev = read_config_option("camm_sys_max_row_per_device",true);
 		if ($max_sys_rows_per_dev > 0) {
 			camm_debug("S2.6 Max row per device in day for syslog is set to [" . $max_sys_rows_per_dev . "]");
 			$str_sql_select = "SELECT host, date(`sys_date`) as day_noumber, count(*) as count_rows FROM `" . $camm_syslog_db_name . "`.`plugin_camm_syslog` " .
 				" where date(`sys_date`) < date('" . $delete_sys_older_data . "') " .
 				" group by host, date(`sys_date`) " .
 				" HAVING count_rows > " . $max_sys_rows_per_dev .
 				" order by count_rows;";
 				
 			$syslog_devices_days=db_fetch_assoc($str_sql_select);
 			if (sizeof($syslog_devices_days)) {
 				foreach ($syslog_devices_days as $syslog_devices_day) {
 					camm_debug("S2.6  - The next count of rows will be deleted from table " . read_config_option("camm_syslog_db_name") . " = [" . ($syslog_devices_day["count_rows"] - $max_sys_rows_per_dev) . "] for host=[" . $syslog_devices_day["host"]  . "]");
 					db_execute("DELETE FROM `" . read_config_option("camm_syslog_db_name") . "`.`plugin_camm_syslog`  where (date(`sys_date`) = date('" . $syslog_devices_day["day_noumber"] . "')) and host='" . $syslog_devices_day["host"] . "' order by `sys_date` desc limit " . ($syslog_devices_day["count_rows"] - $max_sys_rows_per_dev) . "");				
 					$count_sys_del_rows_traps = $count_sys_del_rows_traps + ($syslog_devices_day["count_rows"] - $max_sys_rows_per_dev);
 				}
 			}else{
 				camm_debug("S2.6 No bigger rows found in SYSLOG table ..");
 			}
 		}
 	}
 	
 	
 	//2.1. Process max row in tables
 	$camm_snmptt_max_row_all = read_config_option("camm_snmptt_max_row_all", true);
 	if ($camm_snmptt_max_row_all > 0) {
 		camm_debug("S2.7 Max rowin table snmptt is set to [" . $camm_snmptt_max_row_all . "]");
 		$camm_snmptt_delay_purge_day = read_config_option("camm_snmptt_delay_purge_day");
 		
 		if ($use_traps_table) {
 		$rows_traps_all = db_fetch_cell("SELECT count(*) from `plugin_camm_snmptt` where ( date(`traptime`) < date('" . $delete_older_data . "'))");
 		if ($rows_traps_all > $camm_snmptt_max_row_all) {
 			camm_debug("S2.7 The next count of rows will be deleted from table plugin_camm_snmptt = [" . ($rows_traps_all - $camm_snmptt_max_row_all) . "]");
 			db_execute("DELETE FROM `plugin_camm_snmptt` where ( date(`traptime`) < date('" . $delete_older_data . "')) order by `traptime` desc limit " . ($rows_traps_all - $camm_snmptt_max_row_all) . ";");
 			$count_del_rows_traps = $count_del_rows_traps + ($rows_traps_all - $camm_snmptt_max_row_all);
 			}
 		}
 		if ($use_unk_traps_table) {
 		$rows_unk_traps_all = db_fetch_cell("SELECT count(*) from `plugin_camm_snmptt_unk` where ( date(`traptime`) < date('" . $delete_older_data . "'))");
 			if ($rows_unk_traps_all > $camm_snmptt_max_row_all) {
 				camm_debug("S2.7 The next count of rows will be deleted from table plugin_camm_snmptt_unk = [" . ($rows_unk_traps_all - $camm_snmptt_max_row_all) . "]");
 				db_execute("DELETE FROM `plugin_camm_snmptt_unk`  where ( date(`traptime`) < date('" . $delete_older_data . "')) order by `traptime` desc limit " . ($rows_unk_traps_all - $camm_snmptt_max_row_all) . "");
 				$count_del_rows_unk_traps = $count_del_rows_unk_traps + ($rows_unk_traps_all - $camm_snmptt_max_row_all);
 			}
 		}
 	}
 
 	//2.2. Process max row in syslog table
 	if ($camm_use_syslog == "1") {
 		$camm_sys_max_row_all = read_config_option("camm_sys_max_row_all", true);
 		if ($camm_sys_max_row_all > 0) {
 			camm_debug("S2.8 Max rowin table syslog is set to [" . $camm_sys_max_row_all . "]");
 			$purge_sys_delay_days = read_config_option("camm_sys_delay_purge_day");
 			
 			$rows_syslog_all = db_fetch_cell("SELECT count(*) from `" . $camm_syslog_db_name . "`.`plugin_camm_syslog` where ( date(`sys_date`) < date('" . $delete_sys_older_data . "'))");
 			if ($rows_syslog_all > $camm_sys_max_row_all) {
 				camm_debug("S2.8 The next count of rows will be deleted from table " . $camm_syslog_db_name . " = [" . ($rows_syslog_all - $camm_sys_max_row_all) . "]");
 				db_execute("DELETE FROM `" . $camm_syslog_db_name . "`.`plugin_camm_syslog` where ( date(`sys_date`) < date('" . $delete_sys_older_data . "')) order by `sys_date` desc limit " . ($rows_syslog_all - $camm_sys_max_row_all) . ";");
 				$count_sys_del_rows_traps = $count_sys_del_rows_traps + ($rows_syslog_all - $camm_sys_max_row_all);
 				}
 		}
 	}
 	
 	/* take time and log performance data */
 	list($micro,$seconds) = split(" ", microtime());
 	$end = $seconds + $micro;
 	
 	$camm_stats = sprintf(
 		"Time:%01.4f " ,
 		round($end-$start,4));
 
 	/* log to the database */
 	db_execute("REPLACE INTO settings (name,value) VALUES ('camm_stats_time', '" . $camm_stats . "')");
 
 	$camm_stats = sprintf(
 		"del_traps:%s " .
 		"del_unk_traps:%s " .
 		"del_sys_messages:%s ",
 		$count_del_rows_traps,
 		$count_del_rows_unk_traps,
 		$count_sys_del_rows_traps);
 
 	/* log to the database */
 	db_execute("REPLACE INTO settings (name,value) VALUES ('camm_stats', '" . $camm_stats . "')");
 	
 	/* log to the logfile */
 	cacti_log("camm STATS: " . $camm_stats ,true,"SYSTEM");	
 	
 }	
 
 
 
 function process_alerts() {
 global $cacti_camm_components;
 
 	$alerts = db_fetch_assoc("SELECT * FROM `plugin_camm_rule` where `rule_enable`=1 ORDER BY `is_delete` DESC, `count_triggered` DESC;");
 	//$use_syslog = (read_config_option("camm_use_syslog") == "1");
 	$camm_syslog_db_name = read_config_option("camm_syslog_db_name");
 
 	camm_debug("S1. Found " . sizeof($alerts) . " camm rule" . (sizeof($alerts) == 1 ? "" : "s" ) . " to process");
 	
 	/* FLAG ALL THE CURRENT ITEMS TO WORK WITH */
 	if ($cacti_camm_components["snmptt"]) {
 		camm_debug("S1.1 Use SNMPTT component");
 		db_execute("UPDATE `plugin_camm_snmptt` set status=1 where status=0");
 		$stat_ruleDeleTraps=mysql_affected_rows();
 	}else{
 		$stat_ruleDeleTraps = 0;
 	}
 	
 	if ($cacti_camm_components["syslog"]) {
 		camm_debug("S1.1 Use SYSLOG component");
 		if ((strlen(trim(read_config_option("camm_syslog_pretable_name"))) > 0) && (read_config_option("camm_syslog_pretable_name") != "plugin_camm_syslog")) {
 			$syslog_use_pretable = true;
 			$syslog_table = '`' . read_config_option("camm_syslog_db_name") . '`.`' . read_config_option("camm_syslog_pretable_name") . '`';
 		}else{
 			$syslog_use_pretable = false;
 			$syslog_table = '`' . read_config_option("camm_syslog_db_name") . '`.`plugin_camm_syslog`';
 		}
 		db_execute("UPDATE " . $syslog_table . " set status=1 where status=0");
 		$stat_ruleDeleSys=mysql_affected_rows();
 	}else{
 		$stat_ruleDeleSys = 0;
 	}
 	
 	
 	if (sizeof($alerts) > 0) {
 		camm_debug("S1.2 Found " . $stat_ruleDeleTraps . " new trap" . ($stat_ruleDeleTraps == 1 ? "" : "s" ) . " to process");
 		camm_debug("S1.2 Found " . $stat_ruleDeleSys . " new syslog message" . ($stat_ruleDeleSys == 1 ? "" : "s" ) . " to process");
 		if (($stat_ruleDeleTraps > 0) || ($stat_ruleDeleSys > 0)) {
 			foreach ($alerts as $alert) {
 				if ($cacti_camm_components[$alert["rule_type"]]) {
 					$rule_rezult=camm_process_rule($alert);
 				}
 			}
 		}else{
 			camm_debug("S1.2 No new records to process rules");
 		}
 	}else{
 		camm_debug("S1.2 No enabled rules found");
 	}
 	
 
 	if ($cacti_camm_components["snmptt"]) {
 		$stat_ruleDeleTraps_end = db_fetch_cell("select count(*) FROM `plugin_camm_snmptt` where status=1");
 		$camm_stats = sprintf(
 			"%s" ,
 			round($stat_ruleDeleTraps-$stat_ruleDeleTraps_end,4));
 		db_execute("REPLACE INTO settings (name,value) VALUES ('camm_stats_ruledel_snmptt', '" . $camm_stats . "')");
 		db_execute("UPDATE `plugin_camm_snmptt` set status=2 where status=1");	
 	}
 	
 	if ($cacti_camm_components["syslog"]) {
 		$stat_ruleDeleSys_end = db_fetch_cell("select count(*) FROM " . $syslog_table . " where `status`=1");
 		
 		$camm_stats = sprintf(
 			"%s" ,
 			round($stat_ruleDeleSys-$stat_ruleDeleSys_end,4));
 		db_execute("REPLACE INTO settings (name,value) VALUES ('camm_stats_ruledel_syslog', '" . $camm_stats . "')");	
 		db_execute("UPDATE " . $syslog_table . " set status=2 where status=1");	
 		
 		//IF use syslog pre table - need copy processed message to main table
 		if ($syslog_use_pretable) {
 			camm_debug("S1.3 Use syslog pre table. Move processed records to main syslog table");
 			// db_execute("INSERT INTO `" . read_config_option("camm_syslog_db_name") . "`.`plugin_camm_syslog` " .
 						// "SELECT * FROM " . $syslog_table . " WHERE status=2");
 			db_execute("INSERT INTO `" . read_config_option("camm_syslog_db_name") . "`.`plugin_camm_syslog` (`host`, `sourceip`, `facility`, `priority`, `sys_date`, `message`, `status`, `alert`) " .
 						"SELECT `host`, `sourceip`, `facility`, `priority`, `sys_date`, `message`, `status`, `alert` FROM " . $syslog_table . " WHERE status=2");						
 			db_execute("DELETE FROM " . $syslog_table . " where `status`=2");
 										 
 		}
 		
 	}
 }
 
 
 
 
 
 ?>
