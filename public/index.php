<?php

include dirname(__DIR__) . "/vendor/autoload.php";
use JiraRestApi\Issue\IssueService;

try {
    $issueService = new IssueService();

    $fields = [
        // default: '*all'
        'summary',
        'assignee',
        'developer',
        'customfield_10006',
        'customfield_10603',
        'key',
    ];
    $expand = [];

    $fieldNameMap = [
        'customfield_10603' => 'IT Lead',
        'assignee' => 'PM Lead',
        'summary' => 'Projekt',
        'key' => 'Epic',
    ];

    #$issuesResult = $issueService->search('filter=64306', 0,50, $fields, $expand);
    $issuesResult = $issueService->search('key=HOAPI-23762', 0,50, $fields, $expand);

    $issueDetails = [];
    foreach ($issuesResult->issues as $issue) {
        $key = $issue->key;
        $issueDetails[$key] = $issueService->get($key, ['fields' => ['*all'], 'expand' => $expand]);
        break;
    }

    header('Content-Type: application/json');
    echo json_encode([
        #'issues' => $issuesResult,
        'issueDetails' => $issueDetails,
    ]);
} catch (JiraRestApi\JiraException $e) {
    print("Error Occured! " . $e->getMessage());
}
