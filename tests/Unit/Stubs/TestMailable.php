<?php

namespace Tests\Unit\Stubs;

use Illuminate\Mail\Mailable;

class TestMailable extends Mailable
{
    public function build(): static
    {
        return $this->to('test@example.com')
            ->html(static::class);
    }
}
