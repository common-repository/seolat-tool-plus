(function ($) {
    var Sitemap = {
        run: function (config) {
            var self = this;
            this.c = config;

            this.c.btns.on('click', function () { self.changeState($(this)); });
            this.c.ul.on('click', function (e) { self.makeChange($(e.target)); });
            this.c.defaults.on('click', function () { self.restoreDefaults(); });
            this.c.form.on('submit', function (e) { e.preventDefault(); self.submitForm(); });
            this.c.theBtn.on('click', function () { self.upgrade(self.c.theField); });
            this.c.theField.on('keypress', function (e) {
                if (e.which === 13) {
                    e.preventDefault();
                    self.upgrade(self.c.theField);
                }
            }).on('click', function () {
                if (self.c.error.text()) {
                    self.c.error.text('');
                }
            });

            this.c.btns.each(function (i, btn) {
                if (self.c.activeP.val() === $(btn).attr('id')) {
                    self.c.btns.eq(i).click();
                }
            });

            if (!this.c.textarea.val()) {
                this.c.textarea.attr('placeholder', 'http://www.example.com/a-page/\nhttp://www.example.com/fruits/apples.html');
            }
        },

        changeState: function (btn) {
            this.c.btns.attr('class', '');
            btn.attr('class', 'sitemap-active');
            this.c.tables.attr('id', '').parent().find('table[data-id="' + btn.attr('id') + '"]').attr('id', 'sitemap-table-show');
            this.c.activeP.val(btn.attr('id'));
        },

        makeChange: function (node) {
            var li = node.parent(), elem;

            if (node.attr('class') === 'sitemap-up' && li.prev()[0]) {
                li.prev().before(li);
            } else if (node.attr('class') === 'sitemap-down' && li.next()[0]) {
                li.next().after(li);
            } else if (node.hasClass('sitemap-change-btn')) {
                elem = li.find('.swp-name');

                if (node.val() === 'Change') {
                    elem.replaceWith('<input type="text" class="swp-name" value="' + this.esc(elem.text()) + '" data-name="' + elem.attr('data-name') + '">');
                    node.val('Ok');
                } else {
                    elem.replaceWith('<span class="swp-name" data-name="' + elem.attr('data-name') + '">' + this.esc(elem.val()) + '</span>');
                    node.val('Change');
                }
            }
        },

        submitForm: function () {
            var inputs = this.c.ul.find('input[type=hidden]'),
                titles = this.c.ul.find('[data-name]'),
                self = this;

            $.each(inputs, function (i, node) {
                inputs.eq(i).val(self.esc((i + 1) + '-|-' + (titles.eq(i).text() || titles.eq(i).val())));
            });
            this.c.form[0].submit();
        },

        upgrade: function (input) {
            var form = $('#simpleWpHiddenForm');

            form.find('input[type=hidden]').attr({name: 'upgrade_to_premium', value: this.esc(input.val())});
            form.submit();
        },

        restoreDefaults: function () {
            var sections = ['Home', 'Posts', 'Pages', 'Other', 'Categories', 'Tags', 'Authors'],
                html = '';

            $.each(sections, function (i) {
                html += '<li><span class="swp-name" data-name="' + sections[i].toLowerCase() + '">' + sections[i] + '</span><span class="sitemap-down" title="move down"></span><span class="sitemap-up" title="move up"></span>' +
                    '<input type="hidden" name="simple_wp_' + sections[i].toLowerCase() + '_n" value="' + (i + 1) + '"><input type="button" value="Change" class="button-secondary sitemap-change-btn"></li>';
            });
            this.c.ul.html(html);
            this.c.updated.val('');
        },

        esc: function (str) {
            return str.replace(/[<"'>]/g, function (ch) {
                return {'<': "&lt;", '>': "&gt;", '"': '&quot;', '\'': '&#39;'}[ch];
            });
        }
    };

    Sitemap.run({
        tables: $('#simple-wp-sitemap-form table'),
        textarea: $('#swsp-add-pages-textarea'),
        updated: $('#simple_wp_last_updated'),
        activeP: $('#simple_wp_active-page'),
        form: $('#simple-wp-sitemap-form'),
        defaults: $('#sitemap-defaults'),
        ul: $('#sitemap-display-order'),
        btns: $('#sitemap-settings li'),
        theBtn: $('#upgradeToPremium'),
        theField: $('#upgradeField'),
        error: $('#swpErrorText'),
    });
})(jQuery);
