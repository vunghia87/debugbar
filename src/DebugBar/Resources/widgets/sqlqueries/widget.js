(function ($) {

    var csscls = PhpDebugBar.utils.makecsscls('phpdebugbar-widgets-');

    /**
     * Widget for the displaying sql queries
     *
     * Options:
     *  - data
     */
    var SQLQueriesWidget = PhpDebugBar.Widgets.SQLQueriesWidget = PhpDebugBar.Widget.extend({

        className: csscls('sqlqueries'),

        onCopyToClipboard: function (el) {
            var code = $(el).parent('li').find('code').get(0);
            var copy = function () {
                try {
                    document.execCommand('copy');
                    alert('Query copied to the clipboard');
                } catch (err) {
                    console.log('Oops, unable to copy');
                }
            };
            var select = function (node) {
                if (document.selection) {
                    var range = document.body.createTextRange();
                    range.moveToElementText(node);
                    range.select();
                } else if (window.getSelection) {
                    var range = document.createRange();
                    range.selectNodeContents(node);
                    window.getSelection().removeAllRanges();
                    window.getSelection().addRange(range);
                }
                copy();
                window.getSelection().removeAllRanges();
            };
            select(code);
        },
        onToggleCode: function (el, sql) {
            el.find('.' + csscls('sql')).remove();
            $('<pre />').addClass(csscls('sql')).prependTo(el).promise().done(function () {
                $('<code />').html(PhpDebugBar.Widgets.highlight(sql, 'sql')).appendTo($(this));
            });
        },
        render: function () {
            var self = this;
            this.$status = $('<div />').addClass(csscls('status')).appendTo(this.$el);
            this.$toolbar = $('<div></div>').addClass(csscls('toolbar')).appendTo(this.$el);

            this.$list = new PhpDebugBar.Widgets.ListWidget({
                itemRenderer: function (li, stmt) {
                    this.$sql = $('<div />').addClass(csscls('sql')).addClass(csscls('ellipsis')).text(stmt.sql).appendTo(li);

                    if (stmt.duration_str) {
                        $('<span title="Duration" />').addClass(csscls('duration')).text(stmt.duration_str).appendTo(li);
                    }
                    if (stmt.memory_str) {
                        $('<span title="Memory usage" />').addClass(csscls('memory')).text(stmt.memory_str).appendTo(li);
                    }
                    if (typeof (stmt.row_count) != 'undefined') {
                        $('<span title="Row count" />').addClass(csscls('row-count')).text(stmt.row_count).appendTo(li);
                    }
                    if (typeof (stmt.stmt_id) != 'undefined' && stmt.stmt_id) {
                        $('<span title="Prepared statement ID ' + stmt.stmt_id + '" />').css('margin-left', '5px').addClass(csscls('stmt-id')).appendTo(li);
                    }
                    if (stmt.connection) {
                        $('<span title="Connection" />').addClass(csscls('database')).text(stmt.connection).appendTo(li);
                        li.attr("connection", stmt.connection);
                    }
                    if (typeof (stmt.is_success) != 'undefined' && !stmt.is_success) {
                        li.addClass(csscls('error'));
                        li.append($('<span />').addClass(csscls('error')).text("[" + stmt.error_code + "] " + stmt.error_message));
                    }
                    if (typeof (stmt.match) != 'undefined' && stmt.match) {
                        li.addClass(csscls('matching'));
                    }
                    $('<span title="Copy to clipboard" />')
                        .addClass(csscls('copy-clipboard'))
                        .css('cursor', 'pointer')
                        .on('click', function (event) {
                            self.onCopyToClipboard(this);
                            event.stopPropagation();
                        })
                        .appendTo(li);

                    var table = $('<table></table>').addClass(csscls('params')).appendTo(li);

                    li.css('cursor', 'pointer').click(function (e) {
                        if (!$(e.target).is("a")) {
                            if (table.is(':visible')) {
                                table.hide();
                            } else {
                                table.show();
                            }
                            self.onToggleCode($(this), stmt.sql);
                        }
                    });

                    if (stmt.hints && stmt.hints.length) {
                        table.append(function () {
                            var icon = 'question-circle';
                            var $icon = '<i class="phpdebugbar-fa phpdebugbar-fa-' + icon + ' phpdebugbar-text-muted"></i>';
                            var $name = $('<td />').addClass(csscls('name')).html('Hints ' + $icon);
                            var $value = $('<td />').addClass(csscls('value'));

                            var $hints = new PhpDebugBar.Widgets.ListWidget({
                                itemRenderer: function (li, hint) {
                                    li.append(hint).removeClass(csscls('list-item')).addClass(csscls('table-list-item'));
                                }
                            });

                            $hints.set('data', stmt.hints);
                            $hints.$el
                                .removeClass(csscls('list'))
                                .addClass(csscls('table-list'))
                                .appendTo($value);

                            return $('<tr />').append($name, $value);
                        });
                    }

                    if (stmt.params && !$.isEmptyObject(stmt.params)) {
                        table.append(function () {
                            var icon = 'thumb-tack';
                            var $icon = '<i class="phpdebugbar-fa phpdebugbar-fa-' + icon + ' phpdebugbar-text-muted"></i>';
                            var $name = $('<td />').addClass(csscls('name')).html('Bindings ' + $icon);

                            for (var key in stmt.params) {
                                var htmlParram = '';
                                if (typeof stmt.params[key] !== 'function') {
                                    htmlParram += '<li class="' + csscls('value') + '">' + key + ' => ' + stmt.params[key] + '</li>';
                                }
                            }

                            var $value = $('<td />').addClass(csscls('value')).html('<ul>' + htmlParram + '</ul>');

                            var $bindings = new PhpDebugBar.Widgets.ListWidget();

                            $bindings.$el
                                .removeClass(csscls('list'))
                                .addClass(csscls('table-list'))
                                .appendTo($value);

                            return $('<tr />').append($name, $value);
                        });
                    }

                    if (stmt.backtrace && stmt.backtrace.length) {
                        table.append(function () {
                            var $value = $('<td colspan="2" />').addClass(csscls('value'));
                            var $span = $('<span />').addClass('phpdebugbar-text-muted');

                            var $backtrace = new PhpDebugBar.Widgets.ListWidget({
                                itemRenderer: function (li, source) {
                                    var $parts = [
                                        $span.clone().text(source.index + '.'),
                                        '&nbsp;',
                                    ];

                                    if (source.namespace) {
                                        $parts.push(source.namespace + '::');
                                    }

                                    $parts.push(source.name);
                                    $parts.push($span.clone().text(':' + source.line));
                                    if (source.editorHref) {
                                        $parts.push('&nbsp;');
                                        $parts.push($('<a target="_blank" href="' + source.editorHref + '"></a>').addClass(csscls('editor-link')));
                                    }

                                    li.append($parts).removeClass(csscls('list-item')).addClass(csscls('table-list-item'));
                                }
                            });

                            $backtrace.set('data', stmt.backtrace);

                            $backtrace.$el
                                .removeClass(csscls('list'))
                                .addClass(csscls('table-list'))
                                .appendTo($value);

                            return $('<tr />').append($value);
                        });
                    }
                }
            });

            this.$list.$el.appendTo(this.$el);

            this.$message = $('<div></div>').addClass(csscls('messages')).appendTo(this.$el);
            this.$search = $('<div><i class="phpdebugbar-fa phpdebugbar-fa-search"></i></div>')
                .addClass(csscls('toolbar')).css('display', 'block').appendTo(this.$message);

            $('<input type="text" />')
                .on('change', function () {
                    self.set('search', this.value);
                })
                .appendTo(this.$search);

            this.bindAttr('data', function (data) {
                // the PDO collector maybe is empty
                if (data.length <= 0) {
                    return false;
                }
                this.set({exclude: [], search: ''});
                this.$list.set('data', data.statements);
                this.$status.empty();

                // Search for duplicate statements.
                for (var sql = {}, unique = 0, duplicate = 0, i = 0; i < data.statements.length; i++) {
                    var stmt = data.statements[i].sql;
                    if (data.statements[i].params && !$.isEmptyObject(data.statements[i].params)) {
                        stmt += ' {' + $.param(data.statements[i].params, false) + '}';
                    }
                    sql[stmt] = sql[stmt] || {keys: []};
                    sql[stmt].keys.push(i);
                }
                // Add classes to all duplicate SQL statements.
                for (var stmt in sql) {
                    if (sql[stmt].keys.length > 1) {
                        duplicate += sql[stmt].keys.length;
                        for (var i = 0; i < sql[stmt].keys.length; i++) {
                            this.$list.$el.find('.' + csscls('list-item')).eq(sql[stmt].keys[i])
                                .addClass(csscls('sql-duplicate'));
                        }
                    } else {
                        unique++;
                    }
                }

                var t = $('<span />').text(data.nb_statements + " statements were executed").appendTo(this.$status);
                if (data.nb_failed_statements) {
                    t.append(", " + data.nb_failed_statements + " of which failed");
                }
                if (duplicate) {
                    t.append(", " + duplicate + " of which were duplicates");
                    t.append(", " + unique + " unique");
                }
                if (data.accumulated_duration_str) {
                    this.$status.append($('<span title="Accumulated duration" />').addClass(csscls('duration')).text(data.accumulated_duration_str));
                }
                if (data.memory_usage_str) {
                    this.$status.append($('<span title="Memory usage" />').addClass(csscls('memory')).text(data.memory_usage_str));
                }

                var filters = ['select', 'insert', 'update', 'delete', 'join', 'count', 'like', 'having', 'group by', 'order by', 'lender_configurations'];

                this.$search.find('.' + csscls('filter')).remove();

                for (var i = 0; i < filters.length; i++) {
                    $('<a />')
                        .addClass(csscls('filter'))
                        .text(filters[i])
                        .attr('rel', filters[i])
                        .on('click', function () {
                            self.onFilterClick(this);
                        })
                        .appendTo(this.$search);
                }
            });

            this.bindAttr(['exclude', 'search'], function () {
                var data = this.get('data'),
                    exclude = this.get('exclude'),
                    search = this.get('search'),
                    caseless = false,
                    fdata = [];

                // if (search && search === search.toLowerCase()) {
                caseless = true;
                // }

                var statements = [...data.statements];
                if (exclude.length) {
                    for (var i = 0; i < exclude.length; i++) {
                        for (var j = 0; j < statements.length; j++) {
                            var message = caseless ? statements[j].sql.toLowerCase() : statements[j].sql;
                            if (message.indexOf(exclude[i]) > -1) {
                                statements.splice(j, 1);
                                j--;
                            }
                        }
                    }
                }

                for (var i = 0; i < statements.length; i++) {
                    var message = caseless ? statements[i].sql.toLowerCase() : statements[i].sql;
                    if (!search || message.indexOf(search) > -1) {
                        fdata.push(statements[i]);
                    }
                }

                this.$list.set('data', fdata);
            });
        },

        onFilterClick: function (el) {
            $(el).toggleClass(csscls('excluded'));

            var excludedLabels = [];
            this.$search.find(csscls('.filter') + csscls('.excluded')).each(function () {
                excludedLabels.push(this.rel);
            });

            this.set('exclude', excludedLabels);
        }

    });

})(PhpDebugBar.$);
