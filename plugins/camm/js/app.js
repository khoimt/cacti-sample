/* App Module */
var iperApp = angular.module('iperApp', [
    'ngRoute',
    'iperControllers'
]);

(function () {
    iperApp.factory('jQuery', [
        '$window',
        function ($window) {
            return $window.jQuery;
        }
    ]);

    //jquery
    $(document).ready(function () {
        $('ul.nav li').click(function () {
            $('ul.nav li').removeClass('active');
            $(this).addClass('active');
        });
    })
})();

iperApp.config(['$routeProvider',
    function ($routeProvider) {
        $routeProvider.
                when('/monitor', {
                    templateUrl: 'partials/monitor.html',
                    controller: 'MonitorCtrl'
                }).
                when('/thold', {
                    templateUrl: 'partials/thold.html',
                    controller: 'TholdCtrl'
                }).
                when('/syslog', {
                    templateUrl: 'partials/syslog.html',
                    controller: 'SyslogCtrl'
                }).
                when('/snmptt', {
                    templateUrl: 'partials/snmptt.html',
                    controller: 'SnmpttCtrl'
                }).
                when('/snmpttunk', {
                    templateUrl: 'partials/snmpttunk.html',
                    controller: 'SnmpttUnkCtrl'
                }).
                otherwise({
                    redirectTo: '/monitor'
                });
    }]);
