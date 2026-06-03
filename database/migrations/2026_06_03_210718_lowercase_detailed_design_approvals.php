<?php

use App\Models\DetailedDesign;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * The approval columns whose stored values need bringing into line with
     * the lowercase keys used everywhere else in the app.
     *
     * @var array<int, string>
     */
    private array $columns = [
        'approval_delivery',
        'approval_operations',
        'approval_resilience',
        'approval_agb',
        'approval_change_board',
    ];

    public function up(): void
    {
        $this->remap([
            'Pending' => 'pending',
            'Approved' => 'approved',
            'Deferred' => 'deferred',
            'Rejected' => 'rejected',
            'Not Required' => 'not_required',
        ]);
    }

    public function down(): void
    {
        $this->remap([
            'pending' => 'Pending',
            'approved' => 'Approved',
            'deferred' => 'Deferred',
            'rejected' => 'Rejected',
            'not_required' => 'Not Required',
        ]);
    }

    /**
     * @param  array<string, string>  $map
     */
    private function remap(array $map): void
    {
        DetailedDesign::query()->each(function (DetailedDesign $design) use ($map): void {
            foreach ($this->columns as $column) {
                $current = $design->{$column};

                if ($current !== null && isset($map[$current])) {
                    $design->{$column} = $map[$current];
                }
            }

            if ($design->isDirty()) {
                $design->timestamps = false;
                $design->saveQuietly(['touch' => false]);
            }
        });
    }
};
