+function ($) {
    'use strict';

    // CLASS DEFINITION
    // ======================

    var LadbMapArea = function(element, options) {
        this.options = options;
        this.$element = $(element);
        this.map = null;
        this.bounds = null;
        this.clusterGroup = null;
    };

    LadbMapArea.DEFAULTS = {
        mapId: 'ladb_map_area_map',
        onToggleFullscreen: null,
        markersUrl: null
    };

    LadbMapArea.prototype.toggleFullScreen = function() {
        var that = this;
        var $mapAreaOverlay = $('.ladb-map-area-overlay', this.$element);
        var $mapAreaBtnZoom = $('.ladb-map-area-btn-zoom', this.$element);
        var fullscreen = !this.$element.data('ladb-fullscreen');
        if (fullscreen) {
            this.$element
                .addClass('ladb-fullscreen')
                .data('ladb-fullscreen', true);
            $mapAreaOverlay.hide();
            $mapAreaBtnZoom.find('i').removeClass('ladb-icon-zoomin').addClass('ladb-icon-zoomout');
        } else {
            this.$element
                .removeClass('ladb-fullscreen')
                .data('ladb-fullscreen', false);
            $mapAreaOverlay.show();
            $mapAreaBtnZoom.find('i').removeClass('ladb-icon-zoomout').addClass('ladb-icon-zoomin');
        }
        this.map.closePopup();
        this.map.invalidateSize({
            animate: false
        });
        this.fitBounds();
        $('.ladb-topbar').ladbTopbarTranslucent(fullscreen ? 'desactivate' : 'activate');
        if (this.options.onToggleFullscreen) {
            this.options.onToggleFullscreen(fullscreen);
        }
    };

    LadbMapArea.prototype.fitBounds = function(bounds) {
        if (bounds) {
            this.bounds = bounds;
        }
        if (this.map && this.bounds && this.bounds.isValid()) {
            this.map.fitBounds(this.bounds, {
                paddingTopLeft: [50, 10],
                paddingBottomRight: [10, 10]
            });
        }
    };

    LadbMapArea.prototype.clearMarkers = function() {
        if (this.map && this.clusterGroup && this.map.hasLayer(this.clusterGroup)) {
            this.map.removeLayer(this.clusterGroup);
            this.clusterGroup = null;
        }
    };

    LadbMapArea.prototype.load = function(url) {
        var that = this;

        // Clear previous markers
        this.clearMarkers();

        $.ajax({
            type: "GET",
            url: url,
            dataType: 'json',
            success: function(response) {
                var geojsonLayer = L.geoJson(response);

                // Fit new map bounds
                var bounds = geojsonLayer.getBounds();
                that.fitBounds(bounds);

                // Create new markers
                that.clusterGroup = new L.MarkerClusterGroup({
                    maxClusterRadius: 20,
                    iconCreateFunction: function(cluster) {
                        return L.divIcon({
                            html: '<span>' + cluster.getChildCount() + '</span>',
                            className: 'marker-cluster',
                            iconSize: L.point(35, 90)
                        });
                    }
                });
                var classes = {
                    0: 'default',
                    1: 'asso',
                    2: 'pro',
                    3: 'default'
                };
                geojsonLayer.eachLayer(function(layer) {
                    layer.setIcon(L.divIcon({
                        className: 'ladb-marker-' + classes[layer.feature.properties.type],
                        iconSize: L.point(30, 70),
                        popupAnchor: [0, -35]
                    }));
                    that.clusterGroup.addLayer(layer);
                    if (layer.feature.properties.cardUrl) {
                        layer.bindPopup('<i class="ladb-icon-spinner"></i>', {
                            closeButton: false
                        });
                        layer.on('click', function (e) {
                            var popup = e.target.getPopup();
                            if (popup.getContent().startsWith('<i')) {
                                $.ajax(layer.feature.properties.cardUrl, {
                                    cache: false,
                                    dataType: 'html',
                                    context: document.body,
                                    success: function (data, textStatus, jqXHR) {
                                        popup.setContent(data);
                                        popup.update();
                                    },
                                    error: function () {
                                        popup.setContent('!!!');
                                        popup.update();
                                    }
                                });
                            }
                        });
                    }
                });
                that.map.addLayer(that.clusterGroup);

                // Define marker click event
                that.clusterGroup.on('click', function(e) {
                    that.map.panTo(e.layer.getLatLng());
                });

            }
        });

    };

    LadbMapArea.prototype.init = function() {
        var that = this;

        $('.ladb-map-area-overlay', this.$element)
            .on('click', function() {
                that.toggleFullScreen();
            });

        $('.ladb-map-area-btn-zoom', this.$element)
            .on('click', function() {
                that.toggleFullScreen();
            });

        // Setup leaflet
        this.map = L.map(this.options.mapId, {
            zoomControl: false,
            maxBounds: L.latLngBounds(L.latLng(84.86, -169.45), L.latLng(-84.47, 200.39)),
            center: L.latLng([46.495, 2.201]),
            minZoom: 2
        });

        // Use basemaps on CartoDB
        L.tileLayer('https://cartodb-basemaps-{s}.global.ssl.fastly.net/light_all/{z}/{x}/{y}.png', {
            subdomains: ['a', 'b', 'c', 'd'],
            attribution: '&copy; <a href="http://osm.org/copyright" taget="_blank">OpenStreetMap</a>, &copy; <a href="http://cartodb.com/attributions" taget="_blank">CartoDB</a>'
        }).addTo(this.map);

        // Load markers
        if (this.options.markersUrl) {
            this.load(this.options.markersUrl);
        }

        return this.map;
    };


    // PLUGIN DEFINITION
    // =======================

    function Plugin(option, _parameter) {
        return this.each(function () {
            var $this   = $(this);
            var data    = $this.data('ladb.maparea');
            var options = $.extend({}, LadbMapArea.DEFAULTS, $this.data(), typeof option == 'object' && option);

            if (!data) {
                $this.data('ladb.maparea', (data = new LadbMapArea(this, options)));
            }
            if (typeof option == 'string') {
                data[option](_parameter);
            } else {
                data.init();
            }
        })
    }

    var old = $.fn.ladbMapArea;

    $.fn.ladbMapArea             = Plugin;
    $.fn.ladbMapArea.Constructor = LadbMapArea;


    // NO CONFLICT
    // =================

    $.fn.ladbMapArea.noConflict = function () {
        $.fn.ladbMapArea = old;
        return this;
    }

}(jQuery);