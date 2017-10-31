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

        $pubs = $this->getPubCollection();

        $pubs->each( [$this, 'getSections'] );

        return $this->countConflicts();

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

        $conflicts = 0;

        foreach( $this->sections as $i ) {

            $matches = [];

            foreach( $this->sections as $j ) {

                if( $i['section_id'] === $j['section_id'] ) {

                    $matches[] = $j['publication_id'];

                }

            }

            if( count( $matches ) > 1 ) {

                $this->info( 'Section #' . $i['section_id'] . ' occurs in ' . count($matches) . ' publications: ' . implode(', ', $matches) );

                $conflicts++;

            }

        }

        $this->warn( 'Total Conflicts: ' . $conflicts );

        return $conflicts;

    }

}
