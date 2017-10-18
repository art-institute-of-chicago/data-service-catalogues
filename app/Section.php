<?php

namespace App;

use App\BaseModel;

class Section extends BaseModel
{

    public function publication()
    {

        return $this->belongsTo('App\Publication');

    }

    public function parent()
    {

        return $this->belongsTo('App\Section', 'parent_id');

    }

    public function children()
    {

        return $this->hasMany('App\Section', 'parent_id');

    }

}
