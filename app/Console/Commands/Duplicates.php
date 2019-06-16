<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use DB;
use PHPExcel_IOFactory;
use PHPExcel_Reader_IReadFilter;
use PHPExcel_CachedObjectStorageFactory;
use PHPExcel_Settings;

class Duplicates extends Command {

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mig:dup';

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
        if (!PHPExcel_Settings::setCacheStorageMethod($cacheMethod)) {
            echo date('H:i:s'), " Unable to set Cell Caching using ", $cacheMethod, " method, reverting to memory", PHP_EOL;
            exit;
        }
        $general_student_sheet = public_path('sheets' . DIRECTORY_SEPARATOR . 'General Student Report (New).xlsx');
        $objReader = PHPExcel_IOFactory::createReader('Excel2007');
        $objReader->setLoadSheetsOnly('Report');
        $objReader->setReadDataOnly(true);
        $objPHPExcel = $objReader->load($general_student_sheet);
        $sheet = $objPHPExcel->getActiveSheet();
        $column_values = [];
        $duplicates = [];
        $higestRow = $sheet->getHighestRow();
        for ($rowNum = 3; $rowNum < $sheet->getHighestRow(); $rowNum++) {
            if (($name = $sheet->getCell('B' . $rowNum)->getValue()) == NULL) {
                continue;
            }
            $column_value = $sheet->getCell('A' . $rowNum)->getValue();
            if (isset($column_values[$column_value])) {
                if (!isset($duplicates[$column_value])) {
                    $duplicates[$column_value][] = $column_values[$column_value];
                }
                $duplicates[$column_value][] = ['row' => $rowNum, 'name' => $name];
                continue;
            }
            $column_values[$column_value] = ['row' => $rowNum, 'name' => $name];
        }
        print_r($duplicates);
    }

}
