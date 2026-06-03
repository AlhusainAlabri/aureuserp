<?php

return [
    'install' => [
        'success' => 'My Notes plugin installed successfully.',
    ],

    'navigation' => [
        'label' => 'My Notes',
        'group' => 'My Notes',
    ],

    'topbar' => [
        'quick_add'            => 'Quick note',
        'heading'              => 'Add a note',
        'capture'              => 'Save quick note',
        'capture_placeholder'  => 'Capture a thought…',
        'open_notes'           => 'Open My Notes',
        'new_by_type'          => 'Create by type',
    ],

    'types' => [
        'text'      => 'Text',
        'checklist' => 'Checklist',
        'reminder'  => 'Reminder',
        'voice'     => 'Voice',
    ],

    'colors' => [
        'default' => 'Default',
        'red'     => 'Red',
        'orange'  => 'Orange',
        'yellow'  => 'Yellow',
        'green'   => 'Green',
        'teal'    => 'Teal',
        'blue'    => 'Blue',
        'purple'  => 'Purple',
        'pink'    => 'Pink',
        'gray'    => 'Gray',
    ],

    'form' => [
        'fields' => [
            'type'                => 'Type',
            'color'               => 'Color',
            'title'               => 'Title',
            'body'                => 'Body',
            'checklist_items'     => 'Checklist',
            'item_content'        => 'Item',
            'is_checked'          => 'Done',
            'reminder_at'         => 'Remind me on',
            'audio_path'          => 'Voice Memo',
            'audio_transcription' => 'Transcription',
            'tags'                => 'Tags',
            'link_meeting'        => 'Link to meeting',
            'link_project'        => 'Link to project',
            'link_correspondence' => 'Link to correspondence',
            'board_status'        => 'Board column',
            'is_pinned'           => 'Pin note',
        ],
    ],

    'actions' => [
        'new_note'       => 'New Note',
        'edit_note'      => 'Edit Note',
        'save'           => 'Save',
        'saving'         => 'Saving…',
        'cancel'         => 'Cancel',
        'delete'         => 'Delete',
        'pin'            => 'Pin',
        'unpin'          => 'Unpin',
        'archive'        => 'Archive',
        'unarchive'      => 'Unarchive',
        'edit'           => 'Edit',
        'view'           => 'View notes',
        'add_item'       => 'Add item',
        'confirm_delete' => 'Delete this note?',
    ],

    'canvas' => [
        'showing' => ':count notes',
    ],

    'card' => [
        'updated' => 'Updated :date',
    ],

    'toolbar' => [
        'create_heading'            => 'Create',
        'browse_heading'            => 'Find & organize',
        'quick_capture'             => 'Quick Note',
        'quick_capture_placeholder' => 'Capture a quick thought…',
        'new_note'                  => 'New Note',
        'search_placeholder'        => 'Search notes…',
        'sort'                      => 'Sort',
        'view'                      => 'View',
        'filter'                    => 'Filter',
    ],

    'filters' => [
        'all'      => 'All',
        'pinned'   => 'Pinned',
        'archived' => 'Archived',
        'other'    => 'Other',
    ],

    'sort' => [
        'newest'       => 'Newest',
        'oldest'       => 'Oldest',
        'title'        => 'Title (A–Z)',
        'reminder'     => 'Reminder date',
        'pinned_first' => 'Pinned first',
    ],

    'view_modes' => [
        'grid'     => 'Grid',
        'list'     => 'List',
        'board'    => 'Board',
        'calendar' => 'Calendar',
    ],

    'board_status' => [
        'inbox'        => 'Inbox',
        'in_progress'  => 'In progress',
        'waiting'      => 'Waiting',
        'done'         => 'Done',
    ],

    'board' => [
        'empty_column' => 'Drop notes here',
    ],

    'reminder_presets' => [
        'in_one_hour'  => 'In 1 hour',
        'tomorrow_9am' => 'Tomorrow 9 am',
        'next_monday'  => 'Next Monday',
    ],

    'reminder_status' => [
        'upcoming' => 'Upcoming',
        'overdue'  => 'Overdue',
        'sent'     => 'Sent',
    ],

    'voice' => [
        'record'            => 'Record',
        'record_hint'       => 'Record a voice memo or upload an audio file below.',
        'stop'              => 'Stop',
        'discard'           => 'Discard',
        'not_supported'     => 'Audio recording is not supported in this browser.',
        'permission_denied' => 'Microphone permission denied.',
        'upload_failed'     => 'Could not attach the recording. Try uploading a file instead.',
    ],

    'empty_state' => [
        'heading'             => 'Your notes live here',
        'description'         => 'Start capturing ideas, tasks, and reminders.',
        'filtered_description'=> 'No notes match this filter.',
        'no_checklist_items'  => 'No checklist items yet.',
    ],

    'calendar' => [
        'empty' => 'No reminders to show.',
    ],

    'more_items' => '+:count more',

    'notifications' => [
        'saved'     => 'Note saved',
        'deleted'   => 'Note deleted',
        'archived'  => 'Note archived',
        'unarchived'=> 'Note unarchived',
    ],

    'notify' => [
        'reminder_title' => 'Reminder: :title',
        'reminder_body'  => ':time — :body',
    ],

    'widget' => [
        'board_heading'        => 'My Notes',
        'upcoming_reminders'   => 'Upcoming Reminders',
        'no_upcoming_reminders'=> 'No upcoming reminders',
        'no_pinned'            => 'No pinned notes',
    ],

    'auto_title' => [
        'untitled'  => 'Untitled Note',
        'reminder'  => 'Reminder: :date',
        'checklist' => ':done/:total items completed',
    ],
];
