<?php

return [
    'navigation' => [
        'hub'      => 'Task Hub',
        'kanban'   => 'Kanban Board',
        'calendar' => 'Operations Calendar',
        'group'    => 'Projects',
    ],

    'hub' => [
        'title'          => 'Task Operations Hub',
        'subheading'     => 'Manage tasks, deadlines, and team workload in one place.',
        'view_list'      => 'List',
        'view_kanban'    => 'Kanban',
        'view_calendar'  => 'Calendar',
        'quick_create'   => 'New Task',
        'view_all_tasks' => 'All Tasks',
    ],

    'filters' => [
        'title'              => 'Filters',
        'employee'           => 'Employee',
        'department'         => 'Department',
        'project'            => 'Project',
        'category'           => 'Category',
        'status'             => 'Status',
        'priority'           => 'Priority',
        'clear'              => 'Clear filters',
        'show_project_tasks' => 'Project tasks',
        'show_meetings'      => 'Meetings',
        'show_meeting_tasks' => 'Meeting action items',
        'show_leave'         => 'Leave',
        'show_milestones'    => 'Milestones',
        'show_holidays'      => 'Public holidays',
    ],

    'fields' => [
        'start_date'     => 'Start date',
        'completed_at'   => 'Completed at',
        'owner'          => 'Responsible person',
        'category'       => 'Category',
        'department'     => 'Department',
        'priority_level' => 'Priority',
    ],

    'priority' => [
        'low'    => 'Low',
        'medium' => 'Medium',
        'high'   => 'High',
        'urgent' => 'Urgent',
    ],

    'state' => [
        'pending'     => 'Pending',
        'in_progress' => 'In Progress',
        'on_hold'     => 'On Hold',
        'completed'   => 'Completed',
        'cancelled'   => 'Cancelled',
    ],

    'stats' => [
        'open'           => 'Open tasks',
        'overdue'        => 'Overdue',
        'due_today'      => 'Due today',
        'completed_week' => 'Completed this week',
        'by_status'      => 'Tasks by status',
        'workload'       => 'Workload by employee',
    ],

    'kanban' => [
        'title'        => 'Task Kanban',
        'subheading'   => 'Drag tasks between stages to update workflow.',
        'empty_column' => 'Drag a task here',
        'no_stages'    => 'No task stages configured yet.',
        'overdue'      => 'Overdue',
    ],

    'calendar' => [
        'title'        => 'Operations Calendar',
        'subheading'   => 'Tasks, meetings, leave, and milestones in one view.',
        'legend'       => 'Legend',
        'project_task' => 'Project task',
        'meeting'      => 'Meeting',
        'meeting_task' => 'Meeting action item',
        'leave'        => 'Leave',
        'milestone'    => 'Milestone',
        'holiday'      => 'Public holiday',
    ],

    'actions' => [
        'archive'         => 'Archive task',
        'archive_confirm' => 'Archive this task? It will be hidden from active lists but kept for reporting.',
        'archived'        => 'Task archived',
    ],

    'notifications' => [
        'assigned' => [
            'title' => 'New task assigned',
            'body'  => 'You were assigned to: :title',
        ],
        'status_changed' => [
            'title' => 'Task status updated',
            'body'  => ':title is now :status',
        ],
        'deadline' => [
            'title' => 'Task deadline approaching',
            'body'  => ':title is due on :date',
        ],
        'overdue' => [
            'title' => 'Task overdue',
            'body'  => ':title is overdue',
        ],
        'task_created' => [
            'title' => 'Task created',
            'body'  => 'The task was created successfully.',
        ],
    ],

    'mail' => [
        'deadline' => [
            'subject' => 'Task deadline: :title',
            'heading' => 'Task deadline reminder',
            'intro'   => 'Hello :name, this is a reminder about an upcoming task deadline.',
        ],
        'overdue' => [
            'subject' => 'Overdue task: :title',
            'heading' => 'Overdue task reminder',
            'intro'   => 'Hello :name, the following task is overdue.',
        ],
        'status'         => 'Status',
        'deadline_label' => 'Due date',
    ],

    'columns' => [
        'owner'           => 'Owner',
        'category'        => 'Category',
        'department'      => 'Department',
        'priority_level'  => 'Priority level',
        'featured'        => 'Featured',
        'start_date'      => 'Start',
        'overdue'         => 'Overdue',
    ],

    'empty' => [
        'no_records'                      => 'No tasks',
        'no_records_description'          => 'Create a new task or switch the table view to see records.',
        'no_workload'                     => 'No workload',
        'no_workload_description'         => 'No open tasks are currently assigned to employees.',
        'no_open_tasks_chart'             => 'No open tasks',
        'no_open_tasks_chart_description' => 'There are no open tasks to show by status.',
    ],
];
