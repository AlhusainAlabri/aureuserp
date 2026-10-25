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
            'invalid_upload'   => 'The uploaded file could not be used. Upload it again.',
        ],
    ],

    'day' => [
        'sections' => [
            'entry' => 'Manual entry',
        ],
        'entry_hint'      => 'Use this to add or correct a day by hand. The punches the employee recorded stay as they are; the figures below replace the calculated ones and the day is no longer recalculated.',
        'local_time_hint' => 'Enter the time as shown on the clock in the employee time zone.',
        'overtime_hint'   => 'Overtime of at least the minimum creates a pending overtime request for approval.',
        'note_hint'       => 'Say why the day was entered or changed. It is kept with the record.',
        'columns'         => [
            'employee'           => 'Employee',
            'date'               => 'Date',
            'first_in'           => 'First in',
            'last_out'           => 'Last out',
            'worked'             => 'Worked (h:mm)',
            'late'               => 'Late (min)',
            'early_leave'        => 'Early leave (min)',
            'overtime'           => 'Overtime (min)',
            'status'             => 'Status',
            'override_note'      => 'Reason',
            'is_manual_override' => 'Entered by hand',
        ],
        'tabs' => [
            'all'      => 'All',
            'late'     => 'Late',
            'absent'   => 'Absent',
            'overtime' => 'Overtime',
        ],
        'filters' => [
            'month'    => 'Month',
            'employee' => 'Employee',
        ],
        'actions' => [
            'export_csv' => 'Export CSV',
            'add_entry'  => 'Add a day',
        ],
        'errors' => [
            'duplicate'            => 'This employee already has a record for this date. Edit that record instead.',
            'employee_not_allowed' => 'You cannot enter attendance for this employee.',
        ],
    ],

    'overtime' => [
        'columns' => [
            'employee'   => 'Employee',
            'date'       => 'Date',
            'hours'      => 'Overtime (h:mm)',
            'day_type'   => 'Day type',
            'multiplier' => 'Rate',
            'status'     => 'Status',
            'decided_by' => 'Decided by',
            'decided_at' => 'Decided at',
            'note'       => 'Note',
        ],
        'tabs' => [
            'all' => 'All',
        ],
        'fields' => [
            'note'      => 'Note',
            'note_hint' => 'Optional. Kept with the decision.',
        ],
        'actions' => [
            'approve'          => 'Approve',
            'reject'           => 'Reject',
            'approve_heading'  => 'Approve overtime',
            'reject_heading'   => 'Reject overtime',
            'approve_selected' => 'Approve selected',
        ],
        'notifications' => [
            'approved'      => 'Overtime approved',
            'rejected'      => 'Overtime rejected',
            'bulk_approved' => ':count requests approved',
            'bulk_skipped'  => ':count requests were left as they are because they were already decided or are your own.',
        ],
        'errors' => [
            'already_decided' => 'This overtime request has already been decided.',
            'own_request'     => 'You cannot decide your own overtime request. Ask another manager or HR.',
        ],
    ],

    'monthly' => [
        'navigation'        => 'Monthly summary',
        'title'             => 'Monthly attendance summary',
        'period'            => 'Period',
        'year'              => 'Year',
        'month'             => 'Month',
        'export'            => 'Export CSV',
        'empty'             => 'No employees to show for this month.',
        'totals'            => 'Total',
        'no_schedule'       => 'This employee has no work schedule, so there are no figures.',
        'capped'            => 'Showing the first :shown of :total employees. Narrow the list or export per team to see the rest.',
        'employees_counted' => 'The total covers :count employees with a work schedule. Overtime counts approved requests only.',
        'columns'           => [
            'employee'       => 'Employee',
            'working_days'   => 'Working days',
            'absent_days'    => 'Absent days',
            'late_minutes'   => 'Late (min)',
            'overtime_hours' => 'Approved overtime (h)',
        ],
    ],

    'settings' => [
        'navigation' => 'Attendance settings',
        'title'      => 'Attendance settings',
        'sections'   => [
            'rules'        => 'Lateness and overtime',
            'self_checkin' => 'Self check-in',
            'geofence'     => 'Location',
        ],
        'fields' => [
            'grace_minutes'               => 'Grace period (minutes)',
            'grace_minutes_hint'          => 'Arrivals within this many minutes of the shift start are not late.',
            'overtime_multiplier_normal'  => 'Overtime rate: working day',
            'overtime_multiplier_rest'    => 'Overtime rate: weekly rest day',
            'overtime_multiplier_holiday' => 'Overtime rate: public holiday',
            'multiplier_hint'             => 'Between 1 and 5, up to two decimals. Applies to new overtime requests only.',
            'minimum_overtime_minutes'    => 'Minimum overtime (minutes)',
            'minimum_overtime_hint'       => 'Shorter overtime does not create a request.',
            'workday_close_time'          => 'Day closing time',
            'workday_close_time_hint'     => 'The time of day at which a workday is considered closed (HH:MM).',
            'allow_self_checkin'          => 'Allow self check-in',
            'allowed_ip_ranges'           => 'Allowed IP addresses or ranges',
            'allowed_ip_ranges_hint'      => 'Leave empty to allow any address. Examples: 203.0.113.7, 10.0.0.0/24. These rules only work when the application sees the real address of the employee, because no trusted proxy is configured.',
            'geofence_enabled'            => 'Restrict by location',
            'geofence_hint'               => 'Self check-in is allowed only within the radius of this point.',
            'latitude'                    => 'Latitude',
            'longitude'                   => 'Longitude',
            'radius_metres'               => 'Radius (metres)',
        ],
        'errors' => [
            'invalid_ip' => '":value" is not a valid IP address or CIDR range.',
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
