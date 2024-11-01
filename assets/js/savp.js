(function ($) {
    "use strict";
    const $document = $(document);

    function ajax_get_data($el) {
        let product_ids = {};
        let singleIds = {};

        $(".savp-thumbnail, .savp_gallery", $el).each(function () {
            if ($(this).hasClass("savp-added")) {
                return;
            }
            let is_single = $(this).attr("data-single") || false;
            let id = $(this).attr("data-id") || false;
            if (id) {
                product_ids[`_` + id] = id;
                if (is_single) {
                    singleIds[`_` + id] = id;
                }
            }
        });

        let ids = Object.values(product_ids);

        if (ids.length) {
            $.ajax({
                url: savpConfig.ajax_url,
                type: "get",
                data: {
                    action: "savp_load_variations",
                    ids: ids.join(","),
                    single_ids: Object.values(singleIds).join(","),
                },
                success: function (res) {
                    $document.trigger("savp_data_loaded", [$el, res]);
                },
            });
        }
    }
    ajax_get_data($document);

    // Make sure we run this code under Elementor.
    $(window).on("elementor/frontend/init", function () {
        if (typeof elementorFrontend === "undefined") {
            return;
        }
        elementorFrontend.hooks.addAction(
            "frontend/element_ready/onestore_products.default",
            function ($scope, $) {
                ajax_get_data($scope);
            }
        );
        elementorFrontend.hooks.addAction(
            "frontend/element_ready/wp-widget-onestore_products.default",
            function ($scope, $) {
                ajax_get_data($scope);
            }
        );
        elementorFrontend.hooks.addAction(
            "frontend/element_ready/onestore-woo-products-tabs.default",
            function ($scope, $) {
                ajax_get_data($scope);
            }
        );
    });

    $(document).on("onestore_ajax_more_loaded", function (e, $wrapper, res) {
        ajax_get_data($wrapper);
    });
})(jQuery);
