<?php

return [
    'navigation'        => 'Salary Raises',
    'chart_heading'     => 'Salary timeline',
    'chart_empty'       => 'No salary raises recorded yet. Add a raise to see the timeline.',
    'empty_heading'     => 'No salary raises',
    'empty_description' => 'Record salary changes to build this employee\'s compensation history.',
    'reasons'           => [
        'annual_review'     => 'Annual review',
        'performance'       => 'Performance',
        'promotion'         => 'Promotion',
        'cost_of_living'    => 'Cost of living',
        'market_adjustment' => 'Market adjustment',
        'other'             => 'Other',
    ],
    'fields' => [
        'effective_date' => 'Effective date',
        'old_amount'     => 'Previous salary',
        'new_amount'     => 'New salary',
        'raise_amount'   => 'Raise amount',
        'raise_percent'  => 'Raise percent',
        'reason'         => 'Reason',
        'contract'       => 'Related contract',
        'approved_by'    => 'Approved by',
        'notes'          => 'Notes',
    ],
    'actions' => [
        'add' => 'Add salary raise',
    ],
];
