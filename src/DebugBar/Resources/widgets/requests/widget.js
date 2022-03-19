(function ($) {

    var csscls = PhpDebugBar.utils.makecsscls('phpdebugbar-widgets-');

    /**
     * Widget for the displaying sql queries
     *
     * Options:
     *  - data
     */
    var RequestWidget = PhpDebugBar.Widgets.RequestWidget = PhpDebugBar.Widget.extend({

        className: csscls('sqlqueries'),

        onCopyToClipboard: function (el) {
            var code = $(el).parent('li').find('pre').get(0);
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
            this.$status = $('<div />').addClass(csscls('status')).appendTo(this.$el);
            this.$toolbar = $('<div></div>').addClass(csscls('toolbar')).appendTo(this.$el);

            this.$list = new PhpDebugBar.Widgets.ListWidget({
                itemRenderer: function (li, stmt) {

                    this.$method = $('<div />').addClass(csscls('sql')).text(stmt.method).appendTo(li);

                    $('<span title="Copy to clipboard" />')
                        .addClass(csscls('copy-clipboard'))
                        .css('cursor', 'pointer')
                        .on('click', function (event) {
                            self.onCopyToClipboard(this);
                            event.stopPropagation();
                        })
                        .appendTo(li);

                    var table = $('<table></table>').appendTo(li).show();
                    table.append(function () {
                        //var prettyVal = PhpDebugBar.Widgets.createCodeBlock(stmt.value, 'php');
                        var $value = $('<td />').append(stmt.value);
                        return $('<tr />').append($value);
                    });
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
                if (data.length <= 0) {
                    return false;
                }
                this.set({exclude: [], search: ''});
                this.$list.set('data', data);
                this.$status.empty();

                var filters = [];

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

                if (search && search === search.toLowerCase()) {
                    caseless = true;
                }

                for (var i = 0; i < data.length; i++) {
                    var message = caseless ? data[i].value.toLowerCase() : data[i].value;

                    if ((!data[i].method || $.inArray(data[i].method, exclude) === -1) && (!search || message.indexOf(search) > -1)) {
                        fdata.push(data[i]);
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
