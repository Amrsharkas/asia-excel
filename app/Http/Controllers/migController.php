<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;

use DB;
use PHPExcel_IOFactory;
use PHPExcel_CachedObjectStorageFactory;
use PHPExcel_Settings;
use App\User;
use App\Account;

use Carbon\Carbon;
use App\Student;
use App\Teacher;
use App\TeacherCourse;

class migController extends Controller
{
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
    public function students() {
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
        // loading data from the database
        $users = User::get();
        $accounts = Account::get();
        $students = Student::get();
        $studentCourses = collect(DB::table('student_courses')->get());
        $userId = $users->sortByDesc('id')->pluck('id')->first() + 1;
        $accountId = $accounts->sortByDesc('id')->pluck('id')->first() + 1;
        $studentId = $students->sortByDesc('id')->pluck('id')->first() + 1;
        $studentCourseId = $studentCourses->sortByDesc('id')->pluck('id')->first() + 1;
        $sheetUsers = collect([]);
        $sheetAccounts = collect([]);
        $sheetStudents = collect([]);
        //$sheetSchedules = collect([]);
        $studentsSkipped = [];
        $familyAccounts = [];
        $studentsMulCourses = [];
        $totalStudentsMigrated = 0;
        $totalStudents = 0;
        $highestRow = $sheet->getHighestRow();
        for ($rowNum = 3; $rowNum <= $highestRow; $rowNum++) {
            if (!$name = trim($sheet->getCell('B' . $rowNum)->getValue())) {
                continue;
            }
            $totalStudents++;
            $newUser = $newStudent = false;
            if (($email = strtolower(trim($sheet->getCell('L' . $rowNum)->getValue()))) == NULL) {
                $this->output("ISSUE: GSS - row# {$rowNum} Blank Email\n");
                $studentsSkipped[] = ['row' => $rowNum, 'name' => $name];
                continue;
            }
            $emailConverted = mb_convert_encoding($email, 'ASCII');
            if (strpos($emailConverted, '?') !== FALSE) {
                $email = str_replace('?', '', $emailConverted);
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->output("ISSUE: GSS - row# {$rowNum} invalid email:\t{$email}\n");
                $studentsSkipped[] = ['row' => $rowNum, 'name' => $name];
                continue;
            }
            $type = trim(strtolower($sheet->getCell('D' . $rowNum)->getValue()));
            if (!$type) {
                $this->output("ISSUE: GSS - row# {$rowNum} Blank Student/Teacher Training\t{$name}\n");
                $studentsSkipped[] = ['row' => $rowNum, 'name' => $name];
                continue;
            } elseif ($type == 'student') {
                $type = 'student';
                $teacher_training = NULL;
            } elseif ($type == 'teacher training') {
                $type = 'teacher';
                $teacher_training = true;
            } else {
                $this->output("ISSUE: GSS - row# {$rowNum} Invalid Student/Teacher Training \t{$name}\t{$type}\n");
                $studentsSkipped[] = ['row' => $rowNum, 'name' => $name];
                continue;
            }
            $courseType = trim(strtolower($sheet->getCell('AF' . $rowNum)->getValue()));
            if (!$courseType) {
                $this->output("ISSUE: GSS - row# {$rowNum} blank course type\t{$name}\n");
                $studentsSkipped[] = ['row' => $rowNum, 'name' => $name];
                continue;
            } elseif (strpos($courseType, 'memorization') !== FALSE || strpos($courseType, 'recitation') !== FALSE) {
                $courseId = 1;
            } elseif (strpos($courseType, 'tajweed') !== FALSE) {
                $courseId = 2;
            } elseif (strpos($courseType, 'nourania') !== FALSE) {
                $courseId = 3;
            } else {
                $this->output("ISSUE: GSS - row# {$rowNum} Unknown course type\t{$name}\t{$courseType}\n");
                $studentsSkipped[] = ['row' => $rowNum, 'name' => $name];
                continue;
            }

            $account = $accounts->where('name', $name)->first()
                    ? : $sheetAccounts->where('name', $name)->first();
            if ($account) {
                $user = $users->where('id', $account['user_id'])->first()
                        ? : $sheetUsers->where('id', $account['user_id'])->first();
                if ($email != $user['email']) {
                    $this->output("ISSUE: GSS - row# {$rowNum} Name was assigned to another email\t" . $user['email'] . PHP_EOL);
                    $studentsSkipped[] = ['row' => $rowNum, 'name' => $name];
                    continue;
                }
                if ($type == 'student' && $account['type'] == 'teacher') {
                    $this->output("ISSUE: GSS - row# {$rowNum} Name was saved as a Teacher before\t{$name}\n");
                    $studentsSkipped[] = ['row' => $rowNum, 'name' => $name];
                    continue;
                }
                if ($type == 'teacher' && $account['type'] == 'student') {
                    $this->output("ISSUE: GSS - row# {$rowNum} Name was saved as a Student before\t{$name}\n");
                    $studentsSkipped[] = ['row' => $rowNum, 'name' => $name];
                    continue;
                }
                if ($type == 'teacher') {
                    $account->teacher_training = true;
                    $account->save();
                }
                $student = $students->where('account_id', $account['id'])->first()
                        ? : $sheetStudents->where('account_id', $account['id'])->first();
            } else { // name does not exist
                if ($type == 'teacher') {
                    $this->output("ISSUE: GSS - row# {$rowNum} No teacher was saved with that name before\t{$name}\n");
                    $studentsSkipped[] = ['row' => $rowNum, 'name' => $name];
                    continue;
                }
                $user = $users->where('email', $email)->first()
                        ? : $sheetUsers->where('email', $email)->first();
                if (!$user) { // email doesn't exist
                    $newUser = true;
                    $user = ['email' => $email, 'id' => $userId++];
                } else {
                    $familyAccounts[$email] = true;
                }
                $newStudent = true;
                $account = [];
                $account['name'] = $name;
                $account['id'] = $accountId++;
                $account['user_id'] = $user['id'];
                $account['type'] = $type;
                if (($gender = strtolower(trim($sheet->getCell('G' . $rowNum)->getValue()))) != 'male' && $gender != 'female') {
                    $gender = NULL;
                    $this->output("Notice: GSS - row# {$rowNum} invalid gender\t{$gender}\n");
                }
                $account['gender'] = $gender;
                $account['phone'] = $sheet->getCell('M' . $rowNum)->getValue();
                $account['skype'] = $sheet->getCell('N' . $rowNum)->getValue();
                $dobValue = $sheet->getCell('O' . $rowNum)->getValue();
                if ($dobValue) {
                    $dob = $this->validateDate($dobValue);
                    if (is_int($dob) && $dob == -1) {
                        $this->output("Notice: GTS - row#{$rowNum} invalid/unknown dob format\n");
                        $account['birth_date'] = NULL;
                    } elseif (is_int($dob) && $dob == -2) {
                        $this->output("Notice: GTS - row#{$rowNum} dob in the future\n");
                        $account['birth_date'] = NULL;
                    } else {
                        $account['birth_date'] = $dob->format('Y-m-d');
                    }
                } else {
                    $account['birth_date'] = NULL;
                }
                $account['country'] = $sheet->getCell('V' . $rowNum)->getValue();
                $account['city'] = $sheet->getCell('W' . $rowNum)->getValue();
                $account['original_country'] = $sheet->getCell('X' . $rowNum)->getValue();
                $account['teacher_training'] = $teacher_training;
                $account['student_reg_num'] = strtolower($sheet->getCell('A' . $rowNum)->getValue());
                $student = [];
                $student['id'] = $studentId++;
                $student['teacher_training'] = $teacher_training;
                $student['user_id'] = $user['id'];
                $student['account_id'] = $account['id'];
            }
            if (!$newStudent) {
                $studentCourse = $studentCourses->where('student_id', $account['id'])
                                ->where('course_id', $courseId)->first();
                if ($studentCourse) {
                    $this->info("ISSUE: GSS - row# {$rowNum} Same course was inserted for same student\t{$name}\n");
                    $studentsSkipped[] = ['row' => $rowNum, 'name' => $name];
                    continue;
                }
                $studentsMulCourses[$name] = TRUE;
            }
            $studentCourse = ['id' => $studentCourseId++,
                'course_id' => $courseId,
                'student_id' => $student['id'],
                'status'
            ];
            $dateJoinedValue = $sheet->getCell('H' . $rowNum)->getValue();
            if ($dateJoinedValue) {
                $dateJoined = $this->validateDate($dateJoinedValue);
                if (is_int($dateJoined) && $dateJoined == -1) {
                    $this->output("Notice: GTS - row#{$rowNum} invalid/unknown date joined format\n");
                    $studentCourse['date_joined'] = NULL;
                } elseif (is_int($dateJoined) && $dateJoined == -2) {
                    $this->output("Notice: GTS - row#{$rowNum} date joined in the future \n");
                    $studentCourse['date_joined'] = NULL;
                } else {
                    $studentCourse['date_joined'] = $dateJoined->format('Y-m-d');
                }
            } else {
                $studentCourse['date_joined'] = NULL;
            }
            $dateLeftValue = $sheet->getCell('I' . $rowNum)->getValue();
            if ($dateLeftValue) {
                $dateLeft = $this->validateDate($dateLeftValue);
                if (is_int($dateLeft) && $dateLeft == -1) {
                    $this->output("Notice: GTS - row#{$rowNum} invalid/unknown date left format\n");
                    $studentCourse['date_left'] = NULL;
                } elseif (is_int($dateLeft) && $dateLeft == -2) {
                    $this->output("Notice: GTS - row#{$rowNum} date left in the future\n");
                    $studentCourse['date_left'] = NULL;
                } else {
                    $studentCourse['date_left'] = $dateLeft->format('Y-m-d');
                }
            } else {
                $studentCourse['date_left'] = NULL;
            }

            if ($newUser) {
                $sheetUsers[] = $user;
            }
            if ($newStudent) {
                $totalStudentsMigrated++;
                $sheetAccounts[] = $account;
                $sheetStudents[] = $student;
            }
            //$sheetSchedules[] = $schedule;
        }

        DB::table('users')->insert($sheetUsers->all());
        DB::table('accounts')->insert($sheetAccounts->all());
        DB::table('students')->insert($sheetStudents->all());
        //DB::table('one_schedules')->insert($sheetSchedules->all());
        $this->output("Total rows : {$totalStudents}\n");
        $this->output("Total students migrated successfuly: {$totalStudentsMigrated}\n");
        $this->output("Total skipped rows: " . count($studentsSkipped) . PHP_EOL);
        foreach ($studentsSkipped as $entry) {
            $this->output("\t" . $entry['name'] . "\t" . $entry['row'] . PHP_EOL);
        }
        $this->output('Family accounts: ' . count($familyAccounts) . PHP_EOL);
        foreach ($familyAccounts as $email => $val) {
            $this->output("\t{$email}\n");
        }
        $this->output('Students with multiple courses: ' . count($studentsMulCourses) . PHP_EOL);
        foreach ($studentsMulCourses as $name => $val) {
            $this->output("\t{$name}\n");
        }
        $this->output("Total time: " . ( time() - $t) . " seconds\n");
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
    public function teachers() {
        $t = time();
        $this->initDateFormats();
        $resultsFileName = date('Y_m_d_i_s') . '_' . (microtime(true) * 10000) . '_' . "teachers";
        $this->resultsFile = public_path('results' . DIRECTORY_SEPARATOR . $resultsFileName);
        file_put_contents($this->resultsFile, '');
        // loading data from the sheet
        $cacheMethod = PHPExcel_CachedObjectStorageFactory::cache_to_sqlite3;
        if (!PHPExcel_Settings::setCacheStorageMethod($cacheMethod)) {
            echo "Unable to set Cell Caching using ", $cacheMethod, " method, reverting to memory", PHP_EOL;
            exit;
        }
        $general_teacher_sheet = public_path('sheets' . DIRECTORY_SEPARATOR . 'General Teacher Report (New).xlsx');
        $objReader = PHPExcel_IOFactory::createReader('Excel2007');
        $objReader->setLoadSheetsOnly('Sheet1');
        $objReader->setReadDataOnly(true);
        echo 'Loading general teacher sheet', PHP_EOL;
        $objPHPExcel = $objReader->load($general_teacher_sheet);
        $sheet = $objPHPExcel->getActiveSheet();
        // loading data from the database
        $users = User::all();
        $accounts = Account::all();
        $teachers = Teacher::all();
        $teacherCourses = TeacherCourse::all();
        // the id to start with when inserting into the users table and accounts table
        $userId = $users->sortByDesc('id')->pluck('id')->first() + 1;
        $accountId = $accounts->sortByDesc('id')->pluck('id')->first() + 1;
        $teacherId = $teachers->sortByDesc('id')->pluck('id')->first() + 1;
        $teacherCourseId = $teacherCourses->sortByDesc('id')->pluck('id')->first() + 1;
        $sheetUsers = collect([]);
        $sheetAccounts = collect([]);
        $sheetTeachers = collect([]);
        $sheetTeacherCourses = collect([]);
        $teachersSkipped = [];
        $teachersMulCourses = [];
        $totalTeachers = 0;
        $totalTeachersMigrated = 0;
        $highestRow = $sheet->getHighestRow();
        for ($rowNum = 3; $rowNum <= $highestRow; $rowNum++) {
            if (!$name = trim($sheet->getCell('B' . $rowNum)->getValue())) {
                continue;
            }
            $totalTeachers++;
            // validating email, payment, 
            if (($email = strtolower(trim($sheet->getCell('I' . $rowNum)->getValue()))) == NULL) {
                $this->output("ISSUE: GTS - row# {$rowNum} Blank Email\n");
                $teachersSkipped[] = ['name' => $name, 'row' => $rowNum];
                continue;
            }
            $emailConverted = mb_convert_encoding($email, 'ASCII');
            if (strpos($emailConverted, '?') !== FALSE) {
                $email = str_replace('?', '', $emailConverted);
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->output("ISSUE: GTS - row# {$rowNum} Blank Email\n");
                $teachersSkipped[] = ['name' => $name, 'row' => $rowNum];
                continue;
            }
            if (( $payment = strtolower(trim($sheet->getCell('X' . $rowNum)->getValue()))) == 'paid') {
                $volunteer = false;
            } elseif ($payment == 'voluonteer') {
                $volunteer = true;
            } else {
                $this->output("Issue: GTS - row# {$rowNum} invalid payment status:\t{$payment}\n");
                $teachersSkipped[] = ['name' => $name, 'row' => $rowNum];
                $volunteer = NULL;
            }
            $courseType = strtolower(trim($sheet->getCell('Y' . $rowNum)->getValue()));
            if (!$courseType) {
                $this->output("ISSUE: GTS - row# {$rowNum} blank course type\n");
                $teachersSkipped[] = ['name' => $name, 'row' => $rowNum];
                continue;
            } elseif (strpos($courseType, 'memorization') !== FALSE || strpos($courseType, 'recitation') !== FALSE) {
                $courseId = 1;
            } elseif (strpos($courseType, 'tajweed') !== FALSE) {
                $courseId = 2;
            } elseif (strpos($courseType, 'nourania') !== FALSE) {
                $courseId = 3;
            } else {
                $this->output("ISSUE: GTS - row# {$rowNum} Unknown course type:\t{$courseType}\n");
                $teachersSkipped[] = ['name' => $name, 'row' => $rowNum];
                continue;
            }
            $user = $users->where('email', $email)->first() ? : $sheetUsers->where('email', $email)->first();
            if ($user) { //Email exists then there must be user,account,teacher, at least one teacher course
                $account = $accounts->where('user_id', $user['id'])->first() ? : $sheetAccounts->where('user_id', $user['id'])->first();
                if ($name != $account['name']) { // was the Email assigned to another Name?
                    $this->output("ISSUE: GTS - row# {$rowNum} Email was assigned to another name:\t{$name}\t{$email}\n");
                    $teachersSkipped[] = ['name' => $name, 'row' => $rowNum];
                    continue;
                }
                $sameCourse = $teacherCourses->where('course_id', $courseId)
                                ->where('account_id', $account['id'])
                                ->first()
                        ? : $sheetTeacherCourses->where('course_id', $courseId)
                                ->where('account_id', $account['id'])
                                ->first();
                if ($sameCourse) { // same course for same teacher
                    $this->output("ISSUE: GTS - row# {$rowNum} Same course was inserted to same teacher before:\t{$name}\n");
                    $teachersSkipped[] = ['name' => $name, 'row' => $rowNum];
                    continue;
                }
                $teachersMulCourses[$name] = true;
            } else { // New Email - meaning new User,Account,Teacer
                $account = $accounts->where('name', $name)->first() ? : $sheetAccounts->where('name', $name)->first();
                if ($account) {
                    $this->output("ISSUE: GTS - row# {$rowNum} Name was assigned to another Email before:\t{$name}\t{$email}\n");
                    $teachersSkipped[] = ['name' => $name, 'row' => $rowNum];
                    continue;
                }
                $user = ['email' => $email, 'id' => $userId++];
                $account = [];
                $account['id'] = $accountId++;
                $account['name'] = $name;
                $account['user_id'] = $user['id'];
                $account['type'] = 'teacher';
                if (($gender = strtolower(trim($sheet->getCell('E' . $rowNum)->getValue()))) != 'male' && $gender != 'female') {
                    $gender = NULL;
                    $this->output("Notice: GTS - row# {$rowNum} invalid gender\n");
                }
                $account['gender'] = $gender;
                $account['phone'] = $sheet->getCell('J' . $rowNum)->getValue();
                $account['skype'] = $sheet->getCell('K' . $rowNum)->getValue();
                $account['country'] = $sheet->getCell('S' . $rowNum)->getValue();
                $account['original_country'] = $sheet->getCell('V' . $rowNum)->getValue();
                $dobValue = $sheet->getCell('M' . $rowNum)->getValue();
                if ($dobValue) {
                    $dob = $this->validateDate($dobValue);
                    if (is_int($dob) && $dob == -1) {
                        $this->output("Notice: GTS - row#{$rowNum} invalid/unknown dob format\n");
                        $account['birth_date'] = NULL;
                    } elseif (is_int($dob) && $dob == -2) {
                        $this->output("Notice: GTS - row#{$rowNum} dob in the future\n");
                        $account['birth_date'] = NULL;
                    } else {
                        $account['birth_date'] = $dob->format('Y-m-d');
                    }
                } else {
                    $account['birth_date'] = NULL;
                }
                $account['marital_status'] = $sheet->getCell('O' . $rowNum)->getValue();
                $teacher = [];
                if ($courseId == 1 && !$volunteer) {
                    $teacher['gross_due_amount'] = $sheet->getCell('AG' . $rowNum)->getValue();
                } else {
                    $teacher['gross_due_amount'] = NULL;
                }
                $teacher['id'] = $teacherId++;
                $teacher['user_id'] = $user['id'];
                $teacher['account_id'] = $account['id'];
                $teacher['skype_password'] = $sheet->getCell('L' . $rowNum)->getValue();
                $totalTeachersMigrated++;
                $sheetUsers[] = $user;
                $sheetAccounts[] = $account;
                $sheetTeachers[] = $teacher;
            }
            // here we are are sure that both email and name are new or they are both old and assigned to each other
            $teacherCourse = ['teacher_id' => $teacher['id'], 'course_id' => $courseId,
                'user_id' => $user['id'],
                'account_id' => $account['id'],
                'id' => $teacherCourseId++
            ];
            $dateJoinedValue = $sheet->getCell('F' . $rowNum)->getValue();
            if ($dateJoinedValue) {
                $dateJoined = $this->validateDate($dateJoinedValue);
                if (is_int($dateJoined) && $dateJoined == -1) {
                    $this->output("Notice: GTS - row#{$rowNum} invalid/unknown date joined format\n");
                    $teacherCourse['date_joined'] = NULL;
                } elseif (is_int($dateJoined) && $dateJoined == -2) {
                    $this->output("Notice: GTS - row#{$rowNum} date joined in the future \n");
                    $teacherCourse['date_joined'] = NULL;
                } else {
                    $teacherCourse['date_joined'] = $dateJoined->format('Y-m-d');
                }
            } else {
                $teacherCourse['date_joined'] = NULL;
            }
            $dateLeftValue = $sheet->getCell('F' . $rowNum)->getValue();
            if ($dateLeftValue) {
                $dateLeft = $this->validateDate($dateLeftValue);
                if (is_int($dateLeft) && $dateLeft == -1) {
                    $this->output("Notice: GTS - row#{$rowNum} invalid/unknown date left format\n");
                    $teacherCourse['date_left'] = NULL;
                } elseif (is_int($dateLeft) && $dateLeft == -2) {
                    $this->output("Notice: GTS - row#{$rowNum} date left in the future\n");
                    $teacherCourse['date_left'] = NULL;
                } else {
                    $teacherCourse['date_left'] = $dateLeft->format('Y-m-d');
                }
            } else {
                $teacherCourse['date_left'] = NULL;
            }
            $active = strtolower(trim($sheet->getCell('D' . $rowNum)->getValue()));
            if ($active == 'active') {
                $active = true;
            } elseif ($active == 'inactive') {
                $active = false;
            } else {
                $active = NULL;
                $this->output("Notice: GTS - row# {$rowNum} invalid gender\n");
            }
            $teacherCourse['active'] = $active;
            $teacherCourse['volunteer'] = $volunteer;
            $sheetTeacherCourses[] = $teacherCourse;
        }
        DB::table('users')->insert($sheetUsers->all());
        DB::table('accounts')->insert($sheetAccounts->all());
        DB::table('teachers')->insert($sheetTeachers->all());
        DB::table('teacher_courses')->insert($sheetTeacherCourses->all());
        $this->output("Total rows: {$totalTeachers}\n");
        $this->output("Total teachers migrated successfuly: {$totalTeachersMigrated}\n");
        $this->output("Total skipped rows: " . count($teachersSkipped) . PHP_EOL);
        foreach ($teachersSkipped as $entry) {
            $this->output("\t" . $entry['name'] . "\t" . $entry['row'] . PHP_EOL);
        }
        $this->output('Teachers with multiple courses: ' . count($teachersMulCourses) . PHP_EOL);
        foreach ($teachersMulCourses as $teacher => $val) {
            echo "\t{$teacher}\n";
        }
        $this->output("Total time: " . (time() - $t) . " seconds\n");
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
