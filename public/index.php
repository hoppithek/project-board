<?php

include dirname(__DIR__) . "/vendor/autoload.php";
use JiraRestApi\Issue\IssueService;

class EpicStruct extends \stdClass
{
    public string $key;
    public string $itLeadName;
    public string $pmLeadName;
    public string $summary;

    private function __construct() {}

    public static function fromIssue(JiraRestApi\Issue\Issue $issue): self {
        $struct = new self;
        $struct->key = $issue->key;
        $struct->itLeadName = $issue->fields->getCustomFields()['customfield_10603']->displayName ?? 'no it lead set';
        $struct->pmLeadName = $issue->fields->assignee->displayName ?? 'no pm lead set';
        $struct->summary = $issue->fields->summary ?? 'no summary set';

        return $struct;
    }
}


try {
    $config = include '../config.php';
    $issueService = new IssueService();

    #$issuesResult = $issueService->search('filter=64306', 0,50, $fields, $expand);
    $issuesResult = $issueService->search('filter=64306', 0,50, ['key'], []);

    $issueDetails = [];
    foreach ($issuesResult->issues as $issue) {
        try {
            $key = $issue->key;
            $issueDetails[$key] = EpicStruct::fromIssue($issueService->get($key, ['fields' => $config->fields, 'expand' => $config->expand]));
        } catch (\Throwable $ex) {
            // ignore for now
        }
    }

    header('Content-Type: application/json');
    echo json_encode([
        'issueDetails' => $issueDetails,
    ]);
} catch (JiraRestApi\JiraException $e) {
    print("Error Occured! " . $e->getMessage());
}
