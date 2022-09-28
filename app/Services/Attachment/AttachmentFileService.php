<?php

namespace App\Services\Attachment;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Str;
use JetBrains\PhpStorm\ArrayShape;

class AttachmentFileService
{
    public function __construct(
        protected FilesystemAdapter $filesystem,
        protected Factory $client
    ) {
    }

    /**
     * @throws \League\Flysystem\FileNotFoundException
     * @throws \Illuminate\Http\Client\RequestException
     */
    #[ArrayShape(["type" => "string", "path" => "string", "timestamp" => "int", "size" => "int"])]
    public function downloadFromUrl(string $url): array
    {
        $path = $this->filesystem->path($hash = Str::random(40));

        $this->client
            ->sink($path)
            ->get($url)
            ->throw();

        return $this->filesystem->getMetadata($hash);
    }

    public function downloadMultipleFromUrls(string $url, string ...$urls): array
    {
        $urls = collect([$url, ...array_values($urls)])
            ->mapWithKeys(static function (string $url): array {
                return [Str::random(40) => $url];
            });

        $this->client->pool(function (Pool $pool) use ($urls): void {
            foreach ($urls as $hash => $url) {
                $pool->as($hash)
                    ->sink($this->filesystem->path($hash))
                    ->get($url);
            }
        });

        return $urls
            ->lazy()
            ->mapWithKeys(function (string $url, string $hash): \Generator {
                yield $url => $this->filesystem->getMetadata($hash);
            })
            ->all();
    }
}