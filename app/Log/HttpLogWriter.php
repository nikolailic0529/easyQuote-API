<?php

namespace App\Log;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Psr\Log\LoggerInterface;
use Spatie\HttpLogger\LogWriter;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class HttpLogWriter implements LogWriter
{
    protected LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function logRequest(Request $request)
    {
        $message = $this->formatMessage($this->getMessage($request));

        $this->logger->info($message);
    }

    protected function formatMessage(array $message): string
    {
        $bodyAsJson = json_encode($message['body']);
        $headersAsJson = json_encode($message['headers']);
        $files = $message['files']->implode(',');

        return "{$message['method']} {$message['uri']} - Body: {$bodyAsJson} - Headers: {$headersAsJson} - Files: ".$files;
    }

    public function getMessage(Request $request): array
    {
        $files = (new Collection(iterator_to_array($request->files)))
            ->map([$this, 'flatFiles'])
            ->flatten();

        return [
            'method' => strtoupper($request->getMethod()),
            'uri' => $request->getPathInfo(),
            'body' => $request->except(config('http-logger.except')),
            'headers' => $request->headers->all(),
            'files' => $files,
        ];
    }

    public function flatFiles($file)
    {
        if ($file instanceof UploadedFile) {
            return $file->getClientOriginalName();
        }
        if (is_array($file)) {
            return array_map([$this, 'flatFiles'], $file);
        }

        return (string)$file;
    }
}
