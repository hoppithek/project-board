<?php

return (object)[
    'jqlQuery' => 'filter=64304',
    'epicLinkCustomField' => '10006',
    'fields' => [
        'summary',
        'assignee',
        'customfield_10603', // developer
        'issuetype',
        'priority',
        'worklog',
        'status',
        'duedate',
    ],
    'expand' => ['worklog', 'customfield_10603'],
    'subAggregateKeyPrefixes' => [],
];
