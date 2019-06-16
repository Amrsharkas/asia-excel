<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use DB;
use PHPExcel_IOFactory;
use PHPExcel_CachedObjectStorageFactory;
use PHPExcel_Settings;
use App\Account;
use Carbon\Carbon;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class MigrationSSessions extends Command {

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mig:ssessions {limit?}';

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
        //preparing results file
        $resultsFileName = date('Y_m_d_i_s') . '_' . (microtime(true) * 10000) . '_' . "ssessions";
        $this->resultsFile = public_path('results' . DIRECTORY_SEPARATOR . $resultsFileName);
        file_put_contents($this->resultsFile, ''); //Empty file if exists
        // preparing excel reader
        echo "preparing Excel Reader..\n";
        $cacheMethod = PHPExcel_CachedObjectStorageFactory::cache_to_sqlite3;
        if (!PHPExcel_Settings::setCacheStorageMethod($cacheMethod)) {
            echo "Unable to set Cell Caching using ", $cacheMethod, " method, reverting to memory", PHP_EOL;
        }
        $objReader = PHPExcel_IOFactory::createReader('Excel2007');
        $objReader->setLoadSheetsOnly('Sheet1');
        $objReader->setReadDataOnly(true);
        // geting a list of all files in the "Teachers" directory
        echo "Geting a list of all files in the \"Teachers\" directory..\n";
        $path = public_path('sheets' . DIRECTORY_SEPARATOR . 'Teachers');
        $sheets = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS));
        // retreiving students and teachers
        echo "Retreiving all students and teachers..\n";
        $studentAccounts = collect(DB::table('accounts')->select('*', DB::raw('lower(name) as name_lower'))->where('type', '=', 'student')->orWhere('teacher_training', '=', true)->get());
        $teacherAccounts = collect(DB::table('accounts')->select('*', DB::raw('lower(name) as name_lower'))->where('type', '=', 'teacher')->get());
        $this->output("Total students retreived: " . $studentAccounts->count() . PHP_EOL);
        $this->output("Total teachers retreived: " . $teacherAccounts->count() . PHP_EOL);
        //initializing variables
        $studentSheets = collect(DB::table('student_sheets')->get());
        $newStudentSheets = [];
        $refusedSheets = ['invalid_sheet_name' => [], 'blank_student_name' => [], 'student_not_found' => [], 'invalid_course_type' => []];
        $notFoundTeachers = [];
        $blankTeacherNames = [];
        $unknownDateFormats = [];
        $futureDates = [];
        $allTeachers = [];
        $allStudents = [];
        $foundTeachers = [];
        $foundStudents = [];
        $nonCleanTeachers = [];
        $nonCleanStudents = [];
        $totalSessions = 0;
        $totalSessionsMigrated = 0;
        $excelSheets = 0;
        $refusedSingleSessionsCount = 0;
        $refusedSheetsSessions = 0;
        $notFoundTeachersCount = 0;
        $sheetNum = 1;
        $count = 1;
        foreach ($sheets as $sheetName => $fileObject) {
            $student = null;
            if (($limit = $this->argument('limit')) && $count > $limit) {
                break;
            }
            if ($studentSheets->where('name', $sheetName)->first()) {
                continue;
            }
            $refusedSheet = false;
            echo $sheetName, PHP_EOL;
            if (!preg_match('/\.xlsx$/', $sheetName)) {
                echo "Not Excel Sheet\n";
                continue;
            }
            $excelSheets++;
            if (!file_exists($sheetName)) {
                echo "Invalid sheet name\n";
                $refusedSheets['invalid_sheet_name'][] = $sheetName;
                continue;
            }
            //loading the sheet
            $objPHPExcel = $objReader->load($sheetName);
            $sheet = $objPHPExcel->getActiveSheet();
            $name = strtolower(trim($sheet->getCell('A1')->getValue()));
            $courseType = strtolower(trim($sheet->getCell('P2')->getValue()));
            if (!$name) {
                echo "Blank student name\n";
                $refusedSheets['blank_student_name'][] = $sheetName;
                $refusedSheet = true;
            } elseif (!($student = $studentAccounts->where('name_lower', $name)->first())) {
                echo "Student not found\n";
                $refusedSheets['student_not_found'][] = ['sheet' => $sheetName, 'name' => $name];
                $refusedSheet = true;
            } elseif (!$courseType || strpos($courseType, 'memorization') !== FALSE || strpos($courseType, 'recitation') !== FALSE) {
                $courseId = 1;
            } elseif (strpos($courseType, 'tajweed') !== FALSE) {
                $courseId = 2;
            } elseif (strpos($courseType, 'nourania') !== FALSE) {
                $courseId = 3;
            } else {
                echo "Invalid course type\n";
                $refusedSheets['invalid_course_type'][] = $sheetName;
                $refusedSheet = true;
            }
            if ($name) {
                $allStudents[$name] = true;
            }
            if ($student) {
                $foundStudents[$name] = true;
            }
            $nonCleanStudent = false;
            $highestRow = $sheet->getHighestRow();
            $studentTeachers = [];
            $sessions = [];
            for ($rowNum = 3; $rowNum <= $highestRow; $rowNum++) {
                if (!$date = trim($sheet->getCell('B' . $rowNum)->getValue())) {
                    continue;
                }
                $totalSessions++;
                echo "{$sheetNum} ________ {$rowNum}\n";
                $teacherName = strtolower(trim($sheet->getCell('E' . $rowNum)->getValue()));
                if (!$teacherName) {
                    echo "Issue {$rowNum}: Blank teacher name\n";
                    $refusedSingleSessionsCount++;
                    $blankTeacherNames[] = ['sheet' => $sheetName, 'row' => $rowNum];
                    $nonCleanStudent = true;
                    continue;
                }
                $allTeachers[$teacherName] = true;
                if (isset($notFoundTeachers[$teacherName])) {
                    $refusedSingleSessionsCount++;
                    $notFoundTeachers[$teacherName]['count'] ++;
                    $notFoundTeachersCount++;
                    $notFoundTeachers[$teacherName]['hits'][] = ['sheet' => $sheetName, 'row' => $rowNum];
                    $nonCleanStudent = true;
                    continue;
                }
                if (!in_array($teacherName, $studentTeachers)) { // this teacher didn't teach to this student before
                    if (!$teacherAccounts->where('name_lower', $teacherName)->first()) {
                        echo "Issue {$rowNum}: Teacher name not found\t{$teacherName}\n";
                        $refusedSingleSessionsCount++;
                        $notFoundTeachersCount++;
                        $notFoundTeachers[$teacherName] = [];
                        $notFoundTeachers[$teacherName]['count'] = 1;
                        $notFoundTeachers[$teacherName]['hits'] = [];
                        $notFoundTeachers[$teacherName]['hits'][] = ['sheet' => $sheetName, 'row' => $rowNum];
                        $nonCleanStudent = true;
                        continue;
                    }
                    $studentTeachers[] = $teacherName; //mark this teacher
                }
                $foundTeachers[$teacherName] = true;
                if (is_string($date) && !is_numeric($date)) {
                    if (!$dateConverted = $this->parseDate($date)) {
                        $unknownDateFormats[] = ['sheet' => $sheetName, 'row' => $rowNum];
                    }
                    $refusedSingleSessionsCount++;
                    $nonCleanStudent = true;
                    $nonCleanTeachers[$teacherName] = true;
                    continue;
                } else {
                    $dateConverted = $this->getRealDate($date);
                }
                if ($dateConverted > date_create_from_format('Y-m-d', '2018-01-01')) {
                    $futureDates[] = ['sheet' => $sheetName, 'row' => $rowNum];
                    $refusedSingleSessionsCount++;
                    $nonCleanTeachers[$teacherName] = true;
                    $nonCleanStudent = true;
                    continue;
                }
                if ($refusedSheet) {
                    $refusedSheetsSessions++;
                    $nonCleanTeachers[$teacherName] = true;
                    continue;
                }
                echo "ROW Okay :)\n";
                $session = [];
                $session['date'] = $dateConverted->format('Y-m-d');
                $session['course_id'] = $courseId;
                $session['student_name'] = $name;
                $session['teacher_name'] = $teacherName;
                $session['student_attendance'] = strtolower(trim($sheet->getCell('F' . $rowNum)->getValue()));
                $session['student_attendance_comments'] = trim($sheet->getCell('G' . $rowNum)->getValue());
                $session['duration'] = trim($sheet->getCell('H' . $rowNum)->getValue());
                $session['material'] = trim($sheet->getCell('I' . $rowNum)->getValue());
                $session['homework'] = trim($sheet->getCell('J' . $rowNum)->getValue());
                $session['homework_completed'] = trim($sheet->getCell('K' . $rowNum)->getValue());
                $session['homework_proficiency'] = trim($sheet->getCell('L' . $rowNum)->getValue());
                $session['student_sheet'] = $sheetName;
                $session['student_row'] = $rowNum;
                $sessions[] = $session;
                $totalSessionsMigrated++;
            }
            if ($nonCleanStudent && !$refusedSheet) {
                $nonCleanStudents[$name] = true;
            }
            $newStudentSheets[] = ['name' => $sheetName];
            if ($refusedSheet) {
                continue;
            }
            $count++;
            $sheetNum++;
            echo "inserting sheet sessions to DB\n";
            DB::table('student_sessions')->insert($sessions);
            // Freeing Memory
            $objPHPExcel->__destruct();
            $sheet->__destruct();
            $objPHPExcel = $sheet = NULL;
            unset($objPHPExcel);
            unset($sheet);
        }
//        DB::table('student_sheets')->insert($newStudentSheets);
        $this->output("Total excel sheets: {$excelSheets}\n");
        $refusedSheetsCount = count($refusedSheets['invalid_sheet_name']) + count($refusedSheets['blank_student_name']) + count($refusedSheets['student_not_found']) + count($refusedSheets['invalid_course_type']);
        $this->output("Total refused Sheets: {$refusedSheetsCount}\n");
        $this->output("Invalid Sheet Names: " . count($refusedSheets['invalid_sheet_name']) . PHP_EOL);
        foreach ($refusedSheets['invalid_sheet_name'] as $sheet) {
            $this->output("\t$sheet\n");
        }
        $this->output("Blank Student Names: " . count($refusedSheets['blank_student_name']) . PHP_EOL);
        foreach ($refusedSheets['blank_student_name'] as $sheet) {
            $this->output("\t$sheet\n");
        }
        $this->output("Not Found Student Names: " . count($refusedSheets['student_not_found']) . PHP_EOL);
        foreach ($refusedSheets['student_not_found'] as $entry) {
            $this->output("\t" . $entry['sheet'] . "\t" . ucwords($entry['name']) . "\n");
        }
        $this->output("Invalid Course type: " . count($refusedSheets['invalid_course_type']) . PHP_EOL);
        foreach ($refusedSheets['invalid_course_type'] as $sheet) {
            $this->output("\t$sheet\n");
        }
        $this->output("Total refused whole sheet Sessions: {$refusedSheetsSessions}\n");
        $refusedSessions = $refusedSingleSessionsCount + $refusedSheetsSessions;
        $this->output("Total refused Single Sessions: {$refusedSingleSessionsCount}\n");
        $this->output('Invalid/unknown date format: ' . count($unknownDateFormats) . PHP_EOL);
        foreach ($unknownDateFormats as $entry) {
            $this->output("\t" . $entry['sheet'] . "  :  " . $entry['row'] . PHP_EOL);
        }
        $this->output('dates in future: ' . count($futureDates) . PHP_EOL);
        foreach ($futureDates as $entry) {
            $this->output("\t" . $entry['sheet'] . "  :  " . $entry['row'] . PHP_EOL);
        }
        $this->output("Blank Teacher Names: " . count($blankTeacherNames) . PHP_EOL);
        foreach ($blankTeacherNames as $blank) {
            $this->output("\t" . $blank['sheet'] . "\t" . $blank['row'] . PHP_EOL);
        }
        $this->output("Not found teacher names: " . count($notFoundTeachers) . "(Hits " . $notFoundTeachersCount . ")\n");
        foreach ($notFoundTeachers as $teacher => $list) {
            $this->output("\t" . ucwords($teacher) . ":" . $list['count'] . "\n");
        }
        $realAllTeachers = array_keys($allTeachers);
        $realFoundTeahcers = array_keys($foundTeachers);
        $realNonCleanTeachers = array_keys($nonCleanTeachers);
        $realCleanTeachers = array_diff($realFoundTeahcers, $realNonCleanTeachers);
        $realAllStudents = array_keys($allStudents);
        $realFoundStudents = array_keys($foundStudents);
        $realNonCleanStudents = array_keys($nonCleanStudents);
        $realCleanStudents = array_diff($realFoundStudents, $realNonCleanStudents);
        $this->output("All students encountered:" . count($allStudents) . PHP_EOL);
        foreach ($realAllStudents as $student) {
            $this->output("\t" . ucwords($student) . "\n");
        }
        $this->output("All found students:" . count($foundStudents) . PHP_EOL);
        foreach ($realFoundStudents as $student) {
            $this->output("\t" . ucwords($student) . "\n");
        }
        $this->output("All students with issues:" . count($realNonCleanStudents) . PHP_EOL);
        foreach ($realNonCleanStudents as $student) {
            $this->output("\t" . ucwords($student) . "\n");
        }
        $cleanStudentsToSave = [];
        $this->output("All clean students:" . count($realCleanStudents) . PHP_EOL);
        foreach ($realCleanStudents as $student) {
            $this->output("\t" . ucwords($student) . "\n");
            $cleanStudentsToSave[] = ['name' => $student];
        }
        $this->output("All teachers encountered:" . count($allTeachers) . PHP_EOL);
        foreach ($realAllTeachers as $teacher) {
            $this->output("\t" . ucwords($teacher) . "\n");
        }
        $this->output("All found teachers:" . count($foundTeachers) . PHP_EOL);
        foreach ($realFoundTeahcers as $teacher) {
            $this->output("\t" . ucwords($teacher) . "\n");
        }
        $this->output("All teachers with issues:" . count($realNonCleanTeachers) . PHP_EOL);
        foreach ($realNonCleanTeachers as $teacher) {
            $this->output("\t" . ucwords($teacher) . "\n");
        }
        $this->output("All clean teachers:" . count($realCleanTeachers) . PHP_EOL);
        $cleanTeachersToSave = [];
        foreach ($realCleanTeachers as $teacher) {
            $this->output("\t" . ucwords($teacher) . "\n");
            $cleanTeachersToSave[] = ['name' => $teacher];
        }
        DB::table('s_clean_students')->insert($cleanStudentsToSave);
        DB::table('s_clean_teachers')->insert($cleanTeachersToSave);
        $this->output("************************** TEACHER NOT FOUND DETAILS**********************\n");
        foreach ($notFoundTeachers as $teacher => $list) {
            $this->output("\t{$teacher}:" . $list['count'] . "\n");
            foreach ($list['hits'] as $entry) {
                $this->output("\t\t" . $entry['sheet'] . "\t" . $entry['row'] . PHP_EOL);
            }
        }
        $this->output("************************** END TEACHER NOT FOUND DETAILS**********************\n");
        $this->output("Total excel sheets: {$excelSheets}\n");
        $this->output("Total refused Sheets: {$refusedSheetsCount}\n");
        $this->output("Total refused whole sheet Sessions: {$refusedSheetsSessions}\n");
        $this->output("Total refused Single Sessions: {$refusedSingleSessionsCount}\n");
        $this->output("Total refused Sessions: " . $refusedSessions . PHP_EOL);
        $this->output("Total Sessions: " . $totalSessions . PHP_EOL);
        $this->output("Total sessions migrated successfuly: {$totalSessionsMigrated} sessions\n");
        $t = time() - $t;
        $this->output("Total time: {$t} seconds || " . ($t / 60) . " minutes\n");
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

    public function parseDate($dateString) {
        foreach ($this->dateFormats as $format) {
            $date = date_create_from_format($format, $dateString);
            if ($date) {
                return $date;
            }
        }
        return false;
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

}
