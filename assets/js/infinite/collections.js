/**
 * This file is part of the InfiniteFormBundle package.
 *
 * (c) Infinite Networks Pty Ltd <http://infinite.net.au>
 */

/**
 * Provides helper javascript for handling adding and removing items from a form
 * collection. It requires jQuery to operate.
 *
 * To use this collection javascript, initialise it against a collection and pass in any
 * prototype add links as a second argument.
 *
 * The example below assumes that PR https://github.com/symfony/symfony/pull/7713 has been
 * merged.
 *
 *      $('[data-form-widget=collection]').each(function () {
 *          new window.infinite.Collection(this, $('[data-prototype]', this));
 *      });
 *
 * @author Tim Nagel <t.nagel@infinite.net.au>
 */
(function ($) {
    "use strict";

    window.infinite = window.infinite || {};

    /**
     * Creates a new collection object.
     *
     * @param collection The DOM element passed here is expected to be a reference to the
     *                   containing element that wraps all items.
     * @param prototypes We expect a jQuery array passed here that will provide one or
     *                   more clickable elements that contain a prototype to be inserted
     *                   into the collection as a data-prototype attribute.
     * @param options    Allows configuration of different aspects of the Collection
     *                   objects behavior.
     */
    window.infinite.Collection = function (collection, prototypes, options) {
        this.$collection = $(collection);
        this.anchorId = null;
        this.internalCount = this.$collection.children().length * 10;    // LADB : * 2 to avoid ids continuity holes
        this.$prototypes = prototypes;

        this.options = $.extend({
            allowAdd: true,
            allowDelete: true,
            disabledSelector: '[data-disabled]',
            itemSelector: '.infinite_collections_item',
            prototypeAttribute: 'data-prototype',
            prototypeName: '__name__',
            removeSelector: '.infinite_collections_remove_item',
            anchorSelector: '.infinite_collections_anchor_item',
            moveUpSelector: '.infinite_collections_move_up_item',
            moveDownSelector: '.infinite_collections_move_down_item'
        }, options || {});

        this.initialise();
    };

    window.infinite.Collection.prototype = {
        /**
         * Sets up the collection and its prototypes for action.
         */
        initialise: function () {
            var that = this;
            this.$prototypes.on('click', function (e) {
                e.preventDefault();

                that.addToCollection($(this));
            });

            this.$collection.on('click', this.options.removeSelector, function (e) {
                e.preventDefault();

                that.removeFromCollection($(this).closest(that.options.itemSelector));
            });

            this.$collection.on('click', this.options.anchorSelector, function (e) {
                e.preventDefault();

                that.toggleAnchor($(this).closest(that.options.itemSelector));
            });

            this.$collection.on('click', this.options.moveUpSelector, function (e) {
                e.preventDefault();

                that.moveUpInCollection($(this).closest(that.options.itemSelector));
            });

            this.$collection.on('click', this.options.moveDownSelector, function (e) {
                e.preventDefault();

                that.moveDownInCollection($(this).closest(that.options.itemSelector));
            });

            this.$collection.data('collection', this);
            this._updateRows();
        },

        /**
         * Adds another row to the collection
         */
        addToCollection: function ($prototype) {
            if (!this.options.allowAdd || this.$collection.is(this.options.disabledSelector)) {
                return;
            }

            var html = this._getPrototypeHtml($prototype, this.internalCount++),
                $row = $($.parseHTML(html, document, true));                            // LADB : Add 'true'

            var event = this._createEvent('infinite_collection_add');
            event.$triggeredPrototype = $prototype;
            event.$row = $row;
            event.insertBefore = null;
            this.$collection.trigger(event);

            if (!event.isDefaultPrevented()) {
                if (event.insertBefore) {
                    $row.insertBefore(event.insertBefore);
                } else {
                    this.$collection.append($row);
                }
            }

            $row.find("input, textarea").focus();
            this._updateRows();
            this._triggerChange($row);
        },

        /**
         * Removes a supplied row from the collection.
         */
        removeFromCollection: function ($row) {
            if (!this.options.allowDelete || this.$collection.is(this.options.disabledSelector)) {
                return;
            }

            var event = this._createEvent('infinite_collection_remove');
            $row.trigger(event);
            this._triggerChange($row);

            if (!event.isDefaultPrevented()) {
                $row.remove();
            }
        },

        /**
         * Set item as anchor.
         */
        toggleAnchor: function ($row) {
            var oldAnchorId = this.anchorId;
            if (oldAnchorId != null) {
                this.anchorId = null;
                if (oldAnchorId == $row.attr('id')) {
                    this._updateRows();
                    return;
                }
            }
            this.anchorId = $row.attr('id');
            this._updateRows();
        },

        /**
         * Move item up in the collection.
         */
        moveUpInCollection: function ($row) {
            if (this.anchorId == null) {
                $row.after($row.prev());
            } else {
                $('#' + this.anchorId).after($row);
            }
            this._updateRows();
            this._triggerChange($row);
            this._scrollToRow($row);
            $row.effect("slide", {}, 1000);
        },

        /**
         * Move item down in the collection.
         */
        moveDownInCollection: function ($row) {
            if (this.anchorId == null) {
                $row.before($row.next());
            } else {
                $('#' + this.anchorId).before($row);
            }
            this._updateRows();
            this._triggerChange($row);
            this._scrollToRow($row);
            $row.effect("slide", {}, 1000);
        },

        /**
         * @private
         */
        _updateRows: function() {
            var that = this;
            var items = this.$collection.find(this.options.itemSelector);
            var itemCount = items.length;
            var lastIndex = items.length - 1;
            var anchorIndex = -1;
            var moveUpTitle = that.anchorId == null ? "Monter ce bloc" : "Placer ce bloc après l'ancre";
            var moveDownTitle = that.anchorId == null ? "Descendre ce bloc" : "Placer ce bloc avant l'ancre";
            items.each(function(index) {
                var $anchorBtn = $(this).find(that.options.anchorSelector);
                if (itemCount > 3) {
                    $anchorBtn.addClass("enabled");
                    $anchorBtn.removeClass("disabled");
                } else {
                    $anchorBtn.removeClass("enabled");
                    $anchorBtn.addClass("disabled");
                }
                if (that.anchorId == $(this).attr('id')) {
                    anchorIndex = index;
                    $anchorBtn.addClass("active");
                } else {
                    $anchorBtn.removeClass("active");
                }
                $(this).find(that.options.moveUpSelector).attr("data-original-title", moveUpTitle);
                $(this).find(that.options.moveDownSelector).attr("data-original-title", moveDownTitle);
            });
            var moveUpEnabled = true;
            var moveDownEnabled = true;
            items.each(function(index) {
                if (anchorIndex >= 0) {
                    moveUpEnabled = index > anchorIndex + 1;
                    moveDownEnabled = index < anchorIndex - 1;
                }
                if (index == 0 || !moveUpEnabled) {
                    $(this).find(that.options.moveUpSelector).addClass("disabled");
                } else {
                    $(this).find(that.options.moveUpSelector).removeClass("disabled");
                }
                if (index == lastIndex || !moveDownEnabled) {
                    $(this).find(that.options.moveDownSelector).addClass("disabled");
                } else {
                    $(this).find(that.options.moveDownSelector).removeClass("disabled");
                }
                $(this).find("[id$=sortIndex]").val(index);
            });
            setupTooltips();
            LADBCommon.setupTextareas();
        },

        /**
         * @private
         */
        _triggerChange: function($row) {
            $row.find("[id$=sortIndex]").trigger("change");
        },

            /**
         * @private
         */
        _scrollToRow: function($row) {
            var rowTop = $row.offset().top;
            var scrollTop = $(window).scrollTop();
            var scrollMax = $(document).height() - $(window).height();
            if (scrollTop < scrollMax || rowTop - 100 <= scrollTop) {
                $(window).scrollTo($row, {
                    duration: 500,
                    offset: { top: (rowTop - 100) < scrollMax ? -100 : 0 }
                });
            }
        },

        /**
         * Retrieves the HTML from the prototype button, replacing __name__label__
         * and __name__ with the supplied replacement value.
         *
         * @private
         */
        _getPrototypeHtml: function ($prototype, replacement) {
            var event = this._createEvent('infinite_collection_prototype');
            event.$triggeredPrototype = $prototype;
            event.html = $prototype.attr(this.options.prototypeAttribute);
            event.replacement = replacement;
            this.$collection.trigger(event);

            if (!event.isDefaultPrevented()) {
                var labelRegex = new RegExp(this.options.prototypeName + 'label__', 'gi'),
                    prototypeRegex = new RegExp(this.options.prototypeName, 'gi');

                event.html = event.html.replace(labelRegex, replacement)
                    .replace(prototypeRegex, replacement);
            }

            return event.html;
        },

        /**
         * Creates a jQuery event object with the given name.
         *
         * @private
         */
        _createEvent: function (eventName) {
            var event = $.Event(eventName);
            event.collection = this;

            return event;
        }
    };
}(window.jQuery));
