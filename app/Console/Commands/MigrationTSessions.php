<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use DB;
use PHPExcel_IOFactory;
use PHPExcel_CachedObjectStorageFactory;
use PHPExcel_Settings;
use Carbon\Carbon;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class MigrationTSessions extends Command {

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mig:tsessions {limit?}';

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
        $resultsFileName = date('Y_m_d_i_s') . '_' . (microtime(true) * 10000) . '_' . "tsessions";
        $this->resultsFile = public_path('results' . DIRECTORY_SEPARATOR . $resultsFileName);
        file_put_contents($this->resultsFile, ''); //Empty file if exists
        // preparing excel reader
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
        echo "Geting a list of all files in the \"Teachers\" directory..\n";
        $path = public_path('sheets' . DIRECTORY_SEPARATOR . 'Teachers Reports');
        $sheets = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS));
        // retreiving students and teachers
        echo "Retreiving all students and teachers..\n";
        $studentAccounts = collect(DB::table('accounts')->select('*', DB::raw('lower(name) as name_lower'))->where('type', '=', 'student')->orWhere('teacher_training', '=', true)->get());
        $teacherAccounts = collect(DB::table('accounts')->select('*', DB::raw('lower(name) as name_lower'))->where('type', '=', 'teacher')->get());
        $this->output("Total students retreived: " . $studentAccounts->count() . PHP_EOL);
        $this->output("Total teachers retreived: " . $teacherAccounts->count() . PHP_EOL);
        //initialzing variables
        $teacherSheets = collect(DB::table('student_sheets')->get());
        $newTeacherSheets = [];
        $refusedSheets = ['invalid_sheet_name' => [], 'blank_teacher_name' => [], 'teacher_not_found' => [], 'invalid_course_type' => []];
        $notFoundStudents = [];
        $blankStudentNames = [];
        $unknownDateFormats = [];
        $futureDates = [];
        $allTeachers = [];
        $allStudents = [];
        $foundTeachers = [];
        $foundStudents = [];
        $nonCleanTeachers = [];
        $nonCleanStudents = [];
        $totalSessions = 0;
        $excelSheets = 0;
        $refusedSheetsSessions = 0;
        $notFoundStudentsCount = 0;
        $totalSessionsMigrated = 0;
        $refusedSingleSessionsCount = 0;
        $sheetNum = 1;
        $count = 1;
        foreach ($sheets as $sheetName => $fileObject) {
            $teacher = null;
            if (($limit = $this->argument('limit')) && $count > $limit) {
                break;
            }
            if ($teacherSheets->where('name', $sheetName)->first()) {
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
                echo "Blank teacher name\n";
                $refusedSheets['blank_teacher_name'][] = $sheetName;
                $refusedSheet = true;
            } elseif (!$teacher = $teacherAccounts->where('name_lower', $name)->first()) {
                echo "Teacher not found\n";
                $refusedSheets['teacher_not_found'][] = ['sheet' => $sheetName, 'name' => $name];
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
                $allTeachers[$name] = true;
            }
            if ($teacher) {
                $foundTeachers[$name] = true;
            }
            $highestRow = $sheet->getHighestRow();
            $teacherStudents = [];
            $sessions = [];
            $nonCleanTeacher = false;
            for ($rowNum = 3; $rowNum <= $highestRow; $rowNum++) {
                if (!$date = trim($sheet->getCell('C' . $rowNum)->getValue())) {
                    continue;
                }
                $totalSessions++;
                if (!$refusedSheet) {
                    echo "{$sheetNum} ________ {$rowNum}\n";
                }
                $studentName = strtolower(trim($sheet->getCell('B' . $rowNum)->getValue()));
                if (!$studentName) {
                    echo "Issue {$rowNum}: Blank student name\n";
                    $refusedSingleSessionsCount++;
                    $blankStudentNames[] = ['sheet' => $sheetName, 'row' => $rowNum];
                    $nonCleanTeacher = true;
                    continue;
                }
                $allStudents[$studentName] = true;
                if (isset($notFoundStudents[$studentName])) {
                    $refusedSingleSessionsCount++;
                    $notFoundStudentsCount++;
                    $notFoundStudents[$studentName]['count'] ++;
                    $notFoundStudents[$studentName]['hits'][] = ['sheet' => $sheetName, 'row' => $rowNum];
                    $nonCleanTeacher = true;
                    continue;
                }
                if (!in_array($studentName, $teacherStudents)) { // this teacher didn't teach to this student before
                    if (!$studentAccounts->where('name_lower', $studentName)->first()) {
                        echo "Issue {$rowNum}: Student name not found\t{$studentName}\n";
                        $refusedSingleSessionsCount++;
                        $notFoundStudentsCount++;
                        $notFoundStudents[$studentName] = [];
                        $notFoundStudents[$studentName]['count'] = 1;
                        $notFoundStudents[$studentName]['hits'] = [];
                        $notFoundStudents[$studentName]['hits'][] = ['sheet' => $sheetName, 'row' => $rowNum];
                        $nonCleanTeacher = true;
                        continue;
                    }
                    $teacherStudents[] = $studentName; //mark this teacher
                }
                $foundStudents[$studentName] = true;
                if (is_string($date) && !is_numeric($date)) {
                    if (!$dateConverted = $this->parseDate($date)) {
                        $unknownDateFormats[] = ['sheet' => $sheetName, 'row' => $rowNum];
                    }
                    $refusedSingleSessionsCount++;
                    $nonCleanStudents[$studentName] = true;
                    $nonCleanTeacher = true;
                    continue;
                } else {
                    $dateConverted = $this->getRealDate($date);
                }
                if ($dateConverted > date_create_from_format('Y-m-d', '2018-01-01')) {
                    $futureDates[] = ['sheet' => $sheetName, 'row' => $rowNum];
                    $refusedSingleSessionsCount++;
                    $nonCleanStudents[$studentName] = true;
                    $nonCleanTeacher = true;
                    continue;
                }

                if ($refusedSheet) {
                    $refusedSheetsSessions++;
                    $nonCleanStudents[$studentName] = true;
                    continue;
                }
                echo "ROW Okay :)\n";
                $session = [];
                $session['date'] = $dateConverted->format('Y-m-d');
                $session['course_id'] = $courseId;
                $session['teacher_name'] = $name;
                $session['student_name'] = $studentName;
                $session['teacher_attendance'] = strtolower(trim($sheet->getCell('F' . $rowNum)->getValue()));
                $session['teacher_attendance_comments'] = trim($sheet->getCell('G' . $rowNum)->getValue());
                $session['duration'] = trim($sheet->getCell('H' . $rowNum)->getValue());
                $session['material'] = trim($sheet->getCell('I' . $rowNum)->getValue());
                $session['homework'] = trim($sheet->getCell('J' . $rowNum)->getValue());
                $session['homework_completed'] = trim($sheet->getCell('K' . $rowNum)->getValue());
                $session['homework_proficiency'] = trim($sheet->getCell('L' . $rowNum)->getValue());
                $session['teacher_sheet'] = $sheetName;
                $session['teacher_row'] = $rowNum;
                $sessions[] = $session;
                $totalSessionsMigrated++;
            }
            if ($nonCleanTeacher && !$refusedSheet) {
                $nonCleanTeachers[$name] = true;
            }
            $newTeacherSheets[] = ['name' => $sheetName];
            if ($refusedSheet) {
                continue;
            }
            $count++; //don't count refused sheets
            $sheetNum++; //only to display current sheet number
            echo "inserting sheet sessions to DB\n";
            DB::table('teacher_sessions')->insert($sessions);
            // Freeing Memory
            $objPHPExcel->__destruct();
            $sheet->__destruct();
            $objPHPExcel = $sheet = NULL;
            unset($objPHPExcel);
            unset($sheet);
        }
//        DB::table('teacher_sheets')->insert($newTeacherSheets);
        $this->output("Total excel sheets: {$excelSheets}\n");
        $refusedSheetsCount = count($refusedSheets['invalid_sheet_name']) + count($refusedSheets['blank_teacher_name']) + count($refusedSheets['teacher_not_found']) + count($refusedSheets['invalid_course_type']);
        $this->output("Total refused Sheets: {$refusedSheetsCount}\n");
        $this->output("Invalid Sheet Names: " . count($refusedSheets['invalid_sheet_name']) . PHP_EOL);
        foreach ($refusedSheets['invalid_sheet_name'] as $sheet) {
            $this->output("\t$sheet\n");
        }
        $this->output("Blank Teacher Names: " . count($refusedSheets['blank_teacher_name']) . PHP_EOL);
        foreach ($refusedSheets['blank_teacher_name'] as $sheet) {
            $this->output("\t$sheet\n");
        }
        $this->output("Not Found Teacher Names: " . count($refusedSheets['teacher_not_found']) . PHP_EOL);
        foreach ($refusedSheets['teacher_not_found'] as $entry) {
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
        $this->output("Blank Student Names: " . count($blankStudentNames) . PHP_EOL);
        foreach ($blankStudentNames as $blank) {
            $this->output("\t" . $blank['sheet'] . "\t" . $blank['row'] . PHP_EOL);
        }
        $this->output("Not found student names: " . count($notFoundStudents) . "(hits: " . $notFoundStudentsCount . ")" . PHP_EOL);
        foreach ($notFoundStudents as $student => $list) {
            $this->output("\t" . ucwords($student) . ":" . $list['count'] . "\n");
        }
        $realAllTeachers = array_keys($allTeachers);
        $realFoundTeahcers = array_keys($foundTeachers);
        $realNonCleanTeachers = array_keys($nonCleanTeachers);
        $realCleanTeachers = array_diff($realFoundTeahcers, $realNonCleanTeachers);
        $realAllStudents = array_keys($allStudents);
        $realFoundStudents = array_keys($foundStudents);
        $realNonCleanStudents = array_keys($nonCleanStudents);
        $realCleanStudents = array_diff($realFoundStudents, $realNonCleanStudents);
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
        $cleanTeachersToSave = [];
        $this->output("All clean teachers:" . count($realCleanTeachers) . PHP_EOL);
        foreach ($realCleanTeachers as $teacher) {
            $this->output("\t" . ucwords($teacher) . "\n");
            $cleanTeachersToSave[] = ['name' => $teacher];
        }
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
        $this->output("All clean students:" . count($realCleanStudents) . PHP_EOL);
        $cleanStudentsToSave = [];
        foreach ($realCleanStudents as $student) {
            $this->output("\t" . ucwords($student) . "\n");
            $cleanStudentsToSave[] = ['name' => $student];
        }
        DB::table('t_clean_students')->insert($cleanStudentsToSave);
        DB::table('t_clean_teachers')->insert($cleanTeachersToSave);
        $this->output("************************** STUDENT NOT FOUND DETAILS**********************\n");
        foreach ($notFoundStudents as $student => $list) {
            $this->output("\t{$student}:" . $list['count'] . "\n");
            foreach ($list['hits'] as $entry) {
                $this->output("\t\t" . $entry['sheet'] . "\t" . $entry['row'] . PHP_EOL);
            }
        }
        $this->output("************************** END STUDENT NOT FOUND DETAILS**********************\n");
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
