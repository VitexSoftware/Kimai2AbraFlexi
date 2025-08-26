<?php

/**
 * KimaiToAbraFlexi - Import Handler.
 *
 * @author Vítězslav Dvořák <info@vitexsoftware.cz>
 * @copyright  2020 Vitex Software
 */

namespace Kimai2AbraFlexi;

use AbraFlexi\Priloha;
use AbraFlexi\RO;
use DateTime;
use Ease\Exception;
use Ease\Shared;

/**
 * Description of Importer
 *
 * @author vitex
 */
class Importer extends FakturaVydana
{
    /**
     *
     * @var DateTime
     */
    public $since = null;

    /**
     *
     * @var DateTime
     */
    public $until = null;

    /**
     *
     * @var array<int>
     */
    public $projects = null;

    /**
     *
     * @var array
     */
    public $me = [];

    /**
     *
     * @var \Fiteco\KimaiClient\Configuration
     */
    private $config;

    /**
     *
     * @var \Fiteco\KimaiClient\Api\TimesheetApi
     */
    private $kimaiTimesheets;
    private $customers;
    private $kimaiProjects;
    private $workspaces;
    private $kimaiCustomers;

    /**
     *
     * @param string $init
     * @param array $options
     */
    public function __construct($init = null, $options = [])
    {
        $this->defaultUrlParams;
        parent::__construct($init, $options);
        $this->scopeToInterval(Shared::cfg('KIMAI_SCOPE', 'last_month'));
        $this->config = \Fiteco\KimaiClient\Configuration::getDefaultConfiguration()->setHost(Shared::cfg('KIMAI_HOST'))->setApiKey('X-AUTH-USER', Shared::cfg('KIMAI_USER'))->setApiKey('X-AUTH-TOKEN', Shared::cfg('KIMAI_TOKEN'));

        $this->kimaiTimesheets = new \Fiteco\KimaiClient\Api\TimesheetApi(new \GuzzleHttp\Client(), $this->config);

        $this->customers = $this->getCustomers();
        $this->projects = $this->getProjects();
    }

    /**
     * Obtain Projects
     *
     * @return array
     */
    public function getProjects()
    {
        $projects = [];
        $this->kimaiProjects = new \Fiteco\KimaiClient\Api\ProjectApi(new \GuzzleHttp\Client(), $this->config);
        try {
            $projectsRaw = $this->kimaiProjects->apiProjectsGet(null, null, null, $this->since, $this->until);

            foreach ($projectsRaw as $projectRaw) {
                $projects[$projectRaw->getId()]['name'] = $projectRaw->getName();
                $projects[$projectRaw->getId()]['cust'] = $projectRaw->getCustomer();
            }
        } catch (Exception $e) {
            echo 'Exception when calling ProjectApi->apiProjectsGet: ', $e->getMessage(), PHP_EOL;
        }

        return $projects;
    }

    /**
     * Obtain Customers
     *
     * @return array
     */
    public function getCustomers()
    {
        $customers = [];
        $this->kimaiCustomers = new \Fiteco\KimaiClient\Api\CustomerApi(new \GuzzleHttp\Client(), $this->config);
        try {
            $customersRaw = $this->kimaiCustomers->apiCustomersGet();
            foreach ($customersRaw as $customerRaw) {
                $customers[$customerRaw->getId()] = $customerRaw->getName();
            }
        } catch (Exception $e) {
            echo 'Exception when calling CustomerApi->apiCustomersGet: ', $e->getMessage(), PHP_EOL;
        }

        return $customers;
    }

    /**
     * Prepare processing interval
     *
     * @param string $scope
     * @throws Exception
     */
    public function scopeToInterval($scope)
    {
        switch ($scope) {
            case 'current_month':
                $this->since = new DateTime("first day of this month");
                $this->until = new DateTime();
                break;
            case 'last_month':
                $this->since = new DateTime("first day of last month");
                $this->until = new DateTime("last day of last month");
                break;

            case 'last_two_months':
                $this->since = (new DateTime("first day of last month"))->modify('-1 month');
                $this->until = (new DateTime("last day of last month"));
                break;

            case 'previous_month':
                $this->since = new DateTime("first day of -2 month");
                $this->until = new DateTime("last day of -2 month");
                break;

            case 'two_months_ago':
                $this->since = new DateTime("first day of -3 month");
                $this->until = new DateTime("last day of -3 month");
                break;

            case 'this_year':
                $this->since = new DateTime('first day of January ' . date('Y'));
                $this->until = new DateTime("last day of December" . date('Y'));
                break;

            case 'January':  //1
            case 'February': //2
            case 'March':    //3
            case 'April':    //4
            case 'May':      //5
            case 'June':     //6
            case 'July':     //7
            case 'August':   //8
            case 'September'://9
            case 'October':  //10
            case 'November': //11
            case 'December': //12
                $this->since = new DateTime('first day of ' . $scope . ' ' . date('Y'));
                $this->until = new DateTime('last day of ' . $scope . ' ' . date('Y'));
                break;

            default:
                throw new Exception('Unknown scope ' . $scope);
                break;
        }
        $this->since = $this->since->setTime(0, 0);
        $this->until = $this->until->setTime(0, 0);
    }

    /**
     *
     * @return FakturaVydana
     */
    public function import()
    {
        $this->logBanner('Import Initiated. From: ' . $this->since->format('c') . ' To: ' . $this->until->format('c'));

        $invoiceItems = [];
        $projects = [];
        $durations = [];

        $detailsData = $this->getAllDetailPages();

        foreach ($detailsData as $detail) {
            $project = empty($detail['project']) ? _('No Project') : $detail['project'];
            $task = $detail['description'];
            $duration = $detail['duration'];

            $durations[] = FakturaVydana::formatSeconds($duration) . ' ' . $project . ' ' . $task;

            if (!array_key_exists($project, $invoiceItems)) {
                $invoiceItems[$project] = [];
            }
            if (!array_key_exists($task, $invoiceItems[$project])) {
                $invoiceItems[$project][$task] = 0;
            }
            $invoiceItems[$project][$task] += $duration;
            $projects[$project] = $project;
        }

        $this->takeItemsFromArray($invoiceItems);

        $cc = empty(\Ease\Shared::cfg('ABRAFLEXI_CC')) ? '' : "\n" . 'cc:' . \Ease\Shared::cfg('ABRAFLEXI_CC');
        $this->setData([
            'typDokl' => RO::code(empty(Shared::cfg('ABRAFLEXI_TYP_FAKTURY')) ? 'FAKTURA' : Shared::cfg('ABRAFLEXI_TYP_FAKTURY')),
            'firma' => RO::code(Shared::cfg('ABRAFLEXI_CUSTOMER')),
            'popis' => sprintf(_('Work from %s to %s'), $this->since->format('Y-m-d'), $this->until->format('Y-m-d')),
            'duzpPuv' => RO::dateToFlexiDate($this->until)
        ]);

        try {
            $created = $this->sync();
            $fromto = $this->since->format('Y-m-d') . '_' . $this->until->format('Y-m-d');

            Priloha::addAttachment($this, sprintf(_('tasks_timesheet_%s.csv'), $fromto), Reporter::csvReport($invoiceItems), 'text/csv');
            Priloha::addAttachment($this, sprintf(_('projects_timesheet_%s.csv'), $fromto), Reporter::cvsReportPerProject($invoiceItems), 'text/csv');

            Priloha::addAttachment($this, sprintf(_('tasks_timesheet_%s.xlsx'), $fromto), Reporter::xlsReport($invoiceItems, $fromto), 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            Priloha::addAttachment($this, sprintf(_('projects_timesheet_%s.xlsx'), $fromto), Reporter::xlsReportPerProject($invoiceItems, $fromto), 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

            $this->addStatusMessage($this->getDataValue('kod') . ': ' . $this->getApiUrl(), $created ? 'success' : 'danger');
        } catch (\AbraFleix\Exception $exc) {
            $created = false;
        }

        return $created;
    }

    /**
     * One page of Report
     *
     * @param int $pageno
     *
     * @return array
     */
    public function getDetailsPage($pageno = 1)
    {
        $records = [];
        try {
            $recordsRaw = $this->kimaiTimesheets->apiTimesheetsGet(null, null, null, null, null, null, null, $pageno, 100, null, null, null, $this->since, $this->until);
            foreach ($recordsRaw as $recordRaw) {
                $records[$recordRaw->getId()]['activity'] = $recordRaw->getActivity();
                $records[$recordRaw->getId()]['description'] = $recordRaw->getDescription();
                $records[$recordRaw->getId()]['project'] = $this->projects[$recordRaw->getProject()]['name'];
                $records[$recordRaw->getId()]['customer'] = $this->customers[$this->projects[$recordRaw->getProject()]['cust']];
                $records[$recordRaw->getId()]['duration'] = $recordRaw->getDuration();
            }
        } catch (Exception $e) {
            echo 'Exception when calling TimesheetApi->apiTimesheetsGet: ', $e->getMessage(), PHP_EOL;
        }
        return $records;
    }

    /**
     * Get full set of results
     *
     * @param string $workspace
     *
     * @return array
     */
    public function getAllDetailPages()
    {
        $result = $this->getDetailsPage();
        /*
          $pages = ceil($result['total_count'] / $result['per_page']);
          $this->addStatusMessage(sprintf(_('reading page %s of %s'), 1, $pages), 'debug');
          $records = [];
          foreach ($result['data'] as $record) {
          $records[$record['start'] . '-' . $record['end']] = $record;
          }
          $result['data'] = $records;

          if ($pages > 1) {
          $page = 2;
          while ($page <= $pages) {
          sleep(1);
          $this->addStatusMessage(sprintf(_('reading page %s of %s'), $page, $pages), 'debug');
          $nextpage = $this->getDetailsPage($workspace, $page++);
          foreach ($nextpage['data'] as $record) {
          $result['data'][$record['start'] . '-' . $record['end']] = $record;
          }
          };
          }
         */
        return $result;
    }

    /**
     *
     * @return FakturaVydana
     */
    public function report()
    {
        $this->logBanner('Report Initiated. From: ' . $this->since->format('c') . ' To: ' . $this->until->format('c'));
        $entries = 0;
        $invoiceItems = [];
        $projects = [];
        $durations = [];

        foreach ($this->workspaces as $wsname => $workspace) {
            $this->addStatusMessage('Workspace: ' . (is_string($wsname) ? $wsname . ' ' : '') . $workspace, 'info');

            $detailsData = $this->getAllDetailPages();

            foreach ($detailsData['data'] as $detail) {
                $entries++;
                $project = empty($detail['project']) ? _('No Project') : $detail['project'];
                $task = $detail['description'];
                $duration = $detail['dur'];

                $durations[] = FakturaVydana::formatMilliseconds($duration) . ' ' . $project . ' ' . $task;

                if (!array_key_exists($project, $invoiceItems)) {
                    $invoiceItems[$project] = [];
                }
                if (!array_key_exists($task, $invoiceItems[$project])) {
                    $invoiceItems[$project][$task] = 0;
                }
                $invoiceItems[$project][$task] += $duration;
                $projects[$project] = $project;
            }
        }
        //            'popis' => sprintf(_('Work from %s to %s'), $this->since->format('Y-m-d'), $this->until->format('Y-m-d')),
        //            'poznam' => 'Kimai Workspace: ' . implode(',', $this->workspaces)

        $this->addStatusMessage($entries . ' entries processed');

        $fromto = $this->since->format('Y-m-d') . '_' . $this->until->format('Y-m-d');
        $saveto = \Ease\Shared::cfg('REPORTS_DIR');

        $tasksCsv = $saveto . sprintf(_('tasks_timesheet_%s.csv'), $fromto);
        $this->addStatusMessage($tasksCsv, file_put_contents($tasksCsv, Reporter::csvReport($invoiceItems)) ? 'success' : 'error');

        $projectsCsv = $saveto . sprintf(_('projects_timesheet_%s.csv'), $fromto);
        $this->addStatusMessage($projectsCsv, file_put_contents($projectsCsv, Reporter::cvsReportPerProject($invoiceItems)) ? 'success' : 'error');

        $tasksXLS = $saveto . sprintf(_('tasks_timesheet_%s.xlsx'), $fromto);
        $this->addStatusMessage($tasksXLS, file_put_contents($tasksXLS, Reporter::xlsReport($invoiceItems, $fromto)) ? 'success' : 'error');

        $projectsXLS = $saveto . sprintf(_('projects_timesheet_%s.xlsx'), $fromto);
        $this->addStatusMessage($projectsXLS, file_put_contents($projectsXLS, Reporter::xlsReportPerProject($invoiceItems, $fromto)) ? 'success' : 'error');

        return;
    }

}
