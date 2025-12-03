define([
    'jquery',
    'jquery-ui-modules/sortable',
    'uiRegistry'
], function ($, sortable, registry) {
    'use strict';

    /**
     * Init sortable for the subcats_children multiselect.
     */
    return function (config, element) {
        var $root = $(element);

        function getComponent() {
            // UI registry key for this field's component
            var key = config.component || 'category_form.category_form.jscriptz_subcats.subcats_children';
            return registry.get(key);
        }

        function initSortable() {
            var cmp = getComponent();
            if (!cmp) {
                return;
            }

            var $list = $root.find('.js-subcats-selected');
            if (!$list.length) {
                return;
            }

            if ($list.data('sortable-initialized')) {
                try {
                    $list.sortable('refresh');
                } catch (e) {}
                return;
            }

            $list.sortable({
                update: function () {
                    var ids = [];

                    $list.find('.js-subcats-selected-item').each(function () {
                        var id = $(this).data('id');
                        if (id !== undefined && id !== null && id !== '') {
                            ids.push(String(id));
                        }
                    });

                    // Update the underlying Knockout observable (array of IDs as strings)
                    if (cmp && cmp.value) {
                        cmp.value(ids);
                    }
                }
            });

            $list.data('sortable-initialized', true);
        }

        // Initial run after DOM/KO render
        setTimeout(initSortable, 300);

        // Re-run when selection changes
        var cmp = getComponent();
        if (cmp && cmp.value && cmp.value.subscribe) {
            cmp.value.subscribe(function () {
                setTimeout(initSortable, 50);
            });
        }
    };
});
