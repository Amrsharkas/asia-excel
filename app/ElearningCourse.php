<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ElearningCourse extends Model {

    public function modules() {
        return $this->hasMany('App\ElearningModule', 'course_id', 'id');
    }

    // a course has single prerequisite
    public function prerequisite() {
        return $this->belongsTo('App\ElearningCourse', 'prerequisite_id', 'id');
    }

    // but the same course may has many dependents
    public function dependents() {
        return $this->hasMany('App\ElearningCourse', 'prerequisite_id', 'id');
    }

    public function unlocks() {
        return $this->hasMany('App\ElearningUnlock', 'course_id', 'id');
    }

}
