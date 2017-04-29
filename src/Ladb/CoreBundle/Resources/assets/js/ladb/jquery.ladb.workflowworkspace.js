+function ($) {
    'use strict';

    var GRID_SPACING = 10;
    var TASK_ROW_PREFIX =  'ladb_workflow_task_row_';
    var TASK_WIDGET_PREFIX =  'ladb_workflow_task_widget_';
    var TASK_WIDGET_BOX_WIDTH = 344;

    // CLASS DEFINITION
    // ======================

    var LadbWorkflowWorkspace = function(element, options) {
        this.options = options;
        this.$element = $(element);

        this.session = null;
        this.plumb = null;

        this.$loadingPanel = $('.ladb-loading-panel', this.$element);
        this.$loadingStatus = $('.ladb-loading-status', this.$loadingPanel);

        this.$rightPanel = $('.ladb-right-panel', this.$element);
        this.$diagram = $('.ladb-workflow-task-diagram', this.$element);
        this.$panzoom = $(".ladb-panzoom", this.$diagram);
        this.$canvas = $('.ladb-jtk-canvas', this.$panzoom);

        this.$modal = $('#workflow_modal', this.$element);

        this.$btnAddTask = $('#ladb_add_task_btn', this.$element);
        this.$btnListLabels = $('#ladb_list_labels_btn', this.$element);
        this.$btnStatistics = $('#ladb_statistics_btn', this.$element);
    };

    LadbWorkflowWorkspace.DEFAULTS = {
        readOnly: false,
        wsUri: 'ws://127.0.0.1:8080',
        wsChannel: '',
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
        deleteTaskConnectionPath: null,
        listLabelPath: null,
        statisticsPath: null
    };

    LadbWorkflowWorkspace.prototype._uiGetDiagramContentRect = function() {

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
        }

        return contentRect;
    };

    LadbWorkflowWorkspace.prototype._uiDiagramShowAll = function() {
        if (this.$panzoom) {

            var areaPadding = 30;

            var contentRect = this._uiGetDiagramContentRect();
            if (contentRect) {

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
                this.plumb.setZoom(this._uiGetCurrentScale());

            }
        }
    };

    LadbWorkflowWorkspace.prototype._uiDiagramCenterOnTask = function(taskId) {

        var $taskWidget = $('#' + TASK_WIDGET_PREFIX + taskId);

        // Retrieve widget position and area rect
        var position = $taskWidget.position();
        var areaRect = this.$diagram.get(0).getBoundingClientRect();

        var panX = (areaRect.width - $taskWidget.outerWidth()) / 2 - position.left;
        var panY = (areaRect.height - $taskWidget.outerHeight()) / 2 - position.top;
        this.$panzoom.panzoom('resetZoom', { animate: true });
        this.$panzoom.panzoom('pan', panX, panY, {
            relative: false,
            animate: true
        });
        $('.ladb-box', $taskWidget).effect('highlight', {}, 1000);

    };

    LadbWorkflowWorkspace.prototype._uiGetCurrentScale = function() {
        if (this.$panzoom) {
            return this.$panzoom.panzoom('getMatrix')[0];
        }
        return 1;
    };

    LadbWorkflowWorkspace.prototype._uiMarkLoading = function(status) {
        this.$loadingPanel.show();
        this.$loadingStatus.html(status ? status : '');
        this.$btnAddTask.prop('disabled', true);
        this.$btnListLabels.prop('disabled', true);
        this.$btnStatistics.prop('disabled', true);
    };

    LadbWorkflowWorkspace.prototype._uiUnmarkLoading = function() {
        this.$loadingPanel.hide();
        this.$btnAddTask.prop('disabled', false);
        this.$btnListLabels.prop('disabled', false);
        this.$btnStatistics.prop('disabled', false);
    };

    LadbWorkflowWorkspace.prototype._uiAppendToAnimate = function(element, newParent) {

        var $taskRow = $(element);
        var $taskBox = $('.ladb-box', $taskRow);
        var $newParent = $(newParent);

        var oldOffset = $taskRow.offset();
        $taskRow.appendTo($newParent);
        var newOffset = $taskRow.offset();

        var $tmpTaskRow = $taskRow.clone().appendTo('body');
        $tmpTaskRow.css({
            'position': 'absolute',
            'top': oldOffset.top,
            'left': oldOffset.left,
            'width': $taskRow.width(),
            'z-index': 1000
        });
        $taskRow.css({
            'height': $taskRow.height()
        });
        $taskBox.hide();
        $tmpTaskRow.animate({
            'top': newOffset.top,
            'left': newOffset.left
        }, 500, function() {
            $taskRow.css({
                'height': 'auto'
            });
            $taskBox.show();
            $tmpTaskRow.remove();
        });

    };

    LadbWorkflowWorkspace.prototype._uiToggleRightPanel = function() {
        if (this.$rightPanel.is(':visible')) {
            this.$rightPanel.hide();
            this.$diagram.removeClass('ladb-with-right-panel');
        } else {
            this.$rightPanel.show();
            this.$diagram.addClass('ladb-with-right-panel');
        }
    };

    LadbWorkflowWorkspace.prototype.updateBoardFromJsonData = function(data) {
        var that = this;

        var response = JSON.parse(data);

        var i, taskInfos, $taskWidget, $taskRow;

        // Workflow
        if (response.workflowInfos) {
            $('#ladb_workflow_status', this.$element).replaceWith(response.workflowInfos.statusPanel);
        }

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

                $taskRow = $(taskInfos.row);
                $('#panel_body_status_' + taskInfos.status).append($taskRow);
                that.initTaskRow($taskRow);

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

                $taskRow = $(taskInfos.row);
                $('#panel_body_status_' + taskInfos.status).append($taskRow);
                that.initTaskRow($taskRow);
                $('.ladb-box', $taskRow).effect('highlight', {}, 500);

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

                $taskRow = $('#' + TASK_ROW_PREFIX + taskInfos.id);
                $('.ladb-box', $taskRow).replaceWith(taskInfos.box);
                that._uiAppendToAnimate($taskRow, $('#panel_body_status_' + taskInfos.status));
                that.bindTaskBox(taskInfos.id, $('.ladb-box', $taskRow));

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
            $taskRow = $('#' + TASK_ROW_PREFIX + response.deletedTaskId);
            $taskRow.remove();
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

        // Update section badges
        for (i = 1; i <= 4; ++i) {
            var $badgeStatus = $('#panel_heading_badge_status_' + i);
            var $panelBody = $('#panel_body_status_' + i);
            var count = $('.ladb-workflow-task-row', $panelBody).length;
            $badgeStatus.html(count);
            if (count > 0) {
                $badgeStatus.removeClass('ladb-null');
                $('.ladb-no-task', $panelBody).hide();
            } else {
                $badgeStatus.addClass('ladb-null');
                $('.ladb-no-task', $panelBody).show();
            }
        }

        setupTooltips();

    };

    LadbWorkflowWorkspace.prototype.bindTaskBox = function(taskId, $taskBox) {
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

    LadbWorkflowWorkspace.prototype.initTaskRow = function($taskRow) {
        var that = this;

        var taskId = $taskRow.attr('id').substring(TASK_ROW_PREFIX.length);

        $taskRow.on('click', function() {
            that._uiDiagramCenterOnTask(taskId);
        });

        this.bindTaskBox(taskId, $('.ladb-box', $taskRow));
    };

    LadbWorkflowWorkspace.prototype.initTaskWidget = function($taskWidget) {
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

            var currentScale = that._uiGetCurrentScale();
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
                currentScale = that._uiGetCurrentScale();
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

    LadbWorkflowWorkspace.prototype.appendFakeTask = function(positionLeft, positionTop, sourceTaskId) {
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

    LadbWorkflowWorkspace.prototype.removeFakeTask = function() {
        if (!this.plumb) {
            return;
        }

        var $fakeTask = $('#fake_task');
        if ($fakeTask.length > 0) {
            this.plumb.remove('fake_task');
        }

    };

    LadbWorkflowWorkspace.prototype.loadTasks = function() {
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
                that.updateBoardFromJsonData(data);

                // Center origin
                that._uiDiagramShowAll();

            },
            error: function (jqXHR, textStatus, errorThrown) {
                console.log('loadTasks failed', textStatus);
            }
        });

    };

    LadbWorkflowWorkspace.prototype.loadModalNewTask = function(positionLeft, positionTop, sourceTaskId) {
        var that = this;

        positionLeft = Math.round(positionLeft / 10) * 10;
        positionTop = Math.round(positionTop / 10) * 10;

        // Append the fake task
        this.appendFakeTask(positionLeft, positionTop, sourceTaskId);

        that.$modal.ladbRemoteModal('loadContent', {
            url: that.options.newTaskPath,
            data: {
                positionLeft: positionLeft,
                positionTop: positionTop,
                sourceTaskId: sourceTaskId
            },
            onSuccess: function($content) {
                that.bindNewAndEditModalContent(that.$modal);
            },
            onError: function() {
                that.removeFakeTask();
            },
            onHidden: function() {
                that.removeFakeTask();
            }
        });

    };

    LadbWorkflowWorkspace.prototype.loadModalEditTask = function(taskId) {
        var that = this;

        that.$modal.ladbRemoteModal('loadContent', {
            url: that.options.editTaskPath,
            data: {
                taskId: taskId
            },
            onSuccess: function($content) {
                that.bindNewAndEditModalContent(that.$modal);
            },
            onError: function() {
            }
        });

    };

    LadbWorkflowWorkspace.prototype.loadModalListLabels = function() {
        var that = this;

        // Load modal
        that.$modal.ladbRemoteModal('loadContent', {
            url: that.options.listLabelPath,
            onSuccess: function($content) {
                // that.$modal.ladbWorkflowLabels();

                var $alertEmpty = $('.ladb-label-empty-alert', $content);
                var $table = $('.ladb-label-table', $content);
                var $newBtn = $('.ladb-label-new-btn', $content);

                // Functions /////

                function checkEmpty() {
                    if ($('.ladb-workflow-label-row, .ladb-workflow-label-row-form', $table).length == 0) {
                        $alertEmpty.show();
                        $table.hide();
                    } else {
                        $alertEmpty.hide();
                        $table.show();
                    }
                }

                function bindRowForm($rowForm, $row) {

                    var $form = $('form', $rowForm);
                    var $loadingPanel = $('.ladb-loading-panel', $rowForm);
                    var $cancelBtn = $('.ladb-label-cancel-btn', $rowForm);
                    var $saveBtn = $('.ladb-label-save-btn', $rowForm);
                    var $inputColor = $('.ladb-input-color', $form);
                    var $inputName = $('.ladb-input-name', $form);

                    // Bind form
                    $form.ajaxForm({
                        cache: false,
                        dataType: "html",
                        context: document.body,
                        clearForm: true,
                        success: function(data, textStatus, jqXHR) {

                            $cancelBtn.unbind('click');
                            $saveBtn.unbind('click');

                            var $data = $(data);
                            $rowForm.replaceWith($data);

                            if ($data.hasClass('ladb-workflow-label-row')) {
                                if ($row) { $row.remove(); }
                                bindRows($data);
                                $alertEmpty.hide();
                            } else {
                                bindRowForm($data, $row);
                            }

                        },
                        error: function() {
                            $loadingPanel.hide();
                        }
                    });

                    // Bind buttons
                    $cancelBtn.on('click', function(e) {
                        if ($row) { $row.show(); }
                        $cancelBtn.unbind('click');
                        $saveBtn.unbind('click');
                        $rowForm.remove();
                        checkEmpty();
                    });
                    $saveBtn.on('click', function(e) {
                        $loadingPanel.show();
                        $form.submit();
                    });

                    // Bind input color
                    $inputColor.ladbInputColor();

                    // Focus name input
                    $inputName.focus();

                }

                function bindRows($rows) {

                    $rows.each(function(index, value) {

                        var $row = $(value);
                        var $loadingPanel = $('.ladb-loading-panel', $row);
                        var $editBtn = $('.ladb-label-edit-btn', $row);
                        var $deleteBtn = $('.ladb-label-delete-btn', $row);

                        $editBtn.on('click', function(e) {
                            e.preventDefault();

                            $loadingPanel.show();

                            // Hide previously edited row
                            $('.ladb-workflow-label-row').show();
                            $('.ladb-workflow-label-row-form').remove();

                            $.ajax($(this).attr('href'), {
                                cache: false,
                                dataType: "html",
                                context: document.body,
                                success: function(data, textStatus, jqXHR) {

                                    var $rowForm = $(data);

                                    // Hide old row
                                    $row.hide();

                                    // Append row form
                                    $row.after($rowForm);

                                    bindRowForm($rowForm, $row);

                                    $loadingPanel.hide();
                                },
                                error: function () {
                                    $loadingPanel.hide();
                                }
                            });

                        });

                        $deleteBtn.on('click', function(e) {
                            e.preventDefault();

                            $loadingPanel.show();

                            $.ajax($(this).attr('href'), {
                                cache: false,
                                dataType: "html",
                                context: document.body,
                                success: function (data, textStatus, jqXHR) {
                                    $row.remove();
                                    checkEmpty();
                                },
                                error: function () {
                                    $loadingPanel.hide();
                                }
                            });

                        });

                    });

                }

                // Binds /////

                // Bind New buttons
                $newBtn.on('click', function(e) {
                    e.preventDefault();

                    // Hide previously edited row
                    $('.ladb-workflow-label-row', $content).show();
                    $('.ladb-workflow-label-row-form', $content).remove();

                    var url = $newBtn.attr('href');

                    // Loading button
                    $newBtn.button('loading');

                    $.ajax(url, {
                        cache: false,
                        dataType: "html",
                        context: document.body,
                        success: function(data, textStatus, jqXHR) {

                            var $tbody = $('tbody', $table);
                            var $rowForm = $(data);

                            // Reset loading
                            $newBtn.button('reset');

                            // Append row form
                            $tbody.append($rowForm);

                            checkEmpty();
                            bindRowForm($rowForm, null);
                        },
                        error: function () {
                            // Reset loading
                            $newBtn.button('reset');
                        }
                    });

                });

                // Bind Rows
                bindRows($('.ladb-workflow-label-row', $content));

            },
            onError: function() {
            }
        });

    };

    LadbWorkflowWorkspace.prototype.loadModalStatistics = function() {
        var that = this;

        // Load modal
        that.$modal.ladbRemoteModal('loadContent', {
            url: that.options.statisticsPath,
            onSuccess: function ($content) {
            }
        });

    };

    LadbWorkflowWorkspace.prototype.bindNewAndEditModalContent = function($modal) {
        var that = this;

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
                    $modal.ladbRemoteModal('hide');
                    that.removeFakeTask();
                } catch (error) {
                    $modal.ladbRemoteModal('setContent', data);
                    that.bindNewAndEditModalContent($modal);
                }
            },
            error: function() {
                console.log('ERROR');
                that.removeFakeTask();
            }
        });
        $('input[type=text]', $form).first().focus();

        // Init duration fileds
        $('input[data-type=duration]').ladbInputDuration();

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
                    $modal.ladbRemoteModal('hide');
                },
                error: function () {
                    console.log('ERROR');
                }
            });

        });

        // Fake Labels select
        var $select = $('.ladb-workflow-label-fake-select', $modal);
        if ($select.length > 0) {
            var $mappedInput = $($select.data('ladb-mapped-input'), $modal);
            var currentValue = $mappedInput.val();
            $select.selectpicker({
                noneSelectedText: 'Aucune étiquette',
                iconBase: '',
                tickIcon: 'ladb-icon-check'
            });
            if (currentValue) {
                $select.selectpicker('val', currentValue.split(','));
            }
            $select.on('changed.bs.select', function (e) {
                var selectValue = $(this).val();
                var fieldValue = selectValue ? selectValue.join(',') : '';
                $mappedInput.val(fieldValue);
            });
            $select.on('show.bs.select', function (e) {
                $modal.ladbRemoteModal('setHiddable', false);
            });
            $select.on('hidden.bs.select', function (e) {
                $modal.ladbRemoteModal('setHiddable', true);
            });
        }

    };

    LadbWorkflowWorkspace.prototype.bind = function() {
        var that = this;

        // Bind modal as remote modal
        this.$modal.ladbRemoteModal();

        // Bind loading mask
        this.$loadingPanel.on('mousedown', function(e) {
            e.stopImmediatePropagation();
        });

        if (this.options.readOnly) {
            return;
        }

        // Bind buttons
        this.$btnAddTask.on('click', function() {

            var currentScale = that._uiGetCurrentScale();
            var positionLeft = (that.$diagram.outerWidth() / 2 - that.$canvas.position().left) / currentScale - TASK_WIDGET_BOX_WIDTH / 2;
            var positionTop = (that.$diagram.outerHeight() / 2 - that.$canvas.position().top) / currentScale - 30;

            that.loadModalNewTask(positionLeft, positionTop);
        });
        this.$btnListLabels.on('click', function() {
            that.loadModalListLabels();
        });
        this.$btnStatistics.on('click', function() {
            that.loadModalStatistics();
        });
        $('.ladb-toggle-right-panel-btn', this.$element).on('click', function() {
            that._uiToggleRightPanel();
        });

    };

    LadbWorkflowWorkspace.prototype.init = function() {
        var that = this;

        // Check capabilities
        if (!Modernizr.touchevents) {}
        if (!Modernizr.websockets) {}

        // Loading
        this._uiMarkLoading('Connexion...');

        // Connect WebSocket
        var ws = WS.connect(this.options.wsUri);
        ws.on('socket/connect', function(session) {

            // Keep ws session
            that.session = session;

            // Subscribe to the channel
            try {

                session.subscribe(that.options.wsChannel, function (uri, payload) {
                    try {
                        that.updateBoardFromJsonData(payload);
                    } catch(error) {
                        console.log("Error updating diagram", error);
                    }
                });

            } catch(error) {
                console.log("Subscription failed", error);
            }

            // Load tasks
            that.loadTasks();

        });
        ws.on('socket/disconnect', function(error){

            // Reset ws session
            that.session = null;

            switch (error.code) {

                case 2: // Connection max retry
                    that._uiMarkLoading('Re-Connexion impossible avec le serveur :(');
                    break;

                case 3: // Connection could not be established
                    that._uiMarkLoading('Connexion impossible avec le serveur :(');

                    // Load tasks
                    that.loadTasks();

                    break;

                case 5: // Connection unreachable
                    that._uiMarkLoading('Connexion impossible : Nouvel essai');
                    break;

                case 6: // Connection lost
                    that._uiMarkLoading('Connexion perdue avec le serveur');
                    break;

                default:
                    that._uiMarkLoading('Déconecté :(');
                    break;

            }

            notifyError('Disconnected for ' + error.reason + ' with code ' + error.code);

        });

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

                        var currentScale = that._uiGetCurrentScale();
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
                        that.plumb.setZoom(that._uiGetCurrentScale());
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

                            var currentScale = that._uiGetCurrentScale();
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
            var data    = $this.data('ladb.workflowworkspace');
            var options = $.extend({}, LadbWorkflowWorkspace.DEFAULTS, $this.data(), typeof option == 'object' && option);

            if (!data) {
                $this.data('ladb.workflowworkspace', (data = new LadbWorkflowWorkspace(this, options)));
            }
            if (typeof option == 'string') {
                data[option]();
            } else {
                data.init();
            }
        })
    }

    var old = $.fn.ladbWorkflowWorkspace;

    $.fn.ladbWorkflowWorkspace             = Plugin;
    $.fn.ladbWorkflowWorkspace.Constructor = LadbWorkflowWorkspace;


    // NO CONFLICT
    // =================

    $.fn.ladbWorkflowWorkspace.noConflict = function () {
        $.fn.ladbWorkflowWorkspace = old;
        return this;
    }

}(jQuery);