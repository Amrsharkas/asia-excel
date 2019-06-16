<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use DB;
use PHPExcel_IOFactory;
use PHPExcel_Reader_IReadFilter;
use PHPExcel_CachedObjectStorageFactory;
use PHPExcel_Settings;
use App\User;
use App\Country;

class MigrationOne extends Command {

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mig:s1 {token?}';

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
//        $token = $this->argument('token');
//        file_put_contents(public_path('sheets' . DIRECTORY_SEPARATOR . 'results' . DIRECTORY_SEPARATOR . $token), 'INIT');
//        $date = date_create_from_format('j-M-Y', '12-01-1991');
//        var_dump($date);
//        exit;
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
        $highestRow = $sheet->getHighestRow();
        $emails = [];
        $accounts = [];
        $schedules = [];
        $invalidRows = [];
        $userId = 1;
        $accountId = 1;
        $scheduleId = 1;
        $countries = Country::all();
        for ($rowNum = 3; $rowNum < $highestRow; $rowNum++) {
            if (($name = trim($sheet->getCell('B' . $rowNum)->getValue())) == NULL) {
                continue;
            }
            if (($email = strtolower(trim($sheet->getCell('L' . $rowNum)->getValue()))) == NULL) {
                echo "ISSUE: GSS - ROW# {$rowNum} ({$name}) has no email", PHP_EOL;
                $invalidRows[] = $rowNum;
                continue;
            }
            $emailConverted = mb_convert_encoding($email, 'ASCII');
            if (strpos($emailConverted, '?') !== FALSE) {
                $email = str_replace('?', '', $emailConverted);
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                echo "ISSUE: GSS - ROW# {$rowNum} invalid email:\t", $email, PHP_EOL;
                $invalidRows[] = $rowNum;
                continue;
            }
            if (isset($emails[$email])) {
                continue;
            }
            $type = strtolower($sheet->getCell('D' . $rowNum)->getValue());
            if ($type == 'student') {
                $emails[$email]['user_type'] = 'student';
                $emails[$email]['teacher_training'] = NULL;
            } elseif ($type == 'teacher_training') {
                $emails[$email]['user_type'] = 'teacher';
                $emails[$email]['teacher_training'] = true;
            } else {
                echo "ISSUE: GSS - Row# {$rowNum} - Blank Student/Teacher Training", PHP_EOL;
                $invalidRows[] = $rowNum;
                continue;
            }
            $emails[$email]['id'] = $userId++;
            $emails[$email]['email'] = $email;
            $gender = strtolower($sheet->getCell('G' . $rowNum));
            if ($gender == NULL) {
                echo "NOTICE: GSS - Row# {$rowNum} - Blank Gender", PHP_EOL;
            } elseif ($gender != 'male' && $gender != 'female') {
                $gender = NULL;
                echo "NOTICE: GSS - Row# {$rowNum} - invalid Gender", PHP_EOL;
                var_dump($gender);
            }
            if (!isset($accounts[$name])) {
                $accounts[$name] = [];
                $accounts[$name]['name'] = $name;
                $accounts[$name]['id'] = $accountId++;
                $accounts[$name]['gender'] = $gender;
                $accounts[$name]['skype'] = $sheet->getCell('N' . $rowNum);
                $accounts[$name][''];
            }
        }
//        DB::table('users')->delete();
//        DB::table('users')->insert($emails);
//        exit;
//        $emails = null;
//        unset($emails);
//        $users = User::all();
//        $accounts = [];
//        for ($rowNum = 3; $rowNum < $highestRow; $rowNum++) {
//            if (($name = trim($sheet->getCell('B' . $rowNum)->getValue())) == NULL || ($email = strtolower(trim($sheet->getCell('L' . $rowNum)->getValue()))) == NULL) {
//                continue;
//            }
//            $emailConverted = mb_convert_encoding($email, 'ASCII');
//            if (strpos($emailConverted, '?') !== FALSE) {
//                $email = str_replace('?', '', $emailConverted);
//            }
//            $user = $users->where('email', $email)->first();
//            if ($user == NULL) {
//                echo $email, PHP_EOL;
//            }
//            if (isset($accounts['name'])) {
//                continue;
//            }
//            $accounts[$name] = [];
//            $accounts[$name]['name'] = $name;
//            $accounts[$name]['user_id'] = $user->id;
//        }
//        DB::table('accounts')->delete();
//        DB::table('accounts')->insert($accounts);
//        file_put_contents(public_path('sheets' . DIRECTORY_SEPARATOR . 'results' . DIRECTORY_SEPARATOR . $token), 'DONE');
    }

    public function getTimestamp($googleTimestamp) {
        return ($googleTimestamp - 25568.0833333) * 24 * 60 * 60;
    }

}
