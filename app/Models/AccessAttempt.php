<?php namespace App\Models;

use App\Models\UuidModel;
use Illuminate\Http\Request;

class AccessAttempt extends UuidModel
{
    protected $fillable = [
        'email', 'token', 'ip_address', 'user_agent', 'is_success'
    ];

    public function markAsSuccessfull()
    {
        return $this->fill([
            'is_success' => true
        ])->save();
    }

    public function setDetails()
    {
        $this->fill([
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
