/**
 * @see https://github.com/jquery/jquery-migrate/blob/master/warnings.md#jqmigrate-jqueryfnclick-event-shorthand-is-deprecated
 */

var savp_product_variant_defaults = {};

(function ($) {
    "use strict";
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

    if (typeof $.fn.wc_set_content === "undefined") {
        /**
         * Stores the default text for an element so it can be reset later
         */
        $.fn.wc_set_content = function (content) {
            if (undefined === this.attr("data-o_content")) {
                this.attr("data-o_content", this.html());
            }
            this.html(content);
        };
    }
    if (typeof $.fn.wc_reset_content === "undefined") {
        /**
         * Stores the default text for an element so it can be reset later
         */
        $.fn.wc_reset_content = function () {
            if (undefined !== this.attr("data-o_content")) {
                this.html(this.attr("data-o_content"));
            }
        };
    }

    var Sa_Vsg = function ($form, settings = window) {
        const that = this;
        that.settings = settings;
        this.$form = $form;
        this.isLoop = $form.hasClass("savp-loop-form");
        this.link = this.isLoop ? $form.attr("action") : window.location;
        this.product_id = $form.attr("data-product_id") || 0;
        this.product_id = parseInt(this.product_id);
        if (isNaN(this.product_id)) {
            this.product_id = 0;
        }
        this.currentVariant = null;
        this.countAttrs = 0;
        this.variants = [];
        this.default = false;
        this.$qty = $form.find("input.qty");
        this.$variation = this.$form.find('input[name="variation_id"]');
        if (this.isLoop) {
            this.$product = this.$form.closest(".product");
        } else {
            this.$product = that.$form;
        }

        this.$price = this.$product.find(".price");

        this.$singleVariation = this.$form.find(".single_variation");
        this.$singleVariationWrap = this.$form.find(".single_variation_wrap");
        this.$add_cart_group = this.$form.find(
            ".woocommerce-variation-add-to-cart"
        );
        this.isAllAttr = this.$form.data("attr-all") === "y";
        if (!this.isLoop) {
            this.$form.addClass("savp-single");
            this.isAllAttr = true;
            that.behavior = savpConfig.settings.behavior.replace("-", " ");
        } else {
            that.behavior = savpConfig.settings.behavior_singular.replace(
                "-",
                " "
            );
        }
        that.eventAction = "click"; // mouseover

        that.swatches =
            typeof that.settings.savp_swatches["_" + this.product_id] !==
            "undefined"
                ? that.settings.savp_swatches["_" + this.product_id]
                : false;

        this.$addCartBtn = this.$product.find("a.add_to_cart_button");
        if (this.$addCartBtn.length) {
            this.$addCartBtn.data("o-link", this.$addCartBtn.attr("href"));
        }

        that.isInCanvas = !that.isLoop && savpConfig.settings.canvas !== "no";

        if (that.isLoop) {
            that.limitTerm = parseInt(
                that.settings.savp_variation_settings.t_limit || 4
            );
            if (isNaN(that.limitTerm)) {
                that.limitTerm = 0; // 0 mean unlimited.
            }
        }

        this.isInit = false;

        if (
            typeof that.settings.savp_product_variations[
                "_" + that.product_id
            ] !== "undefined"
        ) {
            this.variants =
                that.settings.savp_product_variations["_" + this.product_id];
        }
        if (that.isInCanvas) {
            $form.addClass("savp-canvas-form");
        } else {
            $form.addClass("savp-n-form");
        }

        if ($form.hasClass("savp_added")) {
            return;
        }

        that.default = {};

        that.intEvents();

        if (that.swatches) {
            var $inputs = [];
            $.each(that.swatches, function (key, args) {
                var $input = that.$form.find('[name="attribute_' + key + '"]');
                if ($input.length) {
                    that.default[$input.attr("name")] = $input.val();
                    if (that.isLoop) {
                        $input.prepend($input.find("option:selected"));
                    }
                    $inputs.push({
                        el: $input,
                        args: args,
                    });
                }
            });

            $.each($inputs, function (index, item) {
                that.initSwatches(item.el, item.args);
            });
        }

        that.thumbnailSwashes();
        that.toDefault();
        that.updateVariation();
        this.isInit = true;
        $form.addClass("savp_added");

        setTimeout(function () {
            $.each(that.default, function (key, value) {
                if (!value || "" === value) {
                    $form
                        .find('.savp-box[data-name="' + key + '"] li')
                        .first()
                        .addClass("selected");
                }
            });
            that.updateVariation();
        }, 350);
    };

    Sa_Vsg.prototype.initSwatches = function ($input, args) {
        var that = this;
        $input.wrap("<div class='savp-wrapper'></div>");
        var terms = args.terms;
        var tax_type = args.type;
        var tax_type = args.type;
        var tax = args.tax.slug;
        var $wrap = $input.parent();
        var $ul = $("<ul/>");
        $ul.addClass("savp-box vb-" + args.type + " " + that.behavior);
        if (!that.isLoop) {
            if (that.isInCanvas) {
                $ul.addClass("savp-in-canvas");
            } else {
                $ul.addClass("savp-box-singular");
            }
        }
        $input.addClass("savp-input-added");
        var first = false;
        var valDefault = false;
        var id = $input.attr("id") || "";
        var prefixId = "-" + that.product_id;
        var newId = id;
        var name = $input.attr("name");
        if (id.substring(-prefixId.length) !== prefixId) {
            newId = id + prefixId;
        }
        var isList = false;
        $ul.data("settings", args.settings);
        if (args.settings.display === "list" && !that.isLoop) {
            isList = true;
            $ul.addClass("list");
        } else {
            $ul.addClass("box");
        }

        this.countAttrs++;
        $input.attr("id", newId);
        var $inputLabel = that.$form.find('label[for="' + id + '"]');
        $inputLabel.addClass("label-for__attribute_" + tax).attr("for", newId);
        var hasSelected = false;

        var countLimit = 0;
        $input.find("option").each(function () {
            var option = $(this);
            var isSelected = option.is(":selected");
            if (
                that.limitTerm > 0 &&
                countLimit >= that.limitTerm &&
                !isSelected
            ) {
                countLimit++;
                return;
            }

            var optVal = option.val();
            // If found in term list.
            if (optVal && typeof terms[optVal] !== "undefined") {
                var item = terms[optVal];
                var $li = $("<li></li>");
                $li.html(item.name);
                if (first === false) {
                    first = item.slug;
                }
                $li.data("item-data", item);
                var $inner = $("<div class='s-inner'></div>");
                switch (item._type) {
                    case "color":
                        $inner = item._html;
                        break;
                    case "thumbnail":
                        $inner.addClass("no-img");
                        break;
                    case "image":
                        $inner.addClass("has-img");
                        $inner.html(
                            '<img  alt="" src="' + item._type_value + '"/>'
                        );
                        break;
                    default:
                        $inner.html(item.name);
                        break;
                }

                $li.html($inner);
                if (that.isInCanvas || (!that.isLoop && isList)) {
                    var $listName = $(
                        '<div class="l-name-wrap"><div class="l-name"></div></div>'
                    );
                    $listName.find(".l-name").html(item.name);
                    $li.append($listName);
                    if (args.settings.price === "yes") {
                        $listName.append('<div class="l-price"></div>');
                    }
                    if (
                        args.settings.stock_status === "yes" ||
                        that.isInCanvas
                    ) {
                        $li.append('<div class="l-stock-status"></div>');
                    }
                }

                if (item.default) {
                    $li.addClass("default");
                    valDefault = item.slug;
                }

                if (isSelected) {
                    $li.addClass("selected");
                    hasSelected = true;
                }

                $li.attr("data-val", item.slug);
                $li.attr("title", item.name);
                $li.wrapInner('<div class="li-inner"></div>');
                $ul.append($li);
                $li.on(that.eventAction, function (e) {
                    e.preventDefault();
                    that.clickToLabelItem($(this));
                });

                countLimit++;
            } // end if attr term exists.
        });

        // Add more button iff limit terms.
        if (that.isLoop) {
            if (that.limitTerm > 0 && countLimit > that.limitTerm) {
                var $moreLi = $("<li></li>");
                $moreLi.addClass("more-attr-term");
                $moreLi.append(
                    '<div class="li-inner"><a class="s-inner" href="' +
                        that.link +
                        '"><i class="savp-icon-plus"></i></a></div>'
                );
                $ul.append($moreLi);
            }
        }

        if (valDefault) {
            first = valDefault;
        }

        $ul.attr("data-type", tax_type);
        $ul.attr("data-default", first);
        $ul.attr("data-key", tax);
        $ul.attr("data-name", $input.attr("name"));

        // If canvas items
        if (that.isInCanvas) {
            var $copy = $(
                '<div class="savp-copy"><div class="savp-copy-name"></div> <div class="savp-copy-inner"></div></div>'
            );
            $copy.addClass("savp--" + tax_type + " " + that.behavior);
            if (!that.isLoop) {
                $copy.addClass("savp-copy-singular");
            }
            $ul.data("copyEl", $copy);
            $wrap.append($copy);

            if (["color", "label", "select"].indexOf(tax_type) > -1) {
                $ul.addClass("list");
            }

            var canvasTpl =
                '<div class="savp-canvas-wrap">' +
                '<div class="savp-canvas-drop"></div>' +
                '<div class="savp-canvas">' +
                '<div class="savp-canvas-header">' +
                '<div class="savp-canvas-heading"></div>' +
                '<div class="savp-canvas-close"><i class="savp-icon-cancel"></i></div>' +
                "</div>" +
                '<div class="savp-canvas-content"></div>' +
                "</div>" +
                "</div>";

            var $canvas = $(canvasTpl);
            $canvas.find(".savp-canvas-heading").html(args.tax.name);
            $canvas.find(".savp-canvas-content").append($ul);
            $wrap.append($canvas);
            $canvas.addClass("c-" + savpConfig.settings.canvas);
            $ul.data("_canvas", $canvas);

            $copy.on("click", function (e) {
                e.preventDefault();
                $canvas.toggleClass("active");
            });

            $inputLabel.on("click", function (e) {
                e.preventDefault();
                $canvas.toggleClass("active");
            });

            $canvas.on("click", ".savp-canvas-close", function (e) {
                e.preventDefault();
                $canvas.removeClass("active");
            });

            $canvas.on("click", ".savp-canvas-drop", function (e) {
                e.preventDefault();
                $canvas.removeClass("active");
            });

            $(document).on("keyup", function (e) {
                e.preventDefault();
                if (e.which === 27) {
                    $canvas.removeClass("active");
                }
            });
        } else {
            $inputLabel.html(args.tax.label);
            $wrap.append($ul);
        } // end if canvas.
    }; // end function.

    Sa_Vsg.prototype.copyLabel = function ($wrap) {
        var that = this;
        that.$form.find("ul.savp-box").each(function () {
            var $ul = $(this);
            if ($ul.data("copyEl")) {
                var $el = $ul.data("copyEl");
                var $li = $ul.find("li.selected").eq(0);
                var data = $li.data("item-data") || {};
                $el.removeClass("selected disabled unavailable");
                if (["label", "select"].indexOf(data._type) === -1) {
                    $el.find(".savp-copy-inner").html(
                        $li.find(".s-inner").clone()
                    );
                }

                $el.find(".savp-copy-name").html(data.name);
                $el.addClass($li.attr("class"));
            }
        });
    };

    Sa_Vsg.prototype.getValues = function () {
        var values = {};
        this.$form.find(".savp-box").each(function () {
            let name = $(this).attr("data-name") || "";
            let val = $(this).find("li.selected").attr("data-val") || "";
            let title = $(this).find("li.selected").attr("title") || "";
            values[name] = {
                key: val,
                name: title,
            };
        });

        return values;
    };

    Sa_Vsg.prototype.isMatchingPair = function (pairs) {
        var that = this;
        var matchingVariants = [];
        for (var i = 0; i < that.variants.length; i++) {
            var match = true;
            var loopVariant = that.variants[i];
            $.each(pairs, function (vk, vv) {
                try {
                    if (
                        vv !== loopVariant.attributes[vk] &&
                        loopVariant.attributes[vk] !== ""
                    ) {
                        match = false;
                    }
                } catch (e) {
                    match = false;
                }
            });

            if (match) {
                matchingVariants.push(that.variants[i]);
            }
        }

        return matchingVariants;
    };

    Sa_Vsg.prototype.updatePairSelect = function (key, pairs) {
        var that = this;
        var ul = this.$form.find('.savp-box[data-name="' + key + '"]');

        if (ul.hasClass("current-select")) {
            return;
        }
        var $lis = ul.find("li").not(".more-attr-term");
        $lis.addClass("disabled unavailable");
        $lis.removeClass("out-of-stock in-stock").find(".l-stock-status");
        $lis.find(".l-stock-status").html(that.settings.savp_l10n.unavailable);
        $lis.find(".l-price").html("");

        let matchingVariants = this.isMatchingPair(pairs);

        for (let i = 0; i < matchingVariants.length; i++) {
            let variant = matchingVariants[i];
            let $li;
            if (variant.attributes[key] !== "") {
                $li = ul.find('li[data-val="' + variant.attributes[key] + '"]');
            } else {
                $li = ul.find("li");
            }

            $li.removeClass("unavailable out-of-stock in-stock");

            let priceHtml = variant.price_html;

            $li.find(".l-price").wc_set_content(priceHtml);
            if (variant.is_in_stock) {
                $li.find(".l-stock-status")
                    .addClass("in-stock")
                    .removeClass("out-of-stock")
                    .html(that.settings.savp_l10n.in_stock);
            } else {
                $li.find(".l-stock-status")
                    .addClass("out-of-stock")
                    .removeClass("in-stock")
                    .html(that.settings.savp_l10n.out_of_stock);
            }
            if (variant.is_purchasable) {
                $li.removeClass("disabled");
            }
        }
    };

    Sa_Vsg.prototype.updateLink = function (values) {
        if (!this.isInit) {
            return;
        }
        try {
            var url = new URL(this.link);
            for (var k in values) {
                url.searchParams.set(k, values[k].key);
            }
            if (!this.isLoop) {
                window.history.pushState(url.href, document.title, url.href);
            } else {
                this.$addCartBtn.attr("href", url.href);
                this.$form
                    .closest(".product")
                    .find(".woocommerce-LoopProduct-link")
                    .attr("href", url.href);
            }
        } catch (e) {}
    };

    Sa_Vsg.prototype.onFoundVariation = function (variation) {
        const that = this;

        var $sku = that.$product.find(".product_meta").find(".sku"),
            $weight = that.$product.find(
                ".product_weight, .woocommerce-product-attributes-item--weight .woocommerce-product-attributes-item__value"
            ),
            $dimensions = that.$product.find(
                ".product_dimensions, .woocommerce-product-attributes-item--dimensions .woocommerce-product-attributes-item__value"
            );

        var purchasable = true;
        // Hide or show qty input
        if (variation.is_sold_individually === "yes") {
            that.$qty.val("1").attr("min", "1").attr("max", "").change();
            that.$qty.hide();
        } else {
            var qty_val = parseFloat(that.$qty.val());
            if (isNaN(qty_val)) {
                qty_val = variation.min_qty;
            } else {
                qty_val =
                    qty_val > parseFloat(variation.max_qty)
                        ? variation.max_qty
                        : qty_val;
                qty_val =
                    qty_val < parseFloat(variation.min_qty)
                        ? variation.min_qty
                        : qty_val;
            }
            that.$qty
                .attr("min", variation.min_qty)
                .attr("max", variation.max_qty)
                .val(qty_val)
                .change();
            that.$qty.show();
        }

        if (variation.sku) {
            $sku.wc_set_content(variation.sku);
        } else {
            $sku.wc_reset_content();
        }

        if (variation.weight) {
            $weight.wc_set_content(variation.weight_html);
        } else {
            $weight.wc_reset_content();
        }

        if (variation.dimensions) {
            // Decode HTML entities.
            $dimensions.wc_set_content(
                $.parseHTML(variation.dimensions_html)[0].data
            );
        } else {
            $dimensions.wc_reset_content();
        }

        that.$price.wc_set_content(variation.price_html);

        var template;
        if (!variation.variation_is_visible) {
            template = wp_template("unavailable-variation-template");
        } else {
            template = wp_template("variation-template");
        }

        var $template_html = template({
            variation: variation,
        });
        $template_html = $template_html.replace("/*<![CDATA[*/", "");
        $template_html = $template_html.replace("/*]]>*/", "");

        that.$singleVariation.html($template_html);
        // Enable or disable the add to cart button
        if (
            !variation.is_purchasable ||
            !variation.is_in_stock ||
            !variation.variation_is_visible
        ) {
            purchasable = false;
        }

        if (!purchasable) {
            this.$add_cart_group.addClass("disabled");
        } else {
            this.$add_cart_group.removeClass("disabled");
        }

        this.currentVariant = variation;

        // filter wc_add_to_cart_variation_params
        that.$variation.val(variation.variation_id);

        // Change product if in the loop.
        $('.savp-thumbnail[data-id="' + that.product_id + '"]')
            .find("img")
            .attr("src", variation.image.thumb_src);

        if (that.$product.length && variation.price_html) {
            that.$product.find(".price").wc_set_content(variation.price_html);
        } else {
            that.$product.find(".price").wc_reset_content();
        }

        if (that.$addCartBtn.length) {
            if (this.isAllAttr) {
                that.$addCartBtn.prop("disabled", false);
                that.$addCartBtn.addClass("ajax_add_to_cart");
                that.$addCartBtn.attr(
                    "data-product_id",
                    variation.variation_id
                );
            }
        }
        that.copyLabel();

        // Reveal.
        $(document).trigger("savp_variation_found", [
            that.product_id,
            variation,
            that.currentValue,
        ]);

        if ($.trim(that.$singleVariation.text())) {
            that.$singleVariation
                .trigger("show_variation", [variation, purchasable])
                .slideDown(200);
        } else {
            that.$singleVariation
                .trigger("show_variation", [variation, purchasable])
                .show();
        }
    };

    Sa_Vsg.prototype.onNotFoundVariation = function () {
        var that = this;
        this.currentVariant = null;
        that.$variation.val("");
        this.$price.wc_reset_content();
        this.$form
            .find(".woocommerce-variation.single_variation")
            .html(wc_add_to_cart_variation_params.i18n_unavailable_text);
        this.$add_cart_group.addClass("disabled");
        if (that.$addCartBtn.length) {
            that.$addCartBtn.removeClass("ajax_add_to_cart");
            that.$addCartBtn.attr("data-product_id", that.product_id);
            that.$addCartBtn.prop("disabled", true);
        }
        that.copyLabel();
    };

    /**
     * Check onFoundVariation
     */
    Sa_Vsg.prototype.updateVariation = function () {
        var that = this;
        that.$form.trigger("woocommerce_variation_has_changed");
        that.$form.trigger("woocommerce_update_variation_values");

        var values = this.getValues();
        that.currentValue = values;
        var pairs = {};
        var value_keys = {};
        $.each(values, function (vk, vv) {
            value_keys[vk] = vv.key;
            that.$form
                .find(".label-for__" + vk + " .savp-current-term")
                .html(vv.name);
            if (typeof pairs[vk] === "undefined") {
                pairs[vk] = {};
                $.each(values, function (vki, vvi) {
                    if (vki !== vk) {
                        pairs[vk][vki] = vvi.key;
                    }
                });
            }
        });

        that.updateLink(values);
        $.each(pairs, function (vk, pArgs) {
            that.updatePairSelect(vk, pArgs);
        });

        var matchingVariants = this.isMatchingPair(value_keys);

        if (!matchingVariants.length) {
            that.onNotFoundVariation();
            that.$form.trigger("reset_data");
            $(document).trigger("savp_variation_not_found", [
                that.product_id,
                values,
            ]);
        } else {
            var variation = matchingVariants.shift();
            that.onFoundVariation(variation);
            that.$form.trigger("found_variation", [variation, values]);
        }
    };

    Sa_Vsg.prototype.getDataForThumbnails = function () {
        var that = this;
        var data = {
            items: {},
            thumb_v: "",
            thumb_k: "",
        };

        that.$form.find(".savp-box").each(function () {
            var k = $(this).attr("data-key") || "";
            var v = $(this).attr("data-default") || "";
            var t = $(this).attr("data-type") || "";
            if (["thumbnail", "thumbnail_list"].indexOf(t) > -1) {
                data.thumb_v = v;
                data.thumb_k = k;
            }
            data.items[k] = { k, v, t };
        });
        return data;
    };

    Sa_Vsg.prototype.thumbnailSwashes = function () {
        const that = this;
        var defaultData = that.getDataForThumbnails();
        var thumbnails = {};
        $.each(that.variants, function (key, variant) {
            let match = true;
            $.each(defaultData.items, function (ik, iv) {
                if (variant.attributes["attribute_" + ik] !== "") {
                    if (
                        ["thumbnail", "thumbnail_list"].indexOf(iv.t) === -1 &&
                        variant.attributes["attribute_" + ik] !== iv.v
                    ) {
                        match = false;
                    }
                }
            });

            if (match) {
                let vv = variant.attributes["attribute_" + defaultData.thumb_k];
                if (vv) {
                    if (
                        variant.image.gallery_thumbnail_src &&
                        !that.isInCanvas
                    ) {
                        thumbnails[vv] = variant.image.gallery_thumbnail_src;
                    } else {
                        thumbnails[vv] = variant.image.thumb_src;
                    }
                }
            }
        }); // End loop variants.

        let size = Object.keys(thumbnails).length;

        if (size === 0) {
            // find thumbnail in gallery.
            let gallery_items = [];
            if (!that.isLoop) {
                gallery_items =
                    that.settings.savp_product_gallies[
                        "_single_" + that.product_id
                    ];
            } else {
                gallery_items =
                    that.settings.savp_product_gallies["_" + that.product_id];
            }
            if (!that.currentValue) {
                that.currentValue = that.getValues();
            }

            $.each(that.swatches, function (key, args) {
                if (args.type === "thumbnail") {
                    let k = "attribute_" + key;
                    let currentValue = that.currentValue[k].key;
                    $.each(args.terms, function (tk, tArgs) {
                        let slug = tArgs.slug;

                        for (let i = 0; i < gallery_items.length; i++) {
                            let item = gallery_items[i];
                            if (item.index === 0) {
                                if (
                                    item.attrs &&
                                    typeof item.attrs[k][slug] !== "undefined"
                                ) {
                                    // for all;
                                    thumbnails[slug] = item.thumb[0];
                                }
                            }
                        }
                    });
                }
            });
        }

        that.$form
            .find('.savp-box[data-key="' + defaultData.thumb_k + '"] li')
            .each(function () {
                let v = $(this).attr("data-val") || "";
                if (thumbnails[v]) {
                    $(this)
                        .find(".no-img")
                        .replaceWith(
                            '<span class="s-inner has-img"><img src="' +
                                thumbnails[v] +
                                '" alt=""/></span>'
                        );
                }
            });
    };

    Sa_Vsg.prototype.toDefault = function () {
        var that = this;
    };

    Sa_Vsg.prototype.clickToLabelItem = function ($button) {
        var that = this;
        var w = $button.closest(".savp-wrapper");
        var p = $button.closest(".savp-box");
        that.$form.find(".savp-box").removeClass("current-select");
        p.addClass("current-select");
        let select = w.find("select");
        var v = $button.attr("data-val") || "";
        select.find("option").prop("selected", false).removeAttr("selected");
        select.find('option[value="' + v + '"]').prop("selected", true);
        select.trigger("change");
        w.find("li").removeClass("selected");
        $button.addClass("selected");
        if (p.data("_canvas")) {
            p.data("_canvas").removeClass("active");
        }
        that.updateVariation();
    };

    Sa_Vsg.prototype.intEvents = function ($form) {};

    $.fn.savp_vsg = function (settings = window) {
        return this.each(function () {
            new Sa_Vsg($(this), settings);
        });
    };

    $(document).on("savp_data_loaded", function (e, $wrapper, res) {
        $wrapper.find(".variations_form").each(function () {
            $(this).savp_vsg(res.savp);
        });
    });
})(jQuery);
