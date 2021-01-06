<?php

namespace App\Services\Concerns;

use Symfony\Component\Console\Style\OutputStyle;
use Closure;

trait WithOutput
{
    protected ?OutputStyle $output = null;

    /**
     * Set Output to the class instance.
     *
     * @param OutputStyle $output
     * @return $this
     */
    public function withOutput(OutputStyle $output)
    {
        $this->output = $output;

        return $this;
    }

    /**
     * Determine whether isset the output.
     *
     * @return boolean
     */
    public function hasOutput(): bool
    {
        return isset($this->output);
    }

    /**
     * Execute callback when output isset.
     *
     * @param Closure $closure
     * @return mixed
     */
    public function whenHasOutput(Closure $closure)
    {
        if ($this->hasOutput()) {
            return call_user_func($closure, $this->output);
        }
    }
}