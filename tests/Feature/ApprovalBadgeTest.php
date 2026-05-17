<?php

use Illuminate\Support\Facades\Blade;

describe('<x-approval-badge>', function () {
    it('renders a green Approved badge for an approved status', function () {
        $html = Blade::render('<x-approval-badge label="Delivery" status="approved" />');

        expect($html)
            ->toContain('Delivery')
            ->toContain('Approved');

        expect(strtolower($html))->toContain('green');
    });

    it('renders a red Rejected badge for a rejected status', function () {
        $html = Blade::render('<x-approval-badge label="Operations" status="rejected" />');

        expect($html)
            ->toContain('Operations')
            ->toContain('Rejected');

        expect(strtolower($html))->toContain('red');
    });

    it('renders a neutral Pending badge by default', function () {
        $html = Blade::render('<x-approval-badge label="Resilience" />');

        expect($html)
            ->toContain('Resilience')
            ->toContain('Pending');

        expect(strtolower($html))->toContain('zinc');
    });

    it('allows the visible status text to be overridden with a display prop', function () {
        $html = Blade::render('<x-approval-badge label="Deadline Achievable" status="approved" display="Yes" />');

        expect($html)
            ->toContain('Deadline Achievable')
            ->toContain('Yes')
            ->not->toContain('Approved');

        expect(strtolower($html))->toContain('green');
    });
});
