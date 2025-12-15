define([
    'jquery',
    'jquery/ui'
], function ($) {
    'use strict';

    /**
     * Turn selected category list into a sortable list that updates the hidden input value.
     *
     * Usage: see view/adminhtml/templates/category/subcats/children.phtml
     */
    return function (config, element) {
        var $list  = $(element);
        var $input = $(config.inputSelector);

        if (!$list.length || !$input.length) {
            return;
        }

        // Initialize jQuery UI Sortable
        $list.sortable({
            handle: '.drag-handle',
            update: function () {
                var ids = [];

                $list.find('.js-subcats-selected-item').each(function () {
                    var id = $(this).data('id');
                    if (id) {
                        ids.push(id);
                    }
                });

                $input.val(ids.join(','));
            }
        });
    };
});
