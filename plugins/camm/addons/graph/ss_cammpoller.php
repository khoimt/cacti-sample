<?php

$no_http_headers = true;

/* display ALL errors */
error_reporting(E_ALL);

if (!isset($called_by_script_server)) {
	include_once(dirname(__FILE__) . "/../include/global.php");

	print call_user_func("ss_cammpoller") . "\n";
	print call_user_func("ss_cammpoller_time") . "\n";
}

function ss_cammpoller() {
	global $database_username;
	global $database_password;
	global $database_hostname;
	global $database_default;
	global $database_type;

/*	db_connect_real($database_hostname, $database_username, $database_password, $database_default, $database_type);*/
	$stats = db_fetch_cell("select value from settings where name='stats_camm'");
	if (read_config_option("camm_use_syslog") == "0") {
		$count_syslogs = db_fetch_cell("select count(*) from " . read_config_option("camm_syslog_db_name") . ".plugin_camm_syslog;");
	}else{
		$count_syslogs = 0;
	}
	$count_traps = db_fetch_cell("select count(*) from plugin_camm_snmptt;");
	$count_unk_traps = db_fetch_cell("select count(*) from plugin_camm_snmptt_unk;");
	$max_row = db_fetch_cell("select value from settings where name='camm_max_row_all'");
	$ruledel_traps = db_fetch_cell("select value from settings where name='camm_stats_ruledel'");

	return trim($stats . "count_traps:" . $count_traps . " count_unk_traps:" . $count_unk_traps . " max_row:" . $max_row . " ruledel_traps:" . $ruledel_traps . " count_syslogs:" . $count_syslogs);
}

function ss_cammpoller_time() {
	global $database_username;
	global $database_password;
	global $database_hostname;
	global $database_default;
	global $database_type;

	$stats = db_fetch_cell("select value from settings where name='camm_stats_time'");
	$stats_camm_tree = db_fetch_cell("select value from settings where name='camm_stats_snmptt_tree'");
	$stats_syslog_tree = db_fetch_cell("select value from settings where name='camm_stats_syslog_tree'");

	return trim($stats . "" . $stats_camm_tree . "" . $stats_syslog_tree);
}

?>
