<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use GrahamCampbell\Flysystem\Facades\Flysystem;

class ImportPublications extends Command
{

    protected $signature = 'import:pubs';

    protected $description = "Import all configured publications";

    public function handle()
    {

        $this->downloadPubs();

    }

    /**
     * Downloads all publications.
     *
     * @return array
     */
    protected function downloadPubs()
    {

        $pubs = $this->getPubCollection();

        $pubs->each( [$this, 'downloadPub'] );

    }

    /**
     * Downloads a publiation's "Package Document" and saves it to storage.
     *
     * @link https://stackoverflow.com/questions/43170785
     * @return array
     */
    public function downloadPub( $pub )
    {

        $this->downloadPubPackage( $pub );
        $this->downloadPubNav( $pub );

    }

    /**
     * Downloads a publication's "Package Document" and saves it to storage.
     *
     * @return array
     */
    public function downloadPubPackage( $pub )
    {

        $url = $this->getPackageUrl( $pub );
        $contents = file_get_contents( $url );
        $file = $pub->site . '/' . $pub->id . '/package.opf';

        Flysystem::put( $file, $contents );

        $this->info("Downloaded {$url} to {$file}");

    }


    /**
     * Downloads a publication's "Package Document" and saves it to storage.
     *
     * @return array
     */
    public function downloadPubNav( $pub )
    {

        $url = $this->getNavUrl( $pub );
        $contents = file_get_contents( $url );
        $file = $pub->site . '/' . $pub->id . '/nav.opf';

        Flysystem::put( $file, $contents );

        $this->info("Downloaded {$url} to {$file}");

    }

    /**
     * Returns link to a publication's "Package Document"
     *
     * @param object $pub
     * @return string
     */
    protected function getPackageUrl( $pub )
    {
        return 'https://publications.artic.edu/' . $pub->site . '/api/epub/' . $pub->id . '/package.opf';
    }

    /**
     * Returns link to a publication's "Nav Document"
     *
     * @param object $pub
     * @return string
     */
    protected function getNavUrl( $pub )
    {
        return 'https://publications.artic.edu/' . $pub->site . '/api/epub/' . $pub->id . '/nav.xhtml';
    }

    /**
     * Returns necessary config for importing publications. Edit this method to target specific pubs for processing.
     * Publication list has to be hardcoded to avoid importing test publications. Each pub is an object.
     *
     * @return \Illuminate\Support\Collection
     */
    protected function getPubCollection()
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
