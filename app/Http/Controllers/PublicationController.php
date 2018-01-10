<?php

namespace App\Http\Controllers;

use Aic\Hub\Foundation\AbstractController as BaseController;

class PublicationController extends BaseController
{

    protected $model = \App\Publication::class;

    protected $transformer = \App\Http\Transformers\PublicationTransformer::class;

}
