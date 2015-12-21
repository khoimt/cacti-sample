
/* Controllers */
var url = 'iper_ajax.php';
var data = {
    start: 0,
    limit: 1000,
    length: 1000,
    filter: null,
    tree_id: 'root'
};
var iperControllers = angular.module('iperControllers', []);

/*************************************************/

function loadMonitor(url) {
    $.ajax({
        url: url,
        type: 'html',
        success: function (html) {
            $('.monitor-content').html('');
            $('.monitor-detail-content', $(html)).appendTo('.monitor-content');
        },
        error: function () {
            alert('Có lỗi xảy ra, vui lòng thử lại');
        }
    })
}

iperControllers.controller('MonitorCtrl', ['$scope', '$rootScope', '$http', 'jQuery', '$interval',
    function ($scope, $rootScope, $http, $, $interval) {
        $('ul.nav li').removeClass('active');
        $('ul.nav .li-monitor').addClass('active');

        loadMonitor("../monitor/monitor.php");
        var $stopTime = $interval(function () {
            loadMonitor("../monitor/monitor.php");
        }, 3000);

        $rootScope.$on('$locationChangeSuccess', function () {
            $interval.cancel($stopTime);
        });

    }]);

/*************************************************/
function getURLForm(form, isSubmit) {
    var url = "/plugins/thold/thold_graph.php?";
    var urlElements = [];
    $('[name]', form).each(function () {
        if (isSubmit && $(this).attr('name') == 'clear') {
            return;
        }
        urlElements.push($(this).attr("name") + "=" + encodeURIComponent($(this).val()));
    });
    urlElements = urlElements.join("&");
    url += urlElements;
    return url;
}

function loadThold(url) {
    $.ajax({
        url: url,
        type: 'html',
        success: function (html) {
            $('.thold-content').html('');
            $('.thold-detail-content', $(html)).appendTo('.thold-content');
            $('.thold-detail-content a').unbind('click').click(function (event) {
                event.preventDefault();
                loadThold($(this).attr('href'));
                return false;
            });

            $('input[type=submit]', '.thold-content form').each(function () {
                $(this).unbind('click').click(function (event) {
                    event.preventDefault();
                    var form = $(this).closest('form');
                    var url = getURLForm(form, $(this).val() == 'Go');
                    loadThold(url);
                    return false;
                })
            })

            $('select', '.thold-content form').removeAttr('onChange');
            $('select', '.thold-content form').unbind('change');

            $('select', '.thold-content form').change(function () {
                var form = $(this).closest('form');
                var url = getURLForm(form, true);
                loadThold(url);
                return false;
            });
        },
        error: function () {
            alert('Có lỗi xảy ra, vui lòng thử lại');
        }
    })
}

iperControllers.controller('TholdCtrl', ['$scope', '$http', 'jQuery',
    function ($scope, $http, $) {
        $('ul.nav li').removeClass('active');
        $('ul.nav .li-thold').addClass('active');

        loadThold("../thold/thold_graph.php");
    }]);

/*************************************************/

iperControllers.controller('SyslogCtrl', ['$scope', '$http', 'jQuery',
    function ($scope, $http, $) {
        $('ul.nav li').removeClass('active');
        $('ul.nav .li-syslog').addClass('active');

        var dt = {
            filter: null,
            tree_id: 'root'
        };
        dt['task'] = 'iper_get_syslogs';
        dt[csrfMagicName] = csrfMagicToken;
        data[csrfMagicName] = csrfMagicToken;

        $('#syslog-table').DataTable({
            'processing': true,
            'serverSide': true,
            'autoWidth': true,
            'ordering': false,
            'searching': false,
            'order': [
                [0, 'desc']
            ],
            'ajax': {
                'url': url,
                'type': 'POST',
                'data': dt,
                'dataSrc': 'results'
            },
            'columns': [
                {"data": "id", 'title': 'ID'},
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
        dt['task'] = 'iper_get_snmptt_traps';
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
                {"data": "severity", 'title': 'Severity'},
                {"data": "trapoid", 'title': 'OID'},
                {"data": "eventname", 'title': 'Event'},
                {"data": "formatline", 'title': 'Format line'},
                {"data": "category", 'title': 'Category'},
                {"data": "created", 'title': 'Time'}
            ]
        });
    }]);

iperControllers.controller('SnmpttUnkCtrl', ['$scope', '$routeParams', 'jQuery',
    function ($scope, $routeParams, $) {
        $('ul.nav li').removeClass('active');
        $('ul.nav .li-snmpttunk').addClass('active');
        var dt = angular.extend(data);
        dt['task'] = 'iper_get_snmptt_unks';
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
                {"data": "enterprise", 'title': 'Enterprise'},
                {"data": "trapoid", 'title': 'OID'},
                {"data": "formatline", 'title': 'Format line'},
                {"data": "created", 'title': 'Time'}
            ]
        });
    }]);
