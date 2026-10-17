<?php

namespace App\Services;

use App\Models\Dependency;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Support\Collection;

/**
 * Critical path: enumerasi semua jalur dependensi dari sumber ke tugas akhir,
 * total durasi tiap jalur (durasi tugas + delay edge), dan penandaan jalur terpanjang.
 */
class CriticalPathService
{
    private const MAX_PATHS = 10000;

    /**
     * @return array{
     *     paths: list<array{tasks: list<int>, names: list<string>, duration: int}>,
     *     critical_path: array{tasks: list<int>, names: list<string>, duration: int}|null,
     *     critical_task_ids: list<int>
     * }
     */
    public function analyze(Project $project): array
    {
        $tasks = $project->tasks()->get()->keyBy('id');
        $dependencies = $project->dependencies()->get();

        // Graf hanya memakai tugas daun (tanpa anak) agar durasi tidak terhitung ganda.
        $leafIds = $tasks
            ->filter(fn (Task $t) => $t->parent_id === null || ! $tasks->contains('id', $t->parent_id))
            ->pluck('id');

        /** @var Collection<int, Task> $nodes */
        $nodes = $tasks->filter(fn (Task $t) => $leafIds->contains($t->id));

        /** @var array<int, list<array{id:int, delay:int}>> $outgoing */
        $outgoing = [];
        /** @var array<int, true> $hasIncoming */
        $hasIncoming = [];

        foreach ($nodes as $id => $task) {
            $outgoing[$id] = [];
        }

        /** @var Collection<int, Dependency> $edges */
        $edges = $dependencies->filter(
            fn (Dependency $d) => $nodes->has($d->predecessor_id) && $nodes->has($d->successor_id)
        );

        foreach ($edges as $edge) {
            $outgoing[$edge->predecessor_id][] = [
                'id' => $edge->successor_id,
                'delay' => max(0, (int) $edge->delay),
            ];
            $hasIncoming[$edge->successor_id] = true;
        }

        $sources = $nodes->filter(fn (Task $t, int $id) => ! isset($hasIncoming[$id]))->keys();

        $paths = [];

        foreach ($sources as $source) {
            $this->dfs($project, $nodes, $outgoing, $source, [], 0, $paths);
        }

        // Tugas tanpa dependensi sama sekali = jalur tunggal.
        if ($nodes->count() > 0 && $sources->isEmpty()) {
            // Semua tugas terlibat siklus: laporkan tiap tugas sebagai jalur sendiri.
            foreach ($nodes as $id => $task) {
                $paths[] = ['tasks' => [$id], 'names' => [$task->name], 'duration' => max(1, (int) $task->duration)];
            }
        }

        usort($paths, fn (array $a, array $b) => $b['duration'] <=> $a['duration']);

        $critical = $paths[0] ?? null;

        return [
            'paths' => $paths,
            'critical_path' => $critical,
            'critical_task_ids' => $critical['tasks'] ?? [],
        ];
    }

    /**
     * @param Collection<int, Task> $nodes
     * @param array<int, list<array{id:int, delay:int}>> $outgoing
     * @param list<int> $path
     * @param list<array{tasks: list<int>, names: list<string>, duration: int}> $paths
     */
    private function dfs(
        Project $project,
        Collection $nodes,
        array $outgoing,
        int $current,
        array $path,
        int $duration,
        array &$paths
    ): void {
        if (count($paths) >= self::MAX_PATHS) {
            return;
        }

        $path[] = $current;
        $duration += max(1, (int) $nodes[$current]->duration);

        $next = collect($outgoing[$current] ?? [])->filter(fn (array $edge) => ! in_array($edge['id'], $path, true));

        if ($next->isEmpty()) {
            $paths[] = [
                'tasks' => $path,
                'names' => array_map(fn (int $id) => $nodes[$id]->name, $path),
                'duration' => $duration,
            ];

            return;
        }

        foreach ($next as $edge) {
            $this->dfs($project, $nodes, $outgoing, $edge['id'], $path, $duration + $edge['delay'], $paths);
        }
    }
}
