<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ElearningLesson extends Model
{
    public function module(){
        return $this->belongsTo('App\ElearningModule','module_id','id');
    }
    public function topics(){
        return $this->hasMany('App\ElearningTopic','lesson_id','id');
    }
}
