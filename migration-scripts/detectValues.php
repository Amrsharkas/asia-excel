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

        $general_student_sheet = public_path('general_student_sheet.xlsx');
        $objReader = PHPExcel_IOFactory::createReader('Excel2007');
        $objReader->setLoadSheetsOnly('Report');
        $objReader->setReadDataOnly(true);
        $objPHPExcel = $objReader->load($general_student_sheet);
        $sheet = $objPHPExcel->getActiveSheet();
        $column_values = [];
        for ($rowNum = 3; $rowNum < $sheet->getHighestRow(); $rowNum++) {
            if ($name = $sheet->getCell('B' . $rowNum)->getValue() != NULL) {
                $column_value = $sheet->getCell('D' . $rowNum)->getValue();
                if (isset($column_values[$column_value])) {
                    $column_values[$column_value] ++;
                } else {
                    $column_values[$column_value] = 1;
                }
            }
        }
        print_r($column_values);
    }

}
