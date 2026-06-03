<?php

return [
    'sections' => [
        'departments' => 'Departments',
        'file_status' => 'File Status',
        'employment'  => 'Employment Details',
    ],
    'fields' => [
        'primary_job_responsibilities' => 'Primary Job Responsibilities',
    ],
    'document_types' => [
        'professional_conduct' => 'Professional Conduct Policy',
    ],
    'all_departments'         => 'All Departments',
    'primary_department'      => 'Primary Department',
    'show_closed_files'       => 'Show closed files',
    'close_file'              => 'Close Employee File',
    'reopen_file'             => 'Reopen Employee File',
    'file_closed_success'     => 'Employee file has been closed.',
    'file_reopened_success'   => 'Employee file has been reopened.',
    'account_closed'          => 'Your employee file has been closed. Contact HR for assistance.',
    'yes'                     => 'Yes',
    'no'                      => 'No',
    'file_upload_placeholder' => 'Drag & drop files or <span class="filepond--label-action">browse</span>',
    'navigation'              => [
        'more' => 'More',
    ],
    'closure_reasons'         => [
        'administrative' => 'Administrative',
        'ethical'        => 'Ethical',
        'resignation'    => 'Resignation',
        'retirement'     => 'Retirement',
        'contract_ended' => 'Contract ended',
        'other'          => 'Other',
    ],
    'file_status' => [
        'closed'        => 'File closed',
        'reason'        => 'Closure reason',
        'notes'         => 'Closure notes',
        'closed_at'     => 'Closed at',
        'closed_by'     => 'Closed by',
        'reopen_reason' => 'Reopen reason',
        'reopened_at'   => 'Reopened at',
        'reopened_by'   => 'Reopened by',
    ],
    'exceptions' => [
        'cannot_close'  => 'You are not allowed to close this employee file.',
        'cannot_reopen' => 'You are not allowed to reopen this employee file.',
    ],
    'close_helper'  => 'Please provide a clear reason — this will be a permanent record.',
    'reopen_helper' => 'Explain why this file is being reopened.',
    'notifications' => [
        'file_closed_title'    => 'Your employee file has been closed',
        'file_closed_body'     => 'Contact HR if you believe this is an error.',
        'file_closed_hr_title' => 'Employee file closed',
        'file_closed_hr_body'  => ':employee\'s file was closed by :by.',
    ],
];
