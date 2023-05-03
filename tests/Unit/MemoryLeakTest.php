<?php

namespace Tests\Unit;

use Tests\TestCase;

class MemoryLeakTest extends TestCase
{
    public function testLeaksMemoryOn1000Iterations()
    {
        // Remove the existing application instance.
        $this->app->flush();
        $this->app = null;

        // Let's create a fully booted application instance 1,000 times.
        for ($i = 1; $i <= 1000; ++$i) {
            $this->createApplication()->flush();

            // Each 50 times, report the MB used by the PHP process by dumping it.
            if (!($i % 50)) {
                dump('Using '.((int) (memory_get_usage(true) / (1024 * 1024))).'MB as '.$i.' iterations.');
            }
        }

        $this->app = $this->createApplication();
    }
}
