<?php

namespace App\Services\Concerns;

use Symfony\Component\Console\Helper\ProgressBar;

trait WithProgress
{
    private ?ProgressBar $progressBar = null;

    private function setProgressBar($bar, $maxSteps = 0)
    {
        if ($bar instanceof ProgressBar) {
            $this->progressBar = $bar;

            $this->progressBar->setMaxSteps(value($maxSteps));
        }
    }

    private function advanceProgress(int $step = 1): void
    {
        if ($this->progressBar instanceof ProgressBar) {
            $this->progressBar->advance($step);
        }
    }

    private function finishProgress(): void
    {
        if ($this->progressBar instanceof ProgressBar) {
            $this->progressBar->finish();
        }
    }
}