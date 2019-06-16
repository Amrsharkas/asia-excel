<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PHPExcel_IOFactory;
use PHPExcel_CachedObjectStorageFactory;
use PHPExcel_Settings;
use App\Account;
use Carbon\Carbon;

class MigrationIncome extends Command {

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mig:income';

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
        $t = time();
        $resultsFileName = date('Y_m_d_i_s') . '_' . (microtime(true) * 10000) . '_' . "students";
        $this->resultsFile = public_path('results' . DIRECTORY_SEPARATOR . $resultsFileName);
        file_put_contents($this->resultsFile, '');
        $cacheMethod = PHPExcel_CachedObjectStorageFactory::cache_to_sqlite3;
        if (!PHPExcel_Settings::setCacheStorageMethod($cacheMethod)) {
            echo "Unable to set Cell Caching using ", $cacheMethod, " method, reverting to memory", PHP_EOL;
            exit;
        }
        $incomeSheet = public_path('sheets' . DIRECTORY_SEPARATOR . 'Nour Academy Income Sheet .xlsx');
        $objReader = PHPExcel_IOFactory::createReader('Excel2007');
        $objReader->setLoadSheetsOnly('Sheet1');
        $objReader->setReadDataOnly(true);
        echo 'Loading income sheet', PHP_EOL;
        $objPHPExcel = $objReader->load($incomeSheet);
        $sheet = $objPHPExcel->getActiveSheet();
        $students = collect(Account::where('type', '=', 'student')->get());
        $highestRow = $sheet->getHighestRow();
        $totalEntries = 0;
        $notfoundStudents = [];
        $notFoundStudentsCount = 0;
        for ($rowNum = 2; $rowNum <= $highestRow; $rowNum++) {
            $date = $sheet->getCell('B' . $rowNum)->getValue();
            if (!$date) {
                continue;
            }
            $totalEntries++;
            if (!is_float($date)) {
                echo "Issue row# {$rowNum}: Invalid date\n";
                continue;
            }
            $dateConverted = $this->getRealDate($date);
            if ($dateConverted > date_create_from_format('Y-m-d', '2018-01-01')) {
                echo "Issue row# {$rowNum}: Date is in future\n";
                continue;
            }
            $studentRegNum = strtolower($sheet->getCell('J' . $rowNum)->getValue());
            $paymentType = $sheet->getCell('F' . $rowNum)->getValue();
            $amount = $sheet->getCell('E' . $rowNum)->getValue();
            $courseType = $sheet->getCell('G' . $rowNum)->getValue();
            $details = $sheet->getCell('H' . $rowNum)->getValue();
            $paymentMethod = $sheet->getCell('I' . $rowNum)->getValue();
            if ($paymentType == 'Payment') {
                if ($courseType == 'Memorization/Recitation') {
                    $courseId = 1;
                } elseif ($courseType == 'Tajweed Theoretical') {
                    $courseId = 2;
                } elseif ($courseType == 'Annourania') {
                    $courseId = 3;
                } else {
                    echo "Issue row# {$rowNum}: Invalid course type\n";
                    continue;
                }
            } elseif ($paymentType == 'Donation') {
                $courseId = null;
            } else {
                echo "Issue row# {$rowNum}: Invalid payment type\n";
                continue;
            }
            if (!$student = $students->where('student_reg_num', $studentRegNum)->first()) {
                echo "Issue row# {$rowNum}: student not found\t{$studentRegNum}\n";
                $notFoundStudentsCount++;
                if (isset($notfoundStudents[$studentRegNum])) {
                    $notfoundStudents[$studentRegNum] ++;
                } else {
                    $notfoundStudents[$studentRegNum] = 1;
                }
                continue;
            }
        }
        echo $notFoundStudentsCount . PHP_EOL;
        echo count($notfoundStudents) . PHP_EOL;
        echo $totalEntries . PHP_EOL;
    }

    public function getRealDate($googleTimestamp) {
        return Carbon::createFromDate('1899', '12', '30')->addDays((int) $googleTimestamp);
    }

    public function getRealDateTime($googleTimestamp) {
        $days = floor($googleTimestamp);
        $seconds = ($googleTimestamp - $days) * 24 * 60 * 60;
        return Carbon::createFromDate('1899', '12', '30')->addDays($days)->addSeconds($seconds);
    }

    public function output($info) {
        echo $info;
        file_put_contents($this->resultsFile, $info, FILE_APPEND);
    }

}
