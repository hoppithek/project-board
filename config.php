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
        'timetracking',
    ],
    'expand' => ['worklog', 'customfield_10603'],
    'subAggregateKeyPrefixes' => [],
    'statusCategoryIdSortOrder' => [
        EpicStruct::STATUS_CATEGORY_IN_PROGRESS,
        EpicStruct::STATUS_CATEGORY_NEW,
        EpicStruct::STATUS_CATEGORY_DONE,
    ],
];
