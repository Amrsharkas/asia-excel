<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use DB;
use PHPExcel_IOFactory;
use PHPExcel_Reader_IReadFilter;
use PHPExcel_CachedObjectStorageFactory;
use PHPExcel_Settings;
use App\User;
use App\Account;
use App\Teacher;
use App\TeacherCourse;
use App\OneSession;
use DateTime;
use Carbon\Carbon;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class MigrationCount extends Command {

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mig:count {--teachers} {--students} {limit?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display an inspiring quote';
    private $resultsFile;

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() {
        if ($this->option('teachers')) {
            $this->initFile('teachers');
            $this->sessions_count('Teachers Reports' . DIRECTORY_SEPARATOR . 'New', 'C');
        }
        if ($this->option('students')) {
            $this->initFile('students');
            $this->sessions_count('Teachers', 'B');
        }
    }

    public function initFile($type) {
        $resultsFileName = date('Y_m_d_i_s') . '_' . (microtime(true) * 10000) . '_' . "count_{$type}_sessions";
        $this->resultsFile = public_path('results' . DIRECTORY_SEPARATOR . $resultsFileName);
        file_put_contents($this->resultsFile, ''); //Empty file if exists
    }

    public function initStudentsFile($type) {
        $resultsFileName = date('Y_m_d_i_s') . '_' . (microtime(true) * 10000) . '_' . "ssessions";
        $this->resultsFile = public_path('results' . DIRECTORY_SEPARATOR . $resultsFileName);
        file_put_contents($this->resultsFile, ''); //Empty file if exists
    }

    public function output($info) {
        echo $info;
        file_put_contents($this->resultsFile, $info, FILE_APPEND); //Empty file if exists
    }

    public function sessions_count($dir, $cell) {
        $t = time();
        echo "preparing Excel Reader..\n";
        $cacheMethod = PHPExcel_CachedObjectStorageFactory::cache_to_sqlite3;
        if (PHPExcel_Settings::setCacheStorageMethod($cacheMethod)) {
            echo "Enable Cell Caching using ", $cacheMethod, " method", PHP_EOL;
        } else {
            echo "Unable to set Cell Caching using ", $cacheMethod, " method, reverting to memory", PHP_EOL;
        }
        $objReader = PHPExcel_IOFactory::createReader('Excel2007');
        $objReader->setLoadSheetsOnly('Sheet1');
        $objReader->setReadDataOnly(true);
        // geting a list of all files in the "Teachers" directory
        $path = public_path('sheets' . DIRECTORY_SEPARATOR . $dir);
        $sheets = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS));
        $sheetNum = 1;
        $sessionsCount = 0;
        $dates = [];
        $count = 0;
        $excelSheets = 0;
        foreach ($sheets as $sheetName => $fileObject) {
            if (($limit = $this->argument('limit')) && $count > $limit) {
                break;
            }
            if (!preg_match('/\.xlsx$/', $sheetName)) {
                echo "Not Excel Sheet\n";
                continue;
            }
            $excelSheets++;
            $count++;
            $objPHPExcel = $objReader->load($sheetName);
            $sheet = $objPHPExcel->getActiveSheet();
            $highestRow = $sheet->getHighestRow();
            $sheetSessionsCount = 0;
            $this->output($sheetName);
            for ($rowNum = 3; $rowNum <= $highestRow; $rowNum++) {
                if (!$date = trim($sheet->getCell($cell . $rowNum)->getValue())) {
                    continue;
                }
//                if (is_string($date) && !is_numeric($date)) {
//                    $dates[] = $date;
//                }
//                echo "{$sheetNum}\t{$rowNum}\n";
                $sheetSessionsCount++;
                $sessionsCount++;
            }
            $this->output("\t{$sheetSessionsCount}\n");
            $sheetNum++;
            $objPHPExcel->__destruct();
            $sheet->__destruct();
            $objPHPExcel = null;
            $sheet = null;
            unset($objPHPExcel);
            unset($sheet);
        }
        $this->output("Total sheets: {$excelSheets}\n");
        $this->output("Total sessions : {$sessionsCount}\n");
        $t = time() - $t;
        $this->output("Total time {$t} seconds || " . ($t / 60) . " minutes\n");
    }

}
