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

    'console' => [
        'close_day' => [
            'invalid_date' => 'The date must be a valid date in the format Y-m-d.',
            'summary'      => 'Attendance closed for :date: :processed employees processed, :absent absences recorded.',
            'failed'       => 'Could not close the day of :employee: :message',
        ],
    ],
];
