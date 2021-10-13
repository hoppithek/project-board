<?php

include dirname(__DIR__) . "/vendor/autoload.php";
use JiraRestApi\Issue\IssueService;

const HTML_BOILERPLATE = <<<'HTML'
<!DOCTYPE html>
<html>

<head>
  <link href="https://unpkg.com/tailwindcss@^2/dist/tailwind.min.css" rel="stylesheet">
</head>

<body>
</body>
    <div class="flex flex-row min-h-screen justify-center items-center">
    <div class="col-span-full xl:col-span-8 bg-white shadow-lg rounded-sm border border-gray-200">
      <header class="px-5 py-4 border-b border-gray-100">
        <h2 class="font-semibold text-gray-800">Project board</h2>
      </header>
      <div class="p-3">
        <div class="overflow-x-auto">
          <table class="table-auto w-full">
            
            <thead class="text-xs uppercase text-gray-400 bg-gray-50 rounded-sm">
                %s
            </thead>
            <tbody class="text-sm font-medium divide-y divide-gray-100">
                %s
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</html>
HTML;

const HTML_TABLE_HEAD = <<<'HTML'
<tr>
<th class="p-2">
  <div class="font-semibold text-left">Project</div>
</th>
<th class="p-2">
  <div class="font-semibold text-center">PM-Lead</div>
</th>
<th class="p-2">
  <div class="font-semibold text-center">IT-Lead</div>
</th>
</tr>
HTML;

const HTML_TABLE_ROW = <<<'HTML'
<tr>
<td class="p-2">
  <div class="items-center">%s</div>
</td>
<td class="p-2">
  <div class="text-center">%s</div>
</td>
<td>
  <div class="text-center">%s</div>
</td>
</tr>
HTML;

function renderView(EpicStruct ...$epicStructs): void
{
    $rows = '';
    foreach ($epicStructs as $epicStruct) {
        $rows .= sprintf(
            HTML_TABLE_ROW,
            $epicStruct->summary,
            $epicStruct->pmLeadName,
            $epicStruct->itLeadName,
        );
    }

    echo sprintf(
        HTML_BOILERPLATE,
        HTML_TABLE_HEAD,
        $rows
    );

    exit;
}

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

    renderView($issueDetails);

    #header('Content-Type: application/json');
    #echo json_encode([
    #    'issueDetails' => $issueDetails,
    #]);
} catch (JiraRestApi\JiraException $e) {
    print("Error Occured! " . $e->getMessage());
}
