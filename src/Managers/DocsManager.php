<?php

declare(strict_types=1);

namespace Foodkit\OpenApiDto\Managers;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use RuntimeException;

class DocsManager
{
    /**
     * Encodes and writes the given docs to the file.
     *
     * @param array $docs
     * @param string $uri
     */
    public function saveDocs(array $docs, string $uri, bool $standalone = false): void
    {
        if ($standalone) {
            $docs = [
                'openapi' => '3.0.0',
                'paths' => $docs,
            ];
        }

        $encodedDocs = json_encode($docs, JSON_PRETTY_PRINT);

        $uriParts = explode('/', $uri);

        $docsFolder = base_path('docs/'.implode('/', array_slice($uriParts, 0, -1)));
        $docsFileName = Arr::first(array_slice($uriParts, -1, 1)).'.json';

        if (!File::exists($docsFolder)) {
            File::makeDirectory($docsFolder, 493, true);
        }

        File::put("{$docsFolder}/$docsFileName", $encodedDocs);
    }

    /**
     * Loads the docs from file and transforms to array.
     *
     * @param string $uri
     * @return array
     * @throws FileNotFoundException
     */
    public function loadDocs(string $uri): array
    {
        $docsFilePath = base_path("docs/{$uri}.json");

        if (!File::exists($docsFilePath)) {
            return ['paths' => []];
        }

        $encodedDocs = File::get($docsFilePath);
        $decodedDocs = json_decode($encodedDocs, true);

        if ($decodedDocs === null && json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException("Documentation for {$uri} is corrupted.");
        }

        return $decodedDocs;
    }

    /**
     * Deletes docs directories.
     */
    public function deleteDocs(): void
    {
        $docsDirectories = File::directories(base_path("docs"));

        foreach ($docsDirectories as $docsDirectory) {
            if (strpos($docsDirectory, '/docs/build') !== false) {
                continue; //TODO: have a map of excluded directories?
            }
            File::deleteDirectory($docsDirectory);
        }
    }
}
