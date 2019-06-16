<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use DB;
use DateTime;
use App\Account;
use PHPExcel_IOFactory;
use PHPExcel_Reader_IReadFilter;
use PHPExcel_CachedObjectStorageFactory;
use PHPExcel_Settings;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class MigrationExp extends Command {

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mig:exp';

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
        $path = public_path('sheets' . DIRECTORY_SEPARATOR . "Teachers Reports");
        $sheets = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS));
        $sheets = iterator_to_array($sheets);
        shuffle($sheets);
        $sheetName = $sheets[rand(0, count($sheets) - 1)]->getRealPath();
        $sheetName = 'C:\workspace\dev\www\nouracademy\migrations\public\sheets\Teachers Reports\Hadeel Mesbah_Teachers\Essam Fouad.xlsx';
        echo $sheetName . PHP_EOL;
        $cacheMethod = PHPExcel_CachedObjectStorageFactory::cache_to_sqlite3;
        if (!PHPExcel_Settings::setCacheStorageMethod($cacheMethod)) {
            echo "Unable to set Cell Caching using ", $cacheMethod, " method, reverting to memory", PHP_EOL;
            exit;
        }
        $objReader = PHPExcel_IOFactory::createReader('Excel2007');
        $objReader->setLoadSheetsOnly('Sheet1');
        $objReader->setReadDataOnly(true);
        $objPHPExcel = $objReader->load($sheetName);
        $sheet = $objPHPExcel->getActiveSheet();
        $highest = $sheet->getHighestRow('C');
        $count = 0;
        for ($rowNum = 3; $rowNum <= $highest; $rowNum++) {
            if (!$date = trim($sheet->getCell('C' . $rowNum)->getValue())) {
                continue;
            }
            echo $rowNum . PHP_EOL;
            $count++;
//                if (is_string($date) && !is_numeric($date)) {
//                    $dates[] = $date;
//                }
//                echo "{$sheetNum}\t{$rowNum}\n";
        }
        echo $sheetName . PHP_EOL;
        echo $count . PHP_EOL;
    }

}
