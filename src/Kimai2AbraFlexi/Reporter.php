<?php

/**
 * KimaiToAbraFlexi - Reporting Functions.
 *
 * @author Vítězslav Dvořák <info@vitexsoftware.cz>
 * @copyright  2020 Vitex Software
 */

namespace Kimai2AbraFlexi;

use Ease\Functions;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Description of Reporter
 *
 * @author vitex
 */
class Reporter
{
    /**
     * Prepare data for CSV Report
     *
     * @param array $timeEntries
     *
     * @return array data for CSV export
     */
    public static function csvData($timeEntries)
    {
        $columns = ['project', 'name', 'hours', 'duration'];
        $reportData[] = array_combine($columns, $columns);
        foreach ($timeEntries as $projectName => $projectTimeEntries) {
            foreach ($projectTimeEntries as $nazev => $duration) {
                $reportData[] = [
                    'project' => $projectName,
                    'name' => $nazev,
                    'hours' => round($duration / 3600, 3),
                    'duration' => FakturaVydana::formatMilliseconds($duration),
                ];
            }
        }
        return $reportData;
    }

    /**
     * Convert CSV data to CSV multiline string
     *
     * @param array $timeEntries
     *
     * @return string
     */
    public static function csvReport($timeEntries)
    {
        $csvData = self::csvData($timeEntries);
        $csvRows[] = implode(';', (current($csvData)));
        foreach ($csvData as $dataRow) {
            $csvRows[] = implode(';', $dataRow);
        }
        return implode("\n", $csvRows);
    }

    /**
     * Convert CSV data to CSV multiline string
     *
     * @param array $timeEntries
     *
     * @return string
     */
    public static function xlsReport($timeEntries, $fromto)
    {
        $csvData = self::csvData($timeEntries);
        $spreadsheet = self::spreadSheat("Timesheet Report", $fromto);
        self::header($spreadsheet, array_keys(current($csvData)));
        array_shift($csvData);
        self::values($spreadsheet, $csvData);
        self::addSum($spreadsheet, 3, $csvData);
        return self::xslsxString($spreadsheet);
    }

    /**
     * CSV Report with sums by project
     *
     * @param  array $timeEntries
     *
     * @return string
     */
    public static function cvsReportPerProject($timeEntries)
    {
        $csvRows = [];
        $columns = ['project', 'hours', 'duration'];
        $reportData[] = array_combine($columns, $columns);
        foreach ($timeEntries as $projectName => $projectTimeEntries) {
            $duration = array_sum($projectTimeEntries);
            $reportData[] = [
                'project' => $projectName,
                'hours' => round($duration / 3600000, 3),
                'duration' => FakturaVydana::formatMilliseconds($duration),
            ];
        }
        foreach ($reportData as $dataRow) {
            $csvRows[] = implode(';', $dataRow);
        }
        return implode("\n", $csvRows);
    }

    /**
     * CSV Report with sums by project
     *
     * @param  array $timeEntries
     *
     * @return string
     */
    public static function xlsReportPerProject($timeEntries, $fromto)
    {
        $columns = ['project', 'hours', 'duration'];
        $reportData[] = array_combine($columns, $columns);
        foreach ($timeEntries as $projectName => $projectTimeEntries) {
            $duration = array_sum($projectTimeEntries);
            $reportData[] = [
                'project' => $projectName,
                'hours' => round($duration / 3600000, 3),
                'duration' => FakturaVydana::formatMilliseconds($duration),
            ];
        }
        $spreadsheet = self::spreadSheat("Per Project Timesheet Report", $fromto);
        self::header($spreadsheet, $columns);
        array_shift($reportData);
        self::values($spreadsheet, $reportData);
        return self::xslsxString($spreadsheet);
    }

    /**
     *
     * @param string $spreadsheet
     *
     * @return string
     */
    public static function xslsxString($spreadsheet)
    {
        $writer = new Xlsx($spreadsheet);
        $filename = sys_get_temp_dir() . '/' . Functions::randomString() . '.xlsx';
        $writer->save($filename);
        $report = file_get_contents($filename);
        unlink($filename);
        return $report;
    }

    /**
     * Spreadsheet
     *
     * @param string $title
     * @param string $fromto
     *
     * @return Spreadsheet
     */
    public static function spreadSheat($title, $fromto)
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
                ->setCreator(\Ease\Shared::appName())
                ->setTitle($title)
                ->setSubject("Timesheet report " . $fromto)
                ->setDescription("Timesheet report " . $fromto)
                ->setKeywords("timesheet Kimai abraflexi invoice")
                ->setCategory("accounting");

        $spreadsheet->getActiveSheet()->getColumnDimension('A')->setWidth(60);
        $spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(60);

        return $spreadsheet;
    }

    /**
     * Establish report header
     *
     * @param Spreadsheet $spreadSheet
     * @param array $labels
     */
    public static function header($spreadSheet, $labels)
    {
        $spreadSheet->getDefaultStyle()
                ->getFont()
                ->setName('Arial')
                ->setSize(10)
                ->setColor(new Color(Color::COLOR_DARKGREEN));

        $sheet = $spreadSheet->getActiveSheet();
        foreach ($labels as $hid => $val) {
            $sheet->setCellValueByColumnAndRow($hid + 1, 1, $val);
        }
    }

    /**
     * Fill report with values
     *
     * @param Spreadsheet $spreadSheet
     * @param array $data
     */
    public static function values($spreadSheet, $data)
    {
        $spreadSheet->getDefaultStyle()
                ->getFont()
                ->setColor(new Color(Color::COLOR_BLACK));

        $sheet = $spreadSheet->getActiveSheet();
        foreach ($data as $rowId => $values) {
            foreach (array_values($values) as $colId => $value) {
                $sheet->setCellValueByColumnAndRow($colId + 1, $rowId + 2, $value);
            }
        }
    }

    /**
     * Add times sum
     *
     * @param Spreadsheet $spreadSheet
     * @param int $col
     * @param array $data
     */
    public static function addSum($spreadSheet, $col, $data)
    {
        $c = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
        $spreadSheet->getActiveSheet()->setCellValue($c . (count($data) + 2), '=SUM(' . $c . '2:' . $c . (count($data) + 1) . ')');
    }

}
