<?php

namespace App;

use Aic\Hub\Foundation\AbstractModel as BaseModel;

class Publication extends BaseModel
{

    public function sections()
    {
        return $this->hasMany('App\Section', 'publication_id');
    }

    /**
     * Returns link to the publication, rendered in the online reader.
     *
     * @return string
     */
    public function getWebUrl()
    {
        return "https://publications.artic.edu/{$this->site}/reader/{$this->alias}";
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
                'alias' => 'paintingsanddrawings',
                'id' => '135446',
            ],
            [
                'site' => 'monet',
                'alias' => 'paintingsanddrawings',
                'id' => '135466',
            ],
            [
                'site' => 'ensor',
                'alias' => 'temptationstanthony',
                'id' => '226',
            ],
            [
                'site' => 'pissarro',
                'alias' => 'paintingsandpaper',
                'id' => '7',
            ],
            [
                'site' => 'whistler',
                'alias' => 'linkedvisions',
                'id' => '406',
            ],
            [
                'site' => 'caillebotte',
                'alias' => 'paintingsanddrawings',
                'id' => '445',
            ],
            [
                'site' => 'gauguin',
                'alias' => 'gauguinart',
                'id' => '141096',
            ],
            [
                'site' => 'modernseries',
                'alias' => 'shatterrupturebreak',
                'id' => '12',
            ],
            [
                'site' => 'modernseries2',
                'alias' => 'go',
                'id' => '34',
            ],
            [
                'site' => 'roman',
                'alias' => 'romanart',
                'id' => '480',
            ],
            [
                'site' => 'manet',
                'alias' => 'manetart',
                'id' => '140019',
            ],
            [
                'site' => 'americansilver',
                'alias' => 'collection',
                'id' => '2',
            ],
        ];

        // Convert into Laravel Collection
        $pubs = collect($pubs);

        // Convert the assoc. arrays into stdObj
        $pubs->transform(function ($item, $key) {
            return (object) $item;
        });

        return $pubs;
    }

}
