<?php namespace App\Models;

use App\Contracts\WithImage;
use App\Models\UuidModel;
use App\Traits \ {
    BelongsToVendors,
    Image\HasImage
};

class Company extends UuidModel implements WithImage
{
    use HasImage, BelongsToVendors;

    protected $hidden = [
        'pivot', 'created_at', 'updated_at', 'drafted_at', 'deleted_at', 'is_system', 'logo'
    ];

    public function getLogoAttribute()
    {
        if(!isset($this->image)) {
            return null;
        }

        return $this->image->thumbnails;
    }

    public function thumbnailProperties(): array
    {
        return [
            [
                'width' => 60,
                'height' => 30
            ],
            [
                'width' => 120,
                'height' => 60
            ],
            [
                'width' => 240,
                'height' => 120
            ]
        ];
    }

    public function imagesDirectory(): string
    {
        return 'images/companies';
    }
}
