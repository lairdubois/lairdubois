+function ($) {
    'use strict';

    // CLASS DEFINITION
    // ======================

    var LadbCommentWidget = function(element, options) {
        this.options = options;
        this.$element = $(element);

        this.$showActivities = $('#ladb_comment_settings_show_activities', this.$element);
        this.$hideActivities = $('#ladb_comment_settings_hide_activities', this.$element);

        this.activitiesHidden = this.$element.data('ladb-activities-hidden');

    };

    LadbCommentWidget.DEFAULTS = {
    };

    LadbCommentWidget.prototype.layoutActivities = function() {
        if (this.activitiesHidden) {
            $('.ladb-activity-row', this.$element).hide();
            this.$showActivities.show();
            this.$hideActivities.hide();
        } else {
            $('.ladb-activity-row', this.$element).show();
            this.$showActivities.hide();
            this.$hideActivities.show();
        }
    };

    LadbCommentWidget.prototype.toggleActivities = function() {
        this.activitiesHidden = !this.activitiesHidden;
        this.layoutActivities();
    };

    LadbCommentWidget.prototype.init = function() {
        var that = this;

        this.$showActivities.on('click', function() {
            that.toggleActivities();
        });
        this.$hideActivities.on('click', function() {
            that.toggleActivities();
        });
        this.layoutActivities();

    };


    // PLUGIN DEFINITION
    // =======================

    function Plugin(option) {
        return this.each(function () {
            var $this   = $(this);
            var data    = $this.data('ladb.commentWidget');
            var options = $.extend({}, LadbCommentWidget.DEFAULTS, $this.data(), typeof option == 'object' && option);

            if (!data) {
                $this.data('ladb.commentWidget', (data = new LadbCommentWidget(this, options)));
            }
            if (typeof option == 'string') {
                data[option]();
            } else {
                data.init();
            }
        })
    }

    var old = $.fn.ladbCommentWidget;

    $.fn.ladbCommentWidget             = Plugin;
    $.fn.ladbCommentWidget.Constructor = LadbCommentWidget;


    // NO CONFLICT
    // =================

    $.fn.ladbCommentWidget.noConflict = function () {
        $.fn.ladbCommentWidget = old;
        return this;
    }

}(jQuery);