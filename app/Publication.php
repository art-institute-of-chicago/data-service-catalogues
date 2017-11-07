<?php

namespace App;

use App\BaseModel;

class Publication extends BaseModel
{

    public function sections()
    {

        return $this->hasMany('App\Section', 'publication_id');

    }

    /**
     * Returns necessary config for importing publications. Edit this method to target specific pubs for processing.
     * Publication list has to be hardcoded to avoid importing test publications. Each pub is an object.
     *
     * @return \Illuminate\Support\Collection
     */
    public static function getPubCollection()
    {

        $pubs = [
            [
                'site' => 'renoir',
                'id' => '135446',
            ],
            [
                'site' => 'monet',
                'id' => '135466',
            ],
            [
                'site' => 'ensor',
                'id' => '226',
            ],
            [
                'site' => 'pissarro',
                'id' => '7',
            ],
            [
                'site' => 'whistler',
                'id' => '406',
            ],
            [
                'site' => 'caillebotte',
                'id' => '445',
            ],
            [
                'site' => 'gauguin',
                'id' => '141096',
            ],
            [
                'site' => 'modernseries',
                'id' => '12',
            ],
            [
                'site' => 'roman',
                'id' => '480',
            ],
            [
                'site' => 'manet',
                'id' => '140019',
            ],
        ];

        // Convert into Laravel Collection
        $pubs = collect( $pubs );

        // Convert the assoc. arrays into stdObj
        $pubs->transform( function ($item, $key) {
            return (object) $item;
        });

        return $pubs;

    }

}
