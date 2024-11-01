(function ($) {
    "use strict";

    const $document = $(document);

    var Sa_Gallery = function ($el, settings = window) {
        const that = this;
        that.settings = settings;
        that.variation_id = 0;
        that.timeoutInit = false;
        that.viewPortCurrent = "";
        that.isSingle = $el.data("single") || false;

        that.currentAttrValues = {};
        that.sliderAdded = {};
        if (that.isSingle) {
            that.options = $.extend(
                {},
                {
                    gallery_type: "slider",

                    n_slide: 1,
                    n_slide_md: 1,
                    n_slide_sm: 1,
                    n_thumb: 6,
                    n_thumb_md: 6,
                    n_thumb_sm: 6,
                    nav_height: 50,
                    nav_pos: savpConfig.gallerySettings.nav_pos,
                    nav_size: "variation",
                    nav_sm: "hide",
                    nav_md: "show",
                    nav_width: 120,
                    sm: 500,
                    md: 768,
                    spacing: 15,
                    var_only: "yes",
                    video_post: 2,
                    zoom: "yes",
                },
                that.settings.savp_gallery_settings.single
            );
        } else {
            that.options = $.extend(
                {},
                {
                    n_slide: 1,
                    n_thumb: 6,
                    nav_height: 50,
                    nav_pos: "hide",
                    spacing: 0,
                    var_only: "yes",
                    video_post: 2,
                },
                that.settings.savp_gallery_settings.loop
            );
        }

        that.$wrap = $el;
        that.isVariable = false;
        that.$product = that.$wrap.closest(".product") || false;
        if (that.$wrap.hasClass("savp-variable")) {
            that.isVariable = true;
        }

        that.$wrap.addClass("savp-added");
        that.$product.addClass("savp-gallery-added");

        that.id = $el.data("id") || 0;
        that.id = parseInt(that.id);
        if (isNaN(that.id)) {
            that.id = 0;
        }
        that.images = [];

        if (that.isSingle) {
            if (
                typeof that.settings.savp_product_gallies[
                    "_single_" + that.id
                ] !== "undefined"
            ) {
                that.images =
                    that.settings.savp_product_gallies["_single_" + that.id];
            }
        } else {
            if (
                typeof that.settings.savp_product_gallies["_" + that.id] !==
                "undefined"
            ) {
                that.images = that.settings.savp_product_gallies["_" + that.id];
            }
        }

        that.product_id = that.id;
        that.variation_id = 0;
        // Store index of image item.
        that.viewingListIndex = [];
        if (!that.isSingle && that.images.length <= 1) {
            that.$wrap.removeClass("loading");
            return;
        }

        if (!that.isSingle) {
            that.$wrap.addClass("savp-lgs-added");
        } else {
            that.$wrap.html("");
        }

        that.$product.addClass("savp-n-" + that.options.nav_pos);

        that.isVertical = false;
        that.isNavSlider = true;
        that.useNav = true;
        that.useSlider = true;
        that.listItems = [];

        if (that.isSingle) {
            if (that.isVariable) {
                that.intEvents();
            } else {
                that.reIntGallery();
            }
        } else {
            if (that.isVariable) {
                if (!that.$product.find("form.cart").length) {
                    that.reIntGallery();
                } else {
                    that.intEvents();
                }
            } else {
                that.reIntGallery();
            }
        }

        that.removeLoading();
    };

    Sa_Gallery.prototype.removeLoading = function () {
        var that = this;
        setTimeout(function () {
            that.$wrap.removeClass("loading");
        }, 200);
    };

    Sa_Gallery.prototype.createGalleryItem = function (image, t = "main") {
        let $img,
            that = this;

        let is_video = typeof image.video !== "undefined" && image.video;
        let $imgNav;
        let imageSize, mainSize;

        try {
            imageSize = image.sizes[that.options.nav_size];
        } catch (e) {
            imageSize = image.sizes.thumb;
        }
        if (!imageSize) {
            imageSize = image.sizes.thumb;
        }

        if (that.isSingle && t === "main") {
            mainSize = image.sizes.large;
        } else {
            mainSize = imageSize;
        }

        if (that.$nav) {
            $imgNav = $("<img/>");
            $imgNav.attr("src", imageSize[0][0]);
            $imgNav.addClass("savp__image");
        }

        if (is_video) {
            that.has_video = true;
            let video_code = image.embed;
            let $img = $(
                '<div class="savp-main--video">' + video_code + "</div>"
            );
            $img.attr("data-code", video_code);
        } else {
            $img = $("<img/>");
            if (!that.isSingle) {
                $img.attr("src", image.sizes.thumb[0][0]);
                if (image.sizes.thumb[1]) {
                    $img.attr("srcset", image.sizes.thumb[1]);
                }
            } else {
                $img.attr("src", mainSize[0][0]);
                if (mainSize[1]) {
                    $img.attr("srcset", image.sizes.full[1]);
                }
            }
            $img.addClass("savp__image");
        }

        $img.wrap('<div class="slider-item-inner">');

        $img = $img.parent();

        return {
            item: $img,
            nav: $imgNav,
            is_video: is_video,
        };
    };

    Sa_Gallery.prototype.initGallery = function (imageArgs) {
        const that = this;

        let keyId = `_${that.product_id}_${imageArgs.key}`;
        let $main,
            $wrapper,
            $nav,
            isNavSlider = true,
            useNav = true;

        if (!that.isSingle) {
            useNav = false;
        }

        if (typeof that.sliderAdded[keyId] !== "undefined") {
            $wrapper = that.$wrap.find("[data-id='" + keyId + "']");
            $main = $wrapper.find(".savp-main");
            if (useNav) {
                $nav = $wrapper.find(".savp-nav");
            }
            that.$wrap
                .find(".savp-gallery:not(#" + keyId + ")")
                .addClass("hide");
            $wrapper.removeClass("hide");
        } else {
            that.sliderAdded[keyId] = true;
            that.$wrap.find(".savp-gallery").addClass("hide");
            $main = $(
                `<div class="savp-main swiper-container"><div class="swiper-wrapper"></div><div class="swiper-button-prev"></div><div class="swiper-button-next"></div></div>`
            );

            $wrapper = $(`<div data-id="${keyId}" class="savp-gallery"></div>`);
            $wrapper.append($main);

            if (that.isSingle) {
                $main.wrap('<div class="savp-main-wrapper savp-ws"/>');
            }

            if (useNav) {
                $nav = $(
                    `<div class="savp-nav swiper-container"><div class="swiper-wrapper"></div><div class="swiper-button-prev"></div><div class="swiper-button-next"></div></div>`
                );
                $wrapper.append($nav);
            }

            that.$wrap.append($wrapper);

            switch (that.options.nav_pos) {
                case "left":
                    if (useNav) {
                        $wrapper.addClass("ver-nav nav-left");
                    }

                    break;
                case "right":
                    if (useNav) {
                        $wrapper.addClass("ver-nav nav-right");
                    }
                    break;
                case "none":
                case "hide":
                    isNavSlider = false;
                    useNav = false;
                    break;
                case "bottom-grid-1":
                case "bottom-grid-2":
                case "bottom-grid-3":
                case "bottom-grid-4":
                    isNavSlider = false;
                    break;
                default:
                    if (useNav) {
                        $wrapper.addClass("hr-nav nav-bottom");
                        if (that.position === "bottom-inside") {
                            $wrapper.addClass("nav-inside");
                        }
                    }
            }

            if (!useNav) {
                $wrapper.addClass("no-nav");
            }

            let listImages = [];
            let countSlide = 0;
            for (var j = 0; j < imageArgs.images.length; j++) {
                let image = imageArgs.images[j];
                listImages.push({
                    src: image.sizes.full[0][0],
                    w: image.sizes.full[0][1],
                    h: image.sizes.full[0][2],
                });
                let mainItem = that.createGalleryItem(image);
                let navItem = that.createGalleryItem(image);

                if (!mainItem) {
                    continue;
                }

                if (useNav) {
                    $nav.find(".swiper-wrapper").append(navItem.item);
                    navItem.item.wrap("<div class='swiper-slide'></div>");
                }
                countSlide++;

                $main.find(".swiper-wrapper").append(mainItem.item);
                mainItem.item.wrap("<div class='swiper-slide'></div>");
            }

            if (countSlide <= 1) {
                $main.addClass("only-one");
            }

            let sliderThumb, sliderMain, mainArgs, navArgs;
            mainArgs = {
                speed: 400,
                spaceBetween: 0,
                slidesPerView: 1,
                allowTouchMove: true,
                loop: false,
                watchSlidesVisibility: true,
                navigation: {
                    nextEl: ".swiper-button-next",
                    prevEl: ".swiper-button-prev",
                },
                thumbs: {},
            };

            navArgs = {
                allowTouchMove: true,
                centeredSlides: false,
                centeredSlidesBounds: true,
                slidesPerView: "auto",
                spaceBetween: that.options.spacing,
                autoHeight: true,
                loop: false,
                watchOverflow: true,
                slideToClickedSlide: true,
                watchSlidesVisibility: true,
                direction: "vertical",
                navigation: {
                    nextEl: ".swiper-button-next",
                    prevEl: ".swiper-button-prev",
                },
                on: {},
            };

            if (that.isSingle && useNav) {
                $wrapper.addClass("nav-" + that.options.nav_pos);
                if (
                    that.options.nav_pos === "bottom" ||
                    that.options.nav_pos === "bottom-inside"
                ) {
                    navArgs.direction = "horizontal";
                    // navArgs.slidesPerView = that.options.n_thumb_sm;
                    navArgs.slidesPerView = "auto";
                    navArgs.autoHeight = false;
                } else {
                    navArgs.slidesPerView = "auto";
                    navArgs.autoHeight = true;
                }
            } else {
                mainArgs.slidesPerView = 1;
            }

            const gallerySettingKeys = [
                "woocommerce_single_gallery_width",
                "savp_gallery_spacing",
                "savp_gallery_nav_size",
            ];

            const enableSlider = function () {
                disableSlider();
                if (useNav) {
                    $nav.removeClass("hide");
                } else {
                    if ($nav) {
                        $nav.addClass("hide");
                    }
                }

                $main.off(".savp-modal");
                if (useNav) {
                    if (isNavSlider) {
                        sliderThumb = new Swiper($nav.get(0), navArgs);
                        mainArgs.thumbs = {
                            swiper: sliderThumb,
                            multipleActiveThumbs: true,
                        };
                        $document.on(
                            "onestore_customize_setting_changed",
                            function (e, key, value) {
                                if (gallerySettingKeys.indexOf(key) > -1) {
                                    if ("savp_gallery_spacing" === key) {
                                        sliderThumb.params.spaceBetween =
                                            parseInt(value);
                                    }
                                    sliderThumb.update();
                                }
                            }
                        );
                    } else {
                        $nav.on("click", ".swiper-slide", function (e) {
                            e.preventDefault();
                            let index = $(this).index();
                            that.openModal(index, listImages);
                        });
                    }
                }

                sliderMain = new Swiper($main.get(0), mainArgs);
                sliderMain.on("slideChangeTransitionEnd", function () {
                    let elw = sliderMain.slides[sliderMain.activeIndex];
                    that.intZoom(elw);
                });

                $document.on(
                    "onestore_customize_setting_changed",
                    function (e, key, value) {
                        if (gallerySettingKeys.indexOf(key) > -1) {
                            sliderMain.update();
                        }
                    }
                );

                sliderMain.on("click", function (event) {
                    let $target = $(event.target);
                    if (
                        $target.is(".swiper-slide") ||
                        $target.closest(".swiper-slide").length
                    ) {
                        that.openModal(sliderMain.clickedIndex, listImages);
                    }
                });
                let elw = sliderMain.slides[sliderMain.activeIndex];
                that.intZoom(elw);
            };

            const disableSlider = function () {
                if (sliderMain) {
                    sliderMain.destroy(true, true);
                }
                if (sliderThumb) {
                    sliderThumb.destroy(true, true);
                }

                $main.off(".savp-modal");
                $main.on("click.savp-modal", ".swiper-slide", function (e) {
                    e.preventDefault();
                    let index = $(this).index();
                    that.openModal(index, listImages);
                });
                if (useNav) {
                    $nav.addClass("hide");
                }
            };

            const breakpoint = window.matchMedia(
                "(max-width:" + savpConfig.gallerySettings.md + "px)"
            );

            const breakpointChecker = function () {
                if (breakpoint.matches === true) {
                    enableSlider();
                    $wrapper.removeClass("grid-view").addClass("slider-view");
                    $main.removeClass(that.options.gallery_type);
                } else if (breakpoint.matches === false) {
                    disableSlider();
                    $wrapper.addClass("grid-view").removeClass("slider-view");
                    $main.addClass(that.options.gallery_type);
                }
            };

            if (
                that.isSingle &&
                ["grid-1", "grid-2", "grid-3", "grid-4"].indexOf(
                    that.options.gallery_type
                ) > -1
            ) {
                breakpointChecker();
                // Keep an eye on viewport size changes.
                breakpoint.addEventListener("change", breakpointChecker);
            } else {
                enableSlider();
            }
        } // end if slider.

        if (that.options.numberSlide > 1) {
            $main.addClass("gt-1");
        }
    };

    Sa_Gallery.prototype.openModal = function (index, listItems) {
        const that = this;
        if (!that.isSingle) {
            return;
        }
        let pswpElement = document.querySelectorAll(".pswp")[0];
        let swipeOptions = {
            index: 0,
            escKey: true,
            history: false,
        };

        swipeOptions.index = index || 0;
        let pswp = new PhotoSwipe(
            pswpElement,
            PhotoSwipeUI_Default,
            listItems,
            swipeOptions
        );

        pswp.listen("close", function () {
            $.each(pswp.items, function (i, item) {
                $(item.container).find(".pswp--video").remove();
            });
        });
        pswp.init();
    };

    Sa_Gallery.prototype.getViewPort = function () {
        const that = this;
        const winw = $(window).width();
        let viewPort = "lg";
        if (winw <= savpConfig.gallerySettings.md) {
            viewPort = "md";
        }

        if (winw <= savpConfig.gallerySettings.sm) {
            viewPort = "sm";
        }

        return viewPort;
    };

    Sa_Gallery.prototype.isMatchingAttr = function (attrs) {
        let ok = true;
        $.each(this.currentAttrValues, function (ck, cvArgs) {
            let cv = cvArgs.key;
            if (typeof attrs[ck] === "undefined") {
                ok = false;
                return;
            }

            if (attrs[ck] !== "") {
                if (cv !== attrs[ck]) {
                    ok = false;
                    return;
                }
            }
        });

        return ok;
    };

    Sa_Gallery.prototype.findMatchingImages = function (imgType = "") {
        const that = this;
        let n = Object.keys(that.currentAttrValues).length;
        let images = [];
        let key = "";
        // Find image from bulk first.
        if (n > 0) {
            $.each(that.images["bulk"], function (bulk_id, args) {
                if (that.isMatchingAttr(args.attrs)) {
                    key = bulk_id;
                    images = args.images;
                    return;
                }
            });
        }

        // If not found image in bulk. find variation gallery.
        if (
            !images.length &&
            that.variation_id &&
            typeof that.images["_vg" + that.variation_id] !== "undefined"
        ) {
            key = "_vg" + that.variation_id;
            images = that.images["_vg" + that.variation_id];
        }

        // Still not found. find variation thumbnail.
        if (
            !images.length &&
            that.variation_id &&
            typeof that.images["_v" + that.variation_id] !== "undefined"
        ) {
            key = "_v" + that.variation_id;
            images.push(that.images["_v" + that.variation_id]);
        }

        // Still not found. find variation find gallery.
        if (!images.length && typeof that.images["gallery"] !== "undefined") {
            key = "gallery";
            images = that.images["gallery"];
        }

        // Still not found. Find product thumbnail.
        if (!images.length && typeof that.images["thumb"] !== "undefined") {
            key = "thumb";
            images.push(that.images["thumb"]);
        }

        // Insert videos.
        if (typeof that.images.video !== "undefined" && that.isSingle) {
            if (typeof that.images.video !== "undefined") {
                if (variationImages.length) {
                    switch (that.video_post) {
                        case "1":
                        case 1:
                            images.splice(0, 0, that.images.video);
                            break;
                        case "2":
                        case 2:
                            images.splice(1, 0, that.images.video);
                            break;
                        default:
                            images.push(that.images.video);
                    }
                }
            } else {
                images.push(that.images.video);
            }
        }

        if (images.length) {
            return {
                key,
                images,
            };
        }

        return false;
    };
    /**
     *
     * @param {number} vid 0 Mean for all.
     */
    Sa_Gallery.prototype.reIntGallery = function () {
        const that = this;

        if (that.timeoutInit) {
            clearTimeout(that.timeoutInit);
            that.timeoutInit = false;
        }

        let imageArgs = that.findMatchingImages();

        that.initGallery(imageArgs);
    };

    Sa_Gallery.prototype.intGalleryEvents = function () {
        return;
        const that = this;

        if (that.currentZoomEl) {
            try {
                that.currentZoomEl.kill();
            } catch (e) {}
        }

        that.$main.on(
            "click.savp_slide_item",
            ".savp__image, .savp__video",
            function (e) {
                e.preventDefault();
                var index = $(this).data("_index") || 0;
                that.openModal(index);
            }
        );
        if (that.$nav) {
            that.$nav.off(".savp_slide_nav_item");
            that.$nav.on(
                "click.savp_slide_nav_item",
                ".grid-item",
                function (e) {
                    e.preventDefault();
                    var index =
                        that.$nav.find(".grid-item").index($(this)) || 0;
                    that.openModal(index);
                }
            );
        }

        if (!that.isSingle) {
            // Click Nav.
            that.$main.on(
                "click.savp_slide_item",
                ".slick-slide",
                function (e) {
                    e.preventDefault();
                    try {
                        window.location = that.$wrap
                            .closest(".woocommerce-LoopProduct-link")
                            .attr("href");
                    } catch (e) {}
                }
            );
            return;
        }
        var updateSliderCounter = function (slick, currentIndex) {
            try {
                var currentSlide = slick.currentSlide + 1;
                var slidesCount = slick.slideCount;
                that.$counter.text(currentSlide + "/" + slidesCount);
            } catch (e) {}
        };
        updateSliderCounter(that.mainSlick, 0);
        that.currentZoomEl = undefined;
        that.$main.on("destroy.savp_slide_item", function (event, slick) {
            if (that.isSingle && that.zoom) {
                if (that.currentZoomEl) {
                    that.currentZoomEl.kill();
                }
            }
        });

        that.$main.on(
            "afterChange.savp_slide_item",
            function (event, slick, currentSlide) {
                if (that.isSingle && that.zoom) {
                    let wrapper = slick.$slides[currentSlide];
                    that.intZoom(wrapper);
                }

                slick.$slides.each(function (index) {
                    if (index !== currentSlide) {
                        var $el = $(this).find(".savp-main--video");
                        if ($el.length) {
                            var $media = $el.find("iframe, video");
                            // Pause video
                            $media.each(function (index, m) {
                                var $media_item = $(m);
                                if ($media_item.is("iframe")) {
                                    $media_item.attr(
                                        "src",
                                        $media_item.attr("src")
                                    );
                                } else {
                                    $media.get(0).pause();
                                }
                            });
                        }
                    } // end if index
                });
                updateSliderCounter(slick, currentSlide);
            }
        );

        if (that.isZoomTimeout) {
            clearTimeout(that.isZoomTimeout);
            that.isZoomTimeout = undefined;
        }

        that.isZoomTimeout = setTimeout(() => {
            if (that.isSingle && that.zoom) {
                const wrapper = that.mainSlick.$slides[0];
                // that.intZoom(wrapper);
            }
        }, 1000);
    };

    Sa_Gallery.prototype.intZoom = function (wrapperEl) {
        const that = this;
        if (that.options.zoom !== 1) {
            return;
        }
        try {
            if (that.currentZoomEl) {
                that.currentZoomEl.kill();
            }
        } catch (e) {}

        const vp = that.getViewPort();

        if (vp === "sm" || vp === "md") {
            return;
        }

        const $wrapperEl = $(wrapperEl);
        const img = $wrapperEl.find(">div").get(0);
        const wRect = wrapperEl.getBoundingClientRect();

        let options = {
            // scale: 1.5,
            zoomWidth: wRect.width,
            zoomContainer: that.$wrap.get(0),
            offset: { vertical: 0, horizontal: 10 },
            zoomLensStyle:
                "background-color: rgba(167, 167, 167, 0.4); opacity: 0.6",
        };
        that.currentZoomEl = new ImageZoom(img, options);
    };

    Sa_Gallery.prototype.intEvents = function () {
        const that = this;
        $(document).on(
            "savp_variation_found",
            function (e, pid, variant, currentAttrValues) {
                if (pid !== that.product_id) {
                    return;
                }

                that.currentAttrValues = currentAttrValues;

                if (that.timeoutInit) {
                    clearTimeout(that.timeoutInit);
                }

                that.variation_id = parseInt(variant.variation_id);
                that.timeoutInit = setTimeout(function () {
                    that.reIntGallery(that.variation_id);
                }, 1);
            }
        );

        $(document).on(
            "savp_variation_not_found",
            function (e, pid, currentAttrValues) {
                if (pid !== that.product_id) {
                    return;
                }
                that.currentAttrValues = currentAttrValues;
                if (that.timeoutInit) {
                    clearTimeout(that.timeoutInit);
                }
                that.variation_id = 0;
                that.timeoutInit = setTimeout(function () {
                    that.reIntGallery(0);
                }, 300);
            }
        );
    };

    $.fn.savp_gallery = function (settings = window) {
        return this.each(function () {
            new Sa_Gallery($(this), settings);
        });
    };

    $(function () {
        $(document).on("savp_data_loaded", function (e, $wrapper, res) {
            $wrapper.find(".savp-thumbnail").savp_gallery(res.savp);
            $wrapper.find(".savp_gallery").savp_gallery(res.savp);
        });
    });
})(jQuery);
