<?php

namespace App\Http\Transformers;

use Aic\Hub\Foundation\AbstractTransformer;

class PublicationTransformer extends AbstractTransformer
{

    public function transform($publication)
    {

        $data = [
            'id' => $publication->id,
            'site' => $publication->site,
            'alias' => $publication->alias,
            'title' => $publication->title,
            'web_url' => $publication->getWebUrl(),
            'section_ids' => $publication->sections->pluck('id'),
        ];

        // Enables ?fields= functionality
        return parent::transform($data);

    }

}
