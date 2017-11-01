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
            'source_id' => $section->source_id,
            'publication_id' => $section->publication->id ?? null,
            'parent_id' => $section->parent->id ?? null,
            'child_ids' => $section->children->pluck('id'),
        ];

    }

}
