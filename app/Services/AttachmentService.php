<?php

namespace App\Services;

use App\Models\Attachment;
use Illuminate\Http\UploadedFile;
use InvalidArgumentException;
use Arr, File;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

class AttachmentService
{
    public const ATTACHMENT_DISK = 'attachments';
    
    protected Attachment $attachment;

    protected Filesystem $disk;

    public function __construct(Attachment $attachment)
    {
        $this->attachment = $attachment;
        $this->disk = Storage::disk(static::ATTACHMENT_DISK);
    }

    public function store(array $attributes): Attachment
    {
        $file = Arr::get($attributes, 'file');

        static::testFileAttribute($file);

        $filepath = $file->store(null, static::ATTACHMENT_DISK);
        $extension = File::extension($file->getClientOriginalName());
        $filename = static::handleFilename($file->getClientOriginalName());
        $size = $file->getSize();

        return $this->attachment->create($attributes + compact('filepath', 'filename', 'extension', 'size'));
    }

    private static function testFileAttribute($file): void
    {
        if (!$file instanceof UploadedFile) {
            throw new InvalidArgumentException(
                sprintf('Passed file in attributes must be an instance of %s', UploadedFile::class)
            );
        }
    }

    private static function handleFilename(string $filename): string
    {
        $ext = File::extension($filename);
        
        $maxBasenameLength = 191 - strlen($ext);

        $filename = substr(File::name($filename), 0, $maxBasenameLength);

        return implode('.', [$filename, $ext]);
    }
}
