<?php

return [
    'navigation'         => 'My Self-Assessment',
    'navigation_manage'  => 'Self-Assessments',
    'form'               => [
        'section' => 'Monthly Self-Assessment',
    ],
    'history_heading'   => 'Submission History',
    'empty_heading'     => 'No self-assessments',
    'empty_description' => 'Submit your monthly self-evaluation using the form above.',
    'period_label'      => ':month/:year',
    'fields'            => [
        'period'             => 'Period',
        'period_year'        => 'Year',
        'period_month'       => 'Month',
        'employee_comments'  => 'Your Comments',
        'attachment'         => 'Evaluation File',
        'status'             => 'Status',
        'submitted_at'       => 'Submitted At',
        'manager_feedback'   => 'Manager Feedback',
        'reviewed_by'        => 'Reviewed By',
        'reviewed_at'        => 'Reviewed At',
    ],
    'months' => [
        1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
        5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
        9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
    ],
    'status' => [
        'draft'     => 'Draft',
        'submitted' => 'Submitted',
        'reviewed'  => 'Reviewed',
    ],
    'actions' => [
        'submit' => 'Submit Assessment',
        'review' => 'Add Manager Feedback',
    ],
    'notifications' => [
        'submitted'      => 'Self-assessment submitted successfully.',
        'no_employee'    => 'No employee record linked to your account.',
        'reminder_title' => 'Monthly self-assessment due',
        'reminder_body'  => 'Please submit your self-assessment for :month :year.',
        'reviewed_title' => 'Self-assessment reviewed',
        'reviewed_body'  => 'Your self-assessment for :period has been reviewed.',
        'review_saved'   => 'Manager feedback saved.',
    ],
    'mail' => [
        'reminder_subject' => 'Reminder: Self-assessment for :month :year',
        'reminder_heading' => 'Monthly Self-Assessment Reminder',
        'reminder_body'    => 'Dear :name, please submit your self-assessment for :month :year.',
        'submit_button'    => 'Submit Self-Assessment',
    ],
];
