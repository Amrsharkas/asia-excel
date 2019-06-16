<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use DB;
use PHPExcel_IOFactory;
use PHPExcel_CachedObjectStorageFactory;
use PHPExcel_Settings;
use App\User;
use App\Account;
use Carbon\Carbon;
use App\Student;
use Mail;

class MigrationStudents extends Command {

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mig:students';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display an inspiring quote';
    private $resultsFile;
    private $dateFormats;

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() {
        $t = time();
        $this->initDateFormats();
        $resultsFileName = date('Y_m_d_i_s') . '_' . (microtime(true) * 10000) . '_' . "students";
        $this->resultsFile = public_path('results' . DIRECTORY_SEPARATOR . $resultsFileName);
        file_put_contents($this->resultsFile, '');
        $cacheMethod = PHPExcel_CachedObjectStorageFactory::cache_to_sqlite3;
        if (!PHPExcel_Settings::setCacheStorageMethod($cacheMethod)) {
            echo "Unable to set Cell Caching using ", $cacheMethod, " method, reverting to memory", PHP_EOL;
            exit;
        }
        $general_student_sheet = public_path('sheets' . DIRECTORY_SEPARATOR . 'General Student Report final.xlsx');
        $objReader = PHPExcel_IOFactory::createReader('Excel2007');
        $objReader->setLoadSheetsOnly('Report');
        $objReader->setReadDataOnly(true);
        echo 'Loading general student sheet', PHP_EOL;
        $objPHPExcel = $objReader->load($general_student_sheet);
        $sheet = $objPHPExcel->getActiveSheet();
        
        
        $highestRow = $sheet->getHighestRow();
        for ($rowNum = 3; $rowNum <= $highestRow; $rowNum++) {
            $email = strtolower(trim($sheet->getCell('L' . $rowNum)->getValue()));
            $status = strtolower(trim($sheet->getCell('AG' . $rowNum)->getValue()));
            $financial_status = strtolower(trim($sheet->getCell('AI' . $rowNum)->getValue()));
            $due = strtolower(trim($sheet->getCell('AN' . $rowNum)->getCalculatedValue()));
            $name = strtolower(trim($sheet->getCell('B' . $rowNum)->getValue()));
            $cycles = strtolower(trim($sheet->getCell('AK' . $rowNum)->getValue()));
            $due_cycles = strtolower(trim($sheet->getCell('AO' . $rowNum)->getCalculatedValue()));
            $due_amount = strtolower(trim($sheet->getCell('AQ' . $rowNum)->getCalculatedValue()));
           //echo $due." - ".$status." - ".$financial_status, PHP_EOL;
            if(strtolower($due) == "due" && strtolower($status) == "active" && strtolower($financial_status) == "paying"){
                Mail::send('emails.reminder', ['name' => $name , 'email' => $email  ,'cycles'=>$cycles,'due_cycles'=>$due_cycles,'due_amount'=>$due_amount], function ($m) use ($email,$name) {
                    $m->from('payments@nouracademy.com', 'Nour Academy Payments');
                    $m->bcc("h.hafez@nouracademy.com");
                    $m->replyTo("payments@nouracademy.com", "Nour Academy Payments");
                    $m->to($email, $name)->subject('Nour Academy | Gentle Reminder "Due Payments"');
                    
                });
                //exit();
                //echo 'Loading general student sheet', PHP_EOL;
                echo $name." - ".$email, PHP_EOL;

            } 
            //$sheetSchedules[] = $schedule;
        }

        
    }

    public function initDateFormats() {
        $this->dateFormats = [
            'd-F-y', 'j-F-y', 'd-M-y', 'j-M-y', 'd-F-Y', 'j-F-Y', 'd-M-Y', 'j-M-Y',
            'd/F/y', 'j/F/y', 'd/M/y', 'j/M/y', 'd/F/Y', 'j/F/Y', 'd/M/Y', 'j/M/Y',
            'd F y', 'j F y', 'd M y', 'j M y', 'd F Y', 'j F Y', 'd M Y', 'j M Y',
            'l j F y', 'D j F y', 'D d F y', 'l d F y',
            'l j M y', 'D j M y', 'D d M y', 'l d M y',
            'l j F Y', 'D j F Y', 'D d F Y', 'l d F Y',
            'l j M Y', 'D j M Y', 'D d M Y', 'l d M Y',
        ];
    }

    public function getRealDate($googleTimestamp) {
        return Carbon::createFromDate('1899', '12', '30')->addDays((int) $googleTimestamp);
    }

    public function getRealDateTime($googleTimestamp) {
        $days = floor($googleTimestamp);
        $seconds = ($googleTimestamp - $days) * 24 * 60 * 60;
        return Carbon::createFromDate('1899', '12', '30')->addDays($days)->addSeconds($seconds);
    }

    public function parseDate($dateString) {
        foreach ($this->dateFormats as $format) {
            $date = date_create_from_format($format, $dateString);
            if ($date) {
                return $date;
            }
        }
        return false;
    }

    public function validateDate($date) {
        if (is_string($date) && !is_numeric($date)) {
            if (!$dateConverted = $this->parseDate($date)) {
                return -1;
            }
        } else {
            $dateConverted = $this->getRealDate($date);
        }
        if ($dateConverted > date_create_from_format('Y-m-d', '2018-01-01')) {
            return -2;
        }
        return $dateConverted;
    }

    public function output($info) {
        echo $info;
        file_put_contents($this->resultsFile, $info, FILE_APPEND);
    }

}
