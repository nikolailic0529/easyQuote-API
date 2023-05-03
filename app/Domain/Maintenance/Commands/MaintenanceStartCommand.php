<?php

namespace App\Domain\Maintenance\Commands;

use App\Domain\Build\Contracts\BuildRepositoryInterface;
use App\Domain\Build\Models\Build;
use App\Domain\Maintenance\Jobs\DownIntoMaintenanceMode;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use Illuminate\Console\Command;
use Illuminate\Contracts\Bus\Dispatcher;
use React\EventLoop\Loop;
use Symfony\Component\Console\Cursor;
use Symfony\Component\Console\Input\InputArgument;

class MaintenanceStartCommand extends Command
{
    /**
     * @var string
     */
    protected $name = 'eq:maintenance-start';

    /**
     * @var string
     */
    protected $description = 'Put the application into maintenance mode';

    public function handle(Dispatcher $dispatcher): int
    {
        $delay = $this->resolveDelayInterval();
        $down = $this->resolveDownInterval();
        $startIn = now()->add($delay);
        $endIn = $startIn->clone()->add($down);

        $this->createBuild($startIn, $endIn);

        $dispatcher->dispatch(new DownIntoMaintenanceMode($startIn, $endIn));

        $this->waitUntil($startIn);

        $this->output->writeLn(
            sprintf('Maintenance started. Down time: %s', CarbonInterval::create($down)->cascade()->forHumans())
        );

        return self::SUCCESS;
    }

    private function waitUntil(Carbon $schedule): void
    {
        $loop = Loop::get();
        $out = $this->output;
        $cursor = new Cursor($out);

        $timer = $loop->addPeriodicTimer(1.0, static function () use ($out, $cursor, $schedule): void {
            $cursor->moveToColumn(1);
            $cursor->clearLine();
            $out->write(sprintf('Start in: %ds', $schedule->diffInSeconds(now())));
        });

        $loop->addTimer($schedule->diffInSeconds(now()), static function () use ($out, $loop, $timer): void {
            $loop->cancelTimer($timer);
            $out->writeln('');
        });

        $loop->run();
    }

    private function createBuild(Carbon $start_time, Carbon $end_time): void
    {
        $buildNumber = $this->argument('build');
        $buildTag = $this->argument('tag');

        /** @var Build|null $prevBuild */
        $prevBuild = Build::query()->latest()->first();

        $buildNumber ??= $prevBuild?->build_number;
        $buildTag ??= $prevBuild?->git_tag;

        $this->laravel->make(BuildRepositoryInterface::class)->create([
            'start_time' => $start_time,
            'end_time' => $end_time,
            'build_number' => $buildNumber,
            'git_tag' => $buildTag,
        ]);
    }

    private function resolveDelayInterval(): \DateInterval
    {
        return CarbonInterval::seconds(60 * $this->argument('delay'));
    }

    private function resolveDownInterval(): \DateInterval
    {
        return CarbonInterval::seconds(60 * $this->argument('time'));
    }

    protected function getArguments(): array
    {
        return [
            new InputArgument('delay', InputArgument::REQUIRED, 'Delay before start in minutes'),
            new InputArgument('time', InputArgument::REQUIRED, 'Maintenance time in minutes'),
            new InputArgument('build', InputArgument::OPTIONAL, 'Build number'),
            new InputArgument('tag', InputArgument::OPTIONAL, 'Build tag'),
        ];
    }
}
