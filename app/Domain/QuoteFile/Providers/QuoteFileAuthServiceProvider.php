<?php

namespace App\Domain\QuoteFile\Providers;

use App\Domain\QuoteFile\Models\ImportableColumn;
use App\Domain\QuoteFile\Models\QuoteFile;
use App\Domain\QuoteFile\Policies\ImportableColumnPolicy;
use App\Domain\QuoteFile\Policies\QuoteFilePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class QuoteFileAuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(QuoteFile::class, QuoteFilePolicy::class);
        Gate::policy(ImportableColumn::class, ImportableColumnPolicy::class);
    }
}
