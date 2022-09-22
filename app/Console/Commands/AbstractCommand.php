<?php

namespace App\Console\Commands;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\DomCrawler\Crawler;

use Aic\Hub\Foundation\AbstractCommand as BaseCommand;

abstract class AbstractCommand extends BaseCommand
{

    /**
     * Returns path to a publication's directory in storage
     *
     * @param object $pub
     * @return string
     */
    protected function getPubPath($pub)
    {
        return $pub->site . '/' . $pub->id;
    }

    /**
     * Returns link to a publication's "Package Document"
     *
     * @param object $pub
     * @return string
     */
    protected function getPackageUrl($pub)
    {
        return 'https://publications.artic.edu/' . $pub->site . '/api/epub/' . $pub->id . '/package.opf';
    }

    /**
     * Returns path to a publication's downloaded "Package Document"
     *
     * @param object $pub
     * @return string
     */
    protected function getPackagePath($pub)
    {
        return $this->getPubPath($pub) . '/package.opf';
    }

    /**
     * Returns link to a publication's "Nav Document"
     *
     * @param object $pub
     * @return string
     */
    protected function getNavUrl($pub)
    {
        return 'https://publications.artic.edu/' . $pub->site . '/api/epub/' . $pub->id . '/nav.xhtml';
    }

    /**
     * Returns path to a publication's downloaded "Nav Document"
     *
     * @param object $pub
     * @return string
     */
    protected function getNavPath($pub)
    {
        return $this->getPubPath($pub) . '/nav.xhtml';
    }

    protected function getSectionPath($pub, $sectionId)
    {
        return $this->getPubPath($pub) . "/sections/{$sectionId}.xhtml";
    }

    protected function getFigurePath($pub, $figureId)
    {
        return $this->getPubPath($pub) . "/figures/{$figureId}.xhtml";
    }

    protected function getCrawler($path)
    {
        $contents = Storage::get($path);

        $crawler = new Crawler();
        $crawler->addHtmlContent($contents, 'UTF-8');

        return $crawler;
    }
}
