<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use GrahamCampbell\Flysystem\Facades\Flysystem;
use League\Csv\Writer;

use App\Section;

class MatchArtworks extends AbstractCommand
{

    protected $signature = 'match:artworks';

    protected $description = "Attempt to match artworks by accession using the data hub";

    protected $csv;

    public function handle()
    {

        // Reset citi_id for all sections
        Section::query()->update(['citi_id' => null]);

        $sections = Section::whereNotNull('accession')->whereNull('citi_id')->get();

        // For testing, only grab the first few records
        // $sections = $sections->slice(0, 5);

        // TODO: Ask to overwrite existing file? Appending doesn't make much sense for successive runs...

        $path = Flysystem::getAdapter()->getPathPrefix() . 'match.csv';
        $this->csv = Writer::createFromPath( $path, 'w' );
        $this->csv->insertOne( ['matches', 'dsc_id', 'citi_id', 'dsc_mrn', 'citi_mrn', 'dsc_title', 'citi_title'] );

        $results = $sections->map( [$this, 'match'] );

    }

    public function match( $item )
    {

        // Throttle API requests...
        sleep(1);

        $result = $this->search( $item->accession );

        if( $result['match'] )
        {
            $item->citi_id = $result['match']->id;
            $item->save();
        }

        $out = [
            'matches' => $result['count'],
            'dsc_id' => $item->id,
            'citi_id' => $result['match']->id ?? null,
            'dsc_mrn' => $item->accession,
            'citi_mrn' => $result['match']->main_reference_number ?? null,
            'dsc_title' => $item->title,
            'citi_title' => $result['match']->title ?? null,
        ];

        // Output to console
        $this->info( implode(', ', $out) );

        // Append to file
        $this->csv->insertOne( $out );

        return $out;

    }

    // https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-prefix-query.html
    protected function search( $accession )
    {

        $query = [
            'q' => $accession,
            '_source' => [
                'id',
                'title',
                'main_reference_number'
            ],
            'query' => [
                'prefix' => [
                    'main_reference_number' => $accession
                ]
            ]
        ];

        $response = $this->post( env('API_URL'), $query );
        $response = json_decode( $response );

        $results = $response->data;
        $results = collect( $results );

        $results = $results->filter( function( $result ) use ( $accession ) {

            $mrn = $result->main_reference_number;
            $mrn = substr( $mrn, strlen( $accession ) );

            // If there's no "leftover" string, this is an exact match
            if( strlen( $mrn ) === 0 )
            {
                return true;
            }

            // If next char is numeric, ignore, e.g. 1928.23 vs. 1928.230
            if( is_numeric( $mrn[0] ) )
            {
                return false;
            }

            // If next char is a period, ignore, e.g. 1928.23 vs. 1928.23.12
            if( $mrn[0] === '.' )
            {
                return false;
            }

            return true;

        });

        // Sort by length of accession, so shortest is first
        $results = $results->sortBy( function( $result ) {
            return strlen( $result->main_reference_number );
        });

        // Store the number of matches as a measure of certitude
        $count = $results->count();

        // The first result is our match
        $match = $results->first();

        return [
            'match' => $match,
            'count' => $count,
        ];

    }

    // @TODO: Use https://github.com/FriendsOfPHP/Goutte
    // https://stackoverflow.com/questions/5647461/how-do-i-send-a-post-request-with-php
    private function post( $url, $data )
    {

        // use key 'http' even if you send the request to https://...
        $options = array(
            'http' => array(
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'method'  => 'POST',
                'content' => http_build_query($data)
            )
        );
        $context  = stream_context_create($options);
        $result = file_get_contents($url, false, $context);

        return $result;

    }

}
