<?php

namespace App\Http\Controllers;

use App\Section;
use App\Publication;
use Illuminate\Http\Request;

class SectionController extends Controller
{

    protected $model = \App\Section::class;

    protected $transformer = \App\Http\Transformers\SectionTransformer::class;

    // publications/{id}/sections
    public function indexForPublication(Request $request, $id) {

        return $this->collect( $request, function( $limit, $id ) {

            return Publication::findOrFail($id)->sections;

        });

    }

    // publications/{publication_id}/sections/{id}
    // Note that this expects a source id, not Cantor'd id
    public function showForPublication(Request $request, $publication_id, $source_id) {

        return $this->select( $request, function( $source_id ) use ($publication_id) {

            return Section::where('publication_id', $publication_id)->where('source_id', $source_id)->firstOrFail();

        });

    }

}
