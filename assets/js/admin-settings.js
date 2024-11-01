jQuery(function ($) {
    "use strict"; 
    var $custom = $("#savp_custom_attrs").closest("tr");

    $("#savp_attrs_show").on("change", function () {
        var v = $(this).val();
        switch (v) {
            case "1":
            case "2":
               $custom.hide();
                break;
            case "custom":
               $custom.show();
                break;
            default:
               $custom.hide();
        }
    });

    $("#savp_attrs_show").trigger("change");
});
