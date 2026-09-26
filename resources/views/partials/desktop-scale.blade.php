{{--
    Keeps laptops and desktops looking like the 1920px (100%) desktop layout.
    All Tailwind/Filament sizes are in rem, so scaling the root font size by
    viewport width scales the whole page. 1920px / 120 = 16px (the default).
    Phones and tablets (< 1024px) are left untouched.

    Pass ['fitHeight' => true] on single-card pages (login, register) so they
    also shrink to the window height: a 1920x1080 screen shows about 960px of
    page, and 960px / 60 = 16px, so short laptop windows get a smaller scale.
--}}
<style>
    @media (min-width: 1024px) {
        html {
            font-size: clamp(11.2px, calc(100vw / 120), 16px);
        }

        @if($fitHeight ?? false)
            html {
                font-size: clamp(10px, min(100vw / 120, 100vh / 60), 16px);
            }
        @endif
    }
</style>
