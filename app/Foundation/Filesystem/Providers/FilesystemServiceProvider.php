<?php

namespace App\Foundation\Filesystem\Providers;

use App\Foundation\Support\Mixins\FileMixin;
use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;

class FilesystemServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        File::mixin(new FileMixin());
    }
}
