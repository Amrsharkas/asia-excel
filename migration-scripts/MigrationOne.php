<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use DB;
use PHPExcel_IOFactory;
use PHPExcel_Reader_IReadFilter;
use PHPExcel_CachedObjectStorageFactory;
use PHPExcel_Settings;

class MigrationOne extends Command {

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mig:s1';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display an inspiring quote';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() {
        $cacheMethod = PHPExcel_CachedObjectStorageFactory::cache_to_sqlite3;
        if (PHPExcel_Settings::setCacheStorageMethod($cacheMethod)) {
            echo date('H:i:s'), " Enable Cell Caching using ", $cacheMethod, " method", PHP_EOL;
        } else {
            echo date('H:i:s'), " Unable to set Cell Caching using ", $cacheMethod, " method, reverting to memory", PHP_EOL;
        }

        $general_student_sheet = public_path('large.xlsx');
        $objReader = PHPExcel_IOFactory::createReader('Excel2007');
        $objReader->setLoadSheetsOnly('Report');
        $objReader->setReadDataOnly(true);
//        $chunkFilter = new chunkReadFilter();
//        $objReader->setReadFilter($chunkFilter);
//        $chunkFilter->setRows(3, 15000);
        $t = time();
        $objPHPExcel = $objReader->load($general_student_sheet);
        $sheet = $objPHPExcel->getActiveSheet();
        echo $sheet->getHighestRow();
        echo PHP_EOL;
        echo time() - $t;
//        $duplicates = [];
//        for ($rowNum = 3; $rowNum < $sheet->getHighestRow(); $rowNum++) {
//            if (($skype = $sheet->getCell('N' . $rowNum)->getValue()) == Null && ($name = $sheet->getCell('B' . $rowNum)->getValue()) != NULL) {
//                echo $skype, '      ', $name, '        ', $rowNum, PHP_EOL;
//            }
//            if (isset($emails[$email])) {
//                if (!isset($duplicates[$email])) {
//                    $duplicates[$email][] = $emails[$email];
//                }
//                $duplicates[$email][] = $rowNum;
//            } else {
//                $emails[$email] = $rowNum;
//            }
//        }
//        $emails = [];
//        $names = [];
//        $duplicates = [];
//        for ($rowNum = 3; $rowNum < $sheet->getHighestRow(); $rowNum++) {
//            if (($email = $sheet->getCell('L' . $rowNum)->getValue()) == Null && $sheet->getCell('B' . $rowNum)->getValue() != NULL) {
//                echo 'End', $rowNum, PHP_EOL;
//            }
//            if (isset($emails[$email])) {
//                if (!isset($duplicates[$email])) {
//                    $duplicates[$email][] = $emails[$email];
//                }
//                $duplicates[$email][] = $rowNum;
//            } else {
//                $emails[$email] = $rowNum;
//            }
//        }
//        var_dump($duplicates);
//        echo $sheet->getHighestRow();
//        $chunkFilter = new chunkReadFilter();
//        $objReader->setReadFilter($chunkFilter);
//        $chunkSize = 600;
//        $startRow = 3;
//        $highestRow = 1266;
//        for ($startRow = 3; $startRow <= $highestRow; $startRow+=$chunkSize) {
//            $chunkFilter->setRows($startRow, $chunkSize);
//            $objPHPExcel = $objReader->load($general_student_sheet);
//            $sheet = $objPHPExcel->getActiveSheet();
//            for ($rowNum = $startRow; $rowNum < $startRow + $chunkSize; $rowNum++) {
//                if (!$name = $sheet->getCell('B' . $rowNum)) {
//                    break;
//                }
//                echo $name, '         ', $rowNum, PHP_EOL;
//            }
//            $objPHPExcel->disconnectWorksheets();
//            unset($objPHPExcel);
//            $objPHPExcel = null;
//            $sheet = null;
//            $startRow += $chunkSize;
//        }
    }

}

class chunkReadFilter implements PHPExcel_Reader_IReadFilter {

    private $_startRow = 0;
    private $_endRow = 0;

    /**  Set the list of rows that we want to read  */
    public function setRows($startRow, $chunkSize) {
        $this->_startRow = $startRow;
        $this->_endRow = $startRow + $chunkSize;
    }

    public function readCell($column, $row, $worksheetName = '') {
        //  Only read the heading row, and the rows that are configured in $this->_startRow and $this->_endRow 
        if (($row == 1) || ($row >= $this->_startRow && $row < $this->_endRow)) {
            return true;
        }
        return false;
    }

}
