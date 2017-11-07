<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractCommand extends Command
{

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    abstract public function handle();

    /**
     * Here, we've extended the inherited execute method, which allows us to log times
     * for each command call. You can use `handle` in child classes as normal.
     *
     * @link http://api.symfony.com/3.3/Symfony/Component/Console/Command/Command.html
     * @link https://github.com/laravel/framework/blob/5.4/src/Illuminate/Console/Command.php
     *
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @return mixed
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $start = microtime(TRUE);

        // Call Illuminate\Console\Command::execute
        $result = parent::execute( $input, $output );

        $finish = microtime(TRUE);
        $totaltime = $finish - $start;
        $this->warn("Execution Time: {$totaltime} sec");

        return $result;

    }

    /**
     * Returns path to a publication's directory in storage
     *
     * @param object $pub
     * @return string
     */
    protected function getPubPath( $pub )
    {
        return $pub->site . '/' . $pub->id;
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
     * Returns path to a publication's downloaded "Package Document"
     *
     * @param object $pub
     * @return string
     */
    protected function getPackagePath( $pub )
    {
        return $this->getPubPath( $pub ) . '/package.opf';
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
     * Returns path to a publication's downloaded "Nav Document"
     *
     * @param object $pub
     * @return string
     */
    protected function getNavPath( $pub )
    {
        return $this->getPubPath( $pub ) . '/nav.xhtml';
    }

    /**
     * Calculate a unique integer based on two integers (cantor pairing).
     */
    public static function cantor_pair_calculate($x, $y) {
        return (($x + $y) * ($x + $y + 1)) / 2 + $y;
    }

    /**
     * Return the source integers from a cantor pair integer.
     */
    public static function cantor_pair_reverse($z) {
        $t = floor((-1 + sqrt(1 + 8 * $z))/2);
        $x = $t * ($t + 3) / 2 - $z;
        $y = $z - $t * ($t + 1) / 2;
        return array($x, $y);
    }

}


