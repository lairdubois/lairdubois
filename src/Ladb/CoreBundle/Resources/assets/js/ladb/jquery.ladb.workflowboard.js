+function ($) {
    'use strict';

    // CLASS DEFINITION
    // ======================

    var LadbWorkflowBoard = function(element, options) {
        this.options = options;
        this.$element = $(element);

        this.session = null;
        this.plumb = null;

        this.$loadingPanel = $('.ladb-loading-panel', this.$element);

        this.$diagram = $('#ladb_workflow_task_diagram', this.$element);
        this.$panzoom = $(".ladb-panzoom", this.$diagram);
        this.$canvas = $('.ladb-jtk-canvas', this.$panzoom);

        this.$btnAddTask = $('#ladb_btn_add_task', this.$element);
    };

    LadbWorkflowBoard.DEFAULTS = {
        wsUri: 'ws://127.0.0.1:8080',
        wsChannel: '',
        minScale: 0.4,
        maxScale: 1,
        incScale: 0.1,
        newTaskPath: null,
        createTaskPath: null,
        editTaskPath: null,
        updateTaskPath: null,
        positionUpdateTaskPath: null,
        statusUpdateTaskPath: null,
        createTaskConnectionPath: null,
        deleteTaskConnectionPath: null,
        startupConnections: []
    };

    LadbWorkflowBoard.prototype.markLoading = function() {
        this.$loadingPanel.show();
    };

    LadbWorkflowBoard.prototype.unmarkLoading = function() {
        this.$loadingPanel.hide();
    };

    LadbWorkflowBoard.prototype.appendToAnimate = function(element, newParent) {

        var $element = $(element);
        var $newParent= $(newParent);

        var oldOffset = $element.offset();
        $element.appendTo($newParent);
        var newOffset = $element.offset();

        var $tmpElement = $element.clone().appendTo('body');
        $tmpElement.css({
            'position': 'absolute',
            'left': oldOffset.left,
            'top': oldOffset.top,
            'width': $element.width(),
            'z-index': 1000
        });
        $element.hide();
        $tmpElement.animate({'top': newOffset.top, 'left': newOffset.left}, 500, function(){
            $element.show();
            $tmpElement.remove();
        });

    };

    LadbWorkflowBoard.prototype.updateDiagramFromJsonData = function(data) {
        var that = this;

        var response = JSON.parse(data);

        var i, taskInfos, $taskWidget, $taskRow;

        // Workflow
        if (response.workflowInfos) {
            $('#ladb_workflow_status_panel', this.$element).replaceWith(response.workflowInfos.statusPanel);
        }

        // Created tasks
        if (response.createdTaskInfos) {
            for (i = 0; i < response.createdTaskInfos.length; i++) {

                taskInfos = response.createdTaskInfos[i];

                $taskWidget = $(taskInfos.widget);
                that.$canvas.append($taskWidget);
                that.initTaskWidget($taskWidget);
                $('.ladb-box', $taskWidget).effect('highlight', {}, 1500);

                $taskRow = $(taskInfos.row);
                $('#collapse_status_' + taskInfos.status + ' .panel-body').append($taskRow);
                that.initTaskRow($taskRow);
                $('.ladb-box', $taskRow).effect('highlight', {}, 500);

            }
        }

        // Moved tasks
        if (response.movedTaskInfos && that.plumb) {
            for (i = 0; i < response.movedTaskInfos.length; i++) {

                taskInfos = response.movedTaskInfos[i];

                $taskWidget = $('#ladb_workflow_task_widget_' + taskInfos.id, that.$canvas);
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
                    $taskWidget = $('#ladb_workflow_task_widget_' + taskInfos.id, that.$canvas);
                    $('.ladb-box', $taskWidget).replaceWith(taskInfos.box);
                    that.bindTaskBox(taskInfos.id, $('.ladb-box', $taskWidget));
                }

                $taskRow = $('#ladb_workflow_task_row_' + taskInfos.id);
                $('.ladb-box', $taskRow).replaceWith(taskInfos.box);
                that.appendToAnimate($taskRow, $('#collapse_status_' + taskInfos.status + ' .panel-body'));
                that.bindTaskBox(taskInfos.id, $('.ladb-box', $taskRow));

            }
        }

        // Deleted tasks
        if (response.deletedTaskId) {
            if (that.plumb) {
                $taskWidget = $('#ladb_workflow_task_widget_' + response.deletedTaskId);
                if ($taskWidget.length > 0) {
                    that.plumb.remove('ladb_workflow_task_widget_' + response.deletedTaskId);
                }
            }
            $taskRow = $('#ladb_workflow_task_row_' + response.deletedTaskId);
            $taskRow.remove();
        }

        // Created connections
        if (response.createdConnections && that.plumb) {
            _.each(response.createdConnections, function (connection) {
                var sourceId = 'ladb_workflow_task_widget_' + connection.from;
                var targetId = 'ladb_workflow_task_widget_' + connection.to;
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
                    source: 'ladb_workflow_task_widget_' + connection.from,
                    target: 'ladb_workflow_task_widget_' + connection.to
                });
            });
        }

        // Update section badges
        for (i = 1; i <= 4; ++i) {
            var $badgeStatus = $('#collapse_status_' + i + '_badge');
            var $collapse = $('#collapse_status_' + i);
            $badgeStatus.html($('.ladb-workflow-task-row', $collapse).length);
        }

    };

    LadbWorkflowBoard.prototype.appendModalFromHtmlData = function(data) {
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
            that.removeFakeTask();
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
                    JSON.parse(data);
                    that.removeFakeTask();
                } catch (error) {
                    that.appendModalFromHtmlData(data);
                }
                $modal.modal('hide');
            },
            error: function() {
                console.log('ERROR');
                that.removeFakeTask();
            }
        });

        // Bind submit button
        $('button[type=submit]', $modal).on('click', function() {
            $(this).button('loading');
            $form.submit();
        });

        // Bind remove button
        $('.ladb-btn-delete', $modal).on('click', function(e) {
            e.preventDefault();

            $(this).button('loading');

            // Sync with server
            $.ajax($(this).attr('href'), {
                cache: false,
                dataType: "html",
                context: document.body,
                success: function(data, textStatus, jqXHR) {
                    $modal.modal('hide');
                },
                error: function () {
                    console.log('ERROR');
                }
            });

        });

        // Show modal
        $modal.modal('show');

        // Hide loading
        this.unmarkLoading();

    };

    LadbWorkflowBoard.prototype.bindTaskBox = function(taskId, $taskBox) {
        var that = this;

        // Bind done and run button
        $('.ladb-status-update-btn', $taskBox).on('click', function (e) {

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
            that.editTask(taskId);
        });

    };

    LadbWorkflowBoard.prototype.initTaskRow = function($taskRow) {

        var taskId = $taskRow.attr('id').substring('ladb_workflow_task_row_'.length);

        this.bindTaskBox(taskId, $('.ladb-box', $taskRow));
    };

    LadbWorkflowBoard.prototype.initTaskWidget = function($taskWidget) {
        var that = this;

        var taskId = $taskWidget.attr('id').substring('ladb_workflow_task_widget_'.length);

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

        // Bind ep button

        $('.ladb-ep', $taskWidget).on('click', function(e) {

            var positionLeft = e.originalEvent.clientX - that.$canvas.offset().left - 150;    // 150 = half task widget width
            var positionTop = e.originalEvent.clientY - that.$canvas.offset().top + 100;

            // Drop connection on the board -> new Task
            that.newTask(positionLeft, positionTop, taskId);

        });

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

                // Round coordinates to grid
                var positionLeft = Math.round($taskWidget.position().left / 10) * 10;
                var positionTop = Math.round($taskWidget.position().top / 10) * 10;

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

    LadbWorkflowBoard.prototype.appendFakeTask = function(positionLeft, positionTop, sourceTaskId) {
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
                source: 'ladb_workflow_task_widget_' + sourceTaskId,
                target: 'fake_task'
            });
        }

    };

    LadbWorkflowBoard.prototype.removeFakeTask = function() {
        if (!this.plumb) {
            return;
        }

        var $fakeTask = $('#fake_task');
        if ($fakeTask.length > 0) {
            this.plumb.remove('fake_task');
        }

    };

    LadbWorkflowBoard.prototype.newTask = function(positionLeft, positionTop, sourceTaskId) {
        var that = this;

        positionLeft = Math.round(positionLeft / 10) * 10;
        positionTop = Math.round(positionTop / 10) * 10;

        // Append the fake task
        this.appendFakeTask(positionLeft, positionTop, sourceTaskId);

        // Loading
        this.markLoading();

        $.ajax(that.options.newTaskPath, {
            cache: false,
            dataType: "html",
            context: document.body,
            data: {
                positionLeft: positionLeft,
                positionTop: positionTop,
                sourceTaskId: sourceTaskId
            },
            success: function(data, textStatus, jqXHR) {
                that.appendModalFromHtmlData(data);
            },
            error: function () {
                console.log('ERROR');
                that.removeFakeTask();
            }
        });

    };

    LadbWorkflowBoard.prototype.editTask = function(taskId) {
        var that = this;

        // Loading
        this.markLoading();

        $.ajax(that.options.editTaskPath, {
            type: "POST",
            cache: false,
            dataType: "html",
            context: document.body,
            data: {
                taskId: taskId
            },
            success: function(data, textStatus, jqXHR) {
                that.appendModalFromHtmlData(data);
            },
            error: function () {
                console.log('ERROR');
            }
        });

    };

    LadbWorkflowBoard.prototype.bind = function() {
        var that = this;

        // Init initiales tasks rows
        $('.ladb-workflow-task-row', that.$element).each(function (index) {
            that.initTaskRow($(this));
        });

        // Bind buttons
        this.$btnAddTask.on('click', function() {

            var positionLeft = that.$diagram.outerWidth() / 2 - that.$canvas.position().left - 150;
            var positionTop = that.$diagram.outerHeight() / 2 - that.$canvas.position().top - 30;

            that.newTask(positionLeft, positionTop);
        });

        // Bind loading mask
        this.$loadingPanel.on('mousedown', function(e) {
            e.stopImmediatePropagation();
        });

    };

    LadbWorkflowBoard.prototype.init = function() {
        var that = this;

        // Connect WebSocket
        var ws = WS.connect(this.options.wsUri);
        ws.on('socket/connect', function(session) {

            // Hide loading
            that.unmarkLoading();

            // Keep ws session
            that.session = session;

            // Subscribe to the channel
            session.subscribe(that.options.wsChannel, function (uri, payload) {
                try {
                    that.updateDiagramFromJsonData(payload);
                } catch(error) {}
            });

        });
        ws.on('socket/disconnect', function(error){
            notifyError('Disconnected for ' + error.reason + ' with code ' + error.code);

            // Loading
            that.markLoading();

        });

        if (this.$canvas.is(':visible')) {

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
                            sourceTaskId: info.sourceId.substring('ladb_workflow_task_widget_'.length),
                            targetTaskId: info.targetId.substring('ladb_workflow_task_widget_'.length)
                        },
                        error: function () {
                            console.log('ERROR');
                        }
                    });

                });
                that.plumb.bind('connectionAborted', function (connection, originalEvent) {

                    var sourceTaskId = connection.sourceId.substring('ladb_workflow_task_widget_'.length);

                    var positionLeft = originalEvent.clientX - that.$canvas.offset().left - 150;    // 150 = half task widget width
                    var positionTop = originalEvent.clientY - that.$canvas.offset().top;

                    // Drop connection on the board -> new Task
                    that.newTask(positionLeft, positionTop, sourceTaskId);

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
                            sourceTaskId: connection.sourceId.substring('ladb_workflow_task_widget_'.length),
                            targetTaskId: connection.targetId.substring('ladb_workflow_task_widget_'.length)
                        },
                        error: function () {
                            console.log('ERROR');
                        }
                    });

                });

                // Init initiales tasks widgets
                $('.ladb-workflow-task-widget', that.$canvas).each(function (index) {
                    that.initTaskWidget($(this));
                });

                // Apply startup connections
                _.each(that.options.startupConnections, function (connection) {
                    that.plumb.connect({
                        source: 'ladb_workflow_task_widget_' + connection.from,
                        target: 'ladb_workflow_task_widget_' + connection.to
                    });
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
                    })
                    .on("mousedown touchstart", function (e) {
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
                    .on("mousemove touchmove", function (e) {
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
                    .on("mouseup touchend touchcancel", function (e) {
                        $(this).data('dragstart', null);
                        $(e.target).css("cursor", "");
                    })
                    .on('dblclick', function(e) {

                        var positionLeft = e.originalEvent.clientX - that.$canvas.offset().left - 150;    // 150 = half task widget width
                        var positionTop = e.originalEvent.clientY - that.$canvas.offset().top;

                        that.newTask(positionLeft, positionTop);
                    });

            });

        }

        that.bind();

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