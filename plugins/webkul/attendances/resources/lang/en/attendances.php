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

    'self_check_in' => [
        'disabled'          => 'Self check-in is switched off. Please ask HR to record your attendance.',
        'ip_not_allowed'    => 'You can only check in from the company network.',
        'location_required' => 'Your location is required to check in. Allow location access in the browser and try again.',
        'outside_geofence'  => 'You are too far from the workplace to check in.',
        'too_soon'          => 'You have just recorded a punch. Wait a minute before trying again.',
    ],

    'my_attendance' => [
        'navigation'       => 'My attendance',
        'title'            => 'My attendance',
        'check_in'         => 'Check in',
        'check_out'        => 'Check out',
        'recorded_in'      => 'Check-in recorded',
        'recorded_out'     => 'Check-out recorded',
        'recorded_at'      => 'Time: :time',
        'location_hint'    => 'Your location is requested when you check in.',
        'today'            => 'Today',
        'no_punches_today' => 'No punches recorded today.',
        'month'            => 'This month',
        'no_days'          => 'No attendance days recorded this month yet.',
        'columns'          => [
            'time'      => 'Time',
            'direction' => 'Direction',
            'source'    => 'Source',
            'date'      => 'Date',
            'first_in'  => 'First in',
            'last_out'  => 'Last out',
            'worked'    => 'Worked',
            'late'      => 'Late (min)',
            'status'    => 'Status',
        ],
    ],

    'import' => [
        'navigation' => 'Import from device',
        'title'      => 'Import attendance from the device',
        'steps'      => [
            'upload'  => 'Upload file',
            'mapping' => 'Choose columns',
            'preview' => 'Preview and confirm',
        ],
        'fields' => [
            'file'           => 'Device export (CSV or XLSX)',
            'file_help'      => 'Up to 5 MB and 20,000 rows. The file is kept privately only until you confirm or start over.',
            'mapping_help'   => 'Employees are matched on the device user ID entered in the employee form. Times are read in the employee time zone.',
            'has_header'     => 'The first row is a header',
            'device_user_id' => 'Device user ID column',
            'timestamp'      => 'Date and time column',
            'direction'      => 'In/out column (optional)',
            'direction_help' => 'Accepts in/out or 1/0. It is stored for reference only: the first punch of the day counts as in and the last as out.',
            'column'         => 'Column :column',
        ],
        'actions' => [
            'next'       => 'Next',
            'back'       => 'Back',
            'preview'    => 'Preview',
            'confirm'    => 'Confirm import',
            'start_over' => 'Start over',
        ],
        'preview' => [
            'heading'        => 'Preview of :file',
            'total'          => 'Rows read',
            'new_punches'    => 'New punches',
            'duplicates'     => 'Duplicates',
            'invalid'        => 'Invalid rows',
            'unmatched'      => 'Unmatched device IDs (:count)',
            'unmatched_help' => 'No employee has these IDs as a device user ID. Their rows are skipped.',
            'ambiguous'      => 'Ambiguous device IDs (:count)',
            'ambiguous_help' => 'Several employees share these IDs, so their rows are skipped rather than guessed. Make the IDs unique and import again.',
            'rows_heading'   => 'Punches to import (showing :shown of :total)',
            'no_rows'        => 'Nothing new to import from this file.',
            'columns'        => [
                'employee'  => 'Employee',
                'device_id' => 'Device ID',
                'time'      => 'Time',
                'direction' => 'Direction',
            ],
        ],
        'imported'      => 'Import complete',
        'imported_body' => ':created punches created, :duplicates duplicates, :invalid invalid rows, :unmatched unmatched device IDs.',
        'errors'        => [
            'unsupported_type' => 'Only CSV and XLSX files can be imported.',
            'unreadable'       => 'The file could not be read. Check that it is a valid CSV or XLSX export.',
            'empty'            => 'The file has no rows.',
            'too_many_rows'    => 'The file has more than :max rows. Split it into smaller files.',
            'same_column'      => 'Choose a different column for each field.',
            'file_missing'     => 'The uploaded file is no longer available. Upload it again.',
        ],
    ],

    'console' => [
        'close_day' => [
            'invalid_date' => 'The date must be a valid date in the format Y-m-d.',
            'summary'      => 'Attendance closed for :date: :processed employees processed, :absent absences recorded.',
            'failed'       => 'Could not close the day of :employee: :message',
        ],
    ],
];
