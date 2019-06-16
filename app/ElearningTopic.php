<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ElearningTopic extends Model {

    public function lesson() {
        return $this->belongsTo('App\ElearningLesson', 'lesson_id', 'id');
    }

}
