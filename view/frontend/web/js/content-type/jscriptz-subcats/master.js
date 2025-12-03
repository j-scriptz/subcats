define([
    'Magento_PageBuilder/js/content-type/master'
], function (Master) {
    'use strict';

    return Master.extend({
        /**
         * Build the {{block}} directive with selected category IDs
         * and an optional design preset.
         *
         * This runs in admin when Page Builder saves the content.
         */
        getBlockDirective: function () {
            var data = this.contentType.dataStore.getState() || {};
            var ids  = data.category_ids || [];

            if (!Array.isArray(ids)) {
                ids = [ids];
            }

            ids = ids
                .map(function (v) { return String(v).trim(); })
                .filter(function (v) { return v.length; });

            if (!ids.length) {
                return '';
            }

            var directive = '{{block class="Jscriptz\\\\Subcats\\\\Block\\\\Subcats"'
                + ' template="Jscriptz_Subcats::subcats.phtml"'
                + ' category_ids="' + this._escapeAttribute(ids.join(',')) + '"';

            if (data.design_preset && data.design_preset.length) {
                directive += ' design_preset="' + this._escapeAttribute(data.design_preset) + '"';
            }

            directive += '}}';

            return directive;
        },

        /**
         * Basic attribute escaping for safety.
         *
         * @param {String} value
         * @returns {String}
         * @private
         */
        _escapeAttribute: function (value) {
            return String(value)
                .replace(/"/g, '&quot;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;');
        }
    });
});
