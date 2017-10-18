<?php

namespace App\Http\Transformers;

use App\Section;
use League\Fractal\TransformerAbstract;

class SectionTransformer extends TransformerAbstract
{

    public function transform(Section $section)
    {

        return [
            'id' => $section->id,
            'title' => $section->title,
            'revision' => $section->revision,
            'publication_id' => $section->publication_id,
            'parent_id' => $section->parent_id,
        ];

    }

}
