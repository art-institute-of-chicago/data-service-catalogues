<?php

namespace App;

use App\BaseModel;

class Publication extends BaseModel
{

    public function sections()
    {

        return $this->hasMany('App\Section', 'publication_id');

    }

}
