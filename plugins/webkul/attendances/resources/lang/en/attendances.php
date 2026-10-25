<?php

return [
    'navigation' => [
        'group' => 'Attendance',
    ],

    'install' => [
        'success' => 'Attendance plugin installed successfully.',
    ],

    'models' => [
        'attendance_punch'  => 'Attendance Punch',
        'attendance_day'    => 'Attendance Day',
        'overtime_request'  => 'Overtime Request',
        'import_batch'      => 'Import Batch',
    ],

    'models_plural' => [
        'attendance_punch'  => 'Attendance Punches',
        'attendance_day'    => 'Attendance Days',
        'overtime_request'  => 'Overtime Requests',
        'import_batch'      => 'Import Batches',
    ],

    'console' => [
        'close_day' => [
            'invalid_date' => 'The date must be a valid date in the format Y-m-d.',
            'summary'      => 'Attendance closed for :date: :processed employees processed, :absent absences recorded.',
            'failed'       => 'Could not close the day of :employee: :message',
        ],
    ],
];
