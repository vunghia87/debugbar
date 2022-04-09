(function ($) {

    var csscls = PhpDebugBar.utils.makecsscls('phpdebugbar-widgets-');
    /**
     * Widget for the displaying memcache
     *
     * Options:
     *  - data
     */
    var CommandWidget = PhpDebugBar.Widgets.CommandWidget = PhpDebugBar.Widget.extend({

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
        render: function () {
            var self = this;

            this.$list = new PhpDebugBar.Widgets.ListWidget({
                itemRenderer: function (li, stmt) {
                    $('<code />').addClass(csscls('sql')).html(stmt.arguments).appendTo(li);

                    if (stmt.command) {
                        $('<span title="Type" />').addClass(csscls('database')).text(stmt.command).appendTo(li);
                        li.attr("connection", stmt.command);
                    }

                    if (stmt.time) {
                        $('<span title="At" />').addClass(csscls('duration')).text(stmt.time).appendTo(li);
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

                    li.css('cursor', 'pointer').click(function () {
                        if (table.is(':visible')) {
                            table.hide();
                        } else {
                            table.show();
                        }
                    });


                    if (stmt.options) {
                        table.append(function () {
                            var $value = $('<td colspan="2"/>').css('text-align', 'left');

                            var v = stmt.options;
                            if (v && v.length > 100) {
                                v = v.substr(0, 100) + "...";
                            }
                            var prettyVal = null;
                            $value.text(v).click(function (e) {
                                if ($value.hasClass(csscls('pretty'))) {
                                    $value.text(v).removeClass(csscls('pretty'));
                                } else {
                                    prettyVal = prettyVal || PhpDebugBar.Widgets.createCodeBlock(stmt.options);
                                    $value.addClass(csscls('pretty')).empty().append(prettyVal);
                                }
                                e.stopPropagation()
                            });

                            var $widget = new PhpDebugBar.Widgets.ListWidget();
                            $widget.$el
                                .removeClass(csscls('list'))
                                .addClass(csscls('table-list'))
                                .appendTo($value);

                            return $('<tr />').append($value);
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

                                    $parts.push(source.file);
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
            this.$toolbar = $('<div><i class="phpdebugbar-fa phpdebugbar-fa-search"></i></div>')
                .addClass(csscls('toolbar')).css('display', 'block').appendTo(this.$message);

            $('<input type="text" />')
                .on('change', function () {
                    self.set('search', this.value);
                })
                .appendTo(this.$toolbar);

            this.bindAttr('data', function (data) {
                // the PDO collector maybe is empty
                if (data.length <= 0) {
                    return false;
                }
                this.set({exclude: [], search: ''});
                this.$toolbar.find('.' + csscls('filter')).remove();

                var filters = [], self = this;
                for (var i = 0; i < data.length; i++) {
                    if (!data[i].command || $.inArray(data[i].command, filters) > -1) {
                        continue;
                    }
                    filters.push(data[i].command);

                    $('<a />')
                        .addClass(csscls('filter'))
                        .text(data[i].command)
                        .attr('rel', data[i].command)
                        .on('click', function() { self.onFilterClick(this); })
                        .appendTo(this.$toolbar);
                }
            });

            this.bindAttr(['exclude', 'search'], function () {
                var data = this.get('data'),
                    exclude = this.get('exclude'),
                    search = this.get('search'),
                    caseless = false,
                    fdata = [];

                if (search && search === search.toLowerCase()) {
                    caseless = true;
                }

                for (var i = 0; i < data.length; i++) {
                    var message = caseless ? data[i].options.toLowerCase() : data[i].options;

                    if ((!data[i].command || $.inArray(data[i].command, exclude) === -1) && (!search || message.indexOf(search) > -1)) {
                        fdata.push(data[i]);
                    }
                }

                this.$list.set('data', fdata);
            });
        },

        onFilterClick: function(el) {
            $(el).toggleClass(csscls('excluded'));

            var excludedLabels = [];
            this.$toolbar.find(csscls('.filter') + csscls('.excluded')).each(function() {
                excludedLabels.push(this.rel);
            });

            this.set('exclude', excludedLabels);
        }

    });

})(PhpDebugBar.$);
