<?php

namespace App\Domain\Shared\SharingUser\Contacts;

use Illuminate\Database\Eloquent\Relations\HasMany;

interface HasSharingUserRelations
{
    public function sharingUserRelations(): HasMany;
}
