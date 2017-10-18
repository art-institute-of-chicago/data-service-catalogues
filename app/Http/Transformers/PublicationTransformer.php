<?php

namespace App\Http\Transformers;

use App\Publication;
use League\Fractal\TransformerAbstract;

class PublicationTransformer extends TransformerAbstract
{

    public function transform(Publication $publication)
    {

        return [
            'id' => $publication->id,
            'site' => $publication->site,
            'title' => $publication->title,
            'section_ids' => $publication->sections()->pluck('id'),
        ];

    }

}
