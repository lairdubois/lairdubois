+function ($) {
    'use strict';

    var GRID_SPACING = 10;
    var TASK_WIDGET_PREFIX =  'ladb_workflow_task_widget_';
    var TASK_WIDGET_BOX_WIDTH = 344;

    // CLASS DEFINITION
    // ======================

    var LadbWorkflowPage = function(element, options) {
        this.options = options;
        this.$element = $(element);

        this.plumb = null;

        this.$diagram = this.$element;
        this.$panzoom = $(".ladb-panzoom", this.$diagram);
        this.$canvas = $('.ladb-jtk-canvas', this.$panzoom);

    };

    LadbWorkflowPage.DEFAULTS = {
        readOnly: false,
        minScale: 0.2,
        maxScale: 1,
        incScale: 0.1,
        listTaskPath: null,
        newTaskPath: null,
        createTaskPath: null,
        editTaskPath: null,
        updateTaskPath: null,
        positionUpdateTaskPath: null,
        statusUpdateTaskPath: null,
        createTaskConnectionPath: null,
        deleteTaskConnectionPath: null
    };

    LadbWorkflowPage.prototype._uiDiagramShowAll = function() {
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

    LadbWorkflowPage.prototype._uiDiagramPanToTaskWidget = function(taskId) {
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

    LadbWorkflowPage.prototype._uiDiagramGetCurrentScale = function() {
        if (this.$panzoom) {
            return this.$panzoom.panzoom('getMatrix')[0];
        }
        return 1;
    };

    LadbWorkflowPage.prototype.updateDiagramFromJsonData = function(data) {
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

            for (i = 0; i < response.taskInfos.length; i++) {

                taskInfos = response.taskInfos[i];

                if (that.plumb) {
                    $taskWidget = $(taskInfos.widget);
                    that.$canvas.append($taskWidget);
                    that.initTaskWidget($taskWidget);
                }

            }
        }

        // Created tasks
        if (response.createdTaskInfos) {
            for (i = 0; i < response.createdTaskInfos.length; i++) {

                taskInfos = response.createdTaskInfos[i];

                if (that.plumb) {
                    $taskWidget = $(taskInfos.widget);
                    that.$canvas.append($taskWidget);
                    that.initTaskWidget($taskWidget);
                    $('.ladb-box', $taskWidget).effect('highlight', {}, 1500);
                }

            }
        }

        // Moved tasks
        if (response.movedTaskInfos && that.plumb) {
            for (i = 0; i < response.movedTaskInfos.length; i++) {

                taskInfos = response.movedTaskInfos[i];

                $taskWidget = $('#' + TASK_WIDGET_PREFIX + taskInfos.id, that.$canvas);
                $taskWidget.css('left', taskInfos.positionLeft);
                $taskWidget.css('top', taskInfos.positionTop);

                that.plumb.repaint($taskWidget, { left:taskInfos.positionLeft, top:taskInfos.positionTop });

            }
        }

        // Updated tasks
        if (response.updatedTaskInfos) {
            for (i = 0; i < response.updatedTaskInfos.length; i++) {

                taskInfos = response.updatedTaskInfos[i];

                if (that.plumb) {
                    $taskWidget = $('#' + TASK_WIDGET_PREFIX + taskInfos.id, that.$canvas);
                    $('.ladb-box', $taskWidget).replaceWith(taskInfos.box);
                    that.bindTaskBox(taskInfos.id, $('.ladb-box', $taskWidget));
                }

            }
        }

        // Deleted tasks
        if (response.deletedTaskId) {
            if (that.plumb) {
                $taskWidget = $('#' + TASK_WIDGET_PREFIX + response.deletedTaskId);
                if ($taskWidget.length > 0) {
                    that.plumb.remove(TASK_WIDGET_PREFIX + response.deletedTaskId);
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

        // Created connections
        if (response.createdConnections && that.plumb) {
            _.each(response.createdConnections, function (connection) {
                var sourceId = TASK_WIDGET_PREFIX + connection.from;
                var targetId = TASK_WIDGET_PREFIX + connection.to;
                that.plumb.detach({
                    source: sourceId,
                    target: targetId
                });
                that.plumb.connect({
                    source: sourceId,
                    target: targetId
                });
            });
        }

        // Deleted connections
        if (response.deletedConnections && that.plumb) {
            _.each(response.deletedConnections, function (connection) {
                that.plumb.detach({
                    source: TASK_WIDGET_PREFIX + connection.from,
                    target: TASK_WIDGET_PREFIX + connection.to
                });
            });
        }

        setupTooltips();

    };

    LadbWorkflowPage.prototype.bindTaskBox = function(taskId, $taskBox) {
        if (this.options.readOnly) {
            return;
        }

        var that = this;

        // Bind done and run button
        $('.ladb-status-update-btn', $taskBox).on('click', function (e) {
            e.stopPropagation();

            $(this).button('loading');

            // Sync with server
            $.ajax(that.options.statusUpdateTaskPath, {
                type: "POST",
                cache: false,
                dataType: "html",
                context: document.body,
                data: {
                    taskId: taskId,
                    status: $(this).data('action-status')
                },
                error: function () {
                    console.log('ERROR');
                }
            });

        });

        // Bind edit button
        $('.ladb-btn-edit', $taskBox).on('click', function(e) {
            e.stopPropagation();
            that.loadModalEditTask(taskId);
        });

    };

    LadbWorkflowPage.prototype.initTaskWidget = function($taskWidget) {
        var that = this;

        var taskId = $taskWidget.attr('id').substring(TASK_WIDGET_PREFIX.length);

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

        if (this.options.readOnly) {
            return;
        }

        // Bind box buttons

        this.bindTaskBox(taskId, $('.ladb-box', $taskWidget));

        // Bind ep button

        $('.ladb-ep', $taskWidget).on('click', function(e) {

            var currentScale = that._uiDiagramGetCurrentScale();
            var positionLeft = (e.originalEvent.clientX - that.$canvas.offset().left) / currentScale - TASK_WIDGET_BOX_WIDTH / 2;
            var positionTop = (e.originalEvent.clientY - that.$canvas.offset().top) / currentScale + 100;

            // Drop connection on the board -> new Task
            that.loadModalNewTask(positionLeft, positionTop, taskId);

        });

        // Make widget draggable

        var currentScale = 1;
        $taskWidget.draggable({
            grid: [GRID_SPACING, GRID_SPACING],
            handle: ".ladb-box",
            start: function (e) {
                currentScale = that._uiDiagramGetCurrentScale();
                $(this).draggable( "option", "grid", [ GRID_SPACING * currentScale, GRID_SPACING * currentScale ] );
                $(this).css("cursor", "move");
                that.$panzoom.panzoom("disable");
            },
            drag: function (e, ui) {
                ui.position.left = ui.position.left / currentScale;
                ui.position.top = ui.position.top / currentScale;
                if ($(this).hasClass("jtk-connected")) {
                    that.plumb.repaint($(this).attr('id'), ui.position);
                }
            },
            stop: function (e, ui) {
                var nodeId = $(this).attr('id');
                if ($(this).hasClass("jtk-connected")) {
                    that.plumb.repaint(nodeId, ui.position);
                }
                $(this).css("cursor", "");
                that.$panzoom.panzoom("enable");

                // Round coordinates to grid
                var positionLeft = Math.round(($taskWidget.position().left / currentScale) / GRID_SPACING) * GRID_SPACING ;
                var positionTop = Math.round(($taskWidget.position().top / currentScale) / GRID_SPACING) * GRID_SPACING;

                // Sync with server
                $.ajax(that.options.positionUpdateTaskPath, {
                    type: "POST",
                    cache: false,
                    dataType: "html",
                    context: document.body,
                    data: {
                        taskId: taskId,
                        positionLeft: positionLeft,
                        positionTop: positionTop
                    },
                    error: function () {
                        console.log('ERROR');
                    }
                });

            }
        });

    };

    LadbWorkflowPage.prototype.appendFakeTask = function(positionLeft, positionTop, sourceTaskId) {
        if (!this.plumb) {
            return;
        }

        var $fakeTask = $('<div id="fake_task" class="ladb-workflow-task-widget"><div class="ladb-box ladb-status-0">&nbsp;</div></div>');
        $fakeTask.css('left', positionLeft + 'px');
        $fakeTask.css('top', positionTop + 'px');
        this.$canvas.append($fakeTask);

        if (sourceTaskId) {
            this.plumb.makeTarget($fakeTask, {
                endpoint: "Blank",
                anchor: "Top"
            });
            this.plumb.connect({
                source: TASK_WIDGET_PREFIX + sourceTaskId,
                target: 'fake_task'
            });
        }

    };

    LadbWorkflowPage.prototype.removeFakeTask = function() {
        if (!this.plumb) {
            return;
        }

        var $fakeTask = $('#fake_task');
        if ($fakeTask.length > 0) {
            this.plumb.remove('fake_task');
        }

    };

    LadbWorkflowPage.prototype.loadTasks = function() {
        var that = this;

        // Loading
        this._uiMarkLoading('Chargement des tâches...');

        $.ajax(that.options.listTaskPath, {
            cache: false,
            dataType: "html",
            context: document.body,
            success: function(data, textStatus, jqXHR) {

                // Hide loading
                that._uiUnmarkLoading();

                // Update board
                that.updateDiagramFromJsonData(data);

                // Center origin
                that._uiDiagramShowAll();

            },
            error: function (jqXHR, textStatus, errorThrown) {
                console.log('loadTasks failed', textStatus);
            }
        });

    };

    LadbWorkflowPage.prototype.bind = function() {
        var that = this;

        // Bind loading mask
        this.$loadingPanel.on('mousedown', function(e) {
            e.stopImmediatePropagation();
        });

        if (this.options.readOnly) {
            return;
        }

    };

    LadbWorkflowPage.prototype.init = function() {
        var that = this;

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

                if (!that.options.readOnly) {

                    // Bind plumb
                    that.plumb.bind('connection', function (info, originalEvent) {

                        if (!originalEvent || info.targetId == 'fake_task') {
                            return;
                        }

                        // Sync with server
                        $.ajax(that.options.createTaskConnectionPath, {
                            type: 'POST',
                            cache: false,
                            dataType: 'html',
                            context: document.body,
                            data: {
                                sourceTaskId: info.sourceId.substring(TASK_WIDGET_PREFIX.length),
                                targetTaskId: info.targetId.substring(TASK_WIDGET_PREFIX.length)
                            },
                            error: function () {
                                console.log('ERROR');
                            }
                        });

                    });
                    that.plumb.bind('connectionAborted', function (connection, originalEvent) {

                        var sourceTaskId = connection.sourceId.substring(TASK_WIDGET_PREFIX.length);

                        var currentScale = that._uiDiagramGetCurrentScale();
                        var positionLeft = (originalEvent.clientX - that.$canvas.offset().left) / currentScale - TASK_WIDGET_BOX_WIDTH / 2;
                        var positionTop = (originalEvent.clientY - that.$canvas.offset().top) / currentScale;

                        // Drop connection on the board -> new Task
                        that.loadModalNewTask(positionLeft, positionTop, sourceTaskId);

                    });
                    that.plumb.bind('click', function (connection, originalEvent) {
                        that.plumb.detach(connection);

                        // Sync with server
                        $.ajax(that.options.deleteTaskConnectionPath, {
                            type: 'POST',
                            cache: false,
                            dataType: 'html',
                            context: document.body,
                            data: {
                                sourceTaskId: connection.sourceId.substring(TASK_WIDGET_PREFIX.length),
                                targetTaskId: connection.targetId.substring(TASK_WIDGET_PREFIX.length)
                            },
                            error: function () {
                                console.log('ERROR');
                            }
                        });

                    });

                }

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

                if (!that.options.readOnly) {

                    that.$panzoom.parent()
                        .on('dblclick', function(e) {

                            var currentScale = that._uiDiagramGetCurrentScale();
                            var positionLeft = (e.originalEvent.clientX - that.$canvas.offset().left) / currentScale - TASK_WIDGET_BOX_WIDTH / 2;
                            var positionTop = (e.originalEvent.clientY - that.$canvas.offset().top) / currentScale;

                            that.loadModalNewTask(positionLeft, positionTop);
                        });

                }

            });

        }

        that.bind();
    };


    // PLUGIN DEFINITION
    // =======================

    function Plugin(option) {
        return this.each(function () {
            var $this   = $(this);
            var data    = $this.data('ladb.workflowpage');
            var options = $.extend({}, LadbWorkflowPage.DEFAULTS, $this.data(), typeof option == 'object' && option);

            if (!data) {
                $this.data('ladb.workflowpage', (data = new LadbWorkflowPage(this, options)));
            }
            if (typeof option == 'string') {
                data[option]();
            } else {
                data.init();
            }
        })
    }

    var old = $.fn.ladbWorkflowPage;

    $.fn.ladbWorkflowPage             = Plugin;
    $.fn.ladbWorkflowPage.Constructor = LadbWorkflowPage;


    // NO CONFLICT
    // =================

    $.fn.ladbWorkflowPage.noConflict = function () {
        $.fn.ladbWorkflowPage = old;
        return this;
    }

}(jQuery);