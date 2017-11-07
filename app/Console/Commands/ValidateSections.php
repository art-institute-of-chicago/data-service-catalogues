<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use GrahamCampbell\Flysystem\Facades\Flysystem;

use App\Publication;
use App\Section;

class ValidateSections extends AbstractCommand
{

    protected $signature = 'validate:sections';

    protected $description = "Checks if any sections contain identical ids";

    protected $sections = [];

    public function handle()
    {

        $pubs = Publication::getPubCollection();

        $pubs->each( [$this, 'getSections'] );

        $conflicts = $this->countConflicts();

        $this->info('Total Sections Downloaded: ' . count( $this->sections ) );
        $this->info('Total Sections in Database: ' . Section::count() );

        return $conflicts;

    }

    public function getSections( $pub )
    {

        $files = Flysystem::listContents( $this->getPubPath( $pub ) . '/sections' );

        foreach( $files as $file ) {

            $this->sections[] = [
                'publication_id' => (int) $pub->id,
                'section_id' => (int) $file['filename'],
            ];

        }

    }


    public function countConflicts()
    {

        $collates = [];

        // Group publication ids by section id
        foreach( $this->sections as $i ) {

            $collated = false;

            foreach( $collates as &$j ) {

                if( $j['section_id'] === $i['section_id'] ) {
                    $j['publication_ids'][] = $i['publication_id'];
                    $collated = true;
                }

            }

            if( !$collated ) {

                $collates[] = [
                    'section_id' => $i['section_id'],
                    'publication_ids' => [
                        $i['publication_id']
                    ]
                ];

            }

        }

        // Filter out items which have only one publication listed
        $matches = array_filter( $collates, function( $j ) {
            return count( $j['publication_ids'] ) > 1;
        });

        // Sort by section id, ascending
        usort( $matches, function( $a, $b ) {
            return ($a['section_id'] < $b['section_id']) ? -1 : 1;
        });


        // Figure out number of conflicts
        $conflicts = 0;

        foreach( $matches as $i ) {

            if( count( $i['publication_ids'] ) > 1 ) {

                $this->info( 'Section #' . $i['section_id'] . ' occurs in ' . count($i['publication_ids']) . ' publications: ' . implode(', ', $i['publication_ids']) );

                $conflicts++;

            }

        }

        $this->warn( 'Total Conflicts: ' . $conflicts );

        return $conflicts;

    }

}
