<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use DB;
use PHPExcel_IOFactory;
use PHPExcel_Reader_IReadFilter;
use PHPExcel_CachedObjectStorageFactory;
use PHPExcel_Settings;

class MigrationZero extends Command {

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mig:s0';

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
        $general_teacher_sheet = public_path('sheets' . DIRECTORY_SEPARATOR . 'General Teacher Report (New).xlsx');
        $objReader = PHPExcel_IOFactory::createReader('Excel2007');
        $objReader->setLoadSheetsOnly('Sheet1');
        $objReader->setReadDataOnly(true);
        $objPHPExcel = $objReader->load($general_teacher_sheet);
        $sheet = $objPHPExcel->getActiveSheet();
        $highestRow = $sheet->getHighestRow();
        $rowNum = 3;
        $users = [];
        $accounts = [];
        $teachers = [];
        $id = 1;
        while (($name = trim($sheet->getCell('B' . $rowNum)->getValue())) != NULL) {
            $rowNum++;
            if (($email = strtolower(trim($sheet->getCell('I' . $rowNum)->getValue()))) == NULL) {
                echo "ISSUE: GTS - row# {$rowNum} Blank Email", PHP_EOL;
                continue;
            }
            if (isset($teachers[$name])) {
                echo "ISSUE: GTS - row# {$rowNum} Duplicate Teacher Name", PHP_EOL;
                continue;
            }
            $emailConverted = mb_convert_encoding($email, 'ASCII');
            if (strpos($emailConverted, '?') !== FALSE) {
                echo $rowNum;
                break;
                $email = str_replace('?', '', $emailConverted);
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                echo "ISSUE: GTS - ROW# {$rowNum} invalid email:\t", $email, PHP_EOL;
                continue;
            }
            if (isset($users[$email])) {
                echo "ISSUE: GTS - ROW# {$rowNum} duplicate email:\t", $email, PHP_EOL;
                continue;
            }
            if (isset($accounts[$name])) {
                echo "ISSUE: GTS - ROW# {$rowNum} duplicate name:\t", $name, PHP_EOL;
                continue;
            }
            $users[$email] = [];
            $users[$email]['email'] = $email;
            $accounts[$name] = [];
            $accounts[$name]['name'] = $name;
            $teachers[$name] = [];
            $teachers[$name]['user_id'] = $teachers[$name]['id'] = $accounts[$name]['id'] = $accounts[$name]['user_id'] = $users[$email]['id'] = $id++;
        }
    }

}
