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
})


