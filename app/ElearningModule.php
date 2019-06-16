<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ElearningModule extends Model {

    public function course() {
        return $this->belongsTo('App\ElearningCourse', 'course_id', 'id');
    }

    public function lessons() {
        return $this->hasMany('App\ElearningLesson', 'module_id', 'id');
    }

}
