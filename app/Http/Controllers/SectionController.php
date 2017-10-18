<?php

namespace App\Http\Controllers;

class SectionController extends Controller
{

    protected $model = \App\Section::class;

    protected $transformer = \App\Http\Transformers\SectionTransformer::class;

}
