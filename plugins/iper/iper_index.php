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

global $colors, $config;

$show_console_tab = true;
$show_graph_tab = true;

$oper_mode = api_plugin_hook_function('top_header', OPER_MODE_NATIVE);
if ($oper_mode == OPER_MODE_RESKIN) {
    return;
}

/* Alot of this code was taken from the top_graph_header.php */

if (read_config_option("auth_method") != 0) {

    /* find out if we should show the "console" tab or not, based on this user's permissions */
    if (sizeof(db_fetch_assoc("select realm_id from user_auth_realm where realm_id=8 and user_id=" . $_SESSION["sess_user_id"])) == 0) {
        $show_console_tab = false;
    }

    /* find out if we should show the "graph" tab or not, based on this user's permissions */
    if (sizeof(db_fetch_assoc("select realm_id from user_auth_realm where realm_id=7 and user_id=" . $_SESSION["sess_user_id"])) == 0) {
        $show_graph_tab = false;
    }
}

$page_title = api_plugin_hook_function('page_title', 'Cacti');
?>

<!doctype html>
<html ng-app="iperApp">
    <head>


        <title><?php echo $page_title; ?></title>
        <link href="<?php echo $config['url_path']; ?>images/favicon.ico" rel="shortcut icon"/>

        <?php
        // development version

        $lifeTime = 3600 * 24; // one day

        $expires = gmdate("D, d M Y H:i:s", time() + $lifeTime) . " GMT";
        echo "<META HTTP-EQUIV=EXPIRES CONTENT='$expires'>";
        echo "<META HTTP-EQUIV=Cache-Control max-age=$lifeTime pre-check=$lifeTime must-revalidate private>";
        // eof
        ?>

        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="">
        <meta name="iper" content="">

        <!-- Ext CSS and Libs -->
        <!--<link rel="stylesheet" href="bower_components/bootstrap/dist/css/bootstrap.css">-->
        <link href="bower_components/angular/angular-csp.css">
        <!--<link href="bower_components/datatables/media/css/jquery.dataTables.min.css" type="text/css" rel="stylesheet">-->
        <link href="bower_components/datatables/media/css/dataTables.bootstrap.min.css" type="text/css" rel="stylesheet">
        <!-- Bootstrap Core CSS -->
        <link href="css/bootstrap.min.css" rel="stylesheet">

        <!-- Custom CSS -->
        <link href="css/sb-admin.css" rel="stylesheet">

        <!-- Custom Fonts -->
        <link href="font-awesome/css/font-awesome.min.css" rel="stylesheet" type="text/css">

        <script src="bower_components/jquery/dist/jquery.min.js"></script>
        <!--<script src="bower_components/datatables/media/js/jquery.js"></script>-->
        <script src="bower_components/datatables/media/js/jquery.dataTables.min.js"></script>
        <script src="bower_components/datatables/media/js/dataTables.bootstrap.min.js"></script>
        <script src="bower_components/angular/angular.js"></script>
        <!--<script src="bower_components/angular-datatables/dist/angular-datatables.min.js"></script>-->
        <script src="bower_components/angular-route/angular-route.min.js"></script>
        <script src="js/app.js"></script>
        <script src="js/controllers.js"></script>

        <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
        <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
        <!--[if lt IE 9]>
            <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
            <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
        <![endif]-->

    </head>

        <?php if ($oper_mode == OPER_MODE_NATIVE) { ?>
        <body leftmargin="0" topmargin="0" marginwidth="0" marginheight="0" <?php print api_plugin_hook_function("body_style", ""); ?>>
        <?php } else { ?>
        <body leftmargin="15" topmargin="15" marginwidth="15" marginheight="15" <?php print api_plugin_hook_function("body_style", ""); ?>>
        <?php } ?>

        <div id="cacti_north" >

            <table width="100%" cellspacing="0" cellpadding="0">
                <tr height="1" bgcolor="#a9a9a9">
                    <td valign="bottom" colspan="3" nowrap>
                        <table width="100%" cellspacing="0" cellpadding="0">
                            <tr style="background: transparent url('<?php echo $config['url_path']; ?>images/cacti_backdrop.gif') no-repeat center right;">
                                <td id="tabs" valign="bottom">
                                    <?php if ($show_console_tab == true) { ?><a href="<?php echo $config['url_path']; ?>index.php">     <img src="<?php echo $config['url_path']; ?>images/tab_console.gif" alt="Console" align="absmiddle" border="0"></a><?php
                                    };
                                    if ($show_graph_tab == true) {
                                        ?><a href="<?php echo $config['url_path']; ?>graph_view.php"><img src="<?php echo $config['url_path']; ?>images/tab_graphs.gif" alt="Graphs" align="absmiddle" border="0"></a><?php
                                        };
                                        api_plugin_hook('top_header_tabs');
                                        ?>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr height="2" bgcolor="#183c8f">
                    <td colspan="3">
                        <img src="<?php echo $config['url_path']; ?>images/transparent_line.gif" height="2" border="0"><br>
                    </td>
                </tr>
                <tr height="5" bgcolor="#e9e9e9">
                    <td colspan="3">
                        <table width="100%">
                            <tr>
                                <td>
                                    <?php draw_navigation_text(); ?>
                                </td>
                                <td align="right">
                                    <?php if (read_config_option("auth_method") != 0) { ?>
                                        Logged in as <strong><?php print db_fetch_cell("select username from user_auth where id=" . $_SESSION["sess_user_id"]); ?></strong> (<a href="<?php echo $config['url_path']; ?>logout.php">Logout</a>)&nbsp;
                                    <?php } ?>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td colspan="3" height="8" style="background-image: url(<?php echo $config['url_path']; ?>images/shadow.gif); background-repeat: repeat-x;" bgcolor="#ffffff">

                    </td>
                </tr>
            </table>
        </div>

        <div id="wrapper">

            <!-- Navigation -->
            <nav class="navbar navbar-inverse navbar-fixed-top" role="navigation">
                <!-- Top Menu Items -->
                <!-- Sidebar Menu Items - These collapse to the responsive navigation menu on small screens -->
                <div class="collapse navbar-collapse navbar-ex1-collapse">
                    <ul class="nav navbar-nav side-nav">
                        <li class="li-monitor">
                            <a href="iper_index.php#monitor"><i class="fa fa-fw fa-desktop"></i> Monitor</a>
                        </li>
                        <li class="li-syslog active">
                            <a href="iper_index.php#syslog"><i class="fa fa-fw fa-table"></i> Syslog</a>
                        </li>
                        <li class="li-snmptt">
                            <a href="iper_index.php#snmptt"><i class="fa fa-fw fa-table"></i> SNMP Trap Log</a>
                        </li>
                        <li class="li-snmpttunk">
                            <a href="iper_index.php#snmpttunk"><i class="fa fa-fw fa-table"></i> SNMP Trap Unknown Log</a>
                        </li>
                        <li class="li-thold">
                            <a href="iper_index.php#thold"><i class="fa fa-fw fa-bullhorn"></i> THold</a>
                        </li>
                    </ul>
                </div>
                <!-- /.navbar-collapse -->
            </nav>

            <div id="page-wrapper">

                <div class="container-fluid" ng-view="">
                </div>
                <!-- /.container-fluid -->

            </div>
            <!-- /#page-wrapper -->
        </div>
        <!-- body -->
    </body>
</html>

