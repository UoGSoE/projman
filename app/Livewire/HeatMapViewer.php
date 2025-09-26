<?php

namespace App\Livewire;

use App\Enums\Busyness;
use App\Models\Project;
use App\Models\User;
use Carbon\Carbon;
use Livewire\Component;

class HeatMapViewer extends Component
{
    public function render()
    {
        $busynessData = $this->getUserBusynessData();

        return view('livewire.heat-map-viewer', [
            'busynessData' => $busynessData,
        ]);
    }

    /**
     * Get user busyness data for the next two weeks broken down by days.
     * Returns a structured array suitable for heatmap visualization.
     */
    public function getUserBusynessData(): array
    {
        $users = User::where('is_staff', true)
            ->with(['projects' => function ($query) {
                $query->whereNotNull('deadline')
                    ->where('deadline', '>=', now())
                    ->where('deadline', '<=', now()->addWeeks(2));
            }])
            ->select(['id', 'forenames', 'surname', 'busyness_week_1', 'busyness_week_2'])
            ->get();

        $dayRanges = $this->calculateDayRanges();

        $heatmapData = [
            'day_ranges' => $dayRanges,
            'users' => [],
            'summary' => [
                'low' => 0,
                'medium' => 0,
                'high' => 0,
                'unknown' => 0,
            ],
        ];

        foreach ($users as $user) {
            $userData = [
                'id' => $user->id,
                'name' => $user->forenames.' '.$user->surname,
                'days' => $this->calculateDailyBusyness($user, $dayRanges),
            ];

            $heatmapData['users'][] = $userData;

            // Update summary counts based on daily averages
            $this->updateDailySummaryCounts($heatmapData['summary'], $userData['days']);
        }

        return $heatmapData;
    }

    /**
     * Calculate the date ranges for the next two weeks broken down by days.
     */
    private function calculateDayRanges(): array
    {
        $today = Carbon::now();
        $days = [];

        // Get the start of the current week (Monday)
        $currentWeekStart = $today->copy()->startOfWeek();

        // Generate 10 working days (2 weeks, Monday-Friday)
        for ($i = 0; $i < 10; $i++) {
            $date = $currentWeekStart->copy()->addDays($i);
            $days[] = [
                'date' => $date->format('Y-m-d'),
                'day_name' => $date->format('D'),
                'day_number' => $date->format('j'),
                'month' => $date->format('M'),
                'week' => $i < 5 ? 1 : 2,
                'day_of_week' => $i % 5,
            ];
        }

        return $days;
    }

    /**
     * Calculate daily busyness for a user considering project deadlines.
     */
    private function calculateDailyBusyness(User $user, array $dayRanges): array
    {
        $dailyData = [];
        $baseWeek1Busyness = $user->busyness_week_1 ?? Busyness::LOW;
        $baseWeek2Busyness = $user->busyness_week_2 ?? Busyness::LOW;

        // Get project deadlines for this user
        $projectDeadlines = $this->getProjectDeadlinesForUser($user);

        foreach ($dayRanges as $day) {
            $week = $day['week'];
            $baseBusyness = $week === 1 ? $baseWeek1Busyness : $baseWeek2Busyness;

            // Calculate deadline impact
            $deadlineImpact = $this->calculateDeadlineImpact($day['date'], $projectDeadlines);

            // Combine base busyness with deadline impact
            $finalBusyness = $this->combineBusynessLevels($baseBusyness, $deadlineImpact);

            $dailyData[] = [
                'date' => $day['date'],
                'day_name' => $day['day_name'],
                'day_number' => $day['day_number'],
                'month' => $day['month'],
                'week' => $week,
                'busyness' => $this->formatBusynessData($finalBusyness),
                'deadline_projects' => $this->getProjectsDueOnDate($day['date'], $projectDeadlines),
            ];
        }

        return $dailyData;
    }

    /**
     * Get project deadlines for a specific user.
     */
    private function getProjectDeadlinesForUser(User $user): array
    {
        return $user->projects->map(function ($project) {
            return [
                'id' => $project->id,
                'title' => $project->title,
                'deadline' => $project->deadline,
                'status' => $project->status,
            ];
        })->toArray();
    }

    /**
     * Calculate deadline impact on busyness for a specific date.
     */
    private function calculateDeadlineImpact(string $date, array $projectDeadlines): ?Busyness
    {
        $targetDate = Carbon::parse($date);
        $impact = null;

        foreach ($projectDeadlines as $project) {
            $deadline = Carbon::parse($project['deadline']);
            $daysUntilDeadline = $targetDate->diffInDays($deadline, false);

            // If deadline is on this date or very close, increase busyness
            if ($daysUntilDeadline <= 0) {
                // Deadline is today or overdue - very high busyness
                $impact = Busyness::HIGH;
            } elseif ($daysUntilDeadline <= 1) {
                // Deadline is tomorrow - high busyness
                $impact = $this->getHigherBusyness($impact, Busyness::HIGH);
            } elseif ($daysUntilDeadline <= 3) {
                // Deadline is within 3 days - medium to high busyness
                $impact = $this->getHigherBusyness($impact, Busyness::MEDIUM);
            } elseif ($daysUntilDeadline <= 7) {
                // Deadline is within a week - slight increase
                $impact = $this->getHigherBusyness($impact, Busyness::LOW);
            }
        }

        return $impact;
    }

    /**
     * Get the higher busyness level between two levels.
     */
    private function getHigherBusyness(?Busyness $current, Busyness $new): Busyness
    {
        if (! $current) {
            return $new;
        }

        return $current->value > $new->value ? $current : $new;
    }

    /**
     * Combine base busyness with deadline impact.
     */
    private function combineBusynessLevels(Busyness $base, ?Busyness $deadlineImpact): Busyness
    {
        if (! $deadlineImpact) {
            return $base;
        }

        // If deadline impact is higher than base, use deadline impact
        if ($deadlineImpact->value > $base->value) {
            return $deadlineImpact;
        }

        // Otherwise, increase base busyness by one level if possible
        return match ($base) {
            Busyness::UNKNOWN => Busyness::LOW,
            Busyness::LOW => Busyness::MEDIUM,
            Busyness::MEDIUM => Busyness::HIGH,
            Busyness::HIGH => Busyness::HIGH, // Already at maximum
        };
    }

    /**
     * Get projects due on a specific date.
     */
    private function getProjectsDueOnDate(string $date, array $projectDeadlines): array
    {
        return array_filter($projectDeadlines, function ($project) use ($date) {
            return Carbon::parse($project['deadline'])->format('Y-m-d') === $date;
        });
    }

    /**
     * Format busyness data for heatmap display.
     */
    private function formatBusynessData(?Busyness $busyness): array
    {
        if (! $busyness) {
            return [
                'value' => 0,
                'label' => 'Unknown',
                'color' => 'bg-gray-100',
                'intensity' => 0,
            ];
        }

        return [
            'value' => $busyness->value,
            'label' => $busyness->label(),
            'color' => $busyness->color(),
            'intensity' => $this->calculateIntensity($busyness),
        ];
    }

    /**
     * Calculate intensity value for heatmap visualization (0-1 scale).
     */
    private function calculateIntensity(Busyness $busyness): float
    {
        return match ($busyness) {
            Busyness::UNKNOWN => 0.0,
            Busyness::LOW => 0.3,
            Busyness::MEDIUM => 0.6,
            Busyness::HIGH => 1.0,
        };
    }

    /**
     * Update summary counts for daily busyness statistics.
     */
    private function updateDailySummaryCounts(array &$summary, array $dailyData): void
    {
        foreach ($dailyData as $day) {
            $busyness = $day['busyness'];
            $key = strtolower($busyness['label']);

            if (isset($summary[$key])) {
                $summary[$key]++;
            }
        }
    }
}
