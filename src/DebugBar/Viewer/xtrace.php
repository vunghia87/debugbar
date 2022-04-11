<!doctype html>
<html>
<head>
    <title>XDebug Trace Tree</title>
    <style>
        * {
            font: 13px monospace;
            background: #000;
            color: #fff;
        }
        label {
            cursor: pointer;
        }

        div.d {
            padding-left: .5em;
            border-left: 1px solid #ccc;
            cursor: pointer;
        }

        div.d.hide {
            height: 5px;
            margin-top: 1px;
            background-color: #ccc;
        }

        div.d.hide * {
            display: none;
        }

        div.f.hide {
            height: 5px;
            margin-top: 1px;
            background-color: #a1b7cc;
            cursor: pointer;
        }

        div.f {
            cursor: auto;
            display: flex;
            flex-wrap: nowrap;
            width: 100%;
        }

        div.f.hide * {
            display: none;
        }

        div.f.mark {
            background-color: #ffeced;
        }

        div.f div {
            padding: 5px 0;

        }

        div.f div.func {
            flex-grow: 1;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        div.f div.data {
            flex-grow: 0;
            width: 600px;
            min-width: 600px;
            max-width: 600px;
        }

        span {
            display: inline-block;
            line-height: 1.1em;
            vertical-align: bottom;
        }

        span.short {
            max-height: 1.1em;
            overflow: hidden;
            word-break: break-all;
        }

        span.name {
            font-weight: bold;
            cursor: pointer;
        }

        span.params {
            color: #468b5e;
            cursor: pointer;
        }

        span.return {
            color: #8a2200;
            cursor: pointer;
        }

        span.file,
        span.time,
        span.memorydiff,
        span.timediff {
            overflow: hidden;
            width: 150px;
            text-align: right;
            white-space: nowrap;
        }

        span.time {
            cursor: pointer;
        }

        div.header {
            width: 100%;
            font-weight: bold;
            background-color: antiquewhite;
        }

        form.load {
            width: 50%;
            float: left;
        }

        select {
            width: 80%;
        }

        ul.help {
            margin: 0;
            width: 45%;
            float: left;
        }

        form.options {
            clear: both;
        }
    </style>
    <script src="https://code.jquery.com/jquery-2.2.1.min.js"></script>
    <script>
        $(function () {

            /* hide blocks */
            $('div.d, div.f').click(function (e) {
                if (e.target !== this) return;
                $(this).toggleClass('hide');
                e.preventDefault();
                e.stopPropagation();
            });

            /* collapse parameters */
            $('span.params, span.return').click(function (e) {
                if (e.target !== this) return;
                $(this).toggleClass('short');
                e.preventDefault();
                e.stopPropagation();
            });

            /* hide internal funcs */
            $('#internal').change(function () {
                $('div.i').toggle();
            });

            /* hide functions */
            $('span.name').click(function (e) {
                if (e.target !== this) return;

                var $fn = $(this);
                var name = $(this).text();
                $("span.name:contains('" + name + "')").closest('div.f').toggleClass('hide');

                e.preventDefault();
                e.stopPropagation();
            });

            /* mark important */
            $('span.time').click(function (e) {
                if (e.target !== this) return;

                $(this).closest('div.f').toggleClass('mark');

                e.preventDefault();
                e.stopPropagation();
            });

            /* hide internal funcs */
            $('#marked').change(function () {
                $('div.f').toggle();
                $('div.f.mark').show();
            });
        });
    </script>
</head>
<body>

<form method="post" class="load">
    <label for="file">File:</label>
    <select name="file" id="file">
        <?php
        $dir = ini_get('xdebug.output_dir');
        if (!$dir) {
            $dir = '/tmp/';
        }
        $files = glob("$dir/*.xt");
        foreach ($files as $file) {
            $checked = ($file == $request['file']) ? 'selected="selected"' : '';
            echo '<option value="' . htmlspecialchars($file) . '" ' . $checked . '>' . htmlspecialchars(basename($file)) . '</option>';
        }
        ?>
    </select>
    <button type="submit">Load</button>
    <br/>

    <p>Files are read from <code>xdebug.output_dir = <?php echo htmlspecialchars($dir) ?></code></p>
</form>

<form class="options">
    <input type="checkbox" value="1" checked="checked" id="internal">
    <label for="internal">Show internal functions</label>

    <input type="checkbox" value="1" id="marked">
    <label for="marked">Show important only (slow)</label>
</form>


<?php

if (!empty($request['file'])) {
    $parser = new \DebugBar\Supports\XDebugParser($request['file']);
    $parser->parse();
    echo $parser->getTraceHTML();
}
?>

</body>
</html>