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
            'alias' => $publication->alias,
            'title' => $publication->title,
            'section_ids' => $publication->sections->pluck('id'),
        ];

    }

}
