<?php

return [
    'submitted' => [
        'title' => 'Asset borrowing request submitted',
        'body'  => ':employee requested to borrow :asset (:number). Due date: :due_at.',
    ],
    'approved' => [
        'title' => 'Asset borrowing request approved',
        'body'  => 'Your request to borrow :asset (:number) has been approved. Due date: :due_at.',
    ],
    'rejected' => [
        'title' => 'Asset borrowing request rejected',
        'body'  => 'Your request to borrow :asset (:number) was rejected.',
    ],
    'due_reminder' => [
        'title' => 'Asset return due soon',
        'body'  => ':asset (:number) borrowed by :employee is due on :due_at.',
    ],
    'overdue' => [
        'title' => 'Asset borrowing overdue',
        'body'  => ':asset (:number) borrowed by :employee was due on :due_at.',
    ],
    'returned' => [
        'title' => 'Asset returned',
        'body'  => ':asset (:number) borrowed by :employee has been returned.',
    ],
];
