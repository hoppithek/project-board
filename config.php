<?php

return (object)[
    'fields' => [
        // default: '*all'
        'summary',
        'assignee',
        'developer',
        'customfield_10006',
        'customfield_10603',
        'key',
    ],
    'expand' => ['work_log'],
    'fieldLabels' =>  [
        'customfield_10603' => 'IT Lead',
        'assignee' => 'PM Lead',
        'summary' => 'Projekt',
        'key' => 'Epic',
    ],
];
