<?php

namespace App\Http\Transformers;

use Aic\Hub\Foundation\AbstractTransformer;

class SectionTransformer extends AbstractTransformer
{

    public function transform($section)
    {

        $data = [
            'id' => $section->id,
            'title' => $section->title,
            'web_url' => $section->getWebUrl(),
            'accession' => $section->accession,
            'citi_id' => $section->citi_id,
            'revision' => $section->revision,
            'source_id' => $section->source_id,
            'publication_id' => $section->publication->id ?? null,
            'weight' => $section->weight,
            'parent_id' => $section->parent->id ?? null,
            'child_ids' => $section->children->pluck('id'),
            'content' => $section->content,
        ];

        // Enables ?fields= functionality
        return parent::transform($data);

    }

}
