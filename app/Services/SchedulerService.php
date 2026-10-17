<?php

namespace App\Services;

use App\Models\Dependency;
use App\Models\Project;
use App\Models\Task;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Logika penjadwalan: kalender kerja (weekend + libur), dependensi
 * FS/SS/FF/SF dengan delay, dan agregasi tanggal sub-tugas ke induk.
 */
class SchedulerService
{
    /** @var array<int, array{one_off: list<string>, recurring: list<array{month:int,day:int}>}> */
    private array $holidayCache = [];

    /**
     * Hitung ulang jadwal seluruh tugas dalam proyek lalu simpan ke database.
     *
     * @return array{
     *     project_start: string|null,
     *     project_end: string|null,
     *     tasks: Collection<int, array<string, mixed>>
     * }
     */
    public function schedule(Project $project): array
    {
        $tasks = $project->tasks()->with('children')->get()->keyBy('id');
        $dependencies = $project->dependencies()->get();

        if ($tasks->isEmpty()) {
            return ['project_start' => null, 'project_end' => null, 'tasks' => collect()];
        }

        $order = $this->topologicalOrder($tasks, $dependencies);

        /** @var array<int, array{start: CarbonInterface, end: CarbonInterface}> $scheduled */
        $scheduled = [];

        foreach ($order as $taskId) {
            $task = $tasks[$taskId];

            if ($task->children->isNotEmpty()) {
                // Tugas induk: tanggal dan durasi otomatis dari anak-anaknya.
                $childStarts = $task->children->map(fn (Task $c) => $scheduled[$c->id]['start'] ?? null)->filter();
                $childEnds = $task->children->map(fn (Task $c) => $scheduled[$c->id]['end'] ?? null)->filter();

                if ($childStarts->isNotEmpty()) {
                    // Salin instance agar perubahan pada tugas lain tidak ikut menggeser tanggal induk.
                    $start = $childStarts->min()->copy();
                    $end = $childEnds->max()->copy();
                    $scheduled[$taskId] = ['start' => $start, 'end' => $end];
                    $task->forceFill([
                        'start_date' => $start->toDateString(),
                        'end_date' => $end->toDateString(),
                        'duration' => $this->countWorkingDays($project, $start, $end),
                    ])->save();
                    continue;
                }
            }

            $leaf = $this->scheduleLeaf($project, $task, $dependencies, $scheduled, $tasks);
            $scheduled[$taskId] = ['start' => $leaf['start']->copy(), 'end' => $leaf['end']->copy()];
            $task->forceFill([
                'start_date' => $leaf['start']->toDateString(),
                'end_date' => $leaf['end']->toDateString(),
            ])->save();
        }

        $projectStart = collect($scheduled)->min('start');
        $projectEnd = collect($scheduled)->max('end');

        $rows = $tasks->map(function (Task $task) use ($scheduled) {
            $s = $scheduled[$task->id];

            return [
                'id' => $task->id,
                'name' => $task->name,
                'parent_id' => $task->parent_id,
                'start_date' => $s['start']->toDateString(),
                'end_date' => $s['end']->toDateString(),
                'duration' => $task->duration,
                'priority' => $task->priority,
                'color' => $task->color,
                'is_summary' => $task->children->isNotEmpty(),
            ];
        })->values();

        return [
            'project_start' => $projectStart?->toDateString(),
            'project_end' => $projectEnd?->toDateString(),
            'tasks' => $rows,
        ];
    }

    /**
     * Apakah tanggal adalah hari kerja (lewati weekend & libur proyek)?
     */
    public function isWorkingDay(Project $project, CarbonInterface $date): bool
    {
        if ($project->weekend_mode === Project::WEEKEND_OFF && in_array((int) $date->isoWeekday(), [6, 7], true)) {
            return false;
        }

        foreach ($this->holidaysFor($project) as $holiday) {
            if ($holiday['type'] === 'one_off' && $holiday['date'] === $date->toDateString()) {
                return false;
            }

            if ($holiday['type'] === 'recurring'
                && (int) $date->month === $holiday['month']
                && (int) $date->day === $holiday['day']) {
                return false;
            }
        }

        return true;
    }

    /**
     * Hari kerja pertama >= tanggal yang diberikan.
     */
    public function nextWorkingDay(Project $project, CarbonInterface $date): CarbonInterface
    {
        $cursor = $date->copy()->startOfDay();
        $guard = 0;

        while (! $this->isWorkingDay($project, $cursor) && $guard++ < 3660) {
            $cursor = $cursor->addDay();
        }

        return $cursor;
    }

    /**
     * Tambah n hari kerja dari tanggal (tanggal awal tidak dihitung).
     */
    public function addWorkingDays(Project $project, CarbonInterface $date, int $days): CarbonInterface
    {
        $cursor = $date->copy()->startOfDay();
        $remaining = $days;
        $guard = 0;

        while ($remaining > 0 && $guard++ < 100000) {
            $cursor = $cursor->addDay();
            if ($this->isWorkingDay($project, $cursor)) {
                $remaining--;
            }
        }

        return $cursor;
    }

    /**
     * Kurangi n hari kerja dari tanggal (tanggal awal tidak dihitung).
     */
    public function subWorkingDays(Project $project, CarbonInterface $date, int $days): CarbonInterface
    {
        $cursor = $date->copy()->startOfDay();
        $remaining = $days;
        $guard = 0;

        while ($remaining > 0 && $guard++ < 100000) {
            $cursor = $cursor->subDay();
            if ($this->isWorkingDay($project, $cursor)) {
                $remaining--;
            }
        }

        return $cursor;
    }

    /**
     * Hitung jumlah hari kerja inklusif antara dua tanggal.
     */
    public function countWorkingDays(Project $project, CarbonInterface $start, CarbonInterface $end): int
    {
        if ($end->lessThan($start)) {
            return 0;
        }

        $count = 0;
        $cursor = $start->copy()->startOfDay();
        $guard = 0;

        while ($cursor->lessThanOrEqualTo($end) && $guard++ < 100000) {
            if ($this->isWorkingDay($project, $cursor)) {
                $count++;
            }
            $cursor = $cursor->addDay();
        }

        return $count;
    }

    /**
     * Konversi durasi kerja (mis. 5 hari) menjadi tanggal akhir dari tanggal mulai.
     */
    public function finishFor(Project $project, CarbonInterface $start, int $duration): CarbonInterface
    {
        $duration = max(1, $duration);
        $start = $this->nextWorkingDay($project, $start);

        return $this->addWorkingDays($project, $start, $duration - 1);
    }

    /**
     * @param Collection<int, Task> $tasks
     * @param Collection<int, Dependency> $dependencies
     * @param array<int, array{start: CarbonInterface, end: CarbonInterface}> $scheduled
     * @return array{start: CarbonInterface, end: CarbonInterface}
     */
    private function scheduleLeaf(
        Project $project,
        Task $task,
        Collection $dependencies,
        array $scheduled,
        Collection $tasks
    ): array {
        $duration = max(1, (int) $task->duration);
        $incoming = $dependencies->where('successor_id', $task->id);

        if ($incoming->isEmpty()) {
            $start = $task->start_date
                ?? $project->start_date
                ?? CarbonImmutable::today();
            $start = $this->nextWorkingDay($project, $start);

            return ['start' => $start, 'end' => $this->addWorkingDays($project, $start, $duration - 1)];
        }

        $earliestStart = null;
        $earliestEnd = null;

        foreach ($incoming as $dep) {
            $predecessor = $scheduled[$dep->predecessor_id] ?? null;
            if ($predecessor === null) {
                continue; // predecessor tidak bisa dijadwalkan (mis. siklus) — abaikan
            }

            $delay = max(0, (int) $dep->delay);

            // Delay ditulis dalam "hari" (hari kalender), lalu hasilnya di-snap
            // ke hari kerja berikutnya. Durasi tugas tetap dihitung dalam hari kerja.
            switch ($dep->type) {
                case 'FS':
                    // Mulai setelah selesai predecessor (+ delay hari).
                    $cand = $this->nextWorkingDay($project, $predecessor['end']->copy()->addDays(1 + $delay));
                    $earliestStart = $earliestStart === null ? $cand : $earliestStart->max($cand);
                    break;

                case 'SS':
                    // Mulai bersamaan dengan predecessor (+ delay hari).
                    $cand = $this->nextWorkingDay($project, $predecessor['start']->copy()->addDays($delay));
                    $earliestStart = $earliestStart === null ? $cand : $earliestStart->max($cand);
                    break;

                case 'FF':
                    // Selesai bersamaan dengan predecessor (+ delay hari).
                    $cand = $this->nextWorkingDay($project, $predecessor['end']->copy()->addDays($delay));
                    $earliestEnd = $earliestEnd === null ? $cand : $earliestEnd->max($cand);
                    break;

                case 'SF':
                    // Selesai saat predecessor mulai (+ delay hari).
                    $cand = $this->nextWorkingDay($project, $predecessor['start']->copy()->addDays($delay));
                    $earliestEnd = $earliestEnd === null ? $cand : $earliestEnd->max($cand);
                    break;
            }
        }

        if ($earliestStart === null && $earliestEnd === null) {
            $earliestStart = $this->nextWorkingDay($project, $task->start_date ?? $project->start_date ?? CarbonImmutable::today());
        }

        if ($earliestStart !== null) {
            $end = $this->addWorkingDays($project, $earliestStart, $duration - 1);

            // Batas akhir dari konstrain FF/SF bisa menggeser tanggal mulai ke belakang.
            if ($earliestEnd !== null && $end->lessThan($earliestEnd)) {
                $end = $this->nextWorkingDay($project, $earliestEnd);
                $earliestStart = $this->subWorkingDays($project, $end, $duration - 1);
            }

            return ['start' => $earliestStart, 'end' => $end];
        }

        // Hanya konstrain FF/SF: hitung mundur dari batas akhir.
        $end = $this->nextWorkingDay($project, $earliestEnd);
        $start = $this->subWorkingDays($project, $end, $duration - 1);

        return ['start' => $start, 'end' => $end];
    }

    /**
     * Urutan topologis tugas (Kahn), termasuk edge anak -> induk.
     * Jika ada siklus, sisa tugas diproses berdasarkan id tanpa konstrain dependensi.
     *
     * @param Collection<int, Task> $tasks
     * @param Collection<int, Dependency> $dependencies
     * @return list<int>
     */
    private function topologicalOrder(Collection $tasks, Collection $dependencies): array
    {
        /** @var array<int, list<int>> $edges predecessor -> [successor] */
        $edges = [];
        /** @var array<int, int> $indegree */
        $indegree = [];

        foreach ($tasks as $task) {
            $edges[$task->id] = $edges[$task->id] ?? [];
            $indegree[$task->id] = $indegree[$task->id] ?? 0;
        }

        foreach ($dependencies as $dep) {
            if (! isset($tasks[$dep->predecessor_id], $tasks[$dep->successor_id])) {
                continue;
            }
            $edges[$dep->predecessor_id][] = $dep->successor_id;
            $indegree[$dep->successor_id]++;
        }

        // Edge anak -> induk: anak harus dijadwalkan lebih dulu.
        foreach ($tasks as $task) {
            if ($task->parent_id !== null && isset($indegree[$task->parent_id])) {
                $edges[$task->id][] = $task->parent_id;
                $indegree[$task->parent_id]++;
            }
        }

        $queue = collect($indegree)->filter(fn ($deg) => $deg === 0)->keys()->sort()->values();
        $order = [];

        while ($queue->isNotEmpty()) {
            $current = $queue->shift();
            $order[] = $current;

            foreach ($edges[$current] ?? [] as $next) {
                $indegree[$next]--;
                if ($indegree[$next] === 0) {
                    $queue->push($next);
                }
            }

            $queue = $queue->sort()->values();
        }

        // Sisa = bagian siklus → proses berurut berdasarkan id tanpa konstrain dependensi.
        if (count($order) < count($tasks)) {
            $remaining = $tasks->keys()->diff($order)->sort()->values();
            foreach ($remaining as $taskId) {
                $order[] = $taskId;
            }
        }

        return $order;
    }

    /**
     * @return list<array{type: string, date: string|null, month: int|null, day: int|null}>
     */
    private function holidaysFor(Project $project): array
    {
        if (! isset($this->holidayCache[$project->id])) {
            $this->holidayCache[$project->id] = $project->holidays()->get()->map(fn ($h) => [
                'type' => $h->type,
                'date' => $h->date?->toDateString(),
                'month' => $h->month,
                'day' => $h->day,
            ])->all();
        }

        return $this->holidayCache[$project->id];
    }
}
