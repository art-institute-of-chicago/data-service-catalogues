<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use GrahamCampbell\Flysystem\Facades\Flysystem;
use Symfony\Component\DomCrawler\Crawler;

use App\Publication;

class ImportPublications extends Command
{

    protected $signature = 'import:pubs';

    protected $description = "Import all configured publications";

    public function handle()
    {

        $start = microtime(TRUE);

        $this->downloadPubs();
        $this->importPubs();

        $this->downloadSections();

        $finish = microtime(TRUE);
        $totaltime = $finish - $start;
        $this->warn("Execution Time: {$totaltime} sec");

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
     * Downloads all sections.
     *
     * @return array
     */
    protected function downloadSections()
    {

        $pubs = $this->getPubCollection();

        $pubs->each( [$this, 'downloadSectionsForPub'] );

    }

    /**
     * Imports previously downloaded publications into the database.
     */
    protected function importPubs()
    {

        $pubs = $this->getPubCollection();

        $pubs->each( [$this, 'importPub'] );

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
     * Downloads sections listed in a publication's "Nav Document" and saves them to storage.
     */
    public function downloadSectionsForPub( $pub )
    {

        $file = "{$pub->site}/{$pub->id}/nav.opf";

        $contents = Flysystem::read( $file );

        $crawler = new Crawler($contents);

        // http://api.symfony.com/3.2/Symfony/Component/DomCrawler/Crawler.html
        // https://stackoverflow.com/questions/4858689/trouble-using-xpath-starts-with-to-parse-xhtml
        $items = $crawler->filterXPath("//a[@data-section_id]");

        $sections = [];

        $items->each( function( $item ) use (&$sections, &$pub) {

            $id = $item->attr( 'data-section_id' );
            $id = (int) $id;

            $url = $item->attr( 'href' );

            // https://stackoverflow.com/questions/11480763/how-to-get-parameters-from-a-url-string
            parse_str( parse_url( $url, PHP_URL_QUERY ), $query );

            $revision = (int) $query['revision'];

            // Download the section
            $contents = file_get_contents( $url );
            $file = "{$pub->site}/{$pub->id}/sections/{$id}.xhtml";

            Flysystem::put( $file, $contents );

            $this->info("Downloaded {$url} to {$file}");

            // Get the title from the downloaded content file
            $file = "{$pub->site}/{$pub->id}/sections/{$id}.xhtml";
            $contents = Flysystem::read( $file );

            $crawler = new Crawler( $contents );

            $title = $crawler->filterXPath('html/head/title')->text();
            $title = trim( $title );

            $sections[] = [
                'id' => $id,
                'title' => $title,
                'revision' => $revision,
                'publication_id' => $pub->id,
            ];

            // TODO: Actually save this Section data

        });

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
     * Imports a publication using a previously downloaded "Package Document"
     *
     * @return \App\Publication
     */
    public function importPub( $pub )
    {

        $file = $pub->site . '/' . $pub->id . '/package.opf';

        $contents = Flysystem::read( $file );

        $crawler = new Crawler();
        $crawler->setDefaultNamespacePrefix('opf'); // http://www.idpf.org/2007/opf
        $crawler->addXmlContent( $contents );

        // https://symfony.com/doc/current/components/dom_crawler.html
        $title = $crawler->filterXPath('opf:package/opf:metadata/dc:title')->text();
        $title = trim( $title );

        $publication = Publication::findOrNew( $pub->id );
        $publication->id = $pub->id;
        $publication->site = $pub->site;
        $publication->title = $title;
        $publication->save();

        $this->info("Imported Publication #{$publication->id}: '{$publication->title}'");

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
