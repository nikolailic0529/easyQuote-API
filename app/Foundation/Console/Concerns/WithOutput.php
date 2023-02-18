<?php

namespace App\Foundation\Console\Concerns;

use Symfony\Component\Console\Style\OutputStyle;

trait WithOutput
{
    protected ?OutputStyle $output = null;

    /**
     * Set Output to the class instance.
     *
     * @return $this
     */
    public function withOutput(OutputStyle $output)
    {
        $this->output = $output;

        return $this;
    }

    /**
     * Determine whether isset the output.
     */
    public function hasOutput(): bool
    {
        return isset($this->output);
    }

    /**
     * Execute callback when output isset.
     *
     * @return mixed
     */
    public function whenHasOutput(\Closure $closure)
    {
        if ($this->hasOutput()) {
            return call_user_func($closure, $this->output);
        }
    }
}
