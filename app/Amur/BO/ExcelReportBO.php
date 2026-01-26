<?php

namespace App\Amur\BO;

use App\Amur\Bean\IDB;
use App\Amur\Bean\ILogger;
use App\Amur\Utilities\Utils;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class ExcelReportBO {

    private $logger;
    private $db;

    public function __construct(ILogger $logger, IDB $db) {
        $this->logger = $logger;
        $this->db = $db;
    }

    public function test() {

        $sheets = array();

        //Sheet 1
        $columns = array();
        $columns[]['name'] = 'Column1';
        $columns[]['name'] = 'Column2';
        $columns[]['name'] = 'Column3';
        $columns[]['name'] = 'Column4';
        
        $rows = array();

        $content = array();
        $content[] = 'Row 1 Col 1';
        $content[] = 'Row 1 Col 2';
        $content[] = 'Row 1 Col 3';
        $content[] = 'Row 1 Col 4';
        $rows[]['content'] = $content;

        $content = array();
        $content[] = 'Row 2 Col 1';
        $content[] = 'Row 2 Col 2';
        $content[] = 'Row 2 Col 3';
        $content[] = 'Row 2 Col 4';
        $rows[]['content'] = $content;

        $content = array();
        $content[] = 'Row 3 Col 1';
        $content[] = 'Row 3 Col 2';
        $content[] = 'Row 3 Col 3';
        $content[] = 'Row 3 Col 4';
        $rows[]['content'] = $content;

        $sheets[] = [
            'title' => 'Sheet 1',
            'columns' => $columns,
            'rows' => $rows
        ];

        //Sheet 1
        $columns = array();
        $columns[]['name'] = 'Column1';
        $columns[]['name'] = 'Column2';
        $columns[]['name'] = 'Column3';
        
        $rows = array();

        $content = array();
        $content[] = 'Row 1 Col 1';
        $content[] = 'Row 1 Col 2';
        $content[] = 'Row 1 Col 3';
        $rows[]['content'] = $content;

        $content = array();
        $content[] = 'Row 2 Col 1';
        $content[] = 'Row 2 Col 2';
        $content[] = 'Row 2 Col 3';
        $rows[]['content'] = $content;

        $content = array();
        $content[] = 'Row 3 Col 1';
        $content[] = 'Row 3 Col 2';
        $content[] = 'Row 3 Col 3';
        $rows[]['content'] = $content;

        $sheets[] = [
            'title' => 'Sheet 2',
            'columns' => $columns,
            'rows' => $rows
        ];

        $filePath = $this->genericMultipleSheets($sheets);

        return $filePath;
    }

    public function genericMultipleSheets($sheets) {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->removeSheetByIndex(0);

        foreach($sheets as $k => $sheet) {
            $newWorksheet = new Worksheet($spreadsheet, $sheet['title']);
            $spreadsheet->addSheet($newWorksheet, $k);
            $spreadsheet->setActiveSheetIndex($k);

            foreach($sheet['columns'] as $key => $value) {
                $spreadsheet->getActiveSheet()->setCellValue(Utils::toColumn($key) . '1', $value['name']);
            }

            foreach($sheet['rows'] as $key => $value) {
                foreach($value['content'] as $contentKey => $content) {
                    $spreadsheet->getActiveSheet()->setCellValue(Utils::toColumn($contentKey) . ($key + 2), $content); //todo
                }
            }
        }

        if (!Storage::disk('local')->exists('pending_renewals')) {
            Storage::disk('local')->makeDirectory('pending_renewals');
        }

        $files = Storage::disk('local')->files('pending_renewals');

        if (!empty($files)) {
            foreach ($files as $file) {
                Storage::disk('local')->delete($file);  
            }
        }

        $filePath = 'pending_renewals/' . md5(uniqid()) . '.xlsx';
        $destination = Storage::disk('local')->getAdapter()->getPathPrefix() . '/' . $filePath;

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($destination);

        return $filePath;
    }

    public function formatMultipleSheets($sheets) {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->removeSheetByIndex(0);

        foreach ($sheets as $k => $sheet) {
            $newWorksheet = new Worksheet($spreadsheet, $sheet['title']);
            $spreadsheet->addSheet($newWorksheet, $k);
            $spreadsheet->setActiveSheetIndex($k);
            $activeSheet = $spreadsheet->getActiveSheet();
            $highestColumnIndex = 0;

            // Headers
            foreach ($sheet['columns'] as $key => $value) {
                $cell = Utils::toColumn($key) . '1';
                
                $activeSheet->setCellValue($cell, $value['name']);
                
                $activeSheet->getStyle($cell)->getFont()->setBold(true);
                
                // background color
                if (isset($value['bg'])) {
                    $activeSheet->getStyle($cell)->getFill()
                        ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                        ->getStartColor()->setARGB($value['bg']);
                }
                
                // text color
                if (isset($value['color'])) {
                    $activeSheet->getStyle($cell)->getFont()
                        ->getColor()->setARGB($value['color']);
                }

                if ($key > $highestColumnIndex) {
                    $highestColumnIndex = $key;
                }
            }

            // Rows
            foreach ($sheet['rows'] as $rowIndex => $value) {
                foreach ($value['content'] as $colIndex => $content) {

                    $cell = Utils::toColumn($colIndex) . ($rowIndex + 2);

                    if (is_array($content)) {
                        $cellValue = $content['value'] ?? '';
                        $formula   = $content['formula'] ?? null;
                        $bgColor   = $content['bg']    ?? null;
                        $isBold    = $content['isBold']    ?? null;
                        $textColor = $content['textColor'] ?? null;
                        $type      = $content['type']  ?? null;
                        $align     = $content['align'] ?? null;
                        $textColorComparison   = $content['textColorComparison'] ?? null;
                    } else {
                        $cellValue = $content;
                        $formula   = null;
                        $bgColor   = null;
                        $isBold   = false;
                        $textColor = null;
                        $type      = null;
                        $textColorComparison   = null;
                        $align     = null;
                    }

                    if ($formula) {
                        $activeSheet->setCellValue($cell, $formula);
                        $cellValue = $activeSheet->getCell($cell)->getCalculatedValue();
                    } else {
                        $activeSheet->setCellValue($cell, $cellValue);
                    }

                    if ($textColorComparison && is_numeric($cellValue)) {
                        if (isset($textColorComparison['gt']) && $cellValue >= $textColorComparison['gt']) {
                            $textColor = $textColorComparison['gtTextColor'];
                        } elseif (isset($textColorComparison['lt']) && $cellValue < $textColorComparison['lt']) {
                            $textColor = $textColorComparison['ltTextColor'];
                        }
                    }

                    // Background color
                    if ($bgColor) {
                        $activeSheet->getStyle($cell)->getFill()
                            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
                        $activeSheet->getStyle($cell)->getFill()
                            ->getStartColor()->setARGB($bgColor);
                    }

                    // Bold Text
                    if ($isBold) {
                        $activeSheet->getStyle($cell)->getFont()->setBold(true);
                    }

                    // Text color
                    if ($textColor) {
                        $activeSheet->getStyle($cell)->getFont()->getColor()->setARGB($textColor);
                    }
                    
                    // Number / date / currency format handling
                    if ($type) {
                        switch ($type) {
                            case 'number':
                                $activeSheet->getStyle($cell)->getNumberFormat()
                                    ->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                                break;

                            case 'currency':
                                $activeSheet->getStyle($cell)->getNumberFormat()
                                    ->setFormatCode('"$"#,##0.00');
                                break;

                            case 'percent':
                                $activeSheet->getStyle($cell)->getNumberFormat()
                                    ->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_PERCENTAGE_00);
                                break;

                            case 'date':
                                $excelDate = \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel(strtotime($cellValue));
                                $activeSheet->setCellValue($cell, $excelDate);
                                $activeSheet->getStyle($cell)->getNumberFormat()
                                    ->setFormatCode('yyyy-mm-dd');
                                break;

                            case 'string':
                            default:
                                $activeSheet->getStyle($cell)->getNumberFormat()
                                    ->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT);
                                break;
                        }
                    }

                    // Alignment
                    if ($align) {
                        $alignment = $activeSheet->getStyle($cell)->getAlignment();

                        switch (strtolower($align)) {
                            case 'left':
                                $alignment->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
                                break;
                            case 'center':
                                $alignment->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                                break;
                            case 'right':
                                $alignment->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
                                break;
                        }
                    }
                }
            }

            // Auto-size columns / rows
            $maxWidth = 40;

            $activeSheet->getStyle(
                $activeSheet->calculateWorksheetDimension()
            )->getAlignment()->setWrapText(true);

            $highestColumnIndex = $activeSheet->getHighestColumn(); 
            $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumnIndex);

            for ($col = 1; $col <= $highestColumnIndex; $col++) {
                $colLetter = Coordinate::stringFromColumnIndex($col);
                $activeSheet->getColumnDimension($colLetter)->setAutoSize(true);
            }

            $activeSheet->calculateColumnWidths();

            for ($col = 1; $col <= $highestColumnIndex; $col++) {
                $colLetter = Coordinate::stringFromColumnIndex($col);
                $dimension = $activeSheet->getColumnDimension($colLetter);

                if ($dimension->getWidth() > $maxWidth) {
                    $dimension->setAutoSize(false);
                    $dimension->setWidth($maxWidth);
                } 
            }

            $activeSheet->getDefaultRowDimension()->setRowHeight(-1);
        }

        return $spreadsheet;
    }

    private $titleStyle = [
        'font' => [
            'bold' => true,
        ],
        'alignment' => [
            'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
        ]
    ];

    private $headerStyle = [
        'font' => [
            'bold' => true,
        ]
    ];
}
