<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use DB;

class MigrationParingSessions extends Command {

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mig:pair {limit?} ';

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
        $t = time();
        $resultsFileName = date('Y_m_d_i_s') . '_' . (microtime(true) * 10000) . '_' . "pair";
        $this->resultsFile = public_path('results' . DIRECTORY_SEPARATOR . $resultsFileName);
//        $sCLeanStudents = DB::table('s_clean_students')->pluck('name');
//        $sCLeanTeachers = DB::table('s_clean_teachers')->pluck('name');
//        $tCLeanStudents = DB::table('t_clean_students')->pluck('name');
//        $tCLeanTeachers = DB::table('t_clean_teachers')->pluck('name');
//        $cleanStudents = array_intersect($sCLeanStudents, $tCLeanStudents);
//        $cleanTeachers = array_intersect($sCLeanTeachers, $tCLeanTeachers);

        $students = collect(DB::table('accounts')->select('*', DB::raw('lower(name) as name_lower'))
                        ->where(function($query) {
                            $query->where('type', '=', 'student')->orWhere('teacher_training', '=', true);
                        })
                        ->get());
        $teachers = collect(DB::table('accounts')->select('*', DB::raw('lower(name) as name_lower'))->where('type', '=', 'teacher')->get());
        $issues = [];
        $issueIndex = 0;
        $groupNum = 1;
        $total = 0;
        $issueCount = 0;
        $inserted = 0;
        $count = 1;
        $a = 0;
        $b = 0;
        $c = 0;
        foreach ($teachers as $teacher) {
            if (($limit = $this->argument('limit')) && $count > $limit) {
                break;
            }
            $count++;
            $dates = [];
            $oneSessions = [];
            $teacherSessions = collect(
                    DB::table('teacher_sessions')
                            ->where('teacher_name', '=', $teacher->name)
                            ->get()
            );
            $studentSessions = collect(
                    DB::table('student_sessions')
                            ->where('teacher_name', '=', $teacher->name)
                            ->get()
            );
            $sessionGrps = $teacherSessions->groupBy('student_name');
            foreach ($sessionGrps as $studentName => $sessionGrp) {
                $sessionSubGrps = $sessionGrp->groupBy('course_id');
                $studentId = $students->where('name_lower', $studentName)->first()->id;
                foreach ($sessionSubGrps as $courseId => $sessionSubGrp) {
                    $sessionSubSubGrps = $sessionSubGrp->groupBy('date');
                    foreach ($sessionSubSubGrps as $date => $tchrSessions) {
                        $a++;
                        $total += $tchrSessions->count();
                        $stdSessions = $studentSessions
                                ->where('student_name', $studentName)
                                ->where('course_id', $courseId)
                                ->where('date', $date);
                        $dates[] = $date;
                        if ($stdSessions->count() == 1 && $tchrSessions->count() == 1) {
                            $stdSession = $stdSessions->first();
                            $tchrSession = $tchrSessions->first();
                            if (!$this->pairAttendance($stdSession->student_attendance, $tchrSession->teacher_attendance)) {
                                $issueCount++;
                                $b++;
                                $issues[++$issueIndex] = [];
                                $issues[$issueIndex]['type'] = 1;
                                $issues[$issueIndex]['msg'] = 'Single, mismatch#1';
                                $issues[$issueIndex]['s_sheet'] = $stdSession->student_sheet;
                                $issues[$issueIndex]['t_sheet'] = $tchrSession->teacher_sheet;
                                $issues[$issueIndex]['teacher'] = $teacher->name;
                                $issues[$issueIndex]['student'] = $studentName;
                                $issues[$issueIndex]['date'] = $date;
                                $issues[$issueIndex]['t_rows'] = [
                                    ['row' => $tchrSession->teacher_row, 'val' => $tchrSession->teacher_attendance]
                                ];
                                $issues[$issueIndex]['s_rows'] = [
                                    ['row' => $stdSession->student_row, 'val' => $stdSession->student_attendance]
                                ];
                                continue;
                            }
                            $inserted++;
                            $c++;
                            $oneSession = [];
                            $oneSession['teacher_id'] = $teacher->id;
                            $oneSession['student_id'] = $studentId;
                            $oneSession['course_id'] = $courseId;
                            $oneSession['session_date'] = $date;
                            $oneSession['material'] = $tchrSession->material;
                            $oneSession['homework'] = $tchrSession->homework;
                            $oneSession['student_attendance'] = $stdSession->student_attendance;
                            $oneSession['teacher_attendance'] = $tchrSession->teacher_attendance;
                            $oneSessions[] = $oneSession;
                        } elseif ($stdSessions->count() == 0 && $tchrSessions->count() == 1) {
                            $tchrSession = $tchrSessions->first();
                            if ($tchrSession->teacher_attendance != 'reschedule') {
                                $b++;
                                $issueCount++;
                                $issues[++$issueIndex] = [];
                                $issues[$issueIndex]['type'] = 2;
                                $issues[$issueIndex]['msg'] = 'Single, mismatch#2';
                                $issues[$issueIndex]['t_sheet'] = $tchrSession->teacher_sheet;
                                $issues[$issueIndex]['s_sheet'] = 'NONE';
                                $issues[$issueIndex]['teacher'] = $teacher->name;
                                $issues[$issueIndex]['student'] = $studentName;
                                $issues[$issueIndex]['date'] = $date;
                                $issues[$issueIndex]['s_rows'] = [];
                                $issues[$issueIndex]['t_rows'] = [
                                    ['row' => $tchrSession->teacher_row, 'val' => $tchrSession->teacher_attendance]
                                ];
                                continue;
                            }
                            $inserted++;
                            $c++;
                            $oneSession = [];
                            $oneSession['teacher_id'] = $teacher->id;
                            $oneSession['student_id'] = $studentId;
                            $oneSession['course_id'] = $courseId;
                            $oneSession['session_date'] = $date;
                            $oneSession['material'] = $tchrSession->material;
                            $oneSession['homework'] = $tchrSession->homework;
                            $oneSession['student_attendance'] = NULL;
                            $oneSession['teacher_attendance'] = 'reschedule';
                            $oneSessions[] = $oneSession;
                        } elseif ($stdSessions->count() > $tchrSessions->count()) {
                            $countDiff = $stdSessions->count() - $tchrSessions->count();
                            $teachRescCount = $this->countTeacherReschedule($tchrSessions);
                            $stdRescCount = $this->countStudentReschedule($stdSessions);
                            $reschCountDiff = $stdRescCount - $teachRescCount;
                            if ($countDiff != $reschCountDiff) {
                                $b++;
                                $issueCount++;
                                $issues[++$issueIndex] = [];
                                $issues[$issueIndex]['type'] = 3;
                                $issues[$issueIndex]['msg'] = 'multiple, count mismatch#1';
                                $issues[$issueIndex]['s_count'] = $stdSessions->count();
                                $issues[$issueIndex]['t_count'] = $tchrSessions->count();
                                $issues[$issueIndex]['s_sheet'] = $stdSessions->first()->student_sheet;
                                $issues[$issueIndex]['t_sheet'] = ($tchrSessions->count() > 0) ? $tchrSessions->first()->teacher_sheet : 'NONE';
                                $issues[$issueIndex]['teacher'] = $teacher->name;
                                $issues[$issueIndex]['student'] = $studentName;
                                $issues[$issueIndex]['date'] = $date;
                                $issues[$issueIndex]['s_rows'] = [];
                                $issues[$issueIndex]['t_rows'] = [];
                                foreach ($stdSessions as $session) {
                                    $issues[$issueIndex]['s_rows'][] = ['row' => $session->student_row, 'val' => $session->student_attendance];
                                }
                                foreach ($tchrSessions as $session) {
                                    $issues[$issueIndex]['t_rows'][] = ['row' => $session->teacher_row, 'val' => $session->teacher_attendance];
                                }
                                continue;
                            }
                            if (!$paired = $this->pairMultiple($tchrSessions, $stdSessions)) {
                                $b++;
                                $issueCount++;
                                $issues[++$issueIndex] = [];
                                $issues[$issueIndex]['type'] = 4;
                                $issues[$issueIndex]['msg'] = 'multiple, mismatch#1';
                                $issues[$issueIndex]['s_count'] = $stdSessions->count();
                                $issues[$issueIndex]['t_count'] = $tchrSessions->count();
                                $issues[$issueIndex]['s_sheet'] = $stdSessions->first()->student_sheet;
                                $issues[$issueIndex]['t_sheet'] = ($tchrSessions->count() > 0) ? $tchrSessions->first()->teacher_sheet : 'NONE';
                                $issues[$issueIndex]['teacher'] = $teacher->name;
                                $issues[$issueIndex]['student'] = $studentName;
                                $issues[$issueIndex]['date'] = $date;
                                $issues[$issueIndex]['s_rows'] = [];
                                $issues[$issueIndex]['t_rows'] = [];
                                foreach ($stdSessions as $session) {
                                    $issues[$issueIndex]['s_rows'][] = ['row' => $session->student_row, 'val' => $session->student_attendance];
                                }
                                foreach ($tchrSessions as $session) {
                                    $issues[$issueIndex]['t_rows'][] = ['row' => $session->teacher_row, 'val' => $session->teacher_attendance];
                                }
                                continue;
                            }
                            $c++;
                            foreach ($paired as $pair) {
                                $inserted++;
                                $oneSession = [];
                                $oneSession['teacher_id'] = $teacher->id;
                                $oneSession['student_id'] = $studentId;
                                $oneSession['course_id'] = $courseId;
                                $oneSession['session_date'] = $date;
                                $oneSession['material'] = $pair['s']->material;
                                $oneSession['homework'] = $pair['s']->homework;
                                $oneSession['student_attendance'] = $pair['s']->student_attendance;
                                $oneSession['teacher_attendance'] = $pair['t']->teacher_attendance;
                                $oneSessions[] = $oneSession;
                            }
                            for ($i = 0; $i < $reschCountDiff; $i++) {
                                $inserted++;
                                $oneSession = [];
                                $oneSession['teacher_id'] = $teacher->id;
                                $oneSession['student_id'] = $studentId;
                                $oneSession['course_id'] = $courseId;
                                $oneSession['session_date'] = $date;
                                $oneSession['material'] = NULL;
                                $oneSession['homework'] = NULL;
                                $oneSession['student_attendance'] = 'reschedule';
                                $oneSession['teacher_attendance'] = NULL;
                                $oneSessions[] = $oneSession;
                            }
                            for ($i = 0; $i < $teachRescCount; $i++) {
                                $inserted++;
                                $oneSession = [];
                                $oneSession['teacher_id'] = $teacher->id;
                                $oneSession['student_id'] = $studentId;
                                $oneSession['course_id'] = $courseId;
                                $oneSession['session_date'] = $date;
                                $oneSession['material'] = NULL;
                                $oneSession['homework'] = NULL;
                                $oneSession['student_attendance'] = 'reschedule';
                                $oneSession['teacher_attendance'] = 'reschedule';
                                $oneSessions[] = $oneSession;
                            }
                        } elseif ($stdSessions->count() < $tchrSessions->count()) {
                            $countDiff = $tchrSessions->count() - $stdSessions->count();
                            $teachRescCount = $this->countTeacherReschedule($tchrSessions);
                            $stdRescCount = $this->countStudentReschedule($stdSessions);
                            $reschCountDiff = $teachRescCount - $stdRescCount;
                            if ($countDiff != $reschCountDiff) {
                                $issueCount++;
                                $b++;
                                $issues[++$issueIndex] = [];
                                $issues[$issueIndex]['type'] = 5;
                                $issues[$issueIndex]['msg'] = 'multiple, count mismatch#2';
                                $issues[$issueIndex]['s_count'] = $stdSessions->count();
                                $issues[$issueIndex]['t_count'] = $tchrSessions->count();
                                $issues[$issueIndex]['t_sheet'] = $tchrSessions->first()->teacher_sheet;
                                $issues[$issueIndex]['s_sheet'] = ($stdSessions->count() > 0) ? $stdSessions->first()->student_attendance : 'NONE';
                                $issues[$issueIndex]['teacher'] = $teacher->name;
                                $issues[$issueIndex]['student'] = $studentName;
                                $issues[$issueIndex]['date'] = $date;
                                $issues[$issueIndex]['s_rows'] = [];
                                $issues[$issueIndex]['t_rows'] = [];
                                foreach ($stdSessions as $session) {
                                    $issues[$issueIndex]['s_rows'][] = ['row' => $session->student_row, 'val' => $session->student_attendance];
                                }
                                foreach ($tchrSessions as $session) {
                                    $issues[$issueIndex]['t_rows'][] = ['row' => $session->teacher_row, 'val' => $session->teacher_attendance];
                                }
                                continue;
                            }
                            if (!$paired = $this->pairMultiple($tchrSessions, $stdSessions)) {
                                $issueCount++;
                                $b++;
                                $issues[++$issueIndex] = [];
                                $issues[$issueIndex]['type'] = 6;
                                $issues[$issueIndex]['msg'] = 'multiple, mismatch#2';
                                $issues[$issueIndex]['s_count'] = $stdSessions->count();
                                $issues[$issueIndex]['t_count'] = $tchrSessions->count();
                                $issues[$issueIndex]['t_sheet'] = $tchrSessions->first()->teacher_sheet;
                                $issues[$issueIndex]['s_sheet'] = ($stdSessions->count() > 0) ? $stdSessions->first()->student_attendance : 'NONE';
                                $issues[$issueIndex]['teacher'] = $teacher->name;
                                $issues[$issueIndex]['student'] = $studentName;
                                $issues[$issueIndex]['date'] = $date;
                                $issues[$issueIndex]['s_rows'] = [];
                                $issues[$issueIndex]['t_rows'] = [];
                                foreach ($stdSessions as $session) {
                                    $issues[$issueIndex]['s_rows'][] = ['row' => $session->student_row, 'val' => $session->student_attendance];
                                }
                                foreach ($tchrSessions as $session) {
                                    $issues[$issueIndex]['t_rows'][] = ['row' => $session->teacher_row, 'val' => $session->teacher_attendance];
                                }
                                continue;
                            }
                            $c++;
                            foreach ($paired as $pair) {
                                $inserted++;
                                $oneSession = [];
                                $oneSession['teacher_id'] = $teacher->id;
                                $oneSession['student_id'] = $studentId;
                                $oneSession['course_id'] = $courseId;
                                $oneSession['session_date'] = $date;
                                $oneSession['material'] = $pair['s']->material;
                                $oneSession['homework'] = $pair['s']->homework;
                                $oneSession['student_attendance'] = $pair['s']->student_attendance;
                                $oneSession['teacher_attendance'] = $pair['t']->teacher_attendance;
                                $oneSessions[] = $oneSession;
                            }
                            for ($i = 0; $i < $reschCountDiff; $i++) {
                                $inserted++;
                                $oneSession = [];
                                $oneSession['teacher_id'] = $teacher->id;
                                $oneSession['student_id'] = $studentId;
                                $oneSession['course_id'] = $courseId;
                                $oneSession['session_date'] = $date;
                                $oneSession['material'] = NULL;
                                $oneSession['homework'] = NULL;
                                $oneSession['student_attendance'] = NULL;
                                $oneSession['teacher_attendance'] = 'reschedule';
                                $oneSessions[] = $oneSession;
                            }
                            for ($i = 0; $i < $stdRescCount; $i++) {
                                $inserted++;
                                $oneSession = [];
                                $oneSession['teacher_id'] = $teacher->id;
                                $oneSession['student_id'] = $studentId;
                                $oneSession['course_id'] = $courseId;
                                $oneSession['session_date'] = $date;
                                $oneSession['material'] = NULL;
                                $oneSession['homework'] = NULL;
                                $oneSession['student_attendance'] = 'reschedule';
                                $oneSession['teacher_attendance'] = 'reschedule';
                                $oneSessions[] = $oneSession;
                            }
                        } else {
                            $teachRescCount = $this->countTeacherReschedule($tchrSessions);
                            $stdReschCount = $this->countStudentReschedule($stdSessions);
                            if ($teachRescCount != $stdReschCount) {
                                $b++;
                                $issueCount++;
                                $issues[++$issueIndex] = [];
                                $issues[$issueIndex]['type'] = 7;
                                $issues[$issueIndex]['msg'] = 'multiple, count mismatch#3';
                                $issues[$issueIndex]['s_count'] = $stdSessions->count();
                                $issues[$issueIndex]['t_count'] = $tchrSessions->count();
                                $issues[$issueIndex]['s_sheet'] = $stdSessions->first()->student_sheet;
                                $issues[$issueIndex]['t_sheet'] = $tchrSessions->first()->teacher_sheet;
                                $issues[$issueIndex]['teacher'] = $teacher->name;
                                $issues[$issueIndex]['student'] = $studentName;
                                $issues[$issueIndex]['date'] = $date;
                                $issues[$issueIndex]['s_rows'] = [];
                                $issues[$issueIndex]['t_rows'] = [];
                                foreach ($stdSessions as $session) {
                                    $issues[$issueIndex]['s_rows'][] = ['row' => $session->student_row, 'val' => $session->student_attendance];
                                }
                                foreach ($tchrSessions as $session) {
                                    $issues[$issueIndex]['t_rows'][] = ['row' => $session->teacher_row, 'val' => $session->teacher_attendance];
                                }
                                continue;
                            }
                            if (!$paired = $this->pairMultiple($tchrSessions, $stdSessions)) {
                                $b++;
                                $issueCount++;
                                $issues[++$issueIndex] = [];
                                $issues[$issueIndex]['type'] = 8;
                                $issues[$issueIndex]['msg'] = 'multiple, mismatch#3';
                                $issues[$issueIndex]['s_count'] = $stdSessions->count();
                                $issues[$issueIndex]['t_count'] = $tchrSessions->count();
                                $issues[$issueIndex]['s_sheet'] = $stdSessions->first()->student_sheet;
                                $issues[$issueIndex]['t_sheet'] = $tchrSessions->first()->teacher_sheet;
                                $issues[$issueIndex]['teacher'] = $teacher->name;
                                $issues[$issueIndex]['student'] = $studentName;
                                $issues[$issueIndex]['date'] = $date;
                                $issues[$issueIndex]['s_rows'] = [];
                                $issues[$issueIndex]['t_rows'] = [];
                                foreach ($stdSessions as $session) {
                                    $issues[$issueIndex]['s_rows'][] = ['row' => $session->student_row, 'val' => $session->student_attendance];
                                }
                                foreach ($tchrSessions as $session) {
                                    $issues[$issueIndex]['t_rows'][] = ['row' => $session->teacher_row, 'val' => $session->teacher_attendance];
                                }
                                continue;
                            }
                            $c++;
                            foreach ($paired as $pair) {
                                $inserted++;
                                $oneSession = [];
                                $oneSession['teacher_id'] = $teacher->id;
                                $oneSession['student_id'] = $studentId;
                                $oneSession['course_id'] = $courseId;
                                $oneSession['session_date'] = $date;
                                $oneSession['material'] = $pair['s']->material;
                                $oneSession['homework'] = $pair['s']->homework;
                                $oneSession['student_attendance'] = $pair['s']->student_attendance;
                                $oneSession['teacher_attendance'] = $pair['t']->teacher_attendance;
                                $oneSessions[] = $oneSession;
                            }
                            for ($i = 0; $i < $teachRescCount; $i++) {
                                $inserted++;
                                $oneSession = [];
                                $oneSession['teacher_id'] = $teacher->id;
                                $oneSession['student_id'] = $studentId;
                                $oneSession['course_id'] = $courseId;
                                $oneSession['session_date'] = $date;
                                $oneSession['material'] = NULL;
                                $oneSession['homework'] = NULL;
                                $oneSession['student_attendance'] = 'reschedule';
                                $oneSession['teacher_attendance'] = 'reschedule';
                                $oneSessions[] = $oneSession;
                            }
                        }
                    }
                }
            }
            $sessions = DB::table('student_sessions')
                    ->where('teacher_name', '=', $teacher->name)
                    ->whereNotIn('date', $dates)
                    ->get();
            foreach ($sessions as $session) {
                $a++;
                if ($session->student_attendance != 'reschedule') {
                    $b++;
                    $issueCount++;
                    $issues[++$issueIndex] = [];
                    $issues[$issueIndex]['type'] = 9;
                    $issues[$issueIndex]['msg'] = 'Single, mismatch#3';
                    $issues[$issueIndex]['s_sheet'] = $session->student_sheet;
                    $issues[$issueIndex]['t_sheet'] = 'NONE';
                    $issues[$issueIndex]['teacher'] = $teacher->name;
                    $issues[$issueIndex]['student'] = $session->student_name;
                    $issues[$issueIndex]['date'] = $session->date;
                    $issues[$issueIndex]['t_rows'] = [];
                    $issues[$issueIndex]['s_rows'] = [
                        ['row' => $session->student_row, 'val' => $session->student_attendance]
                    ];
                } else {
                    $c++;
                    $inserted++;
                    $oneSession = [];
                    $oneSession['teacher_id'] = $teacher->id;
                    $oneSession['student_id'] = $students->where('name_lower', $session->student_name)->first()->id;
                    $oneSession['course_id'] = $session->course_id;
                    $oneSession['session_date'] = $session->date;
                    $oneSession['material'] = $session->material;
                    $oneSession['homework'] = $session->homework;
                    $oneSession['student_attendance'] = 'reschedule';
                    $oneSession['teacher_attendance'] = null;
                    $oneSessions[] = $oneSession;
                }
            }
            echo "\rInserting group {$groupNum}";
            $groupNum++;
            DB::table('one_sessions')->insert($oneSessions);
        }
        echo PHP_EOL;
        var_dump($b);
        var_dump($c);
        var_dump($b + $c);
        var_dump($a);
        echo "Done..\n";
        $issuesCount = count($issues);
        $this->output("Issues ({$issuesCount}):\n");
        $issuesCollection = collect($issues);
        $issuesGroups = $issuesCollection->groupBy('type');
        foreach ($issuesGroups as $type => $group) {
            echo "Type $type: " . $group->count() . PHP_EOL;
        }
        var_dump($issueCount);
        var_dump($inserted);
        var_dump($total);
        foreach ($issues as $issues) {
            $this->output(print_r($issues, true), false);
        }
        $this->output("Total time: " . ( time() - $t) . " seconds\n");
    }

    public function output($info, $echo = true) {
        if ($echo)
            echo $info;
        file_put_contents($this->resultsFile, $info, FILE_APPEND);
    }

    function countAttr($list, $attr, $value) {
        $count = 0;
        foreach ($list as $element) {
            if ($element->$attr == $value) {
                $count++;
            }
        }
        return $count;
    }

    function countTeacherReschedule($list) {
        return $this->countAttr($list, 'teacher_attendance', 'reschedule');
    }

    function countStudentReschedule($list) {
        return $this->countAttr($list, 'student_attendance', 'reschedule');
    }

    function countTeacherNoShow($list) {
        return $this->countAttr($list, 'teacher_attendance', 'attended no show');
    }

    public function pairAttendance($studentAttendance, $teacherAttendance) {
        $cases = [
            'attended_attended',
            'attended_late',
            'attended_attended no show',
            'late_late',
            'late_attended',
            'late_absent',
            'absent_absent',
            'absent_attended no show',
            'attended no show_absent',
            'attended no show_reschedule',
            'reschedule_reschedule',
            'attended no show_attended no show'
        ];
        if (in_array("{$teacherAttendance}_{$studentAttendance}", $cases)) {
            return true;
        } else {
            return false;
        }
    }

    function pairMultiple($teacherList, $studentList) {
        $pairs = [];
        $index = 0;
        foreach ($studentList as $session) {
            if ($session->student_attendance != 'reschedule') {
                $pairs[$index]['s'] = $session;
                $index++;
            }
        }
        $index = 0;
        foreach ($teacherList as $session) {
            if ($session->teacher_attendance != 'reschedule') {
                $pairs[$index]['t'] = $session;
                $index++;
            }
        }
        foreach ($pairs as $pair) {
            if (!$this->pairAttendance($pair['s']->student_attendance, $pair['t']->teacher_attendance)) {
                return false;
            }
        }
        return $pairs;
    }

}
