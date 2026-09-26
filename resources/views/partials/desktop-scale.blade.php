{{--
    Keeps laptops and desktops looking like the 1920px (100%) desktop layout.
    All Tailwind/Filament sizes are in rem, so scaling the root font size by
    viewport width scales the whole page. 1920px / 120 = 16px (the default).
    Phones and tablets (< 1024px) are left untouched.
--}}
<style>
    @media (min-width: 1024px) {
        html {
            font-size: clamp(11.2px, calc(100vw / 120), 16px);
        }
    }
</style>
