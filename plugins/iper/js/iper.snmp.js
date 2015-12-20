(function ($) {
    function loadSyslogs(data) {
        var url = 'camm_db.php';
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
                {"data": "traptime", 'title': 'Time'},
            ]
        });
    }

    $(function () {
        var data = {
            start: 0,
            limit: 1000,
            filter: null,
            task: 'camm_get_traps_records',
            tree_id: 'root'
        };
        data[csrfMagicName] = csrfMagicToken;
        $(document).ready(function () {
            loadSyslogs(data);
        });
    });
})(jQuery);
