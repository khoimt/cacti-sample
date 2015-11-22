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
 function plugin_init_camm() {
 
     global $plugin_hooks;
     $plugin_hooks['config_arrays']['camm'] = 'camm_config_arrays'; 
     $plugin_hooks['config_settings']['camm'] = 'camm_config_settings'; // Settings tab
     $plugin_hooks['top_header_tabs']['camm'] = 'plugin_camm_show_tab'; // Top tab
     $plugin_hooks['top_graph_header_tabs']['camm'] = 'plugin_camm_show_tab'; // Top tab for graphs
     $plugin_hooks['draw_navigation_text']['camm'] = 'camm_draw_navigation_text';
 	$plugin_hooks['poller_top']['camm'] = 'camm_poller_bottom';
 
 }
 
 function plugin_camm_install () {
 
     api_plugin_register_hook('camm', 'top_header_tabs', 'plugin_camm_show_tab', 'includes/tab.php');
     api_plugin_register_hook('camm', 'top_graph_header_tabs', 'plugin_camm_show_tab', 'includes/tab.php');
     api_plugin_register_hook('camm', 'config_arrays', 'camm_config_arrays', 'setup.php');
     api_plugin_register_hook('camm', 'config_settings', 'camm_config_settings', 'setup.php');
     api_plugin_register_hook('camm', 'draw_navigation_text', 'camm_draw_navigation_text', 'setup.php');
     api_plugin_register_hook('camm', 'poller_bottom', 'camm_poller_bottom', 'setup.php');
 	api_plugin_register_hook('camm', 'page_title', 'camm_page_title', 'setup.php');
 	
 
     //Check - if this is a upgrade from snmptt plugin
 	$old_snmp_tt_realm = db_fetch_cell("SELECT count(*) FROM `plugin_realms` where `plugin`='snmptt';");
 	$new_camm_realm = db_fetch_cell("SELECT count(*) FROM `plugin_realms` where `plugin`='camm';");
 	if (($old_snmp_tt_realm > 0) && ($new_camm_realm == 0)){
 		db_execute("UPDATE `plugin_realms` SET `plugin`='camm', `file`='camm_view.php,camm_db.php', `display`='Plugin -> camm: View' WHERE `plugin`='snmptt' and `file`='snmptt_view.php,snmptt_db.php';");
 		db_execute("UPDATE `plugin_realms` SET `plugin`='camm', `file`='camm_db_admin.php', `display`='Plugin -> camm: Manage' WHERE `plugin`='snmptt' and `file`='snmptt_db_admin.php';");
 		
 		camm_raise_message3(array("device_descr" => "Upgrade from SNMPTT plugin" , "type" => "upgrade_db", "object"=> "update","cellpading" => false, "message" => "upgrade realms from snmptt plugin", "step_rezult" => "OK", "step_data" => "OK"));     
 	}else{	
 		api_plugin_register_realm('camm', 'camm_view.php,camm_db.php', 'Plugin -> camm: View', 1);
 		api_plugin_register_realm('camm', 'camm_db_admin.php', 'Plugin -> camm: Manage', 1);
 	}
 	
 	camm_setup_table ();
 
 }
 
 function plugin_camm_show_tab () {
 
 	global $config, $user_auth_realm_filenames;
 	$realm_id2 = 0;
 
 	if (isset($user_auth_realm_filenames{basename('camm_view.php')})) {
 		$realm_id2 = $user_auth_realm_filenames{basename('camm_view.php')};
 	}
 	
 	if ((db_fetch_assoc("select user_auth_realm.realm_id
 		from user_auth_realm where user_auth_realm.user_id='" . $_SESSION["sess_user_id"]
 		. "'and user_auth_realm.realm_id='$realm_id2'")) || (empty($realm_id2))) {
 		print '<a href="' . $config['url_path'] . 'plugins/camm/camm_view.php"><img src="' . $config['url_path'] . 'plugins/camm/images/tab_camm';
 		// Red tab code
 		if(preg_match('/plugins\/camm\/camm_view.php/',$_SERVER['REQUEST_URI'] ,$matches) || preg_match('/plugins\/camm\/camm_alert.php/',$_SERVER['REQUEST_URI'] ,$matches))
 		{
 			print "_red";
 		}
 
 		if(read_config_option("camm_tab_image_size") == "1"){
 			print "_small";
 		}
 
 		print '.gif" alt="camm" align="absmiddle" border="0"></a>';
 	}
 	camm_check_upgrade ();	
 }
 
 function camm_page_title ($in) {
 	global $config;
 	
 	$out = $in;
 	
 	$url = $_SERVER['REQUEST_URI'];
 		
 	if(preg_match('#/plugins/camm/camm_view.php#', $url))
 	{
 		$out .= " - CAMM (CActi Message Managment)";
 	}
 		
 	return ($out);	
 }
 
 function plugin_camm_uninstall () {
     // Do any extra Uninstall stuff here
 	db_execute("delete FROM settings where name like 'camm%';");
 	kill_session_var("camm_output_messages");
 }
 
 
 function plugin_camm_check_config () {
     // Here we will check to ensure everything is configured
     camm_check_upgrade ();
     return true;
 }
 
 function plugin_camm_upgrade () {
     // Here we will upgrade to the newest version
     camm_check_upgrade ();
     return false;
 }
 
 function plugin_camm_version () {
     // Here we will upgrade to the newest version
     return camm_version ();
 }
 
 function camm_config_arrays () {
 	global $user_auth_realms, $menu, $user_auth_realm_filenames;
 	global $camm_poller_frequencies, $camm_purge_delay, $camm_purge_tables,  $camm_rows_test, $camm_tree_update, $camm_rows_selector,  $camm_grid_update;
 
 
     $camm_rows_test = array(
     100 => "100",
     200 => "200",
     500 => "500",
     1000 => "1000",
 	5000 => "5000",
 	10000 => "10000",
     0 => "ALL");
 	
 	$camm_rows_selector = array(
 		-1 => "Default",
 		10 => "10",
 		15 => "15",
 		20 => "20",
 		30 => "30",
 		50 => "50",
 		100 => "100",
 		500 => "500",
 		1000 => "1000",
 		-2 => "All");	
 		
 	$camm_poller_frequencies = array(
 		"disabled" => "Disabled",
 		"10" => "Every 10 Minutes",
 		"15" => "Every 30 Minutes",
 		"60" => "Every 1 Hour",
 		"120" => "Every 2 Hours",
 		"240" => "Every 4 Hours",
 		"480" => "Every 8 Hours",
 		"720" => "Every 12 Hours",
 		"1440" => "Every Day");
 	$camm_purge_delay = array(
 		"1" => "1 Day",
 		"3" => "3 Days",
 		"5" => "5 Days",
 		"7" => "1 Week",
 		"14" => "2 Week",
 		"30" => "1 Month",
 		"60" => "2 Month");	
 	$camm_tree_update = array(
 		"30" => "30 Sec",
 		"60" => "1 Minute",
 		"120" => "2 Minutes",
 		"180" => "3 Minutes",
 		"300" => "5 Minutes",
 		"600" => "10 Minutes",
 		"1800" => "30 Minutes",
 		"3600" => "Every 1 Hour",
 		"7200" => "Every 2 Hours",
 		"14400" => "Every 4 Hours",
 		"28800" => "Every 8 Hours"		
 		);		
 	$camm_purge_tables = array(
 		"1" => "plugin_camm_traps",
 		"2" => "plugin_camm_unknown_traps",
 		"3" => "both");
 	$camm_grid_update = array(
 		"0" => "Never",
 		"0.2" => "12 Sec",
 		"0.5" => "30 Sec",
 		"1" => "1 Minute",
 		"5" => "5 Minutes",
 		"10" => "10 Minutes",
 		"15" => "15 Minutes",
 		"30" => "30 Minutes",
 		"60" => "Every 1 Hour"
 		);			
 }
 
 function camm_config_settings () {
 	global $tabs, $settings, $camm_poller_frequencies, $camm_purge_delay, $camm_purge_tables, $camm_tree_update, $camm_rows_test, $camm_grid_update;
 
 	$tabs["camm"] = "camm";
 
 	$settings["camm"] = array(
 		"camm_hdr_components" => array(
 			"friendly_name" => "1. CaMM components",
 			"method" => "spacer",
 			),
 		"camm_use_snmptt" => array(
 			"friendly_name" => "Use SNMPTT",
 			"description" => "Use SNMPTT component (both traps and unknown traps)",
 			"order" => "1.1.",			
 			"method" => "drop_array",
 			"default" => "false",
 			"array" => array(1=>"true",0=>"false"),
 			),
 		"camm_use_syslog" => array(
 			"friendly_name" => "Use SYSLOG",
 			"description" => "Use Syslog-ng database data",
 			"order" => "1.2.",			
 			"method" => "drop_array",
 			"default" => "false",
 			"array" => array(1=>"true",0=>"false"),
 			),
 		"camm_use_cactilog" => array(
 			"friendly_name" => "Use Cacti log",
 			"description" => "Use Cacti log from database",
 			"order" => "1.3.",			
 			"method" => "drop_array",
 			"default" => "not yet :)",
 			"array" => array(0=>"not yet :)"),
 			),				
 		"camm_hdr_general" => array(
 			"friendly_name" => "2. CaMM General Settings",
 			"method" => "spacer",
 			),			
 		"camm_test_row_count" => array(
 			"friendly_name" => "Count rows to test",
 			"description" => "Choose count rows to test with rule when create it.",
 			"order" => "2.1.",
 			"method" => "drop_array",
 			"default" => "1000",
 			"array" => $camm_rows_test,			
 			),
 		"camm_autopurge_timing" => array(
 			"friendly_name" => "Data Purge Timing",
 			"description" => "Choose when auto purge records from database.",
 			"order" => "2.2.",			
 			"method" => "drop_array",
 			"default" => "disabled",
 			"array" => $camm_poller_frequencies,
 			),		
 		"camm_show_all_records" => array(
 			"friendly_name" => "Show all records",
 			"description" => "Choose - show all records or only already processed by rules.",
 			"order" => "2.3.",			
 			"method" => "drop_array",
 			"default" => "show all",
 			"array" => array(0=>"show only processed",1=>"show all"),
 			),
 		"camm_join_field" => array(
 			"friendly_name" => "Join field name",
 			"description" => "Choose join field on which record (trap or syslog) will be joined with cacti device's",
 			"order" => "2.3.",			
 			"method" => "drop_array",
 			"default" => "IP-address (if you device DON'T use DNS name)",
 			"array" => array("hostname"=>"DNS-hostname (if you device use DNS name)","sourceip"=>"IP-address (if you device DON'T use DNS name)"),
 			),
 		"camm_debug_mode" => array(
 			"friendly_name" => "Debug mode",
 			"description" => "Enable debug mode for more verbose output in cacti log file",
 			"order" => "2.4.",			
 			"method" => "drop_array",
 			"default" => "0",
 			"array" => array(0=>"Disable",1=>"Enable"),	
 			),
 		"camm_general_graphs_ids" => array(
 			"friendly_name" => "Graphs ID's to show",
 			"description" => "Enter the Graph's ID to show in stats tab.",
 			"order" => "2.5.",			
 			"method" => "textbox",
 			"value" => "|arg1:camm_general_graphs_ids|",
 			"default" => "0",
 			"max_length" => "50",
 			),			
 		"camm_tab_image_size" => array(
 			"friendly_name" => "Tab style",
 			"description" => "Which size tabs to use?",
 			"order" => "2.6.",			
 			"method" => "drop_array",
 			"default" => "0",
 			"array" => array(0=>"Regular",1=>"Smaller"),	
 			),			
 		"camm_hdr_timing" => array(
 			"friendly_name" => "3. CaMM SNMPTT Settings",
 			"method" => "spacer",
 			),
 		"camm_snmptt_delay_purge_day" => array(
 			"friendly_name" => "Data Purge Delay",
 			"description" => "Choose after what period data may be purged.",
 			"order" => "3.1.",			
 			"method" => "drop_array",
 			"default" => "7",
 			"array" => $camm_purge_delay,
 			),			
 		"camm_snmptt_max_row_all" => array(
 			"friendly_name" => "Max rows in tables",
 			"description" => "Enter max count rows in tables. Zerro for unlimited.",
 			"order" => "3.2.",			
 			"method" => "textbox",
 			"value" => "|arg1:camm_snmptt_max_row_all|",
 			"default" => "50000",
 			"max_length" => "7",
 			),
 		"camm_snmptt_max_row_per_device" => array(
 			"friendly_name" => "Max rows per device in day",
 			"description" => "Enter max count rows in tables per device per day. Zerro for unlimited.",
 			"order" => "3.3.",			
 			"method" => "textbox",
 			"value" => "|arg1:camm_snmptt_max_row_per_device|",
 			"default" => "1200",
 			"max_length" => "7",
 			),				
 		"camm_snmptt_tables" => array(
 			"friendly_name" => "What tables process",
 			"description" => "Choose table for processing",
 			"order" => "3.4.",			
 			"method" => "drop_array",
 			"default" => "3",
 			"array" => $camm_purge_tables,
 			),
 		"camm_snmptt_trap_tab_update" => array(
 			"friendly_name" => "Default Traps tab autoupdate interval",
 			"description" => "Choose how often Traps Tab grid will be AutoUpdated ?",
 			"order" => "3.5.",			
 			"method" => "drop_array",
 			"default" => "0",
 			"array" => $camm_grid_update,		
 			),
 		"camm_snmptt_unktrap_tab_update" => array(
 			"friendly_name" => "Default Unk. Traps tab autoupdate interval",
 			"description" => "Choose how often Unk. Traps Tab grid will be AutoUpdated ?",
 			"order" => "3.6.",			
 			"method" => "drop_array",
 			"default" => "0",
 			"array" => $camm_grid_update,		
 			),				
 		"camm_hdr_sys_purge" => array(
 			"friendly_name" => "4. CaMM SYSLOG Settings",
 			"method" => "spacer",
 			),
 		"camm_syslog_db_name" => array(
 			"friendly_name" => "Syslog db name",
 			"description" => "Enter syslog database name.",
 			"order" => "4.1.",			
 			"method" => "textbox",
 			"value" => "|arg1:camm_syslog_db_name|",
 			"default" => "syslog_ng",
 			"max_length" => "50",
 			),
 		"camm_syslog_pretable_name" => array(
 			"friendly_name" => "Syslog incoming table",
 			"description" => "If You use separate table for incoming messages before processing rules - enter table name here <br> Or use [plugin_camm_syslog] for default (one table shema) <br> Table must be in [Syslog db name] database!",
 			"order" => "4.2.",			
 			"method" => "textbox",
 			"value" => "|arg1:camm_syslog_pretable_name|",
 			"default" => "plugin_camm_syslog",
 			"max_length" => "50",
 			),			
 		"camm_sys_delay_purge_day" => array(
 			"friendly_name" => "Data Purge Delay",
 			"description" => "Choose after what period data may be purged.",
 			"order" => "4.3.",			
 			"method" => "drop_array",
 			"default" => "7",
 			"array" => $camm_purge_delay,
 			),			
 		"camm_sys_max_row_all" => array(
 			"friendly_name" => "Max rows in table",
 			"description" => "Enter max count rows in table. Zerro for unlimited.",
 			"order" => "4.4.",			
 			"method" => "textbox",
 			"value" => "|arg1:camm_sys_max_row_all|",
 			"default" => "50000",
 			"max_length" => "7",
 			),
 		"camm_sys_max_row_per_device" => array(
 			"friendly_name" => "Max rows per device in day",
 			"description" => "Enter max count rows in table per device per day. Zerro for unlimited.",
 			"order" => "4.5.",			
 			"method" => "textbox",
 			"value" => "|arg1:camm_sys_max_row_per_device|",
 			"default" => "1200",
 			"max_length" => "7",
 			),
 		"camm_sys_tab_update" => array(
 			"friendly_name" => "Default Sysalog tab autoupdate interval",
 			"description" => "Choose how often Syslog Tab grid will be AutoUpdated ?",
 			"order" => "4.6.",			
 			"method" => "drop_array",
 			"default" => "0",
 			"array" => $camm_grid_update,		
 			),			
 		"camm_hdr_startup" => array(
 			"friendly_name" => "5. CaMM Startup Settings",
 			"method" => "spacer",
 			),	
 		"camm_startup_tab" => array(
 			"friendly_name" => "Default start tab",
 			"description" => "Choose which tab will be opeb by default, at startup",
 			"order" => "5.1.",			
 			"method" => "drop_array",
 			"default" => "0",
 			"array" => array(0=>"Syslog",1=>"Traps",2=>"Unknown Traps",3=>"Rules",4=>"Stats"),		
 			),				
 		"camm_tree_update" => array(
 			"friendly_name" => "Tree update interval",
 			"description" => "Choose how often update Tree.",
 			"order" => "5.2.",			
 			"method" => "drop_array",
 			"default" => "300",
 			"array" => $camm_tree_update,			
 			),
 		"camm_num_rows" => array(
 			"friendly_name" => "Rows Per Page",
 			"description" => "The number of rows to display on a single page for Syslog messages, Traps and unknow Traps.",
 			"order" => "5.3.",			
 			"method" => "drop_array",
 			"default" => "50",
 			"array" => array("5"=>5,"10"=>10,"20"=>20,"50"=>50,"100"=>100,"200"=>200)		
 			),
 		
 			
 	);
 
 }
 
 
 function camm_draw_navigation_text ($nav) {
 
   $nav["camm_devices.php:"] = array("title" => "camm", "mapping" => "index.php:", "url" => "camm_devices.php", "level" => "1");
   $nav["camm_view.php:"] = array("title" => "camm", "mapping" => "index.php:", "url" => "camm_view.php", "level" => "1");
   $nav["start.php:"] = array("title" => "CAMM (CActi Message Manager)", "mapping" => "index.php:", "url" => "start.php", "level" => "2");
   
    return $nav;
 }
 
 function camm_poller_bottom () {
 	global $config;
 	$command_string = read_config_option("path_php_binary");
 	$extra_args = "-q " . $config["base_path"] . "/plugins/camm/poller_camm.php";
 	exec_background($command_string, "$extra_args");
 }
 
 function camm_version () {
 	return array(	
 		'name'		=> 'CAMM',
 		'version' 	=> '1.5.3',
 		'longname'	=> 'CAMM (CActi Message Manager)',
 		'author'	=> 'Susanin',
 		'homepage'	=> 'http://forums.cacti.net/viewtopic.php?p=156769#156769',
 		'url'		=> '',
 		'email'		=> 'gthe72@yandex.ru'
 	);
 }
 
 
 function camm_check_upgrade () {
 
 	// Let's only run this check if we are on a page that actually needs the data
 	$files = array('camm_view.php');
 	if (isset($_SERVER['PHP_SELF']) && !in_array(basename($_SERVER['PHP_SELF']), $files))
 		return;
 
 	$current = camm_version ();
 	$current = $current['version'];
 	$old = db_fetch_cell("SELECT `value` FROM `settings` where name = 'camm_version'");
 	if ($current != $old) {
 		/* re-register the hooks */
 		plugin_camm_install();
 	}
 }
 
 function camm_check_dependencies() {
 	global $plugins, $config;
 	$rezult = false;
 	if (in_array('settings', $plugins)) {
 		$v = settings_version();
 		if ($v['version'] >= 0.2) {
 			$rezult = true;
 		}
 		
 	}
 	return $rezult;
 }
 
 function camm_upgrade_from_snm_ptt () {
 
 	$result = db_fetch_assoc("show tables LIKE '%plugin_snmptt%';");
 
 	//Change cacti old table names
 	if (count($result) > 1) {
 		foreach($result as $index => $arr) {
 			foreach ($arr as $old_name) {
 				if ($old_name != "plugin_snmptt_syslog") {
 					if ($old_name == "plugin_snmptt") {
 						$new_name="plugin_camm_snmptt";
 					}elseif($old_name == "plugin_snmptt_unknown"){
 						$new_name="plugin_camm_snmptt_unk";
 					}elseif($old_name == "plugin_snmptt_statistics"){
 						$new_name="plugin_camm_snmptt_stat";
 					}elseif($old_name == "plugin_snmptt_alert"){
 						$new_name="plugin_camm_rule";
 					}else{
 						$new_name=str_replace("snmptt","camm",$old_name);
 					}
 					db_execute("ALTER TABLE `" . $old_name . "` RENAME TO `" . $new_name . "`;");
 				}
 			}
 		}
 	}
 	camm_raise_message3(array("device_descr" => "Upgrade from SNMPTT plugin" , "type" => "upgrade_db", "object"=> "update","cellpading" => false, "message" => "Change cacti old table names", "step_rezult" => "OK", "step_data" => "OK"));     
 
 	//Change syslog DB if used
 	$camm_use_syslog = read_config_option("snmptt_use_syslog");
 	if ($camm_use_syslog == '1') {
 		$camm_syslog_db_name = read_config_option("snmptt_syslog_db_name");
 		$camm_syslog_db_name = (strlen(trim($camm_syslog_db_name))>0 ? $camm_syslog_db_name : "syslog_ng");
 		db_execute("ALTER TABLE `" . $camm_syslog_db_name . "`.`plugin_snmptt_syslog` RENAME TO `" . $camm_syslog_db_name . "`.`plugin_camm_syslog`;");
 		camm_raise_message3(array("device_descr" => "Upgrade from SNMPTT plugin" , "type" => "upgrade_db", "object"=> "update","cellpading" => false, "message" => "Change syslog DB if used", "step_rezult" => "OK", "step_data" => "OK"));     
 	}
 	
 
 	//Change settings name
 	$result = db_fetch_assoc("SELECT `name` FROM `settings` WHERE `name` like '%snmptt%';");
 
 	if (count($result) > 1) {
 		foreach($result as $index => $arr) {
 			foreach ($arr as $old_name) {
 				if ($old_name != "dimpb_use_snmptt_plugin") {
 					if ($old_name == "stats_snmptt_time") {
 						$new_name="camm_stats_time";
 					}elseif($old_name == "stats_snmptt_ruledel"){
 						$new_name="camm_stats_ruledel";
 					}elseif($old_name == "stats_snmptt"){
 						$new_name="camm_stats";
 					}elseif($old_name == "snmptt_delay_purge_day"){
 						$new_name="camm_snmptt_delay_purge_day";
 					}elseif($old_name == "snmptt_max_row_all"){
 						$new_name="camm_snmptt_max_row_all";
 					}elseif($old_name == "snmptt_max_row_per_device"){
 						$new_name="camm_snmptt_max_row_per_device";
 					}elseif($old_name == "snmptt_tables"){
 						$new_name="camm_snmptt_tables";						
 					}elseif($old_name == "snmptt_trap_tab_update"){
 						$new_name="camm_snmptt_trap_tab_update";	
 					}elseif($old_name == "snmptt_unktrap_tab_update"){
 						$new_name="camm_snmptt_unktrap_tab_update";	
 					}else{
 						$new_name=str_replace("snmptt","camm",$old_name);
 					}
 					$new_name=str_replace("snmptt","camm",$old_name);
 					db_execute("UPDATE `settings` SET `name` = '" . $new_name . "' WHERE `name` = '" . $old_name . "';");
 				}
 			}
 		}
 		
 		camm_raise_message3(array("device_descr" => "Upgrade from SNMPTT plugin" , "type" => "upgrade_db", "object"=> "update","cellpading" => false, "message" => "Change settings name", "step_rezult" => "OK", "step_data" => "OK"));     
 	}
 
 }
 
 
 function camm_setup_table () {
     global $config, $database_default;
     include_once($config["library_path"] . "/database.php");
 
     //Check - if this is a upgrade from snmptt plugin
 	$snm_ptt_db = db_fetch_cell("SELECT count(*) FROM `plugin_db_changes` where `plugin`='snmptt';");
 	$camm_db_change_count = db_fetch_cell("SELECT count(*) FROM `plugin_db_changes` where `plugin`='camm';");
 	$snm_ptt_db_real = db_fetch_assoc("show tables LIKE '%plugin_snmptt%';");
 	if (($snm_ptt_db > 0) && (count($snm_ptt_db_real) > 1) && ($camm_db_change_count == 0)){
 		camm_upgrade_from_snm_ptt();
 		db_execute("DELETE FROM  `plugin_db_changes` WHERE `plugin`='snmptt';");
 	}
 	
 	
 	$data = array();
     $data['columns'][] = array('name' => 'id', 'type' => 'int(11)', 'unsigned' => true, 'NULL' => false, 'auto_increment' => true);
     $data['columns'][] = array('name' => 'eventname', 'type' => 'varchar(50)', 'NULL' => true);
     $data['columns'][] = array('name' => 'eventid', 'type' => 'varchar(50)', 'NULL' => true);
     $data['columns'][] = array('name' => 'trapoid', 'type' => 'varchar(100)', 'NULL' => true);
 	$data['columns'][] = array('name' => 'enterprise', 'type' => 'varchar(100)', 'NULL' => true);
 	$data['columns'][] = array('name' => 'community', 'type' => 'varchar(20)', 'NULL' => true);
 	$data['columns'][] = array('name' => 'hostname', 'type' => 'varchar(250)', 'NULL' => true);
 	$data['columns'][] = array('name' => 'agentip', 'type' => 'varchar(16)', 'NULL' => true);
 	$data['columns'][] = array('name' => 'category', 'type' => 'varchar(20)', 'NULL' => true);
 	$data['columns'][] = array('name' => 'severity', 'type' => 'varchar(20)', 'NULL' => true);
 	$data['columns'][] = array('name' => 'uptime', 'type' => 'varchar(20)', 'NULL' => true);
 	$data['columns'][] = array('name' => 'traptime', 'type' => 'datetime', 'NULL' => true);
 	$data['columns'][] = array('name' => 'formatline', 'type' => 'text', 'NULL' => true);
     $data['primary'] = 'id';
 	$data['keys'][] = array('name' => 'hostname', 'columns' => 'hostname');
     $data['type'] = 'MyISAM';
     $data['comment'] = 'camm data';
 
     api_plugin_db_table_create ('camm', 'plugin_camm_snmptt', $data);
 
     $data = array();
 	
 	$data['columns'][] = array('name' => 'stat_time', 'type' => 'datetime', 'NULL' => true);
     $data['columns'][] = array('name' => 'total_received', 'type' => 'bigint(20)', 'NULL' => true);
 	$data['columns'][] = array('name' => 'total_translated', 'type' => 'bigint(20)', 'NULL' => true);
 	$data['columns'][] = array('name' => 'total_ignored', 'type' => 'bigint(20)', 'NULL' => true);
 	$data['columns'][] = array('name' => 'total_unknown', 'type' => 'bigint(20)', 'NULL' => true);
     $data['type'] = 'MyISAM';
 	$data['keys'][] = array('name' => 'stat_time', 'columns' => 'stat_time');
     $data['comment'] = 'camm Statistics';
 
     api_plugin_db_table_create ('camm', 'plugin_camm_snmptt_stat', $data);
 	
 	$data = array();
 	
     $data['columns'][] = array('name' => 'id', 'type' => 'int(11)', 'unsigned' => true, 'NULL' => false, 'auto_increment' => true);
     $data['columns'][] = array('name' => 'device_type_name', 'type' => 'varchar(100)', 'NULL' => true);
     $data['columns'][] = array('name' => 'description', 'type' => 'varchar(150)', 'NULL' => true);
     $data['columns'][] = array('name' => 'hostname', 'type' => 'varchar(100)', 'NULL' => true);
 	$data['columns'][] = array('name' => 'agentip', 'type' => 'int(10)', 'unsigned' => true, 'NULL' => true);
 	$data['columns'][] = array('name' => 'eventname', 'type' => 'varchar(50)', 'NULL' => true);
     $data['primary'] = 'id';
 	$data['keys'][] = array('name' => 'hostname', 'columns' => 'hostname');
 	$data['keys'][] = array('name' => 'eventname', 'columns' => 'eventname');
     $data['type'] = 'MyISAM';
     $data['comment'] = 'camm Tree';
 
     api_plugin_db_table_create ('camm', 'plugin_camm_tree', $data);
 
 
 	$data = array();
 	
     $data['columns'][] = array('name' => 'id', 'type' => 'int(11)', 'unsigned' => true, 'NULL' => false, 'auto_increment' => true);
     $data['columns'][] = array('name' => 'trapoid', 'type' => 'varchar(100)', 'NULL' => true);
     $data['columns'][] = array('name' => 'enterprise', 'type' => 'varchar(100)', 'NULL' => true);
     $data['columns'][] = array('name' => 'community', 'type' => 'varchar(20)', 'NULL' => true);
 	$data['columns'][] = array('name' => 'hostname', 'type' => 'varchar(250)', 'NULL' => true);
 	$data['columns'][] = array('name' => 'agentip', 'type' => 'varchar(16)', 'NULL' => true);
 	$data['columns'][] = array('name' => 'uptime', 'type' => 'varchar(20)', 'NULL' => true);
 	$data['columns'][] = array('name' => 'traptime', 'type' => 'datetime', 'NULL' => true);
 	$data['columns'][] = array('name' => 'formatline', 'type' => 'text', 'NULL' => true);
     $data['primary'] = 'id';
 	$data['keys'][] = array('name' => 'id', 'columns' => 'id');
     $data['type'] = 'MyISAM';
     $data['comment'] = 'camm Unkn Traps';
 
     api_plugin_db_table_create ('camm', 'plugin_camm_snmptt_unk', $data);
 
 	
 	$data = array();
 	
     $data['columns'][] = array('name' => 'id', 'type' => 'int(10)', 'unsigned' => true, 'NULL' => false, 'auto_increment' => true);
     $data['columns'][] = array('name' => 'name', 'type' => 'varchar(255)', 'NULL' => false);
 	$data['columns'][] = array('name' => 'rule_type', 'type' => 'varchar(10)', 'NULL' => false, 'default' => 'camm');
 	$data['columns'][] = array('name' => 'rule_enable', 'type' => 'tinyint(1)', 'NULL' => false, 'default' => '1');
 	$data['columns'][] = array('name' => 'is_function', 'type' => 'tinyint(1)', 'NULL' => false, 'default' => '0');
 	$data['columns'][] = array('name' => 'is_email', 'type' => 'tinyint(1)', 'NULL' => false, 'default' => '0');	
 	$data['columns'][] = array('name' => 'is_mark', 'type' => 'tinyint(1)', 'NULL' => false, 'default' => '0');	
 	$data['columns'][] = array('name' => 'is_delete', 'type' => 'tinyint(1)', 'NULL' => false, 'default' => '0');	
 	$data['columns'][] = array('name' => 'function_name', 'type' => 'varchar(255)', 'NULL' => true);
 	$data['columns'][] = array('name' => 'email', 'type' => 'varchar(255)', 'NULL' => true);
 	$data['columns'][] = array('name' => 'email_message', 'type' => 'text');
 	$data['columns'][] = array('name' => 'marker', 'type' => 'tinyint(2)', 'NULL' => false, 'default' => '0');	
 	$data['columns'][] = array('name' => 'notes', 'type' => 'varchar(255)', 'NULL' => true);
 	$data['columns'][] = array('name' => 'json_filter', 'type' => 'text');
 	$data['columns'][] = array('name' => 'sql_filter', 'type' => 'text');
 	$data['columns'][] = array('name' => 'user_id', 'type' => 'int(10)', 'unsigned' => true, 'NULL' => false, 'default' => '0');
 	$data['columns'][] = array('name' => 'date', 'type' => 'datetime', 'NULL' => true);
 	$data['columns'][] = array('name' => 'count_triggered', 'type' => 'int(11)', 'unsigned' => true, 'NULL' => false, 'default' => '0');
     $data['primary'] = 'id';
 	$data['keys'][] = array('name' => 'id', 'columns' => 'id');
     $data['type'] = 'MyISAM';
     $data['comment'] = 'Traps Alert';
 
     api_plugin_db_table_create ('camm', 'plugin_camm_rule', $data);
 
 	camm_update();
 
 }
 
 	
 function camm_update () {
 	global $config, $database_default;;
 
 	include_once($config["library_path"] . "/database.php");
 	include_once($config["base_path"] . "/plugins/camm/lib/camm_functions.php");
 
 	// Set the new version
 	$new = camm_version();
 	$n_name = $new['longname'];
 	$new = $new['version'];
 	$old = db_fetch_cell("SELECT `value` FROM `settings` where name = 'camm_version'");
 	db_execute("REPLACE INTO settings (name, value) VALUES ('camm_version', '$new')");
 	if (trim($old) == "") {
 		$old = "0.0.01b";
 	}
 
 	
 	$result = db_fetch_assoc("SELECT `name` FROM `settings` where name like '%camm%' order by name");
 	foreach($result as $row) {
 		$result_new[] =$row['name'];
 	}
 	//delete block
 	if (in_array("stats_camm_tree", $result_new))
 		$sql[] = array("camm_execute_sql","Delete from [settings] unused parameter [stats_camm_tree]","DELETE FROM `settings` WHERE `name` = 'stats_camm_tree';");	
 	if (in_array("camm_sys_collection_timing", $result_new))
 		$sql[] = array("camm_execute_sql","Delete from [settings] unused parameter [camm_sys_collection_timing]","DELETE FROM `settings` WHERE `name` = 'camm_sys_collection_timing';");			
 	if (in_array("camm_stats_ruledel", $result_new))
 		$sql[] = array("camm_execute_sql","Delete from [settings] unused parameter [camm_stats_ruledel]","DELETE FROM `settings` WHERE `name` = 'camm_stats_ruledel';");			
 	if (in_array("camm_collection_timing", $result_new)) {
 		if (in_array("camm_autopurge_timing", $result_new)) {
 			$sql[] = array("camm_execute_sql","Delete unused parameter in  [settings] [camm_collection_timing]","DELETE FROM `settings` WHERE `name` = 'camm_collection_timing';");			
 		}else{
 			$sql[] = array("camm_execute_sql","Change parameter in  [settings] unused parameter [camm_collection_timing] to [camm_autopurge_timing]","UPDATE settings SET `name` = 'camm_autopurge_timing' WHERE `name` = 'camm_collection_timing';");					
 		}
 	}
 
 	//change block
 	if (in_array("snmptt_delay_purge_day", $result_new))
 		$sql[] = array("camm_execute_sql","Rename in [settings] parameter [snmptt_delay_purge_day] to [camm_snmptt_delay_purge_day]","UPDATE `settings`  SET `name`='camm_snmptt_delay_purge_day' WHERE `name` = 'snmptt_delay_purge_day';");			
 	if (in_array("snmptt_max_row_all", $result_new))
 		$sql[] = array("camm_execute_sql","Rename in [settings] parameter [snmptt_max_row_all] to [camm_snmptt_max_row_all]","UPDATE `settings`  SET `name`='camm_snmptt_max_row_all' WHERE `name` = 'snmptt_max_row_all';");
 	if (in_array("snmptt_max_row_per_device", $result_new))
 		$sql[] = array("camm_execute_sql","Rename in [settings] parameter [snmptt_max_row_per_device] to [camm_snmptt_max_row_per_device]","UPDATE `settings`  SET `name`='camm_snmptt_max_row_per_device' WHERE `name` = 'snmptt_max_row_per_device';");			
 	if (in_array("snmptt_tables", $result_new))
 		$sql[] = array("camm_execute_sql","Rename in [settings] parameter [snmptt_tables] to [camm_snmptt_tables]","UPDATE `settings`  SET `name`='camm_snmptt_tables' WHERE `name` = 'snmptt_tables';");			
 	if (in_array("snmptt_trap_tab_update", $result_new))
 		$sql[] = array("camm_execute_sql","Rename in [settings] parameter [snmptt_trap_tab_update] to [camm_snmptt_trap_tab_update]","UPDATE `settings`  SET `name`='camm_snmptt_trap_tab_update' WHERE `name` = 'snmptt_trap_tab_update';");			
 	if (in_array("snmptt_unktrap_tab_update", $result_new))
 		$sql[] = array("camm_execute_sql","Rename in [settings] parameter [snmptt_unktrap_tab_update] to [camm_snmptt_unktrap_tab_update]","UPDATE `settings`  SET `name`='camm_snmptt_unktrap_tab_update' WHERE `name` = 'snmptt_unktrap_tab_update';");
 		
 
 	//add block				
 	if (!in_array("camm_num_rows", $result_new))
 		$sql[] = array("camm_execute_sql","Insert into [settings] new parameter [camm_num_rows]","INSERT INTO settings VALUES ('camm_num_rows','50');");	
 	if (!in_array("camm_last_run_time", $result_new))		
 		$sql[] = array("camm_execute_sql","Insert into [settings] new parameter [camm_last_run_time]","INSERT INTO settings VALUES ('camm_last_run_time',0);");
 	if (!in_array("camm_autopurge_timing", $result_new))	
 		$sql[] = array("camm_execute_sql","Insert into [settings] new parameter [camm_autopurge_timing]","INSERT INTO settings VALUES ('camm_autopurge_timing','120');");
 	if (!in_array("camm_snmptt_delay_purge_day", $result_new))	
 		$sql[] = array("camm_execute_sql","Insert into [settings] new parameter [camm_snmptt_delay_purge_day]","INSERT INTO settings VALUES ('camm_snmptt_delay_purge_day','7');");
 	if (!in_array("camm_snmptt_max_row_all", $result_new))	
 		$sql[] = array("camm_execute_sql","Insert into [settings] new parameter [camm_snmptt_max_row_all]","INSERT INTO settings VALUES ('camm_snmptt_max_row_all','0');");
 	if (!in_array("camm_snmptt_max_row_per_device", $result_new))	
 		$sql[] = array("camm_execute_sql","Insert into [settings] new parameter [camm_snmptt_max_row_per_device]","INSERT INTO settings VALUES ('camm_snmptt_max_row_per_device','0');");
 	if (!in_array("camm_snmptt_tables", $result_new))
 		$sql[] = array("camm_execute_sql","Insert into [settings] new parameter [camm_snmptt_tables]","INSERT INTO settings VALUES ('camm_snmptt_tables','3');");	
 	if (!in_array("camm_startup_tab", $result_new))
 		$sql[] = array("camm_execute_sql","Insert into [settings] new parameter [camm_startup_tab]","INSERT INTO settings VALUES ('camm_startup_tab','0');");	
 	if (!in_array("camm_tree_update_time", $result_new))
 		$sql[] = array("camm_execute_sql","Insert into [settings] new parameter [camm_tree_update_time]","INSERT INTO settings VALUES ('camm_tree_update_time','300');");	
 	if (!in_array("camm_stats_time", $result_new))
 		$sql[] = array("camm_execute_sql","Insert into [settings] new parameter [camm_stats_time]","INSERT INTO settings VALUES ('camm_stats_time','Time:0');");	
 	if (!in_array("camm_stats_ruledel_snmptt", $result_new))
 		$sql[] = array("camm_execute_sql","Insert into [settings] new parameter [camm_stats_ruledel_snmptt]","INSERT INTO settings VALUES ('camm_stats_ruledel_snmptt','0');");	
 	if (!in_array("camm_stats_ruledel_syslog", $result_new))
 		$sql[] = array("camm_execute_sql","Insert into [settings] new parameter [camm_stats_ruledel_syslog]","INSERT INTO settings VALUES ('camm_stats_ruledel_syslog','0');");	
 	if (!in_array("camm_test_row_count", $result_new))
 		$sql[] = array("camm_execute_sql","Insert into [settings] new parameter [camm_test_row_count]","INSERT INTO settings VALUES ('camm_test_row_count','1000');");	
 	if (!in_array("camm_use_syslog", $result_new))
 		$sql[] = array("camm_execute_sql","Insert into [settings] new parameter [camm_use_syslog]","INSERT INTO settings VALUES ('camm_use_syslog','0');");	
 	if (!in_array("camm_sys_delay_purge_day", $result_new))
 		$sql[] = array("camm_execute_sql","Insert into [settings] new parameter [camm_sys_delay_purge_day]","INSERT INTO settings VALUES ('camm_sys_delay_purge_day','7');");	
 	if (!in_array("camm_sys_max_row_all", $result_new))
 		$sql[] = array("camm_execute_sql","Insert into [settings] new parameter [camm_sys_max_row_all]","INSERT INTO settings VALUES ('camm_sys_max_row_all','50000');");	
 	if (!in_array("camm_sys_max_row_per_device", $result_new))
 		$sql[] = array("camm_execute_sql","Insert into [settings] new parameter [camm_sys_max_row_per_device]","INSERT INTO settings VALUES ('camm_sys_max_row_per_device','1200');");	
 	if (!in_array("camm_snmptt_unktrap_tab_update", $result_new))
 		$sql[] = array("camm_execute_sql","Insert into [settings] new parameter [camm_snmptt_unktrap_tab_update]","INSERT INTO settings VALUES ('camm_snmptt_unktrap_tab_update','0');");	
 	if (!in_array("camm_snmptt_trap_tab_update", $result_new))
 		$sql[] = array("camm_execute_sql","Insert into [settings] new parameter [camm_snmptt_trap_tab_update]","INSERT INTO settings VALUES ('camm_snmptt_trap_tab_update','0');");	
 	if (!in_array("camm_sys_tab_update", $result_new))
 		$sql[] = array("camm_execute_sql","Insert into [settings] new parameter [camm_sys_tab_update]","INSERT INTO settings VALUES ('camm_sys_tab_update','0');");	
 	if (!in_array("camm_stats_snmptt_tree", $result_new))
 		$sql[] = array("camm_execute_sql","Insert into [settings] new parameter [camm_stats_snmptt_tree]","INSERT INTO settings VALUES ('camm_stats_snmptt_tree','TreecammTime:0');");	
 	if (!in_array("camm_stats_syslog_tree", $result_new))
 		$sql[] = array("camm_execute_sql","Insert into [settings] new parameter [camm_stats_syslog_tree]","INSERT INTO settings VALUES ('camm_stats_syslog_tree','TreesyslogTime:0');");	
 	if (!in_array("camm_syslog_db_name", $result_new)) {
 		$sql[] = array("camm_execute_sql","Insert into [settings] new parameter [camm_syslog_db_name]","INSERT INTO settings VALUES ('camm_syslog_db_name','syslog_ng');");	
 		$camm_syslog_db_name = 'syslog_ng';
 	}else{
 		$camm_syslog_db_name = read_config_option("camm_syslog_db_name");
 	}
 	if (!in_array("camm_show_all_records", $result_new))
 		$sql[] = array("camm_execute_sql","Insert into [settings] new parameter [camm_show_all_records]","INSERT INTO settings VALUES ('camm_show_all_records','1');");				
 	if (!in_array("camm_join_field", $result_new)) {
 		$sql[] = array("camm_execute_sql","Truncate table plugin_camm_tree","TRUNCATE table `plugin_camm_tree`;");				
 		$sql[] = array("camm_execute_sql","Insert into [settings] new parameter [camm_join_field]","INSERT INTO settings VALUES ('camm_join_field','sourceip');");				
 	}
 	if (!in_array("camm_tab_image_size", $result_new))
 		$sql[] = array("camm_execute_sql","Insert into [settings] new parameter [camm_tab_image_size]","INSERT INTO settings VALUES ('camm_tab_image_size','0');");				
 	if (!in_array("camm_debug_mode", $result_new))
 		$sql[] = array("camm_execute_sql","Insert into [settings] new parameter [camm_debug_mode]","INSERT INTO settings VALUES ('camm_debug_mode','0');");				
 	if (!in_array("camm_syslog_pretable_name", $result_new))
 		$sql[] = array("camm_execute_sql","Insert into [settings] new parameter [camm_syslog_pretable_name]","INSERT INTO settings VALUES ('camm_syslog_pretable_name','plugin_camm_syslog');");				
 
 
 		
 
 
 				
 	$found = false;
 	$result = db_fetch_assoc("SHOW INDEX FROM `host`;");
 	foreach($result as $row) {
 		if ($row['Column_name'] == 'hostname')
 			$found = true;
 	}
 	if (!$found) {
 		$sql[] = array("camm_add_index","host","hostname", "ALTER TABLE `host` ADD INDEX `hostname`(`hostname`);");
 	}
 
 	$found = false;
 	$result = db_fetch_assoc("SHOW INDEX FROM `plugin_camm_rule`;");
 	foreach($result as $row) {
 		if ($row['Key_name'] == 'unique')
 			$found = true;
 	}
 	if (!$found) {
 		$sql[] = array("camm_add_index","plugin_camm_rule","unique", "ALTER TABLE `plugin_camm_rule` ADD UNIQUE KEY `unique` USING BTREE (`is_function`,`is_email`,`is_mark`,`is_delete`,`function_name`(25),`email`(25),`marker`);");
 	}	
 
 	$result = db_fetch_row("SHOW TABLE STATUS where Name = 'plugin_camm_rule';");
 
 	if ($result["Auto_increment"] < 100) {
 		$sql[] = array("camm_execute_sql","UPDATE plugin_camm_rule Auto_increment field","ALTER TABLE `plugin_camm_rule` AUTO_INCREMENT = 100;");
 	}
 	
 	$found = false;
 	$result = db_fetch_assoc("SHOW columns FROM `plugin_camm_snmptt`;");
 	foreach($result as $row) {
 		if ($row['Field'] == 'status')
 			$found = true;
 	}
 	if (!$found) {
 		$sql[] = array("camm_add_column","plugin_camm_snmptt","status", "ALTER TABLE `plugin_camm_snmptt` ADD column `status` tinyint(1) NOT NULL default '0';");
 	}	
 
 	$found = false;
 	$result = db_fetch_assoc("SHOW INDEX FROM `plugin_camm_snmptt`;");
 	foreach($result as $row) {
 		if ($row['Key_name'] == 'status')
 			$found = true;
 	}
 	if (!$found) {
 		$sql[] = array("camm_add_index","plugin_camm_snmptt","status", "ALTER TABLE `plugin_camm_snmptt` ADD INDEX `status`(`status`);");
 	}
 
 	//v 0.0.17b
 	$found = false;
 	$result = db_fetch_assoc("SHOW columns FROM `plugin_camm_tree`;");
 	foreach($result as $row) {
 		if ($row['Field'] == 'host_template_id')
 			$found = true;
 	}
 	if (!$found) {
 		$sql[] = array("camm_add_column","plugin_camm_tree","host_template_id", "ALTER TABLE `plugin_camm_tree` ADD column `host_template_id` mediumint(8) unsigned NOT NULL default '0' after `device_type_name`;");
 	}	
 	//v 0.0.19b
 	$found = false;
 	$result = db_fetch_assoc("SHOW columns FROM `plugin_camm_snmptt`;");
 	foreach($result as $row) {
 		if ($row['Field'] == 'alert')
 			$found = true;
 	}
 	if (!$found) {
 		$sql[] = array("camm_add_column","plugin_camm_snmptt","alert", "ALTER TABLE `plugin_camm_snmptt` ADD column `alert` int(10) unsigned NOT NULL default '0';");
 	}
 	
 	$result = db_fetch_assoc("SHOW columns FROM `plugin_camm_snmptt`;");
 	foreach($result as $row) {
 		if ($row['Field'] == 'hostname')
 			if ($row['Type'] == 'varchar(250)') {
 			}else{
 				$sql[] = array("camm_modify_column","plugin_camm_snmptt","hostname", "ALTER TABLE `plugin_camm_snmptt` MODIFY COLUMN `hostname` VARCHAR(250) ;");
 			}
 	}
 
 	$result = db_fetch_assoc("SHOW columns FROM `plugin_camm_snmptt_unk`;");
 	foreach($result as $row) {
 		if ($row['Field'] == 'hostname')
 			if ($row['Type'] == 'varchar(250)') {
 			}else{
 				$sql[] = array("camm_modify_column","plugin_camm_snmptt_unk","hostname", "ALTER TABLE `plugin_camm_snmptt_unk` MODIFY COLUMN `hostname` VARCHAR(250) ;");
 			}
 	}	
 
 	$found = false;
 	$result = db_fetch_assoc("SHOW INDEX FROM `plugin_camm_snmptt`;");
 	foreach($result as $row) {
 		if ($row['Key_name'] == 'hostname')
 			$found = true;
 	}
 	if (!$found) {
 		$sql[] = array("camm_add_index","plugin_camm_snmptt","hostname", "ALTER TABLE `plugin_camm_snmptt` ADD INDEX `hostname` USING BTREE(`hostname`);");
 	}
 	
 	$found = false;
 	$found1 = false;
 	$found2 = false;
 	$found3 = false;
 	$result = db_fetch_assoc("SHOW INDEX FROM `plugin_camm_snmptt_unk`;");
 	foreach($result as $row) {
 		if ($row['Key_name'] == 'traptime')
 			$found = true;
 		if ($row['Key_name'] == 'trapoid')
 			$found1 = true;
 		if ($row['Key_name'] == 'community')
 			$found2 = true;	
 		if ($row['Key_name'] == 'hostname')
 			$found3 = true;			
 	}
 	if (!$found) {
 		$sql[] = array("camm_add_index","plugin_camm_snmptt_unk","traptime", "ALTER TABLE `plugin_camm_snmptt_unk` ADD INDEX `traptime`(`traptime`);");
 	}	
 	if (!$found1) {
 		$sql[] = array("camm_add_index","plugin_camm_snmptt_unk","trapoid", "ALTER TABLE `plugin_camm_snmptt_unk` ADD INDEX `trapoid`(`trapoid`);");
 	}
 	if (!$found2) {
 		$sql[] = array("camm_add_index","plugin_camm_snmptt_unk","community", "ALTER TABLE `plugin_camm_snmptt_unk` ADD INDEX `community`(`community`);");
 	}
 	if (!$found3) {
 		$sql[] = array("camm_add_index","plugin_camm_snmptt_unk","hostname", "ALTER TABLE `plugin_camm_snmptt_unk` ADD INDEX `hostname` USING BTREE(`hostname`);");
 	}
 
 	
 	$found = false;
 	$found1 = false;
 	$found2 = false;
 	$found3 = false;
 	$found4 = false;
 	$result = db_fetch_assoc("SHOW INDEX FROM `plugin_camm_snmptt`;");
 	foreach($result as $row) {
 		if ($row['Key_name'] == 'traptime')
 			$found = true;
 		if ($row['Key_name'] == 'eventname')
 			$found1 = true;			
 		if ($row['Key_name'] == 'severity')
 			$found2 = true;
 		if ($row['Key_name'] == 'category')
 			$found3 = true;
 		if ($row['Key_name'] == 'status_date')
 			$found4 = true;				
 	}
 	if (!$found) {
 		$sql[] = array("camm_add_index","plugin_camm_snmptt","traptime", "ALTER TABLE `plugin_camm_snmptt` ADD INDEX `traptime`(`traptime`);");
 	}		
 	if (!$found1) {
 		$sql[] = array("camm_add_index","plugin_camm_snmptt","eventname", "ALTER TABLE `plugin_camm_snmptt` ADD INDEX `eventname`(`eventname`);");
 	}	
 	if (!$found2) {
 		$sql[] = array("camm_add_index","plugin_camm_snmptt","severity", "ALTER TABLE `plugin_camm_snmptt` ADD INDEX `severity`(`severity`);");
 	}
 	if (!$found3) {
 		$sql[] = array("camm_add_index","plugin_camm_snmptt","category", "ALTER TABLE `plugin_camm_snmptt` ADD INDEX `category`(`category`);");
 	}	
 	if (!$found4) {
 		$sql[] = array("camm_add_index","plugin_camm_snmptt","status_date", "ALTER TABLE `plugin_camm_snmptt` ADD INDEX `status_date`(`status`,`traptime`);");
 	}	
 	
 	
 
 	$found = false;
 	$result = db_fetch_assoc("SHOW columns FROM `plugin_camm_snmptt_unk`;");
 	foreach($result as $row) {
 		if ($row['Field'] == 'formatline')
 			if ($row['Type'] == 'varchar(255)') {
 				$sql[] = array("camm_modify_column","plugin_camm_snmptt_unk","formatline", "ALTER TABLE `plugin_camm_snmptt_unk` MODIFY COLUMN `formatline` text ;");			
 			}
 	}
 
 	$result = db_fetch_assoc("show tables;");
  
  	$tables_cacti = array();
  
  	if (count($result) > 1) {
  		foreach($result as $index => $arr) {
  			foreach ($arr as $t) {
  				$tables_cacti[] = $t;
  			}
  		}
  	}
 	
 	
 	$found = false;
 	$found1 = false;
 	$result = db_fetch_assoc("SHOW columns FROM `plugin_camm_rule`;");
 	foreach($result as $row) {
 		if ($row['Field'] == 'type')
 		$found = true;
 		if ($row['Field'] == 'mode')
 		$found1 = true;		
 	}
 	
 	if (($found && $found1) || (!in_array('plugin_camm_rule', $tables_cacti))) {
 		$sql[] = array("camm_execute_sql","Drop table plugin_camm_rule", "DROP TABLE `plugin_camm_rule`;");
 		$sql[] = array("camm_execute_sql","UPDATE plugin_camm_snmptt alert field", "UPDATE `plugin_camm_snmptt` SET `alert`='0'");
 
  		$sql[] = array("camm_create_table","plugin_camm_rule","CREATE TABLE `plugin_camm_rule` (
 		  `id` int(10) unsigned NOT NULL auto_increment,
 		  `name` varchar(255) NOT NULL,
 		  `is_function` tinyint(1) NOT NULL default '0',
 		  `is_email` tinyint(1) NOT NULL default '0',
 		  `is_mark` tinyint(1) NOT NULL default '0',
 		  `is_delete` tinyint(1) NOT NULL default '0',
 		  `function_name` varchar(255) default NULL,
 		  `email` varchar(255) default NULL,
 		  `email_message` text,
 		  `marker` tinyint(2) NOT NULL default '0',
 		  `notes` varchar(255) default NULL,
 		  `json_filter` text,
 		  `sql_filter` text,
 		  `user_id` int(10) unsigned NOT NULL default '0',
 		  `date` datetime default NULL,		  
 		  PRIMARY KEY  (`id`),
 		  UNIQUE KEY `unique` USING BTREE (`is_function`,`is_email`,`is_mark`,`is_delete`,`function_name`(25),`email`(25),`marker`)
 		) ENGINE=MyISAM AUTO_INCREMENT=100 COMMENT='camm rule';");
 		
 	}
 
 	$found = false;
 	$result = db_fetch_assoc("SHOW columns FROM `plugin_camm_tree`;");
 	foreach($result as $row) {
 		if ($row['Field'] == 'type')
 			$found = true;
 	}
 	if (!$found) {
 		$sql[] = array("camm_add_column","plugin_camm_tree","agentip_source", "ALTER TABLE `plugin_camm_tree` ADD column `agentip_source` varchar(16) NOT NULL default '';");
 		$sql[] = array("camm_add_column","plugin_camm_tree","type", "ALTER TABLE `plugin_camm_tree` ADD column `type` varchar(10) NOT NULL default '';");
 		$sql[] = array("camm_add_column","plugin_camm_tree","count", "ALTER TABLE `plugin_camm_tree` ADD column `count` int(10) unsigned NOT NULL default '0';");
 		$sql[] = array("camm_add_index","plugin_camm_tree","type", "ALTER TABLE `plugin_camm_tree` ADD INDEX `type`(`type`);");
 		$sql[] = array("camm_execute_sql","UPDATE plugin_camm_tree type field", "UPDATE `plugin_camm_tree` SET `type`='camm'");
 	}
 
  	$result = db_fetch_assoc("show tables from `" . $camm_syslog_db_name . "`;");
  
  	$tables = array();
  
  	if (count($result) > 1) {
  		foreach($result as $index => $arr) {
  			foreach ($arr as $t) {
  				$tables[] = $t;
  			}
  		}
  	}
 	
  	if (!in_array('plugin_camm_syslog', $tables)) {
 		$data = array();
 		
 	
  		$sql[] = array("camm_create_table","'" . $camm_syslog_db_name . "`.`plugin_camm_syslog'","CREATE TABLE  `" . $camm_syslog_db_name . "`.`plugin_camm_syslog` (
 		  `id` int(10) unsigned NOT NULL auto_increment,
 		  `host` varchar(128) default NULL,
 		  `sourceip` varchar(45) NOT NULL,
 		  `facility` varchar(10) default NULL,
 		  `priority` varchar(10) default NULL,
 		  `sys_date` datetime default NULL,
 		  `message` text,
 		  `status` tinyint(4) NOT NULL default '0',
 		  `alert` tinyint(3) NOT NULL default '0',
 		  PRIMARY KEY  (`id`),
 		  KEY `facility` (`facility`),
 		  KEY `priority` (`priority`),
 		  KEY `sourceip` (`sourceip`),
 		  KEY `status` (`status`),
 		  KEY `alert` (`alert`)
 		) ENGINE=MyISAM AUTO_INCREMENT=100 COMMENT='camm plugin SYSLOG Data';");		
 		$sql[] = array("camm_execute_sql","Truncate Table [plugin_camm_tree]", "TRUNCATE table  `plugin_camm_tree`;");
 	}
 	
 	
 	$found = false;
 	$found1 = false;
 	$found2 = false;
 	$found3 = false;
 	$found4 = false;
 	$found5 = false;
 	$found6 = false;
 	$result = db_fetch_assoc("SHOW INDEX FROM `" . $camm_syslog_db_name . "`.`plugin_camm_syslog`;");
	if (sizeof($result) > 0) {
	 	foreach($result as $row) {
	 		if ($row['Key_name'] == 'facility')
	 			$found = true;
	 		if ($row['Key_name'] == 'priority')
	 			$found1 = true;			
	 		if ($row['Key_name'] == 'sourceip')
	 			$found2 = true;
	 		if ($row['Key_name'] == 'status')
	 			$found3 = true;				
	 		if ($row['Key_name'] == 'alert')
	 			$found4 = true;
	 		if ($row['Key_name'] == 'status_date')
	 			$found5 = true;	
	 		if ($row['Key_name'] == 'sys_date')
	 			$found6 = true;				
	 	}
	 	if (!$found) {
	 		$sql[] = array("camm_execute_sql","Add Index, Table -> `" . $camm_syslog_db_name . "`.`plugin_camm_syslog`, Index -> facility", "ALTER TABLE `" . $camm_syslog_db_name . "`.`plugin_camm_syslog` ADD INDEX `facility`(`facility`);");
	 	}		
	 	if (!$found1) {
	 		$sql[] = array("camm_execute_sql","Add Index, Table -> `" . $camm_syslog_db_name . "`.`plugin_camm_syslog`, Index -> priority","ALTER TABLE `" . $camm_syslog_db_name . "`.`plugin_camm_syslog` ADD INDEX `priority`(`priority`);");
	 	}	
	 	if (!$found2) {
	 		$sql[] = array("camm_execute_sql","Add Index, Table -> `" . $camm_syslog_db_name . "`.`plugin_camm_syslog`, Index -> sourceip","ALTER TABLE `" . $camm_syslog_db_name . "`.`plugin_camm_syslog` ADD INDEX `sourceip`(`sourceip`);");
	 	}
	 	if (!$found3) {
	 		$sql[] = array("camm_execute_sql","Add Index, Table -> `" . $camm_syslog_db_name . "`.`plugin_camm_syslog`, Index -> status", "ALTER TABLE `" . $camm_syslog_db_name . "`.`plugin_camm_syslog` ADD INDEX `status`(`status`);");
	 	}	
	 	if (!$found4) {
	 		$sql[] = array("camm_execute_sql","Add Index, Table -> `" . $camm_syslog_db_name . "`.`plugin_camm_syslog`, Index -> alert", "ALTER TABLE `" . $camm_syslog_db_name . "`.`plugin_camm_syslog` ADD INDEX `alert`(`alert`);");
	 	}	
	 	if (!$found5) {
	 		$sql[] = array("camm_execute_sql","Add Index, Table -> `" . $camm_syslog_db_name . "`.`plugin_camm_syslog`, Index -> status_date", "ALTER TABLE `" . $camm_syslog_db_name . "`.`plugin_camm_syslog` ADD INDEX `status_date`(`status`,`sys_date`);");
	 	}	
	 	if (!$found6) {
	 		$sql[] = array("camm_execute_sql","Add Index, Table -> `" . $camm_syslog_db_name . "`.`plugin_camm_syslog`, Index -> sys_date", "ALTER TABLE `" . $camm_syslog_db_name . "`.`plugin_camm_syslog` ADD INDEX `sys_date`(`sys_date`);");
	 	}
 	}
	
 	$found = false;
 	$result = db_fetch_assoc("SHOW columns FROM `plugin_camm_tree`;");
 	foreach($result as $row) {
 		if ($row['Field'] == 'online')
 			$found = true;
 	}
 	if (!$found) {
 		$sql[] = array("camm_add_column","plugin_camm_tree","online", "ALTER TABLE `plugin_camm_tree` ADD column `online` tinyint(1) NOT NULL default '0'");
 	}	
 	
 	$found = false;
 	$result = db_fetch_assoc("SHOW INDEX FROM `plugin_camm_tree`;");
 	foreach($result as $row) {
 		if ($row['Key_name'] == 'unique')
 			$found = true;
 	}
 	if (!$found) {
 		$sql[] = array("camm_add_index","plugin_camm_tree","unique", "ALTER TABLE `plugin_camm_tree` ADD UNIQUE INDEX `unique` USING BTREE (`hostname`,`eventname`,`type`,`agentip_source`);");
 	}
 	
 	$result = db_fetch_cell("SELECT `file` FROM `plugin_realms` WHERE plugin = 'camm' AND `display`='Plugin -> camm: Manage';");
 	if ($result == 'camm_alert.php,camm_devices.php') {
 		$sql[] = array("camm_execute_sql","Update Plugin Realms", "UPDATE `plugin_realms` SET `file`='camm_db_admin.php' WHERE plugin = 'camm' AND `display`='Plugin -> camm: Manage'");
 	}
 	
 
 	$found = false;
 	$found1 = false;
 	$found2 = false;
	$found3 = false;
 	$result = db_fetch_assoc("SHOW columns FROM `plugin_camm_rule`;");
 	foreach($result as $row) {
 		if ($row['Field'] == 'rule_type')
 		$found = true;
 		if ($row['Field'] == 'rule_enable')
 		$found1 = true;
 		if ($row['Field'] == 'count_triggered')
 		$found2 = true;	
 		if ($row['Field'] == 'email_mode')
 		$found3 = true;			
 	}
 	if (!$found) {
 		$sql[] = array("camm_add_column","plugin_camm_rule","rule_type", "ALTER TABLE `plugin_camm_rule` ADD column `rule_type` varchar(10) NOT NULL default 'camm' after `name`;");
 		$sql[] = array("camm_execute_sql","Truncate Table [plugin_camm_tree]", "TRUNCATE table  `plugin_camm_tree`;");
 	}
 	if (!$found1) {
 		$sql[] = array("camm_add_column","plugin_camm_rule","rule_enable", "ALTER TABLE `plugin_camm_rule` ADD column `rule_enable` tinyint(1) NOT NULL default '1' after `rule_type`;");
 	}
 	if (!$found2) {
 		$sql[] = array("camm_add_column","plugin_camm_rule","count_triggered", "ALTER TABLE `plugin_camm_rule` ADD column `count_triggered` int(11) unsigned NOT NULL default '0';");
 	}
 	if (!$found3) {
 		$sql[] = array("camm_add_column","plugin_camm_rule","email_mode", "ALTER TABLE `plugin_camm_rule` ADD column `email_mode` tinyint(1) unsigned NOT NULL default '1' after `email`;");
 	} 
 
 	$found = false;
 	$result = db_fetch_assoc("SHOW columns FROM `" . $camm_syslog_db_name . "`.`plugin_camm_syslog`;");
	if (sizeof($result) > 0) {
	 	foreach($result as $row) {
	 		if ($row['Field'] == 'alert')
	 		$found = true;
	 	}
	}
 
 	if (!$found) {
 		$sql[] = array("camm_add_column","`" . $camm_syslog_db_name . "`.`plugin_camm_syslog`","alert", "ALTER TABLE `" . $camm_syslog_db_name . "`.`plugin_camm_syslog` ADD column `alert`  tinyint(3) NOT NULL default '0' ;");
 		$sql[] = array("camm_execute_sql","Add Index, Table -> `" . $camm_syslog_db_name . "`.`plugin_camm_syslog`, Index -> alert", "ALTER TABLE `" . $camm_syslog_db_name . "`.`plugin_camm_syslog` ADD INDEX `alert`(`alert`);");		
 	}
 
 	//proper update plugin version and plugin name
 	$sql[] = array("camm_execute_sql","update version", "UPDATE `plugin_config` SET `version`='" . $new . "', `name`='" . $n_name . "' WHERE `directory`='camm';");
 		
 		
 
 	if (!empty($sql)) {
 		for ($a = 0; $a < count($sql); $a++) {
 			$step_sql = $sql[$a];
 			$rezult = "";
 			switch ($step_sql[0]) {
 				case 'camm_execute_sql':
 					$rezult = camm_execute_sql ($step_sql[1], $step_sql[2]);
 					break;
 				case 'camm_create_table':
 					$rezult = camm_create_table ($step_sql[1], $step_sql[2]);
 					break;
 				case 'camm_add_column':
 					$rezult = camm_add_column ($step_sql[1], $step_sql[2],$step_sql[3]);
 					break;				
 				case 'camm_modify_column':
 					$rezult = camm_modify_column ($step_sql[1], $step_sql[2],$step_sql[3]);
 					break;
 				case 'camm_delete_column':
 					$rezult = camm_delete_column ($step_sql[1], $step_sql[2],$step_sql[3]);
 					break;
 				case 'camm_add_index':
 					$rezult = camm_add_index ($step_sql[1], $step_sql[2],$step_sql[3]);
 					break;
 				case 'camm_delete_index':
 					$rezult = camm_delete_index ($step_sql[1], $step_sql[2],$step_sql[3]);
 					break;
 			}
 			camm_raise_message3(array("device_descr" => "Upgrade to [" . $new . "]" , "type" => "update_db", "object"=> "update","cellpading" => false, "message" => $rezult["message"], "step_rezult" => $rezult["step_rezult"], "step_data" => $rezult["step_data"]));     
 		}
 	}
 
 
 	
 }
 
 
 function camm_execute_sql($message, $syntax) {
 	$result = db_execute($syntax);
 	$return_rezult = array();
 	
 	if ($result) {
 		$return_rezult["message"] =  "SUCCESS: Execute SQL,   $message";
 		$return_rezult["step_rezult"] = "OK";
 	}else{
 		$return_rezult["message"] =  "ERROR: Execute SQL,   $message";
 		$return_rezult["step_rezult"] = "Error";
 	}
 	$return_rezult["step_data"] = $return_rezult["step_rezult"] ;
 	return $return_rezult;
 }
 
 function camm_create_table($table, $syntax) {
 	$tables = db_fetch_assoc("SHOW TABLES LIKE '$table'");
 	$return_rezult = array();
 
 	if (!sizeof($tables)) {
 		$result = db_execute($syntax);
 		if ($result) {
 			$return_rezult["message"] =  "SUCCESS: Create Table,  Table -> $table";
 			$return_rezult["step_rezult"] = "OK";
 		}else{
 			$return_rezult["message"] =  "ERROR: Create Table,  Table -> $table";
 			$return_rezult["step_rezult"] = "Error";
 		}
 		$return_rezult["step_data"] = $return_rezult["step_rezult"] ;
 	}else{
 		$return_rezult["message"] =  "SUCCESS: Create Table,  Table -> $table";
 		$return_rezult["step_rezult"] = "OK";
 		$return_rezult["step_data"] = "Already Exists";
 	}
 	return $return_rezult;
 }
 
 function camm_add_column($table, $column, $syntax) {
 	$return_rezult = array();
 	$columns = db_fetch_assoc("SHOW COLUMNS FROM $table LIKE '$column'");
 
 	if (sizeof($columns)) {
 		$return_rezult["message"] = "SUCCESS: Add Column,    Table -> $table, Column -> $column";
 		$return_rezult["step_rezult"] = "OK";
 		$return_rezult["step_data"] = "Already Exists";
 	}else{
 		$result = db_execute($syntax);
 
 		if ($result) {
 			$return_rezult["message"] ="SUCCESS: Add Column,    Table -> $table, Column -> $column";
 			$return_rezult["step_rezult"] = "OK";
 		}else{
 			$return_rezult["message"] ="ERROR: Add Column,    Table -> $table, Column -> $column";
 			$return_rezult["step_rezult"] = "Error";
 		}
 		$return_rezult["step_data"] = $return_rezult["step_rezult"] ;
 	}
 	return $return_rezult;
 }
 
 function camm_add_index($table, $index, $syntax) {
 	$tables = db_fetch_assoc("SHOW TABLES LIKE '$table'");
 	$return_rezult = array();
 
 	if (sizeof($tables)) {
 		$indexes = db_fetch_assoc("SHOW INDEXES FROM $table");
 
 		$index_exists = FALSE;
 		if (sizeof($indexes)) {
 			foreach($indexes as $index_array) {
 				if ($index == $index_array["Key_name"]) {
 					$index_exists = TRUE;
 					break;
 				}
 			}
 		}
 
 		if ($index_exists) {
 			$return_rezult["message"] =  "SUCCESS: Add Index,     Table -> $table, Index -> $index";
 			$return_rezult["step_rezult"] = "OK";
 			$return_rezult["step_data"] = "Already Exists";
 		}else{
 			$result = db_execute($syntax);
 
 			if ($result) {
 				$return_rezult["message"] =  "SUCCESS: Add Index,     Table -> $table, Index -> $index";
 				$return_rezult["step_rezult"] = "OK";
 			}else{
 				$return_rezult["message"] =  "ERROR: Add Index,     Table -> $table, Index -> $index";
 				$return_rezult["step_rezult"] = "Error";
 			}
 			$return_rezult["step_data"] = $return_rezult["step_rezult"] ;
 		}
 	}else{
 		$return_rezult["message"] ="ERROR: Add Index,     Table -> $table, Index -> $index";
 		$return_rezult["step_rezult"] = "Error";
 		$return_rezult["step_data"] = "Table Does NOT Exist";
 	}
 	return $return_rezult;
 }
 
 function camm_modify_column($table, $column, $syntax) {
 	$tables = db_fetch_assoc("SHOW TABLES LIKE '$table'");
 	$return_rezult = array();
 
 	if (sizeof($tables)) {
 		$columns = db_fetch_assoc("SHOW COLUMNS FROM $table LIKE '$column'");
 
 		if (sizeof($columns)) {
 			$result = db_execute($syntax);
 
 			if ($result) {
 				$return_rezult["message"] =  "SUCCESS: Modify Column, Table -> $table, Column -> $column";
 				$return_rezult["step_rezult"] = "OK";
 			}else{
 				$return_rezult["message"] =  "ERROR: Modify Column, Table -> $table, Column -> $column";
 				$return_rezult["step_rezult"] = "Error";
 			}
 			$return_rezult["step_data"] = $return_rezult["step_rezult"] ;
 		}else{
 			$return_rezult["message"] =  "ERROR: Modify Column, Table -> $table, Column -> $column";
 			$return_rezult["step_rezult"] = "Error";
 			$return_rezult["step_data"] = "Column Does NOT Exist";
 		}
 	}else{
 		$return_rezult["message"] =  "ERROR: Modify Column, Table -> $table, Column -> $column";
 		$return_rezult["step_rezult"] = "Error";
 		$return_rezult["step_data"] = "Table Does NOT Exist";
 	}
 	return $return_rezult;
 }
 
 function camm_delete_column($table, $column, $syntax) {
 	$tables = db_fetch_assoc("SHOW TABLES LIKE '$table'");
 	$return_rezult = array();
 
 	if (sizeof($tables)) {
 		$columns = db_fetch_assoc("SHOW COLUMNS FROM $table LIKE '$column'");
 
 		if (sizeof($columns)) {
 			$result = db_execute($syntax);
 
 			if ($result) {
 				$return_rezult["message"] =  "SUCCESS: Delete Column, Table -> $table, Column -> $column";
 				$return_rezult["step_rezult"] = "OK";
 			}else{
 				$return_rezult["message"] =  "ERROR: Delete Column, Table -> $table, Column -> $column";
 				$return_rezult["step_rezult"] = "Error";
 			}
 			$return_rezult["step_data"] = $return_rezult["step_rezult"] ;
 		}else{
 			$return_rezult["message"] =  "SUCCESS: Delete Column, Table -> $table, Column -> $column";
 			$return_rezult["step_rezult"] = "Error";
 			$return_rezult["step_data"] = "Column Does NOT Exist";			
 		}
 	}else{
 		$return_rezult["message"] =  "SUCCESS: Delete Column, Table -> $table, Column -> $column";
 		$return_rezult["step_rezult"] = "Error";
 		$return_rezult["step_data"] = "Table Does NOT Exist";
 	}
 	return $return_rezult;
 }
 
 function camm_delete_index($table, $index, $syntax) {
 	$tables = db_fetch_assoc("SHOW TABLES LIKE '$table'");
 	$return_rezult = array();
 
 	if (sizeof($tables)) {
 		$indexes = db_fetch_assoc("SHOW INDEXES FROM $table");
 
 		$index_exists = FALSE;
 		if (sizeof($indexes)) {
 			foreach($indexes as $index_array) {
 				if ($index == $index_array["Key_name"]) {
 					$index_exists = TRUE;
 					break;
 				}
 			}
 		}
 
 		if (!$index_exists) {
 			$return_rezult["message"] =  "SUCCESS: Delete Index,     Table -> $table, Index -> $index";
 			$return_rezult["step_rezult"] = "OK";
 			$return_rezult["step_data"] = "Index Does NOT Exist!";
 		}else{
 			$result = db_execute($syntax);
 
 			if ($result) {
 				$return_rezult["message"] =  "SUCCESS: Delete Index,     Table -> $table, Index -> $index";
 				$return_rezult["step_rezult"] = "OK";
 			}else{
 				$return_rezult["message"] =  "ERROR: Delete Index,     Table -> $table, Index -> $index";
 				$return_rezult["step_rezult"] = "Error";
 			}
 			$return_rezult["step_data"] = $return_rezult["step_rezult"] ;
 		}
 	}else{
 		$return_rezult["message"] ="ERROR: Delete Index,     Table -> $table, Index -> $index";
 		$return_rezult["step_rezult"] = "Error";
 		$return_rezult["step_data"] = "Table Does NOT Exist";
 	}
 	return $return_rezult;
 }
 	
 ?>
