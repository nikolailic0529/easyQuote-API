<?php

namespace App\Services\Attachment;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Str;
use JetBrains\PhpStorm\ArrayShape;

class AttachmentFileService
{
    public function __construct(protected FilesystemAdapter $filesystem,
                                protected Factory           $client)
    {
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
}