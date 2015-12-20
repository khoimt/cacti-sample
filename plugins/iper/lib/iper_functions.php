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
 
 define("SNMP_METHOD_PHP_SET", 1);
 define("SNMP_METHOD_BINARY_SET", 2);
 
 include_once($config["base_path"] . "/lib/poller.php");
 
 /**
  * The RegEx validation class .
  *
  * Usage:
  * if (!RegEx::isValid($expression)) {
  *    echo 'Your regular expression is invalid because: ' . RegEx::error();
  * }
  */
 class RegEx {
     /**
      * Validates a regular expression. Returns TRUE
      * if the expression is valid, FALSE if not. If
      * the expression is not valid, the reason why
      * can be fetched from RegEx::error().
      *
      * @access public
      * @static
      * @param string $regex Regular Expression
      * @return bool
      */
     function isValid($regex)
     {
         RegEx::error(FALSE);
        
         set_error_handler(array('RegEx', 'errorHandler'));
         preg_match($regex, '');
         restore_error_handler();
        
         return (RegEx::error() === FALSE) ? TRUE : FALSE;
     }
    
     /**
      * Error handler for RegEx. Used internally by RegEx::validate()
      *
      * @access package
      * @static
      * @param int $code Error Code
      * @param string $message Error Message
      */
     function errorHandler($code, $message)
     {
         // Cuts off the 'preg_match(): ' part of the error message.
         $error = substr($message, 14);
        
         // Sets the error flag with the message.
         RegEx::error($error);
     }
    
     /**
      * Holds the error from the last validation check.
      *
      * The first parameter is used internally and should
      * not be used by the developer.
      *
      * @access public
      * @static
      * @param FALSE|string $value value to set for $flag
      * @return FALSE|string
      */
     function error($value = NULL)
     {
         static $flag = FALSE;
         if (!is_null($value))
         {
             $flag = $value;
         }
         return $flag;
     }
 }
 
 /*	valid_snmp_device - This function validates that the device is reachable via snmp.
   It first attempts	to utilize the default snmp readstring.  If it's not valid, it
   attempts to find the correct read string and then updates several system
   information variable. it returns the status	of the host (up=true, down=false)
 */
 /* we must use an apostrophe to escape community names under Unix in case the user uses
 characters that the shell might interpret. the ucd-snmp binaries on Windows flip out when
 you do this, but are perfectly happy with a quotation mark. */
 if ($config["cacti_server_os"] == "unix") {
 	define("SNMP_SET_ESCAPE_CHARACTER", "'");
 }else{
 	define("SNMP_SET_ESCAPE_CHARACTER", "\"");
 }
 
 $cacti_camm_components["snmptt"]=(read_config_option("camm_use_snmptt", true)==1 ? true : false);
 $cacti_camm_components["syslog"]=(read_config_option("camm_use_syslog", true)==1 ? true : false);
 $camm_debug = (read_config_option("camm_debug_mode", true)==1 ? true : false);
 
 
 if (phpversion () < "5"){ // define PHP5 functions if server uses PHP4
 
 function str_split($text, $split = 1)
 {
 if (!is_string($text)) return false;
 if (!is_numeric($split) && $split < 1) return false;
 $len = strlen($text);
 $array = array();
 $s = 0;
 $e=$split;
 while ($s <$len)
     {
         $e=($e <$len)?$e:$len;
         $array[] = substr($text, $s,$e);
         $s = $s+$e;
     }
 return $array;
 }
 }
 if (! function_exists("array_fill_keys")) {
 	function array_fill_keys($array, $values) {
 	    if(is_array($array)) {
 	        foreach($array as $key => $value) {
 	            $arraydisplay[$array[$key]] = $values;
 	        }
 	    }
 	    return $arraydisplay;
 	} 
 }
 
 function camm_debug($message) {
 	global $camm_debug;
 
 	if ($camm_debug) {
 		//print("camm_DEBUG (" . date("H:i:s") . "): [" . $message . "]\n<br>");
 	}
 	
 
 	if (($camm_debug) || (substr_count($message, "ERROR:"))) {
 		cacti_log($message, false, "camm");
 	}
 }
 
 
 function camm_raise_message3($args) {
 	
 	if (count($args) > 0){
 		if (!isset($args["mes_id"])) {
 			if (isset($_SESSION["camm_output_messages"])) {
 		        $mes_id = count($_SESSION["camm_output_messages"]) + 1;
 		    }else{
 			$mes_id = 1;
 			}	
 		}else{
 			$mes_id = $args["mes_id"];
 		}
 		foreach($args as $arg => $value) {
 			$_SESSION["camm_output_messages"][$mes_id][$arg] = $value;
 		}
 	}
 return $mes_id;
 }
 
 function camm_format_filesize( $data ) {
 
 	// bytes
 	if( $data < 1024 ) {
 	
 		return $data . " bytes";
 	
 	}
 	// kilobytes
 	else if( $data < 1024000 ) {
 	
 		return round( ( $data / 1024 ), 1 ) . " KB";
 	
 	}
 	// megabytes
 	else {
 	
 		return round( ( $data / 1024000 ), 1 ) . " MB";
 	
 	}
 }
     
 	
 function camm_format_datediff( $startdata, $enddata = null) {
 $rezult = "";
 if ($enddata == null) {
 	$enddata = strtotime("now");
 }
 	$dateDiff = $enddata - $startdata;
 	$fullDays = floor($dateDiff/(60*60*24));
 	$fullHours = floor(($dateDiff-($fullDays*60*60*24))/(60*60));
 	$fullMinutes = floor(($dateDiff-($fullDays*60*60*24)-($fullHours*60*60))/60); 
 	$fullSeconds = floor($dateDiff-($fullDays*60*60*24)-($fullHours*60*60)-($fullMinutes*60));
 
 	$rezult =  ($fullDays>0 ? $fullDays . "d ": "") . ($fullHours>0 ? $fullHours . "h ": "") . ($fullMinutes>0 ? $fullMinutes . "m ": "") . ($fullSeconds>0 ? $fullSeconds . "s ": "") . "ago";
 
 return $rezult;
 
 }	
  
 	
 function camm_poller_recreate_tree($tree_type = "") {
 global $cacti_camm_components;
 
 	$rezult = "0";
 	
 	if (strlen(trim($tree_type)) > 0) {
 		switch ($tree_type) {
 		case "snmptt":
 			if ($cacti_camm_components["snmptt"]) {
 				$arr[]="snmptt";
 			}else{
 				$rezult = "Can't recreate syslog tree until syslog not used";
 				camm_debug("  Error: Can't recreate syslog tree until syslog not used\n");
 			}
 			break;
 		case "syslog":
 			if ($cacti_camm_components["syslog"]) {
 				$arr[]="syslog";
 			}else{
 				$rezult = "Can't recreate syslog tree until syslog not used";
 				camm_debug("  Error: Can't recreate syslog tree until syslog not used\n");
 			}
 			break;
 		default:
 			if ($cacti_camm_components["syslog"]) {
 				$arr[] ="syslog";
 			}
 			if ($cacti_camm_components["snmptt"]) {
 				$arr[]="snmptt";
 			}			
 		}
 	}else{
 		if ($cacti_camm_components["syslog"]) {
 			$arr[] ="syslog";
 		}
 		if ($cacti_camm_components["snmptt"]) {
 			$arr[]="snmptt";
 		}	
 	}
 	
 	if (read_config_option("camm_join_field") == "sourceip") {
 		$join_field = "agentip_source";
 	}else{
 		$join_field = "hostname";		
 	}
 	
 	if 	(sizeof($arr) > 0) {
 	
 		foreach ($arr as $tree_type) {
 			list($micro,$seconds) = split(" ", microtime());
 			$start = $seconds + $micro;
 			
 
 			
 			db_execute("DELETE FROM `plugin_camm_tree` WHERE `type`='t_" . $tree_type . "';");
 				
 			if ($tree_type == 'snmptt') {
 				db_execute("INSERT INTO `plugin_camm_tree` (`hostname`,`eventname`,`type`,`agentip_source`,`count`)
 					SELECT  `plugin_camm_snmptt`.`hostname`, `eventname`, 't_snmptt',`agentip`,count(*) FROM `plugin_camm_snmptt`
 					GROUP BY `hostname`, `eventname`");
 			}else{
 				db_execute("INSERT INTO `plugin_camm_tree` (`hostname`,`eventname`,`type`,`agentip_source`,`count`)
 					SELECT  `sysl`.`host`, `sysl`.`facility`, 't_syslog',`sysl`.`sourceip`,count(*) FROM `" . read_config_option("camm_syslog_db_name") . "`.`plugin_camm_syslog` as sysl
 					GROUP BY `host`, `facility`");
 			}
 
 			db_execute("UPDATE `plugin_camm_tree` SET `online`='0' WHERE `type`='" . $tree_type . "'");	
 			
 			db_execute("INSERT INTO `plugin_camm_tree` (`hostname`,`eventname`,`type`,`agentip_source`,`count`,`online`)
 				SELECT  `hostname`, `eventname`, '" . $tree_type . "',`agentip_source`,`count`, '1' FROM `plugin_camm_tree` where `type`='t_" . $tree_type . "' " .
 				"ON DUPLICATE KEY UPDATE " .
 				" `plugin_camm_tree`.`hostname` = values(`hostname`), " .
 				" `plugin_camm_tree`.`eventname` = values(`eventname`), " .
 				" `plugin_camm_tree`.`type` = '" . $tree_type . "', " .
 				" `plugin_camm_tree`.`agentip_source` = values(`agentip_source`), " .
 				" `plugin_camm_tree`.`count` = values(`count`), " .
 				" `plugin_camm_tree`.`online` = '1' ;");
 			
 			db_execute("DELETE FROM plugin_camm_tree WHERE `type`='" . $tree_type . "' and online='0';");	
 			db_execute("DELETE FROM `plugin_camm_tree` WHERE `type`='t_" . $tree_type . "';");
 
 			db_execute("UPDATE `plugin_camm_tree`,`host` SET " .
 				" `plugin_camm_tree`.`host_template_id`=`host`.`host_template_id`, " .
 				" `plugin_camm_tree`.`description`=`host`.`description` " .
 				" WHERE (`plugin_camm_tree`.`" . $join_field . "`=`host`.`hostname`) AND `type`='" . $tree_type . "'");	
 
 			db_execute("UPDATE `plugin_camm_tree`,`host_template` SET " .
 				" `plugin_camm_tree`.`device_type_name`=`host_template`.`name` " .
 				" WHERE (`plugin_camm_tree`.`host_template_id`=`host_template`.`id`) AND `type`='" . $tree_type . "'");				
 
 			db_execute("UPDATE `plugin_camm_tree` SET " .
 				" `plugin_camm_tree`.`agentip`= inet_aton(`plugin_camm_tree`.`agentip_source`)  " .
 				" WHERE `type`='" . $tree_type . "'");	
 			
 			db_execute("REPLACE INTO settings (name, value) VALUES ('camm_last_" . $tree_type . "treedb_time', '" . strtotime("now") . "')");
 
 			list($micro,$seconds) = split(" ", microtime());
 			$end = $seconds + $micro;
 			$camm_stats_tree = sprintf(
 			"Tree" . $tree_type . "Time:%01.4f " ,
 			round($end-$start,4));
 				/* log to the database */
 			db_execute("REPLACE INTO settings (name,value) VALUES ('camm_stats_" . $tree_type . "_tree', '" . $camm_stats_tree . "')");
 			
 			$rezult = 1;
 		}
 	}
 	return $rezult;
 
 }
 
 
 
 function camm_sendemail($to, $from, $subject, $message) {
 	
 	if (camm_check_dependencies()) {
 		camm_debug("  Sending Alert email to '" . $to . "'\n");
 		send_mail($to, $from, $subject, $message);
 	} else {
 		camm_debug("  Error: Could not send alert, you are missing the Settings plugin\n");
 	}
 }
 
 function camm_check_regexp ($regexp) {
 $RegEx = new RegEx;
 
  $rezult = array();
  $expression = preg_quote($regexp);
  $rezult["rezult"] = $RegEx->isValid("/" . $expression . "/");
  if (!$rezult["rezult"]) {
     $rezult["error"] = 'Your regular expression is invalid because: ' . $RegEx->error();
  }else{
 	$rezult["regexp"] = $expression;
  }
  return $rezult;
 }
 
 
 
 /**
  * @param string $function_name The user function name, as a string.
  * @return Returns TRUE if function_name  exists and is a function, FALSE otherwise.
  */
 function camm_user_func_exists($function_name = 'do_action') {
   
     $func = get_defined_functions();
   
     $user_func = array_flip($func['user']);
   
     unset($func);
   
     return ( isset($user_func[$function_name]) );  
 }
 
 
 function is_camm_admin () {
 global $user_auth_realm_filenames;
 $rezult = 0;
 //get camm Plugin -> camm: Manage Realm ID
 $camm_admin_realm_id = db_fetch_cell("SELECT `id`+100 FROM `plugin_realms` where `display` like 'plugin%camm%manage%'");
 
 if (isset($camm_admin_realm_id)) {
 	//Check this user - for camm admin realms
 	if ((!empty($camm_admin_realm_id)) && (db_fetch_assoc("select user_auth_realm.realm_id from user_auth_realm
 		where user_auth_realm.user_id='" . $_SESSION["sess_user_id"] . "'
 		and user_auth_realm.realm_id='$camm_admin_realm_id'"))) {
 			$rezult = 1;
 		}	
 }
 
 return $rezult;
 
 }
 
 function camm_JEncode($arr){
 global $config;
     
 	if (function_exists('json_encode')) {
          $data = json_encode($arr);  //encode the data in json format
 		 
     }else{
 		require_once($config["base_path"] . "/plugins/camm/lib/JSON.php"); //if php<5.2 need JSON class
         $json = new Services_JSON();//instantiate new json object
         $data=$json->encode($arr);  //encode the data in json format	
 	}
 		
     return $data;
 }
 
 function camm_JDecode($arr){
 global $config;
     
 	if (function_exists('json_decode')) {
          $data = json_decode($arr);  //encode the data in json format
 		 
     }else{
         require_once($config["base_path"] . "/plugins/camm/lib/JSON.php"); //if php<5.2 need JSON class
         $json = new Services_JSON();//instantiate new json object
         $data=$json->decode(stripslashes($arr));  //encode the data in json format	
 	}
 		
     return $data;
 }
 
 
 function get_graph_camm_url ($sql_like_graph) {
 
 $rezult = 0;
 //get graph template ID
 $graph_template_id = db_fetch_cell("SELECT `id` FROM `graph_templates` where `name` like '" . $sql_like_graph . "'");
 
 if ((isset($graph_template_id)) && ($graph_template_id>0)) {
 	//Now find Graph ID
 	$graph_id = db_fetch_cell("SELECT `id` FROM `graph_local` where `graph_template_id`='" . $graph_template_id . "'");
 	
 	if ((isset($graph_id)) && ($graph_id>0)) {
 		$rezult = $graph_id;
 	}
 }
 
 return $rezult;
 
 }
 
 function camm_input_validate_input_regex($value, $regex, $error_msg = '') {
 	if ((!ereg($regex, $value)) && ($value != "")) {
 		camm_die_html_input_error($error_msg);
 	}
 }
 
 function camm_die_html_input_error($error_msg) {
 	echo json_encode(array('failure' => true,'error' => $error_msg));
 	exit;
 }
 
 function camm_process_rule ($rule, $force = false) {
 //camm_debug("  - Found " . $stat_ruleDeleTraps . " new trap" . ($stat_ruleDeleTraps == 1 ? "" : "s" ) . " to process");
 camm_debug(" = Start process rule id=[" . $rule["id"] . "]");
 
 //$rule = db_fetch_row("SELECT * FROM `plugin_camm_rule` where `rule_enable`=1 AND `id`='" . $rule_id . "';");
 $rezult = 1;
 
 	$sql_where = '';
 	$alertm = '';
 
 	if ($force) {
 		$sql_force = " `status`=2";
 		camm_debug("   - Process already processed record (force execute)");
 	}else{
 		$sql_force = " `status`=1";
 		camm_debug("   - Process only new records");
 	}
 	
 	$sql_where = getSQL(camm_JDecode(stripslashes($rule["json_filter"])));
 	$sql_where .= " AND " . $sql_force;
 
 	if ($rule["rule_type"] == 'snmptt') {
 		$table = '`plugin_camm_snmptt`';
 		$col_alert = '`alert`';
 		$col_message = 'formatline';
 	}elseif($rule["rule_type"] == 'syslog') {
 		if ((strlen(trim(read_config_option("camm_syslog_pretable_name"))) > 0) && (read_config_option("camm_syslog_pretable_name") != "plugin_camm_syslog") && ($force == false)) {
 			$syslog_use_pretable = true;
 			$table = '`' . read_config_option("camm_syslog_db_name") . '`.`' . read_config_option("camm_syslog_pretable_name") . '`';
 		}else{
 			$$syslog_use_pretable = false;
 			$table = '`' . read_config_option("camm_syslog_db_name") . '`.`plugin_camm_syslog`';
 		}
 		$col_alert = '`alert`';
 		$col_message = 'message';
 	}else{
 		$rezult = "Incorrect rule type";
 	}
 	camm_debug("   - SQL where conditions=[" . $sql_where . "]");
 
 	if (($sql_where != '') || ($rezult == 1)) {
 		db_execute("UPDATE " . $table . " SET " . $col_alert . "='" . $rule["id"] . "' WHERE " . $sql_where);
 		$traps_updated=mysql_affected_rows();
 		camm_debug("   - Found count records==[" . $traps_updated . "]");
 		if ($traps_updated > 0) {
 			db_execute("UPDATE `plugin_camm_rule` SET `count_triggered`=`count_triggered`+'" . $traps_updated . "' WHERE id='" . $rule["id"] .  "';");
 		}
 		
 		if (($rule["is_function"]=="1") || ($rule["is_email"]=="1")) { //execute user functions
 			$alerted_rows = db_fetch_assoc("SELECT *  FROM " . $table . " WHERE " . $col_alert . "='" . $rule["id"] . "' AND " . $sql_force . ";");
 		}else{
 			$alerted_rows = array();
 		}
 		
 		
 		if ($rule["is_function"]=="1") { //execute user functions
 		
 			if (strlen(trim($rule["function_name"])) > 0) {
 				if (function_exists($rule["function_name"])) {
 					if (sizeof($alerted_rows) > 0) {
 						call_user_func_array($rule["function_name"], array($alerted_rows, $rule));
 						camm_debug("   -1 Execute user function [" . $rule["function_name"] . "]");
 					}
 				}
 			}
 		}
 		if ($rule["is_email"]=="1") { //email alert rule
 			if (sizeof($alerted_rows) > 0) {
	 				camm_debug("   Alert Rule '" . $rule['name'] . "' - Email Mode - has been activated\n");
					foreach ($alerted_rows as $alerted_trap) {
	 					$alerted_trap[$col_message] = str_replace('  ', "\n", $alerted_trap[$col_message]);
	 					while (substr($alerted_trap[$col_message], -1) == "\n") {
	 						$alerted_trap[$col_message] = substr($alerted_trap[$col_message], 0, -1);
	 					}

						if ($rule["rule_type"] == 'snmptt') {
							$alertm .= "-----NEW---SNMP---TRAP-------------------------\n";
							$alertm .= 'Hostname  : ' . $alerted_trap['hostname'] . "\n";
							$alertm .= 'Date      : ' . $alerted_trap['traptime'] . "\n";
							$alertm .= 'EventName : ' . $alerted_trap['eventname'] . "\n";
							$alertm .= 'TrapOid   : ' . $alerted_trap['trapoid'] . "\n";
							$alertm .= 'Category  : ' . $alerted_trap['category'] . "\n";
							$alertm .= 'Severity  : ' . $alerted_trap['severity'] . "\n";
							$alertm .= 'Trap Message  : ' . $alerted_trap['formatline'] . "\n";
							$alertm .= 'Notes	  : ' . $rule['email_message'] . "\n";
							$alertm .= "-----------------------------------------------\n\n";
						}elseif ($rule["rule_type"] == 'syslog') {
							$alertm .= "-----NEW---SYSLOG---MESSAGE--------------------\n";
							$alertm .= 'Host  : ' . $alerted_trap['host'] . "\n";
							$alertm .= 'Date      : ' . $alerted_trap['sys_date'] . "\n";
							$alertm .= 'SourceIP : ' . $alerted_trap['sourceip'] . "\n";
							$alertm .= 'Facility   : ' . $alerted_trap['facility'] . "\n";
							$alertm .= 'Priority  : ' . $alerted_trap['priority'] . "\n";
							$alertm .= 'Syslog Message  : ' . $alerted_trap['message'] . "\n";
							$alertm .= 'Notes   :'  . $rule['email_message'] . "\n";
							$alertm .= "-----------------------------------------------\n\n";				
						} 
						
						//each record in separate email message
						if ($rule["email_mode"]=="2"){
				 			if ($alertm != '') {
				 				camm_sendemail($rule['email'], '', 'Event Alert - ' . $rule['name'], $alertm);
				 				camm_debug($rule['email'] . "   " . 'Event Alert - ' . "   " . $rule['name'] . "   " . $alertm);
				 			}						
							$alertm = '';
						}

	 				}
					
		 			if ($alertm != '') {
		 				camm_sendemail($rule['email'], '', 'Event Alert - ' . $rule['name'], $alertm);
		 				camm_debug($rule['email'] . "   " . 'Event Alert - ' . "   " . $rule['name'] . "   " . $alertm);
		 			}
 			}

 		}
 		if ($rule["is_delete"]=="1") { //mark traps rule
 			db_execute("DELETE FROM " . $table . " WHERE " . $col_alert . "='" . $rule["id"] . "' AND " . $sql_force . ";");
 			camm_debug("   -3 Delete records.");
 		}elseif ($rule["is_mark"]=="1") { //mark traps rule
 			db_execute("UPDATE " . $table . " SET " . $col_alert . "='" . $rule["marker"] . "' WHERE " . $col_alert . "='" . $rule["id"] . "' AND " . $sql_force . ";");
 			camm_debug("   -4 Mark records [" . $rule["marker"] . "]");
 		}
 		
 	}
 
 return $rezult;
 }
 
 
 
 ?>
