<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PHPExcel_IOFactory;
use PHPExcel_CachedObjectStorageFactory;
use PHPExcel_Settings;
use App\OneCourse;

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
        $general_student_sheet = public_path('sheets' . DIRECTORY_SEPARATOR . 'General Student Report (New).xlsx');
        $general_teacher_sheet = public_path('sheets' . DIRECTORY_SEPARATOR . 'General Teacher Report (New).xlsx');
        $objReader = PHPExcel_IOFactory::createReader('Excel2007');
        $objReader->setReadDataOnly(true);

        //======================= Migrating Teachers =========================
        $objReader->setLoadSheetsOnly('Sheet1');
        $objPHPExcel = $objReader->load($general_teacher_sheet);
        $sheet = $objPHPExcel->getActiveSheet();
        $rowNum = 3;
        $users = [];
        $accounts = [];
        $teachers = [];
        $userId = 1;
        $accountId = 1;
        $teacherId = 1;
        while (($name = trim($sheet->getCell('B' . $rowNum)->getValue())) != NULL) {
            $rowNum++;
            if (($email = strtolower(trim($sheet->getCell('I' . $rowNum)->getValue()))) == NULL) {
                echo "ISSUE: GTS - row# {$rowNum} Blank Email", PHP_EOL;
                continue;
            }
            $emailConverted = mb_convert_encoding($email, 'ASCII');
            if (strpos($emailConverted, '?') !== FALSE) {
                echo $rowNum;
                break;
                $email = str_replace('?', '', $emailConverted);
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                echo "ISSUE: GTS - row# {$rowNum} invalid email:\t", $email, PHP_EOL;
                continue;
            }
            if (!isset($users[$email])) {
                $users[$email] = [];
                $users[$email]['id'] = $userId;
                $users[$email]['email'] = $email;
                $users[$email]['teacher_training'] = FALSE;
                $user_id = $userId++;
            } else {
                $user_id = $users[$email]['id'];
            }
            if (!isset($accounts[$name])) {
                $accounts[$name] = [];
                $accounts[$name]['name'] = $name;
                $accounts[$name]['user_id'] = $user_id;
                $accounts[$name]['id'] = $accountId++;
                $accounts[$name]['gender'] = NULL;
                $accounts[$name]['skype'] = NULL;
                $accounts[$name]['phone'] = NULL;
                $accounts[$name]['country'] = NULL;
                $accounts[$name]['city'] = NULL;
                $accounts[$name]['birth_date'] = NULL;
                $teachers[$name] = [];
            }
        }
        $objPHPExcel->__destruct();
        $sheet->__destruct();
        // ======================= Migrating Students ========================
        $objReader->setLoadSheetsOnly('Report');
        $objPHPExcel = $objReader->load($general_student_sheet);
        $sheet = $objPHPExcel->getActiveSheet();
        $highestRow = $sheet->getHighestRow();
        $users = [];
        $accounts = [];
        $courseAssignments = collect([]);
        $students = [];
        $invalidRows = [];
        $userId = 1;
        $accountId = 1;
        $scheduleId = 1;
        $studentId = 1;
        $courses = OneCourse::all();
        for ($rowNum = 3; $rowNum < $highestRow; $rowNum++) {
            if (($name = trim($sheet->getCell('B' . $rowNum)->getValue())) == NULL) {
                continue;
            }
            if (($email = strtolower(trim($sheet->getCell('L' . $rowNum)->getValue()))) == NULL) {
                echo "ISSUE: GSS - row# {$rowNum} Blank Email", PHP_EOL;
                $invalidRows[] = $rowNum;
                continue;
            }
            $emailConverted = mb_convert_encoding($email, 'ASCII');
            if (strpos($emailConverted, '?') !== FALSE) {
                $email = str_replace('?', '', $emailConverted);
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                echo "ISSUE: GSS - row# {$rowNum} invalid Email:\t", $email, PHP_EOL;
                $invalidRows[] = $rowNum;
                continue;
            }
            $course_type = $sheet->getCell('AF' . $rowNum)->getValue();
            if ($course_type == NULL) {
                echo "ISSUE: GSS - row# {$rowNum} blank course type", PHP_EOL;
                $invalidRows[] = $rowNum;
                continue;
            } else {
                $course = $courses->where('name', $course_type)->first();
                if ($course == NULL) {
                    echo "ISSUE: GSS - row# {$rowNum} invalid course type:\t", $course_type, PHP_EOL;
                    $invalidRows[] = $rowNum;
                    continue;
                }
            }
            if (isset($users[$email])) {
                $userExists = true;
                $user = &$users[$email];
            } else {
                $userExists = false;
            }
            if (isset($accounts[$email])) {
                $accountExists = true;
                $account = &$accounts[$email];
            } else {
                $accountExists = false;
            }
            $account = &$accounts[$name];
            $courseAssignment = $courseAssignments->where('email', $email)->where('name', $name)->where('course', $course->name)->first();
            if ($courseAssignment) {
                echo "ISSUE: GSS - rOW# {$rowNum} Duplicate (email,name,course type) with row# {$courseAssignment['row']}", PHP_EOL;
                $invalidRows[] = $rowNum;
                continue;
            }
            if ($accountExists && !$userExists) {
                echo "ISSUE: GSS - rOW# {$rowNum} name exists with different email", PHP_EOL;
                $invalidRows[] = $rowNum;
                continue;
            }
            $courseAssignments[] = ['email' => $email, 'name' => $name, 'course' => $course->name, 'row' => $rowNum];
            $type = trim(strtolower($sheet->getCell('D' . $rowNum)->getValue()));
            if ($type != 'student' && $type != 'teacher training') {
                echo "ISSUE: GSS - row# {$rowNum} Blank Student/Teacher Training", PHP_EOL;
                $invalidRows[] = $rowNum;
                continue;
            }
            //=================================================================
            continue;
            if (isset($users[$email])) {
                $user = &$users[$email];
                if ($type == 'teacher training') {
                    if ($user['user_type'] != 'teacher') {
                        echo "ISSUE: GSS - row# {$rowNum} Email was set to student ", PHP_EOL;
                        $invalidRows[] = $rowNum;
                        continue;
                    }
                } else {
                    if ($user['user_type'] == 'teacher') {
                        echo "ISSUE: GSS - row# {$rowNum} a teacher was inserted with same Email", PHP_EOL;
                        $invalidRows[] = $rowNum;
                        continue;
                    }
                }
                $user_id = $user['id'];
            } else {
                if ($type == 'student') {
                    $users[$email]['user_type'] = 'student';
                    $teacher_training = NULL;
                    $users[$email]['teacher_training'] = $teacher_training;
                } elseif ($type == 'teacher training') {
                    echo "ISSUE: GSS - row# {$rowNum} Teacher wasn't inserted from GTS", PHP_EOL;
                    $invalidRows[] = $rowNum;
                    continue;
                }
                $users[$email]['id'] = $userId;
                $users[$email]['email'] = $email;
                $user_id = $userId++;
            }
            $gender = strtolower($sheet->getCell('G' . $rowNum));
            if ($gender == NULL) {
                echo "NOTICE: GSS - row# {$rowNum} - Blank Gender", PHP_EOL;
            } elseif ($gender != 'male' && $gender != 'female') {
                $gender = NULL;
                echo "NOTICE: GSS - row# {$rowNum} - invalid Gender", PHP_EOL;
            }
//            $countryCellValue = $sheet->getCell('V' . $rowNum)->getValue();
//            $country = $countries->where('Name', $countryCellValue)->first();
//            if ($country == NULL) {
//                echo "ISSUE: GSS- row# {$rowNum} INVALID COUNTRY\t {$countryCellValue}", PHP_EOL;
//                $invalidRows[] = $rowNum;
//                continue;
//            }
//            $cityCellValue = $sheet->getCell('W' . $rowNum)->getValue();
//            if ($cityCellValue != NULL) {
//            }
            if (isset($accounts[$name])) {
                if (isset($schedules[$name . $course_type])) {
                    
                }
            } else {
                $accounts[$name] = [];
                $accounts[$name]['name'] = $name;
                $accounts[$name]['user_id'] = $user_id;
                $accounts[$name]['id'] = $accountId;
                $accounts[$name]['gender'] = $gender;
                $accounts[$name]['skype'] = $sheet->getCell('N' . $rowNum)->getValue();
                $accounts[$name]['phone'] = $sheet->getCell()->getValue();
                $accounts[$name]['country'] = $sheet->getCell('V' . $rowNum)->getValue();
                $accounts[$name]['city'] = $sheet->getCell('W' . $rowNum)->getValue();
                $accounts[$name]['birth_date'] = date('Y-m-d', $this->getStandardTimestamp($sheet->getCell('O502')->getValue()));
                $students[$name] = [];
                $students[$name]['id'] = $studentId++;
                $students[$name]['user_id'] = $user_id;
                $students[$name]['account_id'] = $accountId;
                $students[$name]['teacher_training'] = $teacher_training;
                $account_id = $student_id = $accountId++;
            }
        }
//        DB::table('users')->delete();
//        DB::table('users')->insert($users);
//        DB::table('accounts')->delete();
//        DB::table('accounts')->insert($accounts);
//        DB::table('students')->delete();
//        DB::table('students')->insert($students);
//        exit;
//        $users = null;
//        unset($users);
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

    public function getStandardTimestamp($googleTimestamp) {
        return ($googleTimestamp - 25568.0833333) * 24 * 60 * 60;
    }

}
