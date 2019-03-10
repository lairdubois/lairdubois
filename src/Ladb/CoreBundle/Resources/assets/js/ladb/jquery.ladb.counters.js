+function ($) {
    'use strict';

    // CLASS DEFINITION
    // ======================

    var LadbCounters = function(element, options) {
        this.options = options;
        this.$element = $(element);
    };

    LadbCounters.DEFAULTS = {
        countersPath: null
    };
    
    LadbCounters.prototype.updateBadge = function(name, count) {
        if (count > 0) {
            $('.badge-notification.badge-nav-' + name, this.$element).each(function() {
                $(this).html(count);
            });
        }
    };

    LadbCounters.prototype.init = function() {
        var that = this;

        // Nav badge notification counters
        $.ajax(this.options.countersPath, {
            cache: false,
            dataType: "html",
            context: document.body,
            success: function(data, textStatus, jqXHR) {
                var counters = JSON.parse(data);

                that.updateBadge('wonder-creations', counters.unlistedWonderCreationCount);
                that.updateBadge('wonder-plans', counters.unlistedWonderPlanCount);
                that.updateBadge('wonder-workshops', counters.unlistedWonderWorkshopCount);
                that.updateBadge('find-finds', counters.unlistedFindFindCount);
                that.updateBadge('howto-howtos', counters.unlistedHowtoHowtoCount);
                that.updateBadge('knowledge-woods', counters.unlistedKnowledgeWoodCount);
                that.updateBadge('knowledge-providers', counters.unlistedKnowledgeProviderCount);
                that.updateBadge('knowledge-schools', counters.unlistedKnowledgeSchoolCount);
                that.updateBadge('knowledge-books', counters.unlistedKnowledgeBookCount);
                that.updateBadge('blog-posts', counters.unlistedBlogPostCount);
                that.updateBadge('faq-questions', counters.unlistedFaqQuestionCount);
                that.updateBadge('qa-questions', counters.unlistedQaQuestionCount);
                that.updateBadge('promotion-graphics', counters.unlistedPromotionGraphicCount);
                that.updateBadge('workflow-workflows', counters.unlistedWorkflowWorkflowCount);
                that.updateBadge('collection-collections', counters.unlistedCollectionCollectionCount);

                that.updateBadge('catalogs',
                    parseInt(counters.unlistedKnowledgeWoodCount) +
                    parseInt(counters.unlistedKnowledgeBookCount));
                that.updateBadge('directories',
                    parseInt(counters.unlistedKnowledgeProviderCount) +
                    parseInt(counters.unlistedKnowledgeSchoolCount));

                that.updateBadge('all',
                    parseInt(counters.unlistedWonderCreationCount) +
                    parseInt(counters.unlistedWonderPlanCount) +
                    parseInt(counters.unlistedWonderWorkshopCount) +
                    parseInt(counters.unlistedFindFindCount) +
                    parseInt(counters.unlistedHowtoHowtoCount) +
                    parseInt(counters.unlistedKnowledgeWoodCount) +
                    parseInt(counters.unlistedKnowledgeProviderCount) +
                    parseInt(counters.unlistedKnowledgeSchoolCount) +
                    parseInt(counters.unlistedKnowledgeBookCount) +
                    parseInt(counters.unlistedBlogPostCount) +
                    parseInt(counters.unlistedFaqQuestionCount) +
                    parseInt(counters.unlistedQaQuestionCount) +
                    parseInt(counters.unlistedPromotionGraphicCount) +
                    parseInt(counters.unlistedWorkflowWorkflowCount) +
                    parseInt(counters.unlistedCollectionCollectionCount)
                );

            },
        });


    };


    // PLUGIN DEFINITION
    // =======================

    function Plugin(option) {
        return this.each(function () {
            var $this   = $(this);
            var data    = $this.data('ladb.counters');
            var options = $.extend({}, LadbCounters.DEFAULTS, $this.data(), typeof option == 'object' && option);

            if (!data) {
                $this.data('ladb.counters', (data = new LadbCounters(this, options)));
            }
            if (typeof option == 'string') {
                data[option]();
            } else {
                data.init();
            }
        })
    }

    var old = $.fn.ladbCounters;

    $.fn.ladbCounters             = Plugin;
    $.fn.ladbCounters.Constructor = LadbCounters;


    // NO CONFLICT
    // =================

    $.fn.ladbCounters.noConflict = function () {
        $.fn.ladbCounters = old;
        return this;
    }

}(jQuery);