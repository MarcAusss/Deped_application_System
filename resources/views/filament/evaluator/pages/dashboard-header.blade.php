@php
    $firstName = str(auth()->user()?->name ?? 'Evaluator')->before(' ');
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
            <span>Evaluator workspace</span>
            <span class="recruitment-command__live">Evaluation queue</span>
        </div>

        <h1 class="recruitment-command__title">
            {{ $greeting }}, {{ $firstName }}.
        </h1>

        <p class="recruitment-command__description">
            Continue applicant assessment from one focused workspace. Review submitted
            credentials, record recommendations, and prepare the IER when the queue is ready.
        </p>

        <div class="recruitment-command__actions">
            <a
                class="recruitment-command__action recruitment-command__action--primary"
                href="{{ \App\Filament\Evaluator\Resources\Applications\ApplicationResource::getUrl('index') }}"
            >
                <x-filament::icon icon="heroicon-o-magnifying-glass-circle" />
                Open evaluation queue
            </a>

            <a
                class="recruitment-command__action"
                href="{{ \App\Filament\Evaluator\Resources\Applications\ApplicationResource::getUrl('index') }}"
            >
                <x-filament::icon icon="heroicon-o-document-chart-bar" />
                View IER export
            </a>
        </div>
    </div>

    <aside class="recruitment-command__brief">
        <span class="recruitment-command__brief-label">Your desk today</span>
        <span class="recruitment-command__brief-date">{{ now()->format('l, d F Y') }}</span>

        <div class="recruitment-command__brief-grid">
            <div class="recruitment-command__brief-item">
                <strong class="recruitment-command__brief-value">{{ number_format($pendingCount) }}</strong>
                <span class="recruitment-command__brief-name">Waiting in queue</span>
            </div>

            <div class="recruitment-command__brief-item">
                <strong class="recruitment-command__brief-value">{{ number_format($evaluatedTodayCount) }}</strong>
                <span class="recruitment-command__brief-name">Reviewed today</span>
            </div>

            <div class="recruitment-command__brief-item">
                <strong class="recruitment-command__brief-value">{{ number_format($recommendedCount) }}</strong>
                <span class="recruitment-command__brief-name">Recommended by you</span>
            </div>
        </div>
    </aside>
</section>
