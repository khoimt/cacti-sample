
/* Controllers */
var url = 'camm_db.php';
var data = {
    start: 0,
    limit: 1000,
    filter: null,
    task: 'camm_get_traps_records',
    tree_id: 'root'
};
var iperControllers = angular.module('iperControllers', []);

iperControllers.controller('MonitorCtrl', ['$scope', '$http', 'jQuery',
    function ($scope, $http, $) {
        $('ul.nav li').removeClass('active');
        $('ul.nav .li-monitor').addClass('active');

        $.ajax({
            url: "../monitor/monitor.php",
            type: 'html',
            success: function (html) {
                $('.monitor-detail-content', $(html)).appendTo('.monitor-content');
            },
            error: function () {
                alert('Có lỗi xảy ra, vui lòng thử lại');
            }
        })
    }]);

iperControllers.controller('TholdCtrl', ['$scope', '$http', 'jQuery',
    function ($scope, $http, $) {
        $('ul.nav li').removeClass('active');
        $('ul.nav .li-thold').addClass('active');

        $.ajax({
            url: "../thold/thold_graph.php",
            type: 'html',
            success: function (html) {
                $('.thold-detail-content', $(html)).appendTo('.thold-content');
            },
            error: function () {
                alert('Có lỗi xảy ra, vui lòng thử lại');
            }
        })
    }]);

iperControllers.controller('SyslogCtrl', ['$scope', '$http', 'jQuery',
    function ($scope, $http, $) {
        $('ul.nav li').removeClass('active');
        $('ul.nav .li-syslog').addClass('active');

        var dt = angular.extend(data);
        dt['task'] = 'camm_get_syslog_records';
        dt[csrfMagicName] = csrfMagicToken;
        data[csrfMagicName] = csrfMagicToken;

        $('#syslog-table').DataTable({
            'processing': true,
            'serverSide': true,
            'autoWidth': true,
            'order': [
                [0, 'desc']
            ],
            'ajax': {
                'url': url,
                'type': 'POST',
                'data': data,
                'dataSrc': 'results'
            },
            'columns': [
                {"data": "id", 'title': 'ID'},
                {"data": "device_id", 'title': 'Device ID'},
                {"data": "host", 'title': 'Host'},
                {"data": "facility", 'title': 'Facility'},
                {"data": "priority", 'title': 'Priority'},
                {"data": "sourceip", 'title': 'IP'},
                {"data": "message", 'title': 'Message'},
                {"data": "sys_date", 'title': 'Time'}
            ]
        });
    }]);

iperControllers.controller('SnmpttCtrl', ['$scope', '$routeParams', 'jQuery',
    function ($scope, $routeParams, $) {
        $('ul.nav li').removeClass('active');
        $('ul.nav .li-snmptt').addClass('active');
        var dt = angular.extend(data);
        dt['task'] = 'camm_get_traps_records';
        dt[csrfMagicName] = csrfMagicToken;

        $('#snmp-table').DataTable({
            'ajax': {
                'url': url,
                'type': 'POST',
                'data': data,
                'dataSrc': 'results'
            },
            'columns': [
                {"data": "hostname", 'title': 'Host'},
                {"data": "agentip", 'title': 'Agent IP'},
                {"data": "description", 'title': 'Description'},
                {"data": "severity", 'title': 'Severity'},
                {"data": "trapoid", 'title': 'OID'},
                {"data": "eventname", 'title': 'Event'},
                {"data": "formatline", 'title': 'Format line'},
                {"data": "category", 'title': 'Category'},
                {"data": "traptime", 'title': 'Time'}
            ]
        });
    }]);

iperControllers.controller('SnmpttUnkCtrl', ['$scope', '$routeParams', 'jQuery',
    function ($scope, $routeParams, $) {
        $('ul.nav li').removeClass('active');
        $('ul.nav .li-snmpttunk').addClass('active');
        var dt = angular.extend(data);
        dt['task'] = 'camm_get_unktraps_records';
        dt[csrfMagicName] = csrfMagicToken;

        $('#snmp-unk-table').DataTable({
            'ajax': {
                'url': url,
                'type': 'POST',
                'data': data,
                'dataSrc': 'results'
            },
            'columns': [
                {"data": "hostname", 'title': 'Host'},
                {"data": "agentip", 'title': 'Agent IP'},
                {"data": "description", 'title': 'Description'},
                {"data": "enterprise", 'title': 'Enterprise'},
                {"data": "trapoid", 'title': 'OID'},
                {"data": "formatline", 'title': 'Format line'}
            ]
        });
    }]);
