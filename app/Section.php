<?php

namespace App;

use Illuminate\Database\Eloquent\Builder;

use GrahamCampbell\Flysystem\Facades\Flysystem;
use Symfony\Component\DomCrawler\Crawler;

use Wa72\HtmlPageDom\HtmlPageCrawler;
use League\HTMLToMarkdown\HtmlConverter;

use App\BaseModel;

class Section extends BaseModel
{

    /**
     * Populated with this section's "Content Document" after the first time `getContent` is run.
     * This doc is scraped to storage/app by `import:publications` from OSCI Toolkit's EPUB API.
     * The purpose of this variable is to reduce the frequency of filesystem access.
     * Ex: https://publications.artic.edu/caillebotte/api/epub/465/content.xhtml
     *
     * @var string
     */
    protected $content;


    /**
     * Defines default order as `publication_id` descending, then `weight` ascending.
     * Uses the inline method for scope definition, rather than creating new classes.
     *
     * @link https://stackoverflow.com/questions/20701216/laravel-default-orderby
     *
     * {@inheritdoc}
     */
    protected static function boot() {

        parent::boot();

        static::addGlobalScope('order-publication', function (Builder $builder) {
            $builder->orderBy('publication_id', 'desc');
        });

        static::addGlobalScope('order-weight', function (Builder $builder) {
            $builder->orderBy('weight', 'asc');
        });

    }

    public function publication()
    {

        return $this->belongsTo('App\Publication');

    }

    public function parent()
    {

        return $this->belongsTo('App\Section', 'parent_id');

    }

    public function children()
    {

        return $this->hasMany('App\Section', 'parent_id');

    }

    /**
     * Returns link to the section, rendered in the online reader.
     *
     * @return string
     */
    public function getWebUrl()
    {
        return $this->publication->getWebUrl() . "/section/{$this->source_id}";
    }

    /**
     * Retrieves the section's "Content Document" (XHTML) from filesystem storage.
     *
     * @return string
     */
    public function getContent()
    {

        if( !$this->content ) {

            $file = "{$this->publication->site}/{$this->publication->id}/sections/{$this->source_id}.xhtml";
            $this->content = Flysystem::read( $file );

        }

        return $this->content;

    }

    /**
     * Retrieves the section's "Content Document" (XHTML) from filesystem storage,
     * and initializes a Symphony DomCrawler with its contents. Call this whenever
     * you need to crawl a fresh copy of the contents.
     *
     * @return \Symfony\Component\DomCrawler\Crawler
     */
    public function getContentCrawler()
    {

        $content = $this->getContent();

        $crawler = new Crawler();
        $crawler->addHtmlContent( $content, 'UTF-8' );

        return $crawler;

    }

    /**
     * Determines this section's content type in the original OSCI Toolkit Drupal instance.
     *
     * @param  \Symfony\Component\DomCrawler\Crawler $crawler
     * @return string
     */
    public function getType( $crawler = null )
    {

        $crawler = $crawler ?? $this->getContentCrawler();

        $body = $crawler->filterXPath('html/body');

        $type = explode( ' ', $body->attr('class') )[1];

        return $type;

    }

    /**
     * Determines whether or not this section had the "Work of Art" content type in Drupal.
     *
     * @param  \Symfony\Component\DomCrawler\Crawler $crawler
     * @return boolean
     */
    public function isArtwork( $crawler = null )
    {

        $type = $this->getType( $crawler );

        return $type === 'node-work-of-art';

    }

    /**
     * Returns Markdown representation of the tombstone section.
     * This only works for sections that were created with the "Work of Art" content type.
     *
     * @param  \Symfony\Component\DomCrawler\Crawler $crawler
     * @return string
     */
    public function getTombstone( $crawler = null )
    {

        $crawler = $crawler ?? $this->getContentCrawler();

        $crawler = $crawler->filterXPath("//section[@id='tombstone']");

        // Return if this doesn't have a tombstone
        if( $crawler->count() < 1 )
        {
            return null;
        }

        return $this->getPlaintext( $crawler );

    }

    /**
     * Returns Markdown representation of section content.
     * This is meant to be a somewhat less-lossy process than `getPlaintext`.
     *
     * @TODO How to best handle tags that cannot be processed into Markdown?
     *
     * @param  \Symfony\Component\DomCrawler\Crawler $crawler
     * @return string
     */
    public function getMarkdown( $crawler = null )
    {

        $crawler = $crawler ?? $this->getContentCrawler()->filter('body');

        $html = $crawler->html();
        $html = trim($html);

        $converter = new HtmlConverter();
        $markdown = $converter->convert($html);

        $markdown_a = explode("\n", $markdown);

        // Remove leading spaces on each line
        $markdown_a = array_map( function( $line ) {
            return ltrim( $line );
        }, $markdown_a);

        // Concatenate
        $markdown = implode("\n", $markdown_a);

        return $markdown;

    }

    /**
     * Return plaintext representation of the entire section. This is a "lossy" process.
     * No styles, figures, or links are retained. It's meant for indexing stuff into search.
     *
     * @TODO Minimize duplication between this and `getMarkdown`?
     *
     * @param  \Symfony\Component\DomCrawler\Crawler $crawler
     * @return string
     */
    public function getPlaintext( $crawler = null )
    {

        $crawler = $crawler ?? $this->getContentCrawler()->filter('body');

        // Use HTMLPageDOM for these manipulations
        $crawler = new HtmlPageCrawler( $crawler );

        // Remove non-text, non-markdown elements
        $crawler->filter('.footnote-reference')->remove();

        // Only h3-h6 should exist, but target all h's just in case
        $crawler->filter('h1, h2, h3, h4, h5, h6')->remove();

        $crawler->filter('span')->unwrapInner();
        $crawler->filter('a')->unwrapInner();

        // Markdown handles these great, but we don't want them for plaintext
        $crawler->filter('u, i, b, em, strong')->unwrapInner();

        // OSCI Toolkit-specific markup for figures
        $crawler->filter('figure > img, figure > .figure_content')->remove();
        $crawler->filter('figure')->unwrapInner();
        $crawler->filter('figcaption, figcaption > div')->unwrapInner();

        $crawler->filter('aside')->unwrapInner();
        $crawler->filter('section')->unwrapInner();

        // Mostly for TOC-like pages
        // http://data-service-catalogues.dev/v1/sections/39548249564.txt
        $crawler->filter('div')->unwrapInner();
        $crawler->filter('img')->remove();

        // TODO: Replace <sup/> and <sub/> numerals w/ Unicode equivallents?
        // https://en.wikipedia.org/wiki/Unicode_subscripts_and_superscripts
        // Meant for chemical formulas, e.g. see tech report section here:
        // http://data-service-catalogues.dev/v1/sections/478250.txt

        // Use the Markdown processor to handle whitespace, etc.
        $markdown = $this->getMarkdown( $crawler );

        // Prepare to remove Markdown artifacts
        $plaintext = $markdown;

        // Remove backslashes from \[ and \]
        $plaintext = str_replace('\[', '[', $plaintext);
        $plaintext = str_replace('\]', ']', $plaintext);

        // Null out if empty
        $plaintext = trim( $plaintext );
        $plaintext = empty( $plaintext ) ? null : $plaintext;

        return $plaintext;

    }

    /**
     * Attempt to associate an accession (main reference number) with a section.
     * This only works for sections that were created with the "Work of Art" content type.
     *
     * @return string
     */
    public function getAccession()
    {

        $tombstone = $this->getTombstone();

        // Try grepping the title, since it's more accurate
        $accession = self::extractAccessionFromString( $this->title );

        // Try grepping the tombstone, if there were no matches
        $accession = $accession ?? self::extractAccessionFromString( $tombstone );

        return $accession;

    }

    /**
     * Given a string, attempt to parse out a single accession number.
     * Meant to target titles and tombstones. Ignores non-numeric parts.
     *
     * @param string $input
     * @return string
     */
    private static function extractAccessionFromString( $input = null ) {

        if( !$input )
        {
            return null;
        }

        // https://regex101.com/r/n1thOj/2
        $pattern = '/(?:18|19|20)[0-9]{2}\.[0-9]+(?:\.[0-9]+)*/';

        preg_match_all($pattern, $input, $matches);

        // For some reason, preg_match returns an array of empty strings for some inputs
        $matches = array_filter($matches, function($item) { return !empty($item); });

        if( count( $matches ) < 1 )
        {
            return null;
        }

        // Focus on the last match (accessions tend to be towards the end of the line)
        $matches = $matches[ count($matches) - 1 ];

        // For some reason, these are also blank sometimes
        if( count( $matches ) < 1 )
        {
            return null;
        }

        return $matches[0];

    }

}
