(function ($) {
    function loadSyslogs(data) {
        var url = 'camm_db.php';
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
                {"data": "formatline", 'title': 'Format line'},
            ]
        });
    }

    $(function () {
        var data = {
            start: 0,
            limit: 1000,
            filter: null,
            task: 'camm_get_unktraps_records',
            tree_id: 'root'
        };
        data[csrfMagicName] = csrfMagicToken;
        $(document).ready(function () {
            loadSyslogs(data);
        });
    });
})(jQuery);
