
jQuery(document).ready(function ($) {
    "use strict";

    var initImagePicker = function ($wrap) {
        var image_frame;
        var $input_id = $wrap.find("input.image_id");
        var $image_html = $wrap.find(".image-html");

        $wrap.on("click", ".image-html", function (event) {
            var $el = $(this);
            event.preventDefault();

            // If the media frame already exists, reopen it.
            if (image_frame) {
                image_frame.open();
                return;
            }

            // Create the media frame.
            image_frame = wp.media.frames.savp_image_frame = wp.media({
                // Set the title of the modal.
                title: $el.data("choose"),
                button: {
                    text: $el.data("update"),
                },
                library: {
                    type: "image",
                },
                states: [
                    new wp.media.controller.Library({
                        title: $el.data("choose"),
                        filterable: "all",
                        multiple: false,
                    }),
                ],
            });

            // When an image is selected, run a callback.
            image_frame.on("select", function () {
                var selection = image_frame.state().get("selection");
                var attachment_ids = $input_id.val();

                selection.map(function (attachment) {
                    attachment = attachment.toJSON();

                    if (attachment.id) {
                        attachment_ids = attachment_ids
                            ? attachment_ids + "," + attachment.id
                            : attachment.id;
                        var attachment_image =
                            attachment.sizes && attachment.sizes.thumbnail
                                ? attachment.sizes.thumbnail.url
                                : attachment.url;

                        +'"></div>';

                        $image_html.html(
                            '<img src="' + attachment_image + '" />'
                        );
                        $image_html.append(
                            '<a href="#" class="delete"><span class="dashicons dashicons-dismiss"></span></a>'
                        );

                        $input_id.val(attachment.id).trigger("change");
                    }
                });
            });

            image_frame.on("open", function () {
                var selection = image_frame.state().get("selection");
                var selected = $input_id.val(); // the id of the image
                if (selected) {
                    selection.add(wp.media.attachment(selected));
                }
            });

            // Finally, open the modal.
            image_frame.open();
        });

        // Remove images.
        $wrap.on("click", "a.delete", function () {
            $(this).closest("li.image").remove();
            $image_html.html("");

            $input_id.val("").trigger("change");
            return false;
        });
    };

    var initGallery = function ($wrap) {
        // Product gallery file uploads.
        var product_gallery_frame;
        var $image_gallery_ids = $wrap.find("input.savp_gallery_ids");
        var $product_images = $wrap.find("ul.savp_images");

        $wrap.on("click", "a.savp_add_images", function (event) {
            var $el = $(this);

            var limit = $el.data("multiple");
            var multiple = true;
            if (false === limit || limit === "false") {
                multiple = false;
            }

            event.preventDefault();

            // If the media frame already exists, reopen it.
            if (product_gallery_frame) {
                product_gallery_frame.open();
                return;
            }

            // Create the media frame.
            product_gallery_frame = wp.media.frames.product_gallery = wp.media({
                // Set the title of the modal.
                title: $el.data("choose"),
                button: {
                    text: $el.data("update"),
                },
                library: {
                    type: "image",
                },
                states: [
                    new wp.media.controller.Library({
                        title: $el.data("choose"),
                        filterable: "all",
                        multiple: multiple,
                    }),
                ],
            });

            // When an image is selected, run a callback.
            product_gallery_frame.on("select", function () {
                var selection = product_gallery_frame.state().get("selection");
                var attachment_ids = $image_gallery_ids.val();

                selection.map(function (attachment) {
                    attachment = attachment.toJSON();

                    if (attachment.id) {
                        attachment_ids = attachment_ids
                            ? attachment_ids + "," + attachment.id
                            : attachment.id;
                        var attachment_image =
                            attachment.sizes && attachment.sizes.thumbnail
                                ? attachment.sizes.thumbnail.url
                                : attachment.url;

                        var html =
                            '<li class="image" data-attachment_id="' +
                            attachment.id +
                            '"><img src="' +
                            attachment_image +
                            '" /><ul class="actions"><li><a href="#" class="delete" title="' +
                            $el.data("delete") +
                            '">' +
                            $el.data("text") +
                            "</a></li></ul></li>";

                        if (multiple) {
                            $product_images.append(html);
                            $image_gallery_ids
                                .val(attachment_ids)
                                .trigger("change");
                        } else {
                            $product_images.html(html);
                            $image_gallery_ids
                                .val(attachment.id)
                                .trigger("change");
                        }
                    }
                });
            });

            // Finally, open the modal.
            product_gallery_frame.open();
        });

        // Image ordering.
        $product_images.sortable({
            items: "li.image",
            cursor: "move",
            scrollSensitivity: 40,
            forcePlaceholderSize: true,
            forceHelperSize: false,
            helper: "clone",
            opacity: 0.65,
            placeholder: "wc-metabox-sortable-placeholder",
            start: function (event, ui) {
                ui.item.css("background-color", "#f6f6f6");
            },
            stop: function (event, ui) {
                ui.item.removeAttr("style");
            },
            update: function () {
                var attachment_ids = "";
                var attachment_ids = [];
                $product_images
                    .find("li.image")
                    .css("cursor", "default")
                    .each(function () {
                        var attachment_id = $(this).attr("data-attachment_id");
                        attachment_ids.push(attachment_id);
                    });

                $image_gallery_ids
                    .val(attachment_ids.join(","))
                    .trigger("change");
            },
        });

        // Remove images.
        $wrap.on("click", "a.delete", function () {
            $(this).closest("li.image").remove();
            var attachment_ids = [];
            $product_images
                .find("li.image")
                .css("cursor", "default")
                .each(function () {
                    var attachment_id = $(this).attr("data-attachment_id");
                    attachment_ids.push(attachment_id);
                });

            $image_gallery_ids.val(attachment_ids.join(",")).trigger("change");
            return false;
        });
    };

    $("#woocommerce-product-data").on(
        "woocommerce_variations_loaded.savp_vsg",
        function (e) {
            $(".savp_images_wrap").each(function () {
                initGallery($(this));
            });
        }
    );

    $(".savp_images_wrap").each(function () {
        initGallery($(this));
    });

    window.savp_init_gallery = initGallery;
    window.savp_init_image_picker = initImagePicker;

    ///

    $("#savp_add_video").on("click", function (e) {
        e.preventDefault();
        //  console.log("Clcikededde");
        var workflow = wp.media({
            frame: "post",
            state: "insert",
            title: wp.media.view.l10n.addMedia,
            multiple: false,
        });

        workflow.on("insert", function (selection) {
            var state = workflow.state();

            selection = selection || state.get("selection");

            if (!selection) {
                return;
            }
            let data = selection.toJSON();
            $("#_savp_video").val(data[0].url);
            // console.log("selection: ", selection.toJSON());
        });

        workflow.state("embed").on("select", function () {
            /**
             * @this wp.media.editor
             */
            var state = workflow.state(),
                type = state.get("type"),
                embed = state.props.toJSON();

            embed.url = embed.url || "";

            if ("link" === type) {
                _.defaults(embed, {
                    linkText: embed.url,
                    linkUrl: embed.url,
                });
            } else if ("image" === type) {
                _.defaults(embed, {
                    title: embed.url,
                    linkUrl: "",
                    align: "none",
                    link: "none",
                });

                if ("none" === embed.link) {
                    embed.linkUrl = "";
                } else if ("file" === embed.link) {
                    embed.linkUrl = embed.url;
                }
            }

            $("#_savp_video").val(embed.url);
        });

        // http://www.mediaelementjs.com/
        workflow.open();
    });
});
