var frame,
    savp_admin_args = savp_admin_args || {};

jQuery(document).ready(function ($) {
    "use strict";
    var wp = window.wp,
        $body = $("body");

    $(".term-color, #term-color").wpColorPicker();

    // Update attribute image
    $body
        .on("click", ".savp_upload-image-button", function (event) {
            event.preventDefault();

            var $button = $(this);
            var $wrapper = $button.closest(".savp-image-wrapper");

            // If the media frame already exists, reopen it.
            if (frame) {
                frame.open();
                return;
            }

            // Create the media frame.
            frame = wp.media({
                title: savp_admin_args.i18n.mediaTitle,
                button: {
                    text: savp_admin_args.i18n.mediaButton,
                },
                multiple: false,
            });

            // When an image is selected, run a callback.
            frame.on("select", function () {
                var attachment = frame
                    .state()
                    .get("selection")
                    .first()
                    .toJSON();

                $button.siblings("input.savp_term-image").val(attachment.id);
                $button.siblings(".savp_remove-image-button").show();
                $wrapper
                    .find(".savp_term-image-thumbnail")
                    .find("img")
                    .attr("src", attachment.sizes.thumbnail.url);

                console.log(
                    "Image",
                    $wrapper.find(".savp_term-image-thumbnail").find("img")
                );
            });
        })
        .on("click", ".savp_remove-image-button", function () {
            var $button = $(this);

            $button.siblings("input.savp_term-image").val("");
            $button.siblings(".savp_remove-image-button").show();
            $button
                .parent()
                .prev(".savp_term-image-thumbnail")
                .find("img")
                .attr("src", savp_admin_args.placeholder);

            return false;
        });

    // Toggle add new attribute term modal
    var $modal = $("#savp_modal-container"),
        $spinner = $modal.find(".spinner"),
        $msg = $modal.find(".message"),
        $metabox = null;

    $body
        .on("click", ".savp_add_new_attribute", function (e) {
            e.preventDefault();

            var $button = $(this),
                taxInputTemplate = wp.template("savp_input-tax"),
                data = {
                    type: $button.data("type"),
                    tax: $button
                        .closest(".woocommerce_attribute")
                        .data("taxonomy"),
                };

            // Insert input
            $modal
                .find(".savp_term-swatch")
                .html($("#tmpl-savp_input-" + data.type).html());
            $modal.find(".savp_term-tax").html(taxInputTemplate(data));

            if ("color" == data.type) {
                $modal.find("input.savp_input-color").wpColorPicker();
            }

            $metabox = $button.closest(".woocommerce_attribute.wc-metabox");
            $modal.show();
        })
        .on("click", ".savp_modal-close, .savp_modal-backdrop", function (e) {
            e.preventDefault();

            closeModal();
        });

    // Send ajax request to add new attribute term
    $body.on("click", ".savp_new-attribute-submit", function (e) {
        e.preventDefault();

        var $button = $(this),
            type = $button.data("type"),
            error = false,
            data = {};

        // Validate
        $modal.find(".savp_input").each(function () {
            var $this = $(this);

            if ($this.attr("name") != "slug" && !$this.val()) {
                $this.addClass("error");
                error = true;
            } else {
                $this.removeClass("error");
            }

            data[$this.attr("name")] = $this.val();
        });

        if (error) {
            return;
        }

        // Send ajax request
        $spinner.addClass("is-active");
        $msg.hide();
        wp.ajax.send("savp_add_new_attribute", {
            data: data,
            error: function (res) {
                $spinner.removeClass("is-active");
                $msg.addClass("error").text(res).show();
            },
            success: function (res) {
                $spinner.removeClass("is-active");
                $msg.addClass("success").text(res.msg).show();

                $metabox
                    .find("select.attribute_values")
                    .append(
                        '<option value="' +
                            res.id +
                            '" selected="selected">' +
                            res.name +
                            "</option>"
                    );
                $metabox.find("select.attribute_values").change();

                closeModal();
            },
        });
    });

    /**
     * Close modal
     */
    function closeModal() {
        $modal.find(".savp_term-name input, .savp_term-slug input").val("");
        $spinner.removeClass("is-active");
        $msg.removeClass("error success").hide();
        $modal.hide();
    }

    // ----------------------------------------------------------------------------------------------------

    /**
     * Avoids using wp.template where possible in order to be CSP compliant.
     * wp.template uses internally eval().
     * @param {string} templateId
     * @return {Function}
     */
    var wp_template = function (templateId) {
        var html = document.getElementById("tmpl-" + templateId).textContent;
        var hard = false;
        // any <# #> interpolate (evaluate).
        hard = hard || /<#\s?data\./.test(html);
        // any data that is NOT data.variation.
        hard = hard || /{{{?\s?data\.(?!variation\.).+}}}?/.test(html);
        // any data access deeper than 1 level e.g.
        hard = hard || /{{{?\s?data\.variation\.[\w-]*[^\s}]/.test(html);
        if (hard) {
            return wp.template(templateId);
        }
        return function template(data) {
            var variation = data.variation || {};
            return html.replace(
                /({{{?)\s?data\.variation\.([\w-]*)\s?(}}}?)/g,
                function (_, open, key, close) {
                    // Error in the format, ignore.
                    if (open.length !== close.length) {
                        return "";
                    }
                    var replacement = variation[key] || "";
                    // {{{ }}} => interpolate (unescaped).
                    // {{  }}  => interpolate (escaped).
                    // https://codex.wordpress.org/Javascript_Reference/wp.template
                    if (open.length === 2) {
                        return window.escape(replacement);
                    }
                    return replacement;
                }
            );
        };
    };

    var savp_attr_list = {};

    var galleryRenameInputs = function () {
        $("#savp-gallery-list .savp-gallery-row").each(function (rIndex) {
            var $row = $(this);
            $row.find(".savp-gallery-h-attrs select").each(function () {
                var $select = $(this);
                var name = $select.attr("data-name");
                $select.attr(
                    "name",
                    "_savp_bulk_gallery_attrs[" + rIndex + "][" + name + "]"
                );
            });

            $row.find(".savp_gallery_ids").each(function () {
                var $select = $(this);
                var name = $select.attr("data-name");
                $select.attr(
                    "name",
                    "_savp_bulk_gallery_images[" + rIndex + "]"
                );
            });
        });
    };

    var galleryHeaderRow = function (list) {
        $(".savp-gallery-row-header .savp-gallery-h-attrs").each(function () {
            var $header = $(this);
            $header.find("label").addClass("tpl-remove");

            var $sep = $('<span class="sep-pos"></span>');
            $header.prepend($sep);

            $.each(list, function (att_key, args) {
                var $input = $header.find('label[data-tax="' + att_key + '"]');
                var $select;
                var oldVal = "";
                if (!$input.length) {
                    $input = $("<label class='tax-val'></label>");
                    $input.attr("data-tax", att_key);
                    $select = $("<select></select>");
                    $select.attr("data-name", att_key);
                    $input.append($select);
                } else {
                    $select = $input.find("select");
                    oldVal = $select.val();
                    $input.removeClass("tpl-remove");
                }

                //$header.append($input);
                $input.insertBefore($sep);
                $select.html(
                    "<option value=''>" +
                        savp_admin_args.i18n.any_format.replace(
                            "%s",
                            args.label
                        ) +
                        "</option>"
                );

                $.each(args.options, function (value, label) {
                    var $opt = $("<option></option>");
                    $opt.attr("value", value);
                    $opt.text(label);
                    if (oldVal === value) {
                        $opt.prop("selected", true);
                    }
                    $select.append($opt);
                });
            });
            $sep.remove();
            $header.find("label.tpl-remove").remove();
        });
    };

    var getVariationData = function () {
        var ptype = $("#product-type").val();
        var pid = $("#post_ID").val();
        $.ajax({
            type: "get",
            url: ajaxurl,
            data: {
                product_type: ptype,
                post_id: pid,
                action: "savp_get_product_attrs",
            },
            success: function (res) {
                savp_attr_list = res.data;
                $(document).trigger("savp_variation_attrs_ready", [
                    savp_attr_list,
                ]);
            },
        });
    };

    if ($("#post_ID").length) {
        getVariationData();
    }

    $(document).on("savp_variation_attrs_ready", function () {
        galleryHeaderRow(savp_attr_list);
        galleryRenameInputs();

        $("#_savp_swatches_show_archives option").addClass("tpl-remove");

        $.each(savp_attr_list, function (att_key, args) {
            var $input = $(
                '#_savp_swatches_show_archives option[value="' + att_key + '"]'
            );

            if ($input.length) {
                $input.removeClass("tpl-remove");
                $input.text(args.label);
            } else {
                $input = $("<option></option>");
                $input.attr("value", att_key);
                $input.text(args.label);
                $("#_savp_swatches_show_archives").append($input);
            }
        });

        $("#_savp_swatches_show_archives .tpl-remove").remove();
    });

    galleryRenameInputs();

    $("#variable_product_options").on("reload", function () {
        getVariationData();
    });

    $(document).on("click", ".savp-row .savp-toggle", function (e) {
        e.preventDefault();

        var p = $(this).closest(".savp-row");
        p.toggleClass("show");
    });
    // Delete
    $(document).on("click", ".savp-row .delete", function (e) {
        e.preventDefault();
        $(this).closest(".savp-row").remove();
        galleryRenameInputs();
    });

    // Sort
    $("#savp-gallery-list").sortable({
        handle: ".move",
        update: function (event, ui) {
            galleryRenameInputs();
        },
    });

    /// Add new
    $("#savp-add-gallery").on("click", function (e) {
        e.preventDefault();
        var template;
        template = wp_template("savp_bulk_item");
        var template_html = template({
            id: "test",
            name: "test",
        });
        template_html = template_html.replace("/*<![CDATA[*/", "");
        template_html = template_html.replace("/*]]>*/", "");
        var $templateEl = $(template_html);
        savp_init_gallery($templateEl);
        $("#savp-gallery-list").append($templateEl);

        galleryHeaderRow(savp_attr_list);
        galleryRenameInputs();
    });

    /// Confirm thumb..
    $(document).on("click", ".savp_set-var-thumb", function (e) {
        e.preventDefault();
        var c = confirm(savp_admin_args.i18n.confirm_set_thumb);
        if (!c) {
            return;
        }
        var $w = $(this).closest(".savp-gallery-row");
        var $selects = $w.find(".savp-gallery-h-attrs select");
        var data = {};
        $selects.each(function () {
            var $s = $(this);
            var name = $s.attr("data-name");
            var v = $s.val();
            data[name] = v;
        });

        var image_id =
            $w.find(".savp_images li").attr("data-attachment_id") || "";
        var product_id = $("#post_ID").val();
        var ptype = $("#product-type").val();
        $.ajax({
            url: ajaxurl,
            type: "post",
            data: {
                action: "savp_bulk_update_variation_thumbnail",
                post_id: product_id,
                image_id: image_id,
                product_type: ptype,
                attributes: data,
            },
            success: function (res) {
                alert(savp_admin_args.i18n.variation_updated);
                wc_meta_boxes_product_variations_ajax.load_variations(1);
                wc_meta_boxes_product_variations_pagenav.set_paginav(0);
            },
        });
    });

    // ---Swashes item -----------

    var SwashItems = function () {
        var $list = $("#savp-swashes-list");
        $list.find(".savp-row").addClass("tpl-remove");
        var $sepPos = $('<div class="sep-pos"></div>');
        $list.prepend($sepPos);

        $.each(savp_attr_list, function (tax, args) {
            var template, templateItem, $templateEl;

            if ($list.find('.savp-row[data-tax="' + tax + '"]').length) {
                $templateEl = $list.find('.savp-row[data-tax="' + tax + '"]');
                $templateEl.removeClass("tpl-remove");
            } else {
                template = wp_template("savp_swashes-row");
                var template_html = template(args);
                template_html = template_html.replace("/*<![CDATA[*/", "");
                template_html = template_html.replace("/*]]>*/", "");
                var $templateEl = $(template_html);
            }

            $($templateEl).insertBefore($sepPos);

            templateItem = wp_template("savp_swashes-item");
            $templateEl.find(".savp-s--item").addClass("tpl-remove");

            $.each(args.options, function (k, l) {
                var $item = $templateEl.find(
                    '.savp-s--item[data-term="' + k + '"]'
                );
                if ($item.length) {
                    $item.removeClass("tpl-remove");
                    $item.find(".s-label").text(l);
                } else {
                    var template_html = templateItem({
                        tax: tax,
                        label: l,
                        name: k,
                    });
                    var $item = $(template_html);
                    $templateEl.find(".attr-terms").append($item);
                }
                try {
                    savp_init_image_picker($item);
                } catch (e) {}
                $(".color", $item).wpColorPicker({
                    clear: true,
                });
            });
            $templateEl.find(".savp-s--item.tpl-remove").remove();
        });

        $sepPos.remove();
        $list.find(".savp-row.tpl-remove").remove();
    };

    $(document).on("savp_variation_attrs_ready", function () {
        SwashItems();
    });

    $(document).on("change", ".savp-s-row-item .current-type", function () {
        var t = $(this).val();
        $(this).closest(".savp-s-row-item").attr("data-current", t);
    });
});
