(function ($) {
    function loadSyslogs(data) {
        var url = 'camm_db.php';
        $('#syslog-table').DataTable({
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
    }

    $(function () {
        var data = {
            start: 0,
            limit: 1000,
            filter: null,
            task: 'camm_get_syslog_records',
            tree_id: 'root'
        };
        data[csrfMagicName] = csrfMagicToken;
        $(document).ready(function () {
            loadSyslogs(data);
        });
    });
})(jQuery);
