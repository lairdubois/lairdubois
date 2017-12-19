+function ($) {
    'use strict';

    var GRID_SPACING = 10;
    var TASK_WIDGET_PREFIX =  'ladb_workflow_task_widget_';
    var TASK_WIDGET_BOX_WIDTH = 344;

    // CLASS DEFINITION
    // ======================

    var LadbWorkflowDiagram = function(element, options) {
        this.options = options;
        this.$element = $(element);

        this.plumb = null;

        this.$diagram = this.$element;
        this.$panzoom = $(".ladb-panzoom", this.$diagram);
        this.$canvas = $('.ladb-jtk-canvas', this.$panzoom);

    };

    LadbWorkflowDiagram.DEFAULTS = {
        listTaskPath: null,
        minScale: 0.2,
        maxScale: 1,
        incScale: 0.1
    };

    LadbWorkflowDiagram.prototype._uiDiagramShowAll = function() {
        if (this.$panzoom) {

            var areaPadding = 30;

            var contentRect = null;
            $('.ladb-workflow-task-widget', this.$canvas).each(function (index, value) {
                var clientRect = value.getBoundingClientRect();
                if (contentRect) {
                    contentRect.top = Math.min(contentRect.top, clientRect.top);
                    contentRect.right = Math.max(contentRect.right, clientRect.right);
                    contentRect.bottom = Math.max(contentRect.bottom, clientRect.bottom);
                    contentRect.left = Math.min(contentRect.left, clientRect.left);
                } else {
                    contentRect = {
                        top: clientRect.top,
                        right: clientRect.right,
                        bottom: clientRect.bottom,
                        left: clientRect.left
                    };
                }
            });

            if (contentRect) {

                contentRect.width = contentRect.right - contentRect.left;
                contentRect.height = contentRect.bottom - contentRect.top;

                var areaRect = this.$diagram.get(0).getBoundingClientRect();

                var panX = areaPadding + areaRect.left - contentRect.left + (areaRect.width - 2 * areaPadding - contentRect.width) / 2;
                var panY = areaPadding + areaRect.top - contentRect.top;
                this.$panzoom.panzoom('pan', panX, panY, { relative: false });

                var scale = Math.min((areaRect.height - 2 * areaPadding) / contentRect.height, (areaRect.width - 2 * areaPadding) / contentRect.width);
                this.$panzoom.panzoom('zoom', scale, {
                    focal: {
                        clientX: areaRect.left + areaRect.width / 2,
                        clientY: areaRect.top + areaPadding
                    }
                });
                this.plumb.setZoom(this._uiDiagramGetCurrentScale());

            }
        }
    };

    LadbWorkflowDiagram.prototype._uiDiagramPanToTaskWidget = function(taskId) {
        if (this.$panzoom) {

            var $taskWidget = $('#' + TASK_WIDGET_PREFIX + taskId);

            // Retrieve widget position and area rect
            var position = $taskWidget.position();
            var areaRect = this.$diagram.get(0).getBoundingClientRect();
            var currentScale = this._uiDiagramGetCurrentScale();

            var panX = (areaRect.width - $taskWidget.outerWidth()) / 2 - position.left / currentScale;
            var panY = (areaRect.height - $taskWidget.outerHeight()) / 2 - position.top / currentScale;
            this.$panzoom.panzoom('pan', panX, panY, {
                relative: false,
                animate: true
            });
            $('.ladb-box', $taskWidget).effect('highlight', {}, 1000);

            this.$panzoom.panzoom('resetZoom', { animate: true });
            this.plumb.setZoom(1);

        }
    };

    LadbWorkflowDiagram.prototype._uiDiagramGetCurrentScale = function() {
        if (this.$panzoom) {
            return this.$panzoom.panzoom('getMatrix')[0];
        }
        return 1;
    };

    LadbWorkflowDiagram.prototype.initTaskWidget = function($taskWidget) {
        var that = this;

        // Setup as plumb source and target

        this.plumb.makeSource($taskWidget, {
            filter: ".ladb-ep",
            endpoint: "Blank",
            anchor: "Bottom",
            connectorStyle: {
                stroke: "#5c96bc",
                strokeWidth: 2,
                outlineStroke: "transparent",
                outlineWidth: 6
            }
        });

        this.plumb.makeTarget($taskWidget, {
            dropOptions: {
                hoverClass: "jtk-drag-hover"
            },
            endpoint: "Blank",
            anchor: "Top",
            allowLoopback: false
        });

    };

    LadbWorkflowDiagram.prototype.updateBoardFromJsonData = function(data) {
        var that = this;

        var response = JSON.parse(data);

        var i, taskInfos, $taskWidget, $taskRow;

        // Tasks
        if (response.taskInfos) {

            if (that.plumb) {
                that.plumb.deleteEveryEndpoint();
                that.plumb.setZoom(1);
                that.$canvas.empty();
                that.$panzoom.panzoom('zoom', 1);
                that.$panzoom.panzoom('pan', 0, 0, { relative: false });
            }
            for (i = 0; i <= 4; i++) {
                $('#panel_body_status_' + i + ' .ladb-workflow-task-row').remove();
            }

            for (i = 0; i < response.taskInfos.length; i++) {

                taskInfos = response.taskInfos[i];

                if (that.plumb) {
                    $taskWidget = $(taskInfos.widget);
                    that.$canvas.append($taskWidget);
                    that.initTaskWidget($taskWidget);
                }

            }
        }

        // Connections
        if (response.connections && that.plumb) {
            _.each(response.connections, function (connection) {
                that.plumb.connect({
                    source: TASK_WIDGET_PREFIX + connection.from,
                    target: TASK_WIDGET_PREFIX + connection.to
                });
            });
        }

    };

    LadbWorkflowDiagram.prototype.initTaskWidget = function($taskWidget) {

        // Setup as plumb source and target

        this.plumb.makeSource($taskWidget, {
            filter: ".ladb-ep",
            endpoint: "Blank",
            anchor: "Bottom",
            connectorStyle: {
                stroke: "#5c96bc",
                strokeWidth: 2,
                outlineStroke: "transparent",
                outlineWidth: 6
            }
        });

        this.plumb.makeTarget($taskWidget, {
            dropOptions: {
                hoverClass: "jtk-drag-hover"
            },
            endpoint: "Blank",
            anchor: "Top",
            allowLoopback: false
        });

    };

    LadbWorkflowDiagram.prototype.loadTasks = function(readOnly) {
        var that = this;

        $.ajax(that.options.listTaskPath + (readOnly ? '?readOnly=1' : ''), {
            cache: false,
            dataType: "html",
            context: document.body,
            success: function(data, textStatus, jqXHR) {

                // Update board
                that.updateBoardFromJsonData(data);

                // Center origin
                that._uiDiagramShowAll();

            },
            error: function (jqXHR, textStatus, errorThrown) {
                console.log('loadTasks failed', textStatus);
            }
        });

    };

    LadbWorkflowDiagram.prototype.init = function() {
        var that = this;

        // Load tasks
        this.loadTasks(true);

        if (this.$canvas.is(':visible')) {

            jsPlumb.ready(function () {

                // Init jsPlumb
                that.plumb = jsPlumb.getInstance({
                    HoverPaintStyle: { stroke: "#f77f00", strokeWidth: 4 },
                    Connector: ["Bezier", { curviness: 50 }],
                    ConnectionOverlays: [
                        ["Arrow", {
                            location: 1,
                            id: "arrow",
                            width: 10,
                            length: 10,
                            foldback: 0.9
                        }]
                    ],
                    Container: that.$canvas
                });

                // Setup panzoom
                that.$panzoom.panzoom({
                    minScale: that.options.minScale,
                    maxScale: that.options.maxScale,
                    increment: that.options.incScale,
                    cursor: "",
                    ignoreChildrensEvents: true
                }).on("panzoomstart", function (e, panzoom, event, touches) {
                    that.$panzoom.css("cursor", "move");
                }).on("panzoomend", function (e, panzoom, matrix, changed) {
                    that.$panzoom.css("cursor", "");
                });
                that.$panzoom.parent()
                    .on('mousewheel.focal', function (e) {
                        e.preventDefault();
                        var delta = e.delta || e.originalEvent.wheelDelta;
                        var zoomOut = delta ? delta < 0 : e.originalEvent.deltaY > 0;
                        that.$panzoom.panzoom('zoom', zoomOut, {
                            animate: false,
                            focal: e
                        });
                        that.plumb.setZoom(that._uiDiagramGetCurrentScale());
                    })
                    .on("mousedown", function (e) {
                        if (e.button > 0) {
                            return;
                        }
                        var matrix = that.$panzoom.panzoom("getMatrix");
                        var offsetX = matrix[4];
                        var offsetY = matrix[5];
                        var dragstart = {x: e.pageX, y: e.pageY, dx: offsetX, dy: offsetY};
                        $(e.target).css("cursor", "move");
                        $(this).data('dragstart', dragstart);
                    })
                    .on("touchstart", function (e) {
                        var matrix = that.$panzoom.panzoom("getMatrix");
                        var offsetX = matrix[4];
                        var offsetY = matrix[5];
                        var dragstart = {x: e.originalEvent.touches[0].pageX, y: e.originalEvent.touches[0].pageY, dx: offsetX, dy: offsetY};
                        $(this).data('dragstart', dragstart);
                    })
                    .on("mousemove", function (e) {
                        var dragstart = $(this).data('dragstart');
                        if (dragstart) {
                            var deltaX = dragstart.x - e.pageX;
                            var deltaY = dragstart.y - e.pageY;
                            var matrix = that.$panzoom.panzoom("getMatrix");
                            matrix[4] = parseInt(dragstart.dx) - deltaX;
                            matrix[5] = parseInt(dragstart.dy) - deltaY;
                            that.$panzoom.panzoom("setMatrix", matrix);
                        }
                    })
                    .on("touchmove", function (e) {
                        var dragstart = $(this).data('dragstart');
                        if (dragstart) {
                            var deltaX = dragstart.x - e.originalEvent.touches[0].pageX;
                            var deltaY = dragstart.y - e.originalEvent.touches[0].pageY;
                            var matrix = that.$panzoom.panzoom("getMatrix");
                            matrix[4] = parseInt(dragstart.dx) - deltaX;
                            matrix[5] = parseInt(dragstart.dy) - deltaY;
                            that.$panzoom.panzoom("setMatrix", matrix);
                            e.preventDefault();
                        }
                    })
                    .on("mouseup touchend touchcancel mouseout", function (e) {
                        $(this).data('dragstart', null);
                        $(e.target).css("cursor", "");
                    });

            });

        }

    };


    // PLUGIN DEFINITION
    // =======================

    function Plugin(option) {
        return this.each(function () {
            var $this   = $(this);
            var data    = $this.data('ladb.workflowdiagram');
            var options = $.extend({}, LadbWorkflowDiagram.DEFAULTS, $this.data(), typeof option == 'object' && option);

            if (!data) {
                $this.data('ladb.workflowdiagram', (data = new LadbWorkflowDiagram(this, options)));
            }
            if (typeof option == 'string') {
                data[option]();
            } else {
                data.init();
            }
        })
    }

    var old = $.fn.ladbWorkflowDiagram;

    $.fn.ladbWorkflowDiagram             = Plugin;
    $.fn.ladbWorkflowDiagram.Constructor = LadbWorkflowDiagram;


    // NO CONFLICT
    // =================

    $.fn.ladbWorkflowDiagram.noConflict = function () {
        $.fn.ladbWorkflowDiagram = old;
        return this;
    }

}(jQuery);