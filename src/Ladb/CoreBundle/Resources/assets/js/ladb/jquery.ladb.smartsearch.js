+function ($) {
    'use strict';

    // CLASS DEFINITION
    // ======================

    var LadbSmartSearch = function(element, options) {
        this.options = options;
        this.$element = $(element);

        this.$widget = this.$element;
        this.$box = $('.ladb-search-box', this.$element);
        this.$boxBottom = $('.ladb-search-box-bottom', this.$box);
        this.$boxBottomLeft = $('.ladb-search-box-bottom-left', this.$boxBottom);
        this.$boxBottomRight = $('.ladb-search-box-bottom-right', this.$boxBottom);
        this.$searchIcon = $('.ladb-search-box-top-left > i', this.$box);
        this.$textInput = $('.ladb-input > input', this.$box);
        this.$filtersBtn = $('.ladb-filters-btn', this.$box);
        this.$shortcuts = $('.ladb-search-shortcuts', this.$element);

        this.facetDefs = {};
        this.isFirstSearch = true;
        this.hasMap = this.options.mapSearchPath !== null;

    };

    LadbSmartSearch.DEFAULTS = {
        query: '',
        containerSelector: '#ladb_autopager_container',
        searchPath: '',
        noAjax: false,
        loadingHtml : '<span>Loading...</span>',  // '{{ include('LadbCoreBundle:Common:_loading.part.html.twig', { 'float':true, 'noHidden':true }) }}',
        masonry: false,
        masonryWide : false,
        mapSearchPath: null,
        mapAreaId: 'ladb_map_area'
    };

    LadbSmartSearch.prototype.parseQuery = function(query) {
        this.clear();
        if (query.length > 0) {

            var re = /(?:@([^\s]+):(?:\"([^\"]+)\"|([^\s]+))|@([^\s]+)|\"([^\"]+)\"|([^\s]+))/g;
            var m;

            var text = '';
            while ((m = re.exec(query)) !== null) {
                if (m.index === re.lastIndex) {
                    re.lastIndex++;
                }

                var name = null;
                var value = null;
                if (m[1] !== undefined) {
                    name = m[1];
                    if (m[2] !== undefined) {
                        value = m[2];
                    } else if (m[3] !== undefined) {
                        value = m[3];
                    }
                } else if (m[4] !== undefined) {
                    name = m[4];
                } else {
                    if (m[5] !== undefined) {
                        value = m[5];
                    } else if (m[6] !== undefined) {
                        value = m[6];
                    }
                }

                if (name) {
                    var facetDef = this.facetDefs[this.generateFacetDefId(name, null)];
                    if (!facetDef) {
                        facetDef = this.facetDefs[this.generateFacetDefId(name, value)];
                    }
                    if (facetDef) {
                        if (facetDef.unique) {
                            this.removeFacetByGroup(facetDef.group);
                        }
                        if (facetDef.needValue && !value) {
                            continue;
                        }
                        var facet = this.createFacet(facetDef);
                        if (facetDef.editable && value) {
                            facet.find('input').val(value);
                        }
                        if (facetDef.geolocation && value) {
                            facet.data('value', value);
                        }
                        this.appendFacet(facet);
                    }
                } else {
                    text += ' ' + value;
                }

            }
            this.$textInput.val(text.trim());
        }
        this.mapLoad(query);
    };

    LadbSmartSearch.prototype.createFacet = function(facetDef) {
        var that = this;
        if (facetDef.editable) {
            var $input = $('<input/>', {
                type: 'text'
            }).on('keyup', function(event) {
                if (event.keyCode === 13 && $(event.target).val().length > 0) {
                    that.search();
                    $(event.target).blur();
                }
            });
            if (facetDef.proposalsUrl) {
                $input.autocomplete({
                    serviceUrl: facetDef.proposalsUrl,
                    paramName: "q",
                    minChars: 2,
                    triggerSelectOnValidInput: false,
                    autoSelectFirst: true,
                    width: 180,
                    onSelect: function(suggestion) {
                        $input.blur();
                        that.search();
                    }
                });
            } else if (facetDef.proposals) {
                var proposalsArray = facetDef.proposals.split(',');
                var lookup = [];
                for (var i = 0; i < proposalsArray.length; i++) {
                    lookup.push({ value:proposalsArray[i] });
                }
                $input.autocomplete({
                    lookup: lookup,
                    minChars: 0,
                    width: 180,
                    onSelect: function(suggestion) {
                        $input.blur();
                        that.search();
                    }
                });
            }
        }
        if (facetDef.random) {
            var $repeat = $('<a/>', {
                'class': 'ladb-repeat'
            })
                .append('<i class="ladb-icon-repeat"></i>')
                .on('click', function() {
                    that.randomizeFacetValue($(this).parent());
                    that.search();
                })
            ;
        }
        return $('<span/>', {
            'class': 'ladb-facet ladb-' + facetDef.type
        })
            .data('name', facetDef.name)
            .data('group', facetDef.group)
            .data('value', facetDef.value)
            .data('facetDef', facetDef)
            .append($('<a/>', {
                'class': 'ladb-remove'
            })
                .append('<i class="ladb-icon-remove"></i>')
                .on('click', function() {
                    that.removeFacet($(this).parent());
                    if (that.countFacets() === 0) {
                        that.$shortcuts.show(); // No more facet => display shortcuts
                    }
                    that.search();
                }))
            .prepend($repeat)
            .prepend($input)
            .prepend('<i class="ladb-icon-' + facetDef.icon + '"></i> <span class="ladb-facet-name">' + facetDef.label + (facetDef.editable ? ' : ' : '') + '</span>')
    };

    LadbSmartSearch.prototype.appendFacet = function($facet) {
        this.$boxBottomLeft
            .append($facet);
        this.$boxBottom
            .show();
    };

    LadbSmartSearch.prototype.removeFacet = function($facet) {
        $facet.remove();
    };

    LadbSmartSearch.prototype.removeFacetByGroup = function(group) {
        var that = this;
        this.$boxBottomLeft.find('.ladb-facet').each(function(i, v) {
            var $facet = $(v);
            if ($facet.data('group') === group) {
                that.removeFacet($facet);
            }
        });
    };

    LadbSmartSearch.prototype.randomizeFacetValue = function($facet) {
        $facet.data('value', Math.random().toString(36).substring(2, 15));
    };

    LadbSmartSearch.prototype.countFacets = function() {
        return $('.ladb-facet', this.$boxBottomLeft).length;
    };

    LadbSmartSearch.prototype.generateQuery = function() {
        var that = this;
        var query = this.$textInput.val();
        this.$boxBottomLeft.find('.ladb-facet').each(function(i, v) {
            var $facet = $(v);
            var facetQuery = ' @' + $facet.data('name');
            var $input = $facet.find('input');
            var value;
            if ($input.length > 0) {
                value = $($input).val();
                if (!value) {
                    that.removeFacet($facet);
                    return;
                }
                facetQuery += ':"' + value + '"';
            } else {
                value = $facet.data('value');
                if (value) {
                    facetQuery += ':' + value;
                } else {
                    var facetDef = $facet.data('facetDef');
                    if (facetDef.needValue) {
                        that.removeFacet($facet);
                        return;     // No value ignor this facet
                    }
                }
            }
            query += facetQuery;
        });
        return query.trim();
    };

    LadbSmartSearch.prototype.generateFacetDefId = function(name, value) {
        return value ? name + ':' + value : name;
    };

    LadbSmartSearch.prototype.markLoading = function() {
        this.$searchIcon.addClass('ladb-icon-spinner');
        $(this.options.containerSelector).addClass('ladb-translucent');
    };

    LadbSmartSearch.prototype.unmarkLoading = function() {
        this.$searchIcon.removeClass('ladb-icon-spinner');
        $(this.options.containerSelector).removeClass('ladb-translucent');
    };

    LadbSmartSearch.prototype.search = function(noPushState) {
        var that = this;
        var query = this.generateQuery();
        var url = this.options.searchPath + (query.length > 0 ? ((this.options.searchPath.indexOf('?') === -1 ? '?' : '&') + 'q=' + query) : '');
        this.markLoading();
        if (this.options.noAjax) {
            window.location.href = url;
        } else {
            $.ajax(url, {
                cache: false,
                dataType: "html",
                context: document.body,
                success: function(data, textStatus, jqXHR) {
                    var options = that.options;
                    var $container = $(options.containerSelector);

                    $container.jscroll.destroy();
                    $container
                        .empty()
                        .append(data);

                    that.grabTotalHits();

                    $container.ladbAutopager({
                        loadingHtml: options.loadingHtml,
                        masonry: options.masonry,
                        masonryWide: options.masonryWide
                    });

                    setupTooltips();

                    if (!noPushState) {

                        history.pushState({query: query}, null, url);

                        if (that.isFirstSearch) {
                            $(window)
                                .on("popstate", function(event) {
                                    that.parseQuery(event.originalEvent.state ? event.originalEvent.state.query : options.query);
                                    that.search(true);
                                });
                        }

                    }

                    that.unmarkLoading();

                    that.isFirstSearch = false;

                    $(window).scrollTop(0);

                    that.mapLoad(query);

                },
                error: function () {
                    console.log('Search Error');
                    $(that.options.containerSelector).empty();
                    that.unmarkLoading();
                }
            });
        }
    };

    LadbSmartSearch.prototype.grabTotalHits = function() {
        this.$boxBottomRight.empty();
        var $totalHits = $('#ladb_total_hits', this.options.containerSelector);
        if ($totalHits.length) {
            this.$boxBottomRight.append($totalHits);
            $totalHits.show();
            this.$boxBottom.show();
        } else {
            this.$boxBottom.hide();
        }
    };

    LadbSmartSearch.prototype.mapLoad = function(query) {
        if (!this.hasMap) {
            return;
        }
        $('#' + this.options.mapAreaId).ladbMapArea('load', this.options.mapSearchPath + '?q=@geocoded ' + query);
    };

    LadbSmartSearch.prototype.bind = function() {
        var that = this;

        // bind text input key
        this.$textInput.on('keyup', function(event) {
            if (event.keyCode === 13) {
                that.search();
                $(event.target).blur();
            }
        });

        // bind clear button
        this.$box.find('.ladb-btn-clear').on('click', function() {
            if (that.$textInput.val()) {
                that.$textInput.val('');
                that.search();
            }
        });

        // bind facetItems
        this.$box.find('.ladb-smartsearch-facet').each(function (i, v) {

            var $facetItem = $(v);

            var type = $facetItem.data('type');
            var name = $facetItem.data('name');
            var value = $facetItem.data('value');
            var label = $facetItem.data('label');
            var editable = $facetItem.data('editable');
            var unique = $facetItem.data('unique');
            var icon = $facetItem.data('icon');
            var proposals = $facetItem.data('proposals');
            var proposalsUrl = $facetItem.data('proposals-url');
            var geolocation = $facetItem.data('geolocation');
            var random = $facetItem.data('random');

            var facetDef = {
                type: type,
                // type: name == 'sort' ? 'sorter' : 'filter',
                group: type === 'sorter' ? 'sort' : name,
                name: name,
                value: value,
                label: label,
                editable: editable,
                unique: unique,
                icon: icon,
                proposals: proposals,
                proposalsUrl: proposalsUrl,
                geolocation: geolocation,
                random: random,
                needValue: geolocation || editable
            };
            that.facetDefs[that.generateFacetDefId(name, null/*value*/)] = facetDef;

            $facetItem.on('click', function() {

                that.$shortcuts.hide();

                var $facet = that.createFacet(facetDef);

                if (unique) {
                    that.removeFacetByGroup(facetDef.group);
                }
                that.appendFacet($facet);
                if (editable) {
                    $facet.find('input').focus();
                } else if (geolocation) {
                    $facet.addClass('ladb-pending');
                    var $name = $('.ladb-facet-name', $facet);
                    $name.text('Géolocalisation...');
                    $facet.data('value', null);
                    if (navigator.geolocation) {
                        navigator.geolocation.getCurrentPosition(
                            function (position) {
                                $facet
                                    .data('value', position.coords.latitude + ',' + position.coords.longitude)
                                    .removeClass('ladb-pending');
                                $name.text(facetDef.label);
                                that.search();
                            },
                            function (error) {
                                $facet
                                    .removeClass('ladb-pending')
                                    .addClass('ladb-disabled');
                                $name.text(facetDef.label);
                                switch (error.code) {
                                    case error.PERMISSION_DENIED:
                                        UIkit.notify("Veuillez autoriser la géolocalisation sur votre navigateur.");
                                        break;
                                    case error.POSITION_UNAVAILABLE:
                                    case error.TIMEOUT:
                                    case error.UNKNOWN_ERROR:
                                        UIkit.notify("Impossible de vous localiser.");
                                        break;
                                }
                            });
                    } else {
                        UIkit.notify("La géolocalisation n'est pas supportée sur votre navigateur.");
                        $facet
                            .removeClass('ladb-pending')
                            .addClass('ladb-disabled');
                        $name.text(facetDef.label);
                    }
                } else if (random) {
                    that.randomizeFacetValue($facet);
                    that.search();
                } else {
                    that.search();
                }

            });

        });

        // bind shortcutItems
        this.$shortcuts.find('.ladb-smartsearch-shortcut').each(function (i, v) {

            var $shortcutItem = $(v);

            var query = $shortcutItem.data('query');
            if (query) {
                $shortcutItem.on('click', function() {
                    that.parseQuery(query);
                    that.search();
                });
            }

        });
        this.$shortcuts.find('.ladb-smartsearch-shortcut-more').on('click', function(event) {
            event.stopPropagation();
            that.$filtersBtn.click();
        });

        // Grab totalHits
        this.grabTotalHits();

        // Init map
        if (this.hasMap) {
            $('#' + this.options.mapAreaId).ladbMapArea({
                onToggleFullscreen: function(fullscreen) {
                    if (fullscreen) {
                        that.$box.addClass('ladb-map-overlay');
                    } else {
                        that.$box.removeClass('ladb-map-overlay');
                        $(window).scrollTop(0);
                    }
                }
            });
        }

    };

    LadbSmartSearch.prototype.clear = function() {
        this.$boxBottomLeft.empty();
        this.$textInput.val('');
    };

    LadbSmartSearch.prototype.init = function() {
        this.bind();
        this.parseQuery(this.options.query);
        if (this.countFacets() === 0) {
            this.$shortcuts.show();
        }
    };


    // PLUGIN DEFINITION
    // =======================

    function Plugin(option) {
        return this.each(function () {
            var $this   = $(this);
            var data    = $this.data('ladb.smartsearch');
            var options = $.extend({}, LadbSmartSearch.DEFAULTS, $this.data(), typeof option === 'object' && option);

            if (!data) {
                $this.data('ladb.smartsearch', (data = new LadbSmartSearch(this, options)));
            }
            if (typeof option === 'string') {
                data[option]();
            } else {
                data.init();
            }
        })
    }

    var old = $.fn.ladbSmartSearch;

    $.fn.ladbSmartSearch             = Plugin;
    $.fn.ladbSmartSearch.Constructor = LadbSmartSearch;


    // NO CONFLICT
    // =================

    $.fn.ladbSmartSearch.noConflict = function () {
        $.fn.ladbSmartSearch = old;
        return this;
    }

}(jQuery);