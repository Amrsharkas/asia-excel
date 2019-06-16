<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use Illuminate\Console\Command;
use DB;
use PHPExcel_IOFactory;
use PHPExcel_CachedObjectStorageFactory;
use PHPExcel_Settings;
use App\User;
use App\Entry;
use App\Course;
use Carbon\Carbon;
use Mail;


class reminderController extends Controller
{
    public function showUploadFile(Request $request)
    {
        $file = $request->file('file');
   
      
   
        //Move Uploaded File
        $timestamp = strtotime(date("Y-m-d H:i:s"));
        $destinationPath = public_path('sheets' . DIRECTORY_SEPARATOR . $timestamp);
        $file->move($destinationPath, $file->getClientOriginalName());
        $this->handle($timestamp, $file->getClientOriginalName());
    }

   
    public function handle($timestamp, $file)
    {
        $t = time();
        $this->initDateFormats();
        // $resultsFileName = date('Y_m_d_i_s') . '_' . (microtime(true) * 10000) . '_' . "students";
        // $this->resultsFile = public_path('results' . DIRECTORY_SEPARATOR . $resultsFileName);
        // file_put_contents($this->resultsFile, '');
        $cacheMethod = PHPExcel_CachedObjectStorageFactory::cache_to_sqlite3;
        if (!PHPExcel_Settings::setCacheStorageMethod($cacheMethod)) {
            echo "Unable to set Cell Caching using ", $cacheMethod, " method, reverting to memory", PHP_EOL;
            exit;
        }
        $general_student_sheet = public_path('sheets' . DIRECTORY_SEPARATOR . $timestamp . DIRECTORY_SEPARATOR . $file);
        $objReader = PHPExcel_IOFactory::createReader('Excel2007');
        $objReader->setLoadSheetsOnly('Report');
        $objReader->setReadDataOnly(true);
        echo 'Loading clients sheet', PHP_EOL;
        echo '<br>';
        $objPHPExcel = $objReader->load($general_student_sheet);
        $sheet = $objPHPExcel->getActiveSheet();
        
        
        $highestRow = $sheet->getHighestRow();
        $entry = new Entry();
        $entry->save();
        for ($rowNum = 2; $rowNum <= $highestRow; $rowNum++) {
            $lead_type = strtolower(trim($sheet->getCell('A' . $rowNum)->getValue()));
            $name = strtolower(trim($sheet->getCell('B' . $rowNum)->getValue()));
            $mobile1 = strtolower(trim($sheet->getCell('C' . $rowNum)->getValue()));
            $mobile2 = strtolower(trim($sheet->getCell('D' . $rowNum)->getValue()));
            $email = strtolower(trim($sheet->getCell('E' . $rowNum)->getValue()));
            $course_name = strtolower(trim($sheet->getCell('F' . $rowNum)->getValue()));
            $remarks = strtolower(trim($sheet->getCell('G' . $rowNum)->getValue()));
            $follow_up_time = $sheet->getCell('H' . $rowNum)->getValue() ;
            $appointment_time =$sheet->getCell('I' . $rowNum)->getValue() ;
            $last_call_time = $sheet->getCell('J' . $rowNum)->getValue() ;

            if($mobile1 != ""){
                $check_mobile1 = User::
                where('phone', $mobile1)
                ->orWhere('mobile2', $mobile1)
                ->count();
            }
            if($mobile2 != ""){
                $check_mobile2 = User::
                where('phone', $mobile2)
                ->orWhere('mobile2', $mobile2)
                ->count();
            }
            $check_email = User::
            where('email', $email)
            ->count();
            if ($check_mobile1 > 0 || $check_mobile1 > 0 ) {
                echo "<span style='color:red;'>".$name." - ".$follow_up_time." Duplicate Phone Number (This client was not imported)</span>";
                echo '<br>';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) && $email != "") {
                echo "<span style='color:red;'>".$name." - ".$email." Invalid Email Address (This client was not imported)</span>";
                echo '<br>';
            } elseif ($check_email > 0 && $email != "") {
                echo "<span style='color:red;'>".$name." - ".$email." Duplicate Email Address (This client was not imported)</span>";
                echo '<br>';
            } else {
                $new_user = new User();
                $new_user->name = $name;
                $new_user->lead_type = $lead_type;
                $new_user->email = $email;
                $new_user->phone = $mobile1;
                $new_user->mobile2 = $mobile2;
                $new_user->remarks = $remarks;
                $new_user->admin_show = 1;
                $new_user->role_id = 4;
                $new_user->entry_id = $entry->id;
                $new_user->follow_up = date("Y-m-d H:i:s", strtotime($follow_up_time));
                $new_user->appointment = date("Y-m-d  H:i:s", strtotime($appointment_time));
                $new_user->last_call = date("Y-m-d  H:i:s", strtotime($last_call_time));
                if ($course_name != "") {
                    $course = Course::where("name", $course_name)->first();
                    if (!$course) {
                        $course = new Course();
                        $course->name = $course_name;
                        $course->admin_show = 1;
                        $course->save();
                    }
                    $new_user->course_id = $course->id;
                }
                $new_user->save();
                echo "<span style='color:green;'>".$name." - ".$follow_up_time." Client Imported :) </span>";
                echo '<br>';
            }
        }
        echo 'Done :)';
    }
    public function initDateFormats()
    {
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

    public function getRealDate($googleTimestamp)
    {
        return Carbon::createFromDate('1899', '12', '30')->addDays((int) $googleTimestamp);
    }

    public function getRealDateTime($googleTimestamp)
    {
        $date = $this->getRealDate($googleTimestamp);
        $date2 = Carbon::parse('1899-12-30');
        $days = $date->diffInDays($date2);
        $seconds = ($googleTimestamp - $days) * 24 * 60 * 60;
        return Carbon::createFromDate('1899', '12', '30')->addDays($days)->addSeconds($seconds);
    }
    function date3339($timestamp=0) {

    if (!$timestamp) {
        $timestamp = time();
    }
    $date = date('Y-m-d\TH:i:s', $timestamp);

    $matches = array();
    if (preg_match('/^([\-+])(\d{2})(\d{2})$/', date('O', $timestamp), $matches)) {
        $date .= $matches[1].$matches[2].':'.$matches[3];
    } else {
        $date .= 'Z';
    }
    return $date;
}
    public function parseDate($dateString)
    {
        foreach ($this->dateFormats as $format) {
            $date = date_create_from_format($format, $dateString);
            if ($date) {
                return $date;
            }
        }
        return false;
    }

    public function validateDate($date)
    {
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

    public function output($info)
    {
        echo $info;
        file_put_contents($this->resultsFile, $info, FILE_APPEND);
    }
}
