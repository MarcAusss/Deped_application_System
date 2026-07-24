@php
    $firstName = str(auth()->user()?->name ?? 'Administrator')->before(' ');
    $hour = now()->hour;
    $greeting = match (true) {
        $hour < 12 => 'Good morning',
        $hour < 18 => 'Good afternoon',
        default => 'Good evening',
    };
@endphp

<section class="recruitment-command">
    <div class="recruitment-command__main">
        <div class="recruitment-command__eyebrow">
            <span>Administration command center</span>
            <span class="recruitment-command__live">System overview</span>
        </div>

        <h1 class="recruitment-command__title">
            {{ $greeting }}, {{ $firstName }}.
        </h1>

        <p class="recruitment-command__description">
            Monitor the recruitment pipeline, review evaluator decisions, and keep every
            application moving toward a timely final action.
        </p>

        <div class="recruitment-command__actions">
            <a
                class="recruitment-command__action recruitment-command__action--primary"
                href="{{ \App\Filament\Resources\Applications\ApplicationResource::getUrl('index') }}"
            >
                <x-filament::icon icon="heroicon-o-clipboard-document-list" />
                Review applications
            </a>

            <a
                class="recruitment-command__action"
                href="{{ \App\Filament\Resources\JobPositions\JobPositionResource::getUrl('index') }}"
            >
                <x-filament::icon icon="heroicon-o-briefcase" />
                Manage job positions
            </a>
        </div>
    </div>

    <aside class="recruitment-command__brief">
        <span class="recruitment-command__brief-label">Operational brief</span>
        <span class="recruitment-command__brief-date">{{ now()->format('l, d F Y') }}</span>

        <div class="recruitment-command__brief-grid">
            <div class="recruitment-command__brief-item">
                <strong class="recruitment-command__brief-value">{{ number_format($pendingCount) }}</strong>
                <span class="recruitment-command__brief-name">Awaiting evaluation</span>
            </div>

            <div class="recruitment-command__brief-item">
                <strong class="recruitment-command__brief-value">{{ number_format($approvalCount) }}</strong>
                <span class="recruitment-command__brief-name">For final action</span>
            </div>

            <div class="recruitment-command__brief-item">
                <strong class="recruitment-command__brief-value">{{ number_format($openPositionCount) }}</strong>
                <span class="recruitment-command__brief-name">Open positions</span>
            </div>
        </div>
    </aside>
</section>
