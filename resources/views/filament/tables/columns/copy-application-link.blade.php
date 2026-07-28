@php
    $applicationUrl = route('apply.form', ['job' => $record]);
@endphp

@if ($record->is_open)
<div
    class="w-full flex justify-center"
    x-data="{
        copied: false,

        async copyApplicationLink() {
            const applicationUrl = @js($applicationUrl);

            try {
                if (navigator.clipboard && window.isSecureContext) {
                    await navigator.clipboard.writeText(applicationUrl);
                } else {
                    const temporaryInput = document.createElement('textarea');

                    temporaryInput.value = applicationUrl;
                    temporaryInput.setAttribute('readonly', '');
                    temporaryInput.style.position = 'fixed';
                    temporaryInput.style.left = '-9999px';
                    temporaryInput.style.opacity = '0';

                    document.body.appendChild(temporaryInput);
                    temporaryInput.select();
                    document.execCommand('copy');
                    temporaryInput.remove();
                }

                this.copied = true;

                window.setTimeout(() => {
                    this.copied = false;
                }, 2000);
            } catch (error) {
                window.prompt('Copy this application link:', applicationUrl);
            }
        },
    }"
>
    <x-filament::button
        type="button"
        size="sm"
        color="info"
        icon="heroicon-o-clipboard-document"
        x-on:click.stop.prevent="copyApplicationLink()"
        x-bind:title="copied ? 'Application link copied' : 'Copy application link'"
    >
        <span x-text="copied ? 'Copied!' : 'Copy Link'">Copy Link</span>
    </x-filament::button>
</div>
@else
    <x-filament::badge color="gray">Closed</x-filament::badge>
@endif
