<?php
ini_set('error_reporting', E_ALL);
ini_set('display_errors', 'on');

include dirname(__DIR__) . "/vendor/autoload.php";

class ViewRenderer {

    private const HTML_BOILERPLATE = <<<'HTML'
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

    private const HTML_TABLE_HEAD = <<<'HTML'
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
        <th class="p-2">
          <div class="font-semibold text-center">Status</div>
        </th>
        <th class="p-2">
          <div class="font-semibold text-center">Progress (HOAPI)</div>
        </th>
        <th class="p-2">
          <div class="font-semibold text-center">Time Spent (HOAPI)</div>
        </th>
        <th class="p-2">
          <div class="font-semibold text-center">Due Date</div>
        </th>
        HTML;

    private const HTML_TABLE_ROW = <<<'HTML'
        <tr>
        <td class="p-2">
          <div>%s</div>
        </td>
        <td class="p-2">
          <div>%s</div>
        </td>
        <td>
          <div>%s</div>
        </td>
        <td>
            <div class="text-center"><span
              class="text-xs font-semibold inline-block py-1 px-2 uppercase rounded-full text-%s-600 bg-%s-200">
              %s
          </span></div>
        </td>
        <td>
          <div class="text-center">%s</div>
          <div class="relative pt-1">
            <div class="overflow-hidden h-2 text-xs flex rounded bg-blue-200">
              <div style="width:%s%%"
                  class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center bg-blue-500">
              </div>
            </div>
          </div>
        </td>
        <td>
          <div>%s</div>
        </td>
        <td>
          <div class="text-right">%s</div>
        </td>
        </tr>
        HTML;

    public function render(EpicStruct ...$epicStructs): void
    {
        $renderedRows = '';
        foreach ($epicStructs as $epicStruct) {
            $pmLeadImage = file_get_contents('https://jira.check24.de/secure/useravatar?ownerId=' . $epicStruct->pmLeadKey);
            $pmLeadImageData = 'data:image/png;' . base64_encode($pmLeadImage);
            $itLeadImage = file_get_contents('https://jira.check24.de/secure/useravatar?ownerId=' . $epicStruct->itLeadKey);
            $itLeadImageData = 'data:image/png;' . base64_encode($itLeadImage);

            $renderedRows .= sprintf(
                self::HTML_TABLE_ROW,
                "{$epicStruct->summary} ({$epicStruct->key})",
                #$itLeadImageData,
                $epicStruct->pmLeadDisplayName,
                #$itLeadImageData,
                $epicStruct->itLeadDisplayName,
                $epicStruct->getStatusColor(),
                $epicStruct->getStatusColor(),
                $epicStruct->getStatus(),
                sprintf('%s (%s)', $epicStruct->getProgressFormatted(), $epicStruct->getProgressFormatted('HOAPI-')),
                $epicStruct->getProgressPercent(),
                sprintf('%s (%s)', $epicStruct->getTimeSpent(), $epicStruct->getTimeSpent('HOAPI-')),
                $epicStruct->dueDate()
            );
        }

        echo sprintf(
            self::HTML_BOILERPLATE,
            self::HTML_TABLE_HEAD,
            $renderedRows
        );

        exit;
    }
}

class EpicStruct extends \stdClass
{
    private const STATUS_CATEGORY_NEW = 2;
    private const STATUS_CATEGORY_IN_PROGRESS = 4;
    private const STATUS_CATEGORY_DONE = 3;

    public string $key;
    public string $itLeadDisplayName;
    public string $itLeadKey;
    public string $pmLeadDisplayName;
    public string $pmLeadKey;
    public string $summary;
    public $issues;
    public \JiraRestApi\Issue\IssueStatus $status;

    private function __construct() {$this->issues = [];}

    public static function fromIssue(JiraRestApi\Issue\Issue $issue): self {
        $struct = new self;
        $struct->key = $issue->key;
        $struct->itLeadDisplayName = $issue->fields->getCustomFields()['customfield_10603']->displayName ?? 'no it lead set';
        $struct->itLeadKey = $issue->fields->getCustomFields()['customfield_10603']->key ?? '';
        $struct->pmLeadDisplayName = $issue->fields->assignee->displayName ?? 'no pm lead set';
        $struct->pmLeadKey = $issue->fields->assignee->key ?? '';
        $struct->summary = $issue->fields->summary ?? 'no summary set';
        $struct->status = $issue->fields->status;
        $struct->dueDate = $issue->fields->duedate;

        return $struct;
    }

    public function addIssue($issue) {
        $this->issues[] = $issue;
    }

    public function getStatus(): string {
        return $this->status->name;
    }

    public function getStatusColor(): string {
        return match ($this->status->statuscategory->id) {
            self::STATUS_CATEGORY_DONE => 'green',
            self::STATUS_CATEGORY_IN_PROGRESS => 'red',
            self::STATUS_CATEGORY_NEW => 'blue',
            default => 'blue',
        };
    }

    private function getProgress(string $keyPrefix = ''): array {
        $len = strlen($keyPrefix);
        $countMatching = 0;
        $countMatchingDone = 0;
        foreach ($this->issues as $issue) {
            if ($keyPrefix && substr($issue->key, 0, $len) !== $keyPrefix)
                continue;

            $countMatching++;
            if (($issue->fields->status->statuscategory->id ?? 0) === self::STATUS_CATEGORY_DONE)
                $countMatchingDone++;
        }

        return [$countMatchingDone, $countMatching];
    }
    public function getProgressFormatted(string $keyPrefix = ''): string {

        list($countMatchingDone, $countMatching) =  $this->getProgress($keyPrefix);

        return sprintf(
            '%d/%d',
            $countMatchingDone,
            $countMatching
        );
    }

    public function getProgressPercent(string $keyPrefix = ''): int {
        list($countMatchingDone, $countMatching) =  $this->getProgress($keyPrefix);
        return $countMatching ? round(((float)$countMatchingDone/(float)$countMatching)*100) : 0;
    }

    public function getTimeSpent(string $keyPrefix = ''): string {
        $totalSeconds = 0;
        $len = strlen($keyPrefix);
        foreach ($this->issues as $issue) {
            if ($keyPrefix && substr($issue->key, 0, $len) !== $keyPrefix) continue;
            foreach ($issue->fields->worklog->worklogs as $worklog) {
                $totalSeconds += $worklog->timeSpentSeconds;
            }
        }
        return (new DateTime('@' . $totalSeconds))->format('d\d h\h i\m');
    }

    public function dueDate(): string {
        return $this->dueDate ? (new DateTime($this->dueDate))->format('d.m.Y') : '--';
    }
}

class ProjectBoard {

    private ViewRenderer $renderer;
    private JiraRestApi\Issue\IssueService $jira;
    private \stdClass $config;

    public function __construct(ViewRenderer $renderer, JiraRestApi\Issue\IssueService $jira, \stdClass $config) {
        $this->renderer = $renderer;
        $this->jira = $jira;
        $this->config = $config;
    }

    public function run(): void {
        try {
            $epics = $this->getProjectIssues();

            $this->addLinkedIssues($epics);

            $this->renderer->render(...$epics);

        } catch (JiraRestApi\JiraException $e) {
            print("Error Occured! " . $e->getMessage());
        }
    }

    private function getProjectIssues(): array {

        $result = $this->jira->search($this->config->jqlQuery, 0, 50, $this->config->fields, $this->config->expand);

        $epics = [];
        foreach ($result->issues as $projectIssue) {
            try {
                $epics[$projectIssue->key] = EpicStruct::fromIssue($projectIssue);
            } catch (\Throwable $ex) {
                // ignore for now
            }
        }

        return $epics;
    }

    /**
     * @param EpicStruct[] $epics
     * @return void
     * @throws JsonMapper_Exception
     * @throws \JiraRestApi\JiraException
     */
    private function addLinkedIssues(array $epics)
    {
        $fields = ['worklog', 'assignee', 'developer', 'status'];
        $expand = ['worklog'];
        foreach ($epics as $key => $epic) {
            $jql = sprintf('cf[%s]=%s', $this->config->epicLinkCustomField, $key);
            $issuesInEpic = $this->jira->search($jql, 0, 50, $fields, $expand);

            foreach ($issuesInEpic->issues as $issue) {
                try {
                    $epic->addIssue($issue);
                } catch (\Throwable $ex) {
                    // ignore for now
                }
            }
        }
    }
}

(new ProjectBoard(new ViewRenderer, new JiraRestApi\Issue\IssueService, include '../config.php'))->run();
