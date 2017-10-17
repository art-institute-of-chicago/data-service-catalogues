<?php

namespace App\Http\Controllers;

class PublicationController extends Controller
{

    protected $model = \App\Publication::class;

    protected $transformer = \App\Http\Transformers\PublicationTransformer::class;

}
