<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\LazyCollection;

return new class() extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $con = DB::connection($this->getConnection());

        /** @var Collection $seeds */
        $seeds = $con->table('tasks')
            ->lazyById(100)
            ->map(static function (stdClass $task): array {
                return [
                    'id' => $task->id,
                    'content' => json_decode($task->content, true),
                ];
            })
            ->filter(static function (array $task): bool {
                return array_is_list($task['content']);
            })
            ->map(function (array $task): array {
                $task['content'] = json_encode([
                    'details' => $this->resolveDetailsFromContent($task['content']),
                    'status' => $this->resolveStatusFromContent($task['content']),
                ]);

                return $task;
            })
            ->pipe(static function (LazyCollection $collection): Collection {
                return collect($collection->all());
            });

        $con->transaction(static function () use ($seeds) {
            foreach ($seeds as $seed) {
                DB::table('tasks')
                    ->where('id', $seed['id'])
                    ->update(['content' => $seed['content']]);
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
    }

    private function resolveDetailsFromContent(array $content): ?string
    {
        foreach ($content as $col) {
            if (!is_array($col) || !key_exists('child', $col)) {
                continue;
            }

            foreach ($col['child'] as $child) {
                foreach ($child['controls'] as $control) {
                    if ('richtext' === $control['type']) {
                        return $control['value'];
                    }
                }
            }
        }

        return null;
    }

    private function resolveStatusFromContent(array $content): ?string
    {
        foreach ($content as $col) {
            if (!is_array($col) || !key_exists('child', $col)) {
                continue;
            }

            foreach ($col['child'] as $child) {
                foreach ($child['controls'] as $control) {
                    if ('dropdown' === $control['type']) {
                        return $control['value'];
                    }
                }
            }
        }

        return null;
    }
};
