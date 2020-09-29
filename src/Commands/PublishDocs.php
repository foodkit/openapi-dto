<?php

declare(strict_types=1);

namespace Foodkit\OpenApiDto\Commands;

use Illuminate\Support\Facades\File;

class PublishDocs extends BaseDocsCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'foodkit:publish-docs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generates OpenAPI specs as html';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $version = $this->ask('For which API version would you like to generate the docs?', '*');
        if (!$this->isValidApiVersion($version)) {
            $this->error("The version you provided is not valid. It can either be any version (\"*\") or a specific version (the latest one is {$this->latestApiVersion}.0).");
            return;
        }

        if ($version === '*') {
            //Only integer versioning is currently supported. If point releases are introduced (e.g. v5, v5.3) use a map of versions
            for ($version = 1; $version < $this->latestApiVersion + 1; $version++) {
                $this->publishDocs($version);
            }
        } else {
            $this->publishDocs((int) $version);
        }
    }

    protected function publishDocs(int $version): void
    {
        $template = File::get(base_path('./docs/template.html'));
        $mergedSpecFilePaths = File::glob(base_path("docs/v6/*.json"));

        foreach ($mergedSpecFilePaths as $mergedSpecFilePath) {
            $filename = basename($mergedSpecFilePath);
            $name = explode('.', $filename)[0];

            $publicDocs = $this->docsManager->loadDocs("v{$version}/{$name}");
            $renderedPublicDocs = $this->renderDocs($publicDocs, $template);

            File::put(base_path("./docs/build/v{$version}-{$name}.html"), $renderedPublicDocs);
        }

        $this->info("Documentation for API version {$version} has been published.");
    }

    protected function renderDocs(array $docs, string $template): string
    {
        $renderedDocs = str_replace('%%SPECS%%', json_encode($docs), $template);

        return $renderedDocs;
    }
}
