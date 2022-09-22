<?php

namespace App\Console\Commands;

use Illuminate\Support\Facades\Storage;

class PubDownload extends AbstractCommand
{

    protected $signature = 'pub:download {--force : Re-scrape, instead of using previously-downloaded files}';

    protected $description = 'Download publication';

    public function handle()
    {
        // See Publication::getPubCollection()
        $pub = (object) [
            'site' => 'whistlerart',
            'alias' => 'paintingsanddrawings',
            'id' => '64',
        ];

        $this->downloadPub($pub);
    }

    private function downloadPub($pub)
    {
        // package.opf
        $this->downloadToPath(
            $this->getPackageUrl($pub),
            $this->getPackagePath($pub)
        );

        // nav.xhtml
        $this->downloadToPath(
            $this->getNavUrl($pub),
            $this->getNavPath($pub)
        );

        // requires nav.xhtml
        $this->downloadSections($pub);
    }

    private function downloadSections($pub)
    {
        $path = $this->getNavPath($pub);
        $crawler = $this->getCrawler($path);

        // http://api.symfony.com/3.2/Symfony/Component/DomCrawler/Crawler.html
        // https://stackoverflow.com/questions/4858689/trouble-using-xpath-starts-with-to-parse-xhtml
        $items = $crawler->filterXPath('//a[@data-section_id]');

        $items->each(function ($item) use ($pub) {
            $sectionId = $item->attr('data-section_id');

            $this->downloadToPath(
                $item->attr('href'),
                $this->getSectionPath($pub, $sectionId)
            );

            $this->downloadFigures($pub, $sectionId);
        });
    }

    private function downloadFigures($pub, $sectionId)
    {
        $path = $this->getSectionPath($pub, $sectionId);
        $crawler = $this->getCrawler($path);

        $items = $crawler->filterXPath('//object[@data]');

        $items->each(function ($item) use ($pub) {
            $figureId = $item->attr('id');

            $this->downloadToPath(
                $item->attr('data'),
                $this->getFigurePath($pub, $figureId)
            );
        });
    }

    private function downloadToPath($url, $path)
    {
        if (!Storage::exists($path) || $this->option('force')) {
            $contents = file_get_contents($url);
            Storage::put($path, $contents);
            $this->warn("Downloaded {$url} to {$path}");
        } else {
            $this->info("Already exists: {$url} to {$path}");
        }
    }
}
