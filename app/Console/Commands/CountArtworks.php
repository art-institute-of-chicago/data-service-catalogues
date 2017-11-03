<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use GrahamCampbell\Flysystem\Facades\Flysystem;

use App\Section;

class CountArtworks extends AbstractCommand
{

    protected $signature = 'count:artworks {--unparsed : Show tombstones of artworks with unparsable accessions}';

    protected $description = "Count how many sections had 'Work of Art' content type";

    protected $sections = [];

    public function handle()
    {

        $sections = Section::all();

        $artworks = $sections->filter( function( $section ) {
            return $section->isArtwork();
        });

        $tombstones = $artworks->filter( function( $artwork ) {
            return $artwork->getTombstone();
        });

        $accessions = $tombstones->filter( function( $artwork ) {
            return $artwork->accession();
        });

        $unparsed = $tombstones->diff( $accessions );

        $this->info( $sections->count() . ' sections in total.');
        $this->info( $artworks->count() . ' of these are artworks.');
        $this->info( $tombstones->count() . ' of artworks have tombstones.');
        $this->info( $accessions->count() . ' of artworks with tombstones have parsable accessions.');

        if( $this->option('unparsed') )
        {

            $this->warn( "Here's the list of unparsable artworks:\n" );

            $unparsed->each( function( $artwork ) {
                $this->warn( $artwork->id . ': ' . $artwork->title . "\n\n" );
                $this->info( $artwork->getTombstone() . "\n" );
            });

        }

    }

}
