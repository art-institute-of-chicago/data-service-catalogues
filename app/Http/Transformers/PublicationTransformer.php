<?php

namespace App\Http\Transformers;

use App\Publication;

use Aic\Hub\Foundation\AbstractTransformer;

class PublicationTransformer extends AbstractTransformer
{

    public function transform(Publication $publication)
    {

        return [
            'id' => $publication->id,
            'site' => $publication->site,
            'alias' => $publication->alias,
            'title' => $publication->title,
            'web_url' => $publication->getWebUrl(),
            'section_ids' => $publication->sections->pluck('id'),
        ];

    }

}
