<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\ElearningCourse;
use App\ElearningContent;
use DB;

class DataController extends Controller {

    public function courses() {
        DB::enableQueryLog();
        $t = microtime(true);
        for ($i = 1; $i < 100; $i++) {
            $course = ElearningCourse::first();
            $contents = ElearningContent::where('course_id', '=', $course->id)->get();
        }
        echo microtime(true) - $t, '<br>';
        $t = microtime(true);

        for ($i = 1; $i < 100; $i++)
            $course = ElearningCourse::with('modules.lessons.topics')->first();
        echo microtime(true) - $t, '<br>';

//        dump(DB::getQueryLog());
//        dd($courses);
//        dd($course->modules->first());
    }

    public function course() {
        $course = ElearningCourse::with('modules.lessons.topics')->with('prerequisite')
                ->with('unlocks')->first();
        $unlock_extensions = $course->unlocks->where('extend','=',true);
        $unlock_periods = $course->unlocks->where('extend','=',false);
        dd(json_decode($course->unlock_periods));
    }

}
