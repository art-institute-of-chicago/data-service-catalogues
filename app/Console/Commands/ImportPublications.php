<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use GrahamCampbell\Flysystem\Facades\Flysystem;
use Symfony\Component\DomCrawler\Crawler;

use App\Publication;
use App\Section;

class ImportPublications extends AbstractCommand
{

    protected $signature = 'import:pubs {--redownload : Re-scrape, instead of using previously-downloaded files}';

    protected $description = "Import all configured publications";

    public function handle()
    {

        $this->downloadPubs();
        $this->importPubs();

        $this->downloadSections();

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

        $file = $this->getNavPath( $pub );

        $contents = Flysystem::read( $file );

        $crawler = new Crawler();
        $crawler->addHtmlContent( $contents, 'UTF-8' );

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
            $file = $this->getPubPath( $pub ) . "/sections/{$id}.xhtml";

            if( !Flysystem::has( $file ) || $this->option('redownload') )
            {

                $contents = file_get_contents( $url );
                Flysystem::put( $file, $contents );

                $this->warn("Downloaded {$url} to {$file}");

            }

            // Get the title from the downloaded content file
            // TODO: Get title from the nav instead?
            $file = $this->getPubPath( $pub ) . "/sections/{$id}.xhtml";
            $contents = Flysystem::read( $file );

            $crawler = new Crawler();
            $crawler->addHtmlContent( $contents, 'UTF-8' );

            $title = $crawler->filterXPath('html/head/title')->text();
            $title = trim( $title );

            // This will be either `nav` or an `li`
            $parent = $item->parents()->eq(2);

            if( $parent->nodeName() == 'li' ) {

                // Get the id from the direct-descendant `a` tag
                $parent_id = $parent->filterXPath('li/a')->attr('data-section_id');
                $parent_id = (int) $parent_id;

            } else {

                $parent_id = null;

            }

            // Save the Section to database
            $section = Section::findOrNew( $id );
            $section->id = $id;
            $section->title = $title;
            $section->revision = $revision;
            $section->parent_id = $parent_id;
            $section->publication_id = $pub->id;
            $section->save();

            $this->info("Imported Section #{$section->id}: '{$section->title}'");

        });

    }

    /**
     * Downloads a publication's "Package Document" and saves it to storage.
     *
     * @return array
     */
    public function downloadPubPackage( $pub )
    {

        $file = $this->getPackagePath( $pub );

        if( !Flysystem::has( $file ) || $this->option('redownload') )
        {

            $url = $this->getPackageUrl( $pub );
            $contents = file_get_contents( $url );

            Flysystem::put( $file, $contents );

            $this->warn("Downloaded {$url} to {$file}");

        }

    }

    /**
     * Downloads a publication's "Package Document" and saves it to storage.
     *
     * @return array
     */
    public function downloadPubNav( $pub )
    {

        $file = $this->getNavPath( $pub );

        if( !Flysystem::has( $file ) || $this->option('redownload') )
        {

            $url = $this->getNavUrl( $pub );
            $contents = file_get_contents( $url );

            Flysystem::put( $file, $contents );

            $this->warn("Downloaded {$url} to {$file}");

        }

    }

    /**
     * Imports a publication using a previously downloaded "Package Document"
     *
     * @return \App\Publication
     */
    public function importPub( $pub )
    {

        $file = $this->getPackagePath( $pub );

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

}
