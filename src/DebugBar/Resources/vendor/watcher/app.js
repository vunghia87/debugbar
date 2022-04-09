$(document).ready(function () {
    var base_url = window.location.origin + '?openPhpDebugBar=true';

    $('#btn-clear').click(function () {
        $.ajax({
            dataType: 'json',
            url: base_url,
            data: {op: 'clear'},
            success: function (res) {
                window.location.href = base_url + '&op=all'
            },
        })
    })

    $('#btn-toggle').click(function () {
        var $this = $(this);
        $.ajax({
            dataType: 'json',
            url: base_url,
            data: {op: 'toggle'},
            success: function (res) {
                var path = res.result ?  'M4 4l12 6-12 6z' : 'M5 4h3v12H5V4zm7 0h3v12h-3V4z';
                $this.find('path').attr('d', path)
            },
        })
    })

    $('.btn-query-trace').click(function () {
        $(this).closest('tr').next().toggleClass('hidden');
    })

    $('.btn-mes-trace').click(function () {
        $(this).closest('.card-content').find('.bt').toggleClass('hidden');
    })

    $('.btn-code').click(function(){
        var id = $("#content").data('id');
        var index = $(this).data('index');
        var type = $(this).closest('.card').attr('id');
        window.open(base_url + '&op=frame&id=' + id + '&index=' + index + '&type=' + type);
    })

    if ($('#pdo').length) {
        var sqls = [];
        $("#pdo").find('.sql').each(function () {
            var sql = $(this).text().trim().toLowerCase();
            var action = sql.substr(0, 6);
            if (action === 'insert' || action === 'update' || action === 'delete') {
                $(this).closest('tr').addClass('bg-danger');
            }

            var indexOf = sqls.indexOf(sql);
            if (indexOf === -1) {
                sqls.push(sql);
            } else {
                $(this).closest('tr').addClass('bg-warning');
            }
        }).promise().done(function () {
            $("#unique").text(sqls.length);
        })

        $('#searchQuery').change(function () {
            var keywords = $(this).val().trim().replace(/ +(?= )/g,'').split(' ');
            if (keywords.length == 0) {
                $("#pdo tr:odd").removeClass('hidden');
                return;
            }

            $("#pdo").find('.sql').each(function () {
                var sql = $(this).text().trim().toLowerCase();
                var check = true;
                keywords.forEach(function (keyword) {
                    if (!sql.includes(keyword)) {
                        check = false;
                    }
                })
                check ? $(this).closest('tr').removeClass('hidden') : $(this).closest('tr').addClass('hidden')
            })
        });

        collapseAll();
    }
})


