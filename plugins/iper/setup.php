<?php

function plugin_iper_install() {

    api_plugin_register_hook('iper', 'top_header_tabs', 'plugin_iper_show_tab', 'includes/tab.php');
    api_plugin_register_hook('iper', 'top_graph_header_tabs', 'plugin_iper_show_tab', 'includes/tab.php');
    api_plugin_register_hook('iper', 'config_arrays', 'iper_config_arrays', 'setup.php');
    api_plugin_register_hook('iper', 'config_settings', 'iper_config_settings', 'setup.php');
    api_plugin_register_hook('iper', 'draw_navigation_text', 'iper_draw_navigation_text', 'setup.php');
    api_plugin_register_hook('iper', 'poller_bottom', 'iper_poller_bottom', 'setup.php');
    api_plugin_register_hook('iper', 'page_title', 'iper_page_title', 'setup.php');

	api_plugin_register_realm('iper', 'iper_index.php,iper_db.php', 'Plugin -> iper: View', 1);
	api_plugin_register_realm('iper', 'iper_db_admin.php', 'Plugin -> iper: Manage', 1);

	iper_setup_table();
}


function plugin_init_iper() {

    global $plugin_hooks;
    $plugin_hooks['config_arrays']['iper'] = 'iper_config_arrays';
    $plugin_hooks['config_settings']['iper'] = 'iper_config_settings'; // Settings tab
    $plugin_hooks['top_header_tabs']['iper'] = 'plugin_iper_show_tab'; // Top tab
    $plugin_hooks['top_graph_header_tabs']['iper'] = 'plugin_iper_show_tab'; // Top tab for graphs
    $plugin_hooks['draw_navigation_text']['iper'] = 'iper_draw_navigation_text';
    $plugin_hooks['poller_top']['iper'] = 'iper_poller_bottom';
}



function plugin_iper_show_tab() {

    global $config, $user_auth_realm_filenames;
    $realm_id2 = 0;

    if (isset($user_auth_realm_filenames{basename('iper_index.php')})) {
        $realm_id2 = $user_auth_realm_filenames{basename('iper_index.php')};
    }

    if ((db_fetch_assoc("select user_auth_realm.realm_id
 		from user_auth_realm where user_auth_realm.user_id='" . $_SESSION["sess_user_id"]
                    . "'and user_auth_realm.realm_id='$realm_id2'")) || (empty($realm_id2))) {
        print '<a href="' . $config['url_path'] . 'plugins/iper/iper_index.php"><img src="' . $config['url_path'] . 'plugins/iper/images/tab_iper';
        // Red tab code
        if (preg_match('/plugins\/iper\/iper_index.php/', $_SERVER['REQUEST_URI'], $matches) 
				|| preg_match('/plugins\/iper\/iper_index.php/', $_SERVER['REQUEST_URI'], $matches)) {
            print "_red";
        }

        print '.jpg" alt="iper" align="absmiddle" border="0"></a>';
    }
}

function iper_page_title($in) {
    global $config;

    $out = $in;

    $url = $_SERVER['REQUEST_URI'];

    if (preg_match('#/plugins/iper/iper_index.php#', $url)) {
        $out .= " - IPER Plugin";
    }

 	return ($out);
}

function plugin_iper_uninstall() {
    // Do any extra Uninstall stuff here
    db_execute("delete FROM settings where name like 'iper%';");
    kill_session_var("iper_output_messages");
}

function plugin_iper_check_config() {
    return true;
}

function plugin_iper_version() {
    // Here we will upgrade to the newest version
    return iper_version();
}

function iper_config_arrays() {
    global $user_auth_realms, $menu, $user_auth_realm_filenames;
    global $camm_poller_frequencies, $camm_purge_delay, $camm_purge_tables, $camm_rows_test, $camm_tree_update, $camm_rows_selector, $camm_grid_update;


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

function iper_config_settings() {
    global $tabs;
}

function iper_draw_navigation_text($nav) {
    $nav["iper_index.php:"] = array("title" => "iper", "mapping" => "index.php:", "url" => "iper_index.php", "level" => "1");
    return $nav;
}

function iper_poller_bottom() {
    global $config;
    $command_string = read_config_option("path_php_binary");
    $extra_args = "-q " . $config["base_path"] . "/plugins/iper/poller.php";
    exec_background($command_string, "$extra_args");
}

function iper_version() {
    return array(
        'name' => 'IPER',
        'version' => '1.0',
        'longname' => 'IPER',
        'author' => 'iper',
        'homepage' => 'http://example.com',
        'url' => '',
        'email' => ''
    );
}

/**
 * 
 * require thold and monitor
 * 
 * @global type $plugins
 * @global type $config
 * @return type
 */
function iper_check_dependencies() {
    global $plugins, $config;
	return in_array('thold', $plugins) && in_array('thold', $plugins);
}

function iper_setup_table() {
    global $config, $database_default;
    include_once($config["library_path"] . "/database.php");

    db_execute('DROP TABLE IF EXISTS `plugin_iper_snmptt`');

    $schema = array();
    $schema['columns'][] = array('name' => 'id', 'type' => 'int(11)', 'unsigned' => true, 'NULL' => false, 'auto_increment' => true);
    $schema['columns'][] = array('name' => 'eventname', 'type' => 'varchar(50)', 'NULL' => true);
    $schema['columns'][] = array('name' => 'eventid', 'type' => 'varchar(50)', 'NULL' => true);
    $schema['columns'][] = array('name' => 'trapoid', 'type' => 'varchar(100)', 'NULL' => true);
    $schema['columns'][] = array('name' => 'enterprise', 'type' => 'varchar(100)', 'NULL' => true);
    $schema['columns'][] = array('name' => 'community', 'type' => 'varchar(20)', 'NULL' => true);
    $schema['columns'][] = array('name' => 'hostname', 'type' => 'varchar(250)', 'NULL' => true);
    $schema['columns'][] = array('name' => 'agentip', 'type' => 'varchar(16)', 'NULL' => true);
    $schema['columns'][] = array('name' => 'category', 'type' => 'varchar(20)', 'NULL' => true);
    $schema['columns'][] = array('name' => 'severity', 'type' => 'varchar(20)', 'NULL' => true);
    $schema['columns'][] = array('name' => 'uptime', 'type' => 'varchar(20)', 'NULL' => true);
    $schema['columns'][] = array('name' => 'traptime', 'type' => 'datetime', 'NULL' => true);
    $schema['columns'][] = array('name' => 'formatline', 'type' => 'text', 'NULL' => true);
    $schema['columns'][] = array('name' => 'created', 'type' => 'timestamp', 'NULL' => true, 'default' => 'CURRENT_TIMESTAMP');
    $schema['primary'] = 'id';
    $schema['keys'][] = array('name' => 'hostname', 'columns' => 'hostname');
    $schema['type'] = 'MyISAM';
    $schema['comment'] = 'snpm trap data';

    api_plugin_db_table_create('iper', 'plugin_iper_snmptt', $schema);

    $schema = array();

    db_execute('DROP TABLE IF EXISTS plugin_iper_snmptt_unk');
    $schema['columns'][] = array('name' => 'id', 'type' => 'int(11)', 'unsigned' => true, 'NULL' => false, 'auto_increment' => true);
    $schema['columns'][] = array('name' => 'trapoid', 'type' => 'varchar(100)', 'NULL' => true);
    $schema['columns'][] = array('name' => 'enterprise', 'type' => 'varchar(100)', 'NULL' => true);
    $schema['columns'][] = array('name' => 'community', 'type' => 'varchar(20)', 'NULL' => true);
    $schema['columns'][] = array('name' => 'hostname', 'type' => 'varchar(250)', 'NULL' => true);
    $schema['columns'][] = array('name' => 'agentip', 'type' => 'varchar(16)', 'NULL' => true);
    $schema['columns'][] = array('name' => 'uptime', 'type' => 'varchar(20)', 'NULL' => true);
    $schema['columns'][] = array('name' => 'traptime', 'type' => 'datetime', 'NULL' => true);
    $schema['columns'][] = array('name' => 'formatline', 'type' => 'text', 'NULL' => true);
    $schema['columns'][] = array('name' => 'created', 'type' => 'timestamp', 'NULL' => true, 'default' => 'CURRENT_TIMESTAMP');
    $schema['primary'] = 'id';
    $schema['keys'][] = array('name' => 'id', 'columns' => 'id');
    $schema['type'] = 'MyISAM';
    $schema['comment'] = 'Unkonwn trap data';

    api_plugin_db_table_create('iper', 'plugin_iper_snmptt_unk', $schema, 1);

    db_execute('DROP TABLE IF EXISTS plugin_iper_syslog');
    $schema = array();
    $schema['columns'][] = array('name' => 'id', 'type' => 'int(11)', 'unsigned' => true, 'NULL' => false, 'auto_increment' => true);
    $schema['columns'][] = array('name' => 'host', 'type' => 'varchar(128)', 'NULL' => true);
    $schema['columns'][] = array('name' => 'sourceip', 'type' => 'varchar(45)', 'NULL' => true);
    $schema['columns'][] = array('name' => 'facility', 'type' => 'varchar(10)', 'NULL' => true);
    $schema['columns'][] = array('name' => 'priority', 'type' => 'varchar(10)', 'NULL' => true);
    $schema['columns'][] = array('name' => 'sys_date', 'type' => 'datetime', 'NULL' => true);
    $schema['columns'][] = array('name' => 'message', 'type' => 'text', 'NULL' => true);
    $schema['columns'][] = array('name' => 'status', 'type' => 'tinyint(4)', 'NULL' => true);
    $schema['columns'][] = array('name' => 'alert', 'type' => 'tinyint(3)', 'NULL' => true);
    $schema['primary'] = 'id';
    $schema['keys'][] = array('name' => 'facility', 'columns' => 'facility');
    $schema['keys'][] = array('name' => 'priority', 'columns' => 'priority');
    $schema['keys'][] = array('name' => 'sourceip', 'columns' => 'sourceip');
    $schema['keys'][] = array('name' => 'status', 'columns' => 'status');
    $schema['keys'][] = array('name' => 'sys_date', 'columns' => 'sys_date');
    $schema['keys'][] = array('name' => 'alert', 'columns' => 'alert');
    $schema['type'] = 'MyISAM';
    $schema['comment'] = 'syslog data';

    api_plugin_db_table_create('iper', 'plugin_iper_syslog', $schema);

    $schema['columns'][6] = array('name' => 'message', 'type' => 'varchar(255)', 'NULL' => true);
    $schema['type'] = 'MEMORY';
    $schema['comment'] = 'syslog imcoming data';

    api_plugin_db_table_create('iper', 'plugin_iper_syslog_incoming', $schema);
}

function iper_execute_sql($message, $syntax) {
    $result = db_execute($syntax);
    $return_rezult = array();

    if ($result) {
        $return_rezult["message"] = "SUCCESS: Execute SQL,   $message";
        $return_rezult["step_rezult"] = "OK";
    } else {
        $return_rezult["message"] = "ERROR: Execute SQL,   $message";
        $return_rezult["step_rezult"] = "Error";
    }
    $return_rezult["step_data"] = $return_rezult["step_rezult"];
    return $return_rezult;
}

function iper_create_table($table, $syntax) {
    $tables = db_fetch_assoc("SHOW TABLES LIKE '$table'");
    $return_rezult = array();

    if (!sizeof($tables)) {
        $result = db_execute($syntax);
        if ($result) {
            $return_rezult["message"] = "SUCCESS: Create Table,  Table -> $table";
            $return_rezult["step_rezult"] = "OK";
        } else {
            $return_rezult["message"] = "ERROR: Create Table,  Table -> $table";
            $return_rezult["step_rezult"] = "Error";
        }
        $return_rezult["step_data"] = $return_rezult["step_rezult"];
    } else {
        $return_rezult["message"] = "SUCCESS: Create Table,  Table -> $table";
        $return_rezult["step_rezult"] = "OK";
        $return_rezult["step_data"] = "Already Exists";
    }
    return $return_rezult;
}

function iper_add_column($table, $column, $syntax) {
    $return_rezult = array();
    $columns = db_fetch_assoc("SHOW COLUMNS FROM $table LIKE '$column'");

    if (sizeof($columns)) {
        $return_rezult["message"] = "SUCCESS: Add Column,    Table -> $table, Column -> $column";
        $return_rezult["step_rezult"] = "OK";
        $return_rezult["step_data"] = "Already Exists";
    } else {
        $result = db_execute($syntax);

        if ($result) {
            $return_rezult["message"] = "SUCCESS: Add Column,    Table -> $table, Column -> $column";
            $return_rezult["step_rezult"] = "OK";
        } else {
            $return_rezult["message"] = "ERROR: Add Column,    Table -> $table, Column -> $column";
            $return_rezult["step_rezult"] = "Error";
        }
        $return_rezult["step_data"] = $return_rezult["step_rezult"];
    }
    return $return_rezult;
}

function iper_add_index($table, $index, $syntax) {
    $tables = db_fetch_assoc("SHOW TABLES LIKE '$table'");
    $return_rezult = array();

    if (sizeof($tables)) {
        $indexes = db_fetch_assoc("SHOW INDEXES FROM $table");

        $index_exists = FALSE;
        if (sizeof($indexes)) {
            foreach ($indexes as $index_array) {
                if ($index == $index_array["Key_name"]) {
                    $index_exists = TRUE;
                    break;
                }
            }
        }

        if ($index_exists) {
            $return_rezult["message"] = "SUCCESS: Add Index,     Table -> $table, Index -> $index";
            $return_rezult["step_rezult"] = "OK";
            $return_rezult["step_data"] = "Already Exists";
        } else {
            $result = db_execute($syntax);

            if ($result) {
                $return_rezult["message"] = "SUCCESS: Add Index,     Table -> $table, Index -> $index";
                $return_rezult["step_rezult"] = "OK";
            } else {
                $return_rezult["message"] = "ERROR: Add Index,     Table -> $table, Index -> $index";
                $return_rezult["step_rezult"] = "Error";
            }
            $return_rezult["step_data"] = $return_rezult["step_rezult"];
        }
    } else {
        $return_rezult["message"] = "ERROR: Add Index,     Table -> $table, Index -> $index";
        $return_rezult["step_rezult"] = "Error";
        $return_rezult["step_data"] = "Table Does NOT Exist";
    }
    return $return_rezult;
}

?>
