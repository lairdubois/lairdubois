+function ($) {
    'use strict';

    // CLASS DEFINITION
    // ======================

    var LadbWorkflowBoard = function(element, options) {
        this.options = options;
        this.$element = $(element);

        this.plumb = null;

        this.$diagram = $('#ladb_workflow_task_diagram', this.$element);
        this.$panzoom = $(".ladb-panzoom", this.$diagram);
        this.$canvas = $('.ladb-jtk-canvas', this.$panzoom);

        this.$btnAddTask = $('#ladb_btn_add_task', this.$element);
        this.$btnLayout = $('#ladb_btn_layout', this.$element);
    };

    LadbWorkflowBoard.DEFAULTS = {
        minScale: 0.4,
        maxScale: 1,
        incScale: 0.1,
        newTaskPath: null,
        createTaskPath: null,
        editTaskPath: null,
        updateTaskPath: null,
        positionUpdateTaskPath: null,
        statusUpdateTaskPath: null,
        deleteTaskPath: null,
        createTaskConnectionPath: null,
        deleteTaskConnectionPath: null,
        startupConnections: []
    };

    LadbWorkflowBoard.prototype.layout = function() {

        var dg = new dagre.graphlib.Graph();
        dg.setGraph({
            nodesep: 50,
            ranksep: 60,
            marginx: 50,
            marginy: 50,
            ranker: 'longest-path'
        });
        dg.setDefaultEdgeLabel(function () {
            return {};
        });
        this.$diagram.find(".ladb-workflow-task-widget").each(
            function (idx, node) {
                var $node = $(node);
                dg.setNode($node.attr('id'), {
                    width: Math.round($node.outerWidth()),
                    height: Math.round($node.outerHeight())
                });
            }
        );
        this.plumb.getAllConnections()
            .forEach(function (edge) {
                dg.setEdge(edge.source.id, edge.target.id);
            });

        dagre.layout(dg);
        var graphInfo = dg.graph();
        dg.nodes().forEach(
            function (n) {
                var node = dg.node(n);
                var top = Math.round(node.y - node.height / 2) + 'px';
                var left = Math.round(node.x - node.width / 2) + 'px';
                $('#' + n).css({left: left, top: top});
            });

        this.plumb.repaintEverything();

    };

    LadbWorkflowBoard.prototype.up = function(data) {
        var that = this;

        var response = JSON.parse(data);

        var i, taskInfos, $taskWidget;

        // Created tasks
        if (response.createdTaskInfos) {
            for (i = 0; i < response.createdTaskInfos.length; i++) {

                taskInfos = response.createdTaskInfos[i];

                that.$canvas.append(taskInfos.widget);
                that.initTaskWidget($('.ladb-workflow-task-widget:last', that.$canvas));

                $('#collapse_status_' + taskInfos.status + ' .panel-body').append(taskInfos.row);
                that.bindTaskRow($('#ladb_workflow_task_row_' + taskInfos.id));
            }
        }

        // Updated tasks
        if (response.updatedTaskInfos) {
            for (i = 0; i < response.updatedTaskInfos.length; i++) {

                taskInfos = response.updatedTaskInfos[i];

                $taskWidget = $('#ladb_workflow_task_' + taskInfos.id, that.$canvas);
                $('.ladb-box', $taskWidget).replaceWith(taskInfos.box);
                that.bindTaskBox(taskInfos.id, $('.ladb-box', $taskWidget));

                $('#ladb_workflow_task_row_' + taskInfos.id).remove();
                $('#collapse_status_' + taskInfos.status + ' .panel-body').append(taskInfos.row);
                that.bindTaskRow($('#ladb_workflow_task_row_' + taskInfos.id));

            }
        }

        // Deleted task
        if (response.deletedTaskId) {
            that.plumb.remove('ladb_workflow_task_' + response.deletedTaskId);
            $('#ladb_workflow_task_row_' + response.deletedTaskId).remove();
        }

        for (i = 1; i <= 3; ++i) {
            var $badgeStatus = $('#collapse_status_' + i + '_badge');
            var $collapse = $('#collapse_status_' + i);
            $badgeStatus.html($('.ladb-workflow-task-row', $collapse).length);
        }

    };

    LadbWorkflowBoard.prototype.mod = function(data) {
        var that = this;

        var $modal = $(data);

        // Append modal to body
        $('body').append($modal);

        // Bind modal
        $modal.on('shown.bs.modal', function() {
            $('input', $modal).focus();
        });
        $modal.on('hidden.bs.modal', function() {
            $modal.remove();
        });

        // Bind form
        var $form = $('form', $modal);
        $form.ajaxForm({
            cache: false,
            dataType: "html",
            context: document.body,
            clearForm: true,
            success: function (data, textStatus, jqXHR) {
                try {
                    that.up(data);
                } catch (error) {
                    that.mod(data);
                }
                $modal.modal('hide');
            },
            error: function() {
                console.log('ERROR');
            }
        });

        // Bind button
        $('button[type=submit]', $modal).on('click', function() {
            $form.submit();
        });

        // Show modal
        $modal.modal('show');

    };

    LadbWorkflowBoard.prototype.bindTaskRow = function($taskRow) {

        var taskId = $taskRow.attr('id').substring('ladb_workflow_task_row_'.length);

        this.bindTaskBox(taskId, $('.ladb-box', $taskRow));
    };

    LadbWorkflowBoard.prototype.bindTaskBox = function(taskId, $taskBox) {
        var that = this;

        // Bind remove button
        $('.ladb-btn-remove', $taskBox).on('click', function(e) {

            // Update remote DB
            $.ajax(that.options.deleteTaskPath, {
                type: "POST",
                cache: false,
                dataType: "html",
                context: document.body,
                data: {
                    taskId: taskId
                },
                success: function(data, textStatus, jqXHR) {
                    that.up(data);
                },
                error: function () {
                    console.log('ERROR');
                }
            });

        });

        // Bind remove button
        $('.ladb-btn-edit', $taskBox).on('click', function(e) {
            that.editTask(taskId);
        });

        // Bind done button
        $('.ladb-btn-done', $taskBox).on('click', function(e) {

            // Update remote DB
            $.ajax(that.options.statusUpdateTaskPath, {
                type: "POST",
                cache: false,
                dataType: "html",
                context: document.body,
                data: {
                    taskId: taskId,
                    status: $('.ladb-icon-check', $(this)).length > 0 ? 2 : 3
                },
                success: function(data, textStatus, jqXHR) {
                    that.up(data);
                },
                error: function () {
                    console.log('ERROR');
                }
            });

        });

    };

    LadbWorkflowBoard.prototype.initTaskWidget = function($taskWidget) {
        var that = this;

        var taskId = $taskWidget.attr('id').substring('ladb_workflow_task_'.length);

        // Setup as plumb source and target

        this.plumb.makeSource($taskWidget, {
            filter: ".ladb-ep",
            endpoint: "Blank",
            anchor: "Bottom",
            connectorStyle: {stroke: "#5c96bc", strokeWidth: 2, outlineStroke: "transparent", outlineWidth: 6}
        });

        this.plumb.makeTarget($taskWidget, {
            dropOptions: {hoverClass: "jtk-drag-hover"},
            endpoint: "Blank",
            anchor: "Top",
            allowLoopback: false
        });

        // Bind box buttons

        this.bindTaskBox(taskId, $('.ladb-box', $taskWidget));

        // Make widget draggable

        var currentScale = 1;
        $taskWidget.draggable({
            grid: [10, 10],
            handle: ".ladb-box",
            start: function (e) {
                currentScale = that.$panzoom.panzoom("getMatrix")[0];
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

                // Update remote DB
                $.ajax(that.options.positionUpdateTaskPath, {
                    type: "POST",
                    cache: false,
                    dataType: "html",
                    context: document.body,
                    data: {
                        taskId: taskId,
                        positionLeft: $taskWidget.position().left,
                        positionTop: $taskWidget.position().top
                    },
                    error: function () {
                        console.log('ERROR');
                    }
                });

            }
        });

    };

    LadbWorkflowBoard.prototype.newTask = function() {
        var that = this;

        $.ajax(that.options.newTaskPath, {
            cache: false,
            dataType: "html",
            context: document.body,
            data: {
                positionLeft: 100,
                positionTop: 100
            },
            success: function(data, textStatus, jqXHR) {
                that.mod(data);
            },
            error: function () {
                console.log('ERROR');
            }
        });

    };

    LadbWorkflowBoard.prototype.editTask = function(taskId) {
        var that = this;

        $.ajax(that.options.editTaskPath, {
            type: "POST",
            cache: false,
            dataType: "html",
            context: document.body,
            data: {
                taskId: taskId
            },
            success: function(data, textStatus, jqXHR) {
                that.mod(data);
            },
            error: function () {
                console.log('ERROR');
            }
        });

    };

    LadbWorkflowBoard.prototype.bind = function() {
        var that = this;

        // Bind initiales tasks rows
        $('.ladb-workflow-task-row', that.$element).each(function (index) {
            that.bindTaskRow($( this ));
        });

        // Bind buttons
        this.$btnAddTask.on('click', function() {
            that.newTask();
        });

        this.$btnLayout.on('click', function() {
            that.layout();
        });

        // Bind plumb
        this.plumb.bind("connection", function (info, originalEvent) {

            // Update remote DB
            $.ajax(that.options.createTaskConnectionPath, {
                type: "POST",
                cache: false,
                dataType: "html",
                context: document.body,
                data: {
                    sourceTaskId: info.sourceId.substring('ladb_workflow_task_'.length),
                    targetTaskId: info.targetId.substring('ladb_workflow_task_'.length)
                },
                success: function(data, textStatus, jqXHR) {
                    that.up(data);
                },
                error: function () {
                    console.log('ERROR');
                }
            });

        });
        this.plumb.bind("connectionDrag", function (connection) {
            console.log('CONNECTION_DRAG');
        });
        this.plumb.bind("connectionDragStop", function (connection) {
            console.log('CONNECTION_DRAG_STOP');
        });
        this.plumb.bind("click", function (connection, originalEvent) {
            that.plumb.detach(connection);

            // Update remote DB
            $.ajax(that.options.deleteTaskConnectionPath, {
                type: "POST",
                cache: false,
                dataType: "html",
                context: document.body,
                data: {
                    sourceTaskId: connection.sourceId.substring('ladb_workflow_task_'.length),
                    targetTaskId: connection.targetId.substring('ladb_workflow_task_'.length)
                },
                success: function(data, textStatus, jqXHR) {
                    that.up(data);
                },
                error: function () {
                    console.log('ERROR');
                }
            });

        });

    };

    LadbWorkflowBoard.prototype.init = function() {
        var that = this;

        jsPlumb.ready(function () {

            // Init jsPlumb
            that.plumb = jsPlumb.getInstance({
                HoverPaintStyle: {stroke: "#f77f00", strokeWidth: 4},
                Connector: ["Bezier", {curviness: 50, stub: 50}],
                ConnectionOverlays: [
                    ["Arrow", {
                        location: 1,
                        id: "arrow",
                        width: 10,
                        length: 10,
                        foldback: 0.8
                    }]
                ],
                Container: that.$canvas
            });

            // Init initiales tasks widgets
            $('.ladb-workflow-task-widget', that.$canvas).each(function (index) {
                that.initTaskWidget($( this ));
            });

            // Apply startup connections
            _.each(that.options.startupConnections, function (connection) {
                that.plumb.connect({
                    source: 'ladb_workflow_task_' + connection.from,
                    target: 'ladb_workflow_task_' + connection.to
                });
            });

            // Setup panzoom
            _.defer(function () {
                that.$panzoom.panzoom({
                    minScale: that.options.minScale,
                    maxScale: that.options.maxScale,
                    increment: that.options.incScale,
                    cursor: "",
                    ignoreChildrensEvents: true
                }).on("panzoomstart", function (e, pz, ev) {
                    that.$panzoom.css("cursor", "move");
                }).on("panzoomend", function (e, pz) {
                    that.$panzoom.css("cursor", "");
                });
                that.$panzoom.parent()
                    .on('mousewheel.focal', function (e) {
                        e.preventDefault();
                        var delta = e.delta || e.originalEvent.wheelDelta;
                        var zoomOut = delta ? delta < 0 : e.originalEvent.deltaY > 0;
                        that.$panzoom.panzoom('zoom', zoomOut, {
                            animate: true,
                            exponential: false
                        });
                    })
                    .on("mousedown touchstart", function (ev) {
                        var matrix = that.$panzoom.panzoom("getMatrix");
                        var offsetX = matrix[4];
                        var offsetY = matrix[5];
                        var dragstart = {x: ev.pageX, y: ev.pageY, dx: offsetX, dy: offsetY};
                        $(ev.target).css("cursor", "move");
                        $(this).data('dragstart', dragstart);
                    })
                    .on("mousemove touchmove", function (ev) {
                        var dragstart = $(this).data('dragstart');
                        if (dragstart) {
                            var deltaX = dragstart.x - ev.pageX;
                            var deltaY = dragstart.y - ev.pageY;
                            var matrix = that.$panzoom.panzoom("getMatrix");
                            matrix[4] = parseInt(dragstart.dx) - deltaX;
                            matrix[5] = parseInt(dragstart.dy) - deltaY;
                            that.$panzoom.panzoom("setMatrix", matrix);
                        }
                    })
                    .on("mouseup touchend touchcancel", function (ev) {
                        $(this).data('dragstart', null);
                        $(ev.target).css("cursor", "");
                    });
            });

            that.bind();
        });

    };


    // PLUGIN DEFINITION
    // =======================

    function Plugin(option) {
        return this.each(function () {
            var $this   = $(this);
            var data    = $this.data('ladb.workflowboard');
            var options = $.extend({}, LadbWorkflowBoard.DEFAULTS, $this.data(), typeof option == 'object' && option);

            if (!data) {
                $this.data('ladb.workflowboard', (data = new LadbWorkflowBoard(this, options)));
            }
            if (typeof option == 'string') {
                data[option]();
            } else {
                data.init();
            }
        })
    }

    var old = $.fn.ladbWorkflowBoard;

    $.fn.ladbWorkflowBoard             = Plugin;
    $.fn.ladbWorkflowBoard.Constructor = LadbWorkflowBoard;


    // NO CONFLICT
    // =================

    $.fn.ladbWorkflowBoard.noConflict = function () {
        $.fn.ladbWorkflowBoard = old;
        return this;
    }

}(jQuery);