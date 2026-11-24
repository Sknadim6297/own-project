{{--
  Shared visual chrome for Customer + Professional password recovery pages.
  Scoped to .auth-recovery-page so other auth screens stay untouched.
--}}
<meta name="theme-color" content="#8A0F4D">
<style>
    body.yuktione-auth-page.auth-recovery-page {
        --color-brand-50: #fff8fb;
        --color-brand-100: #f8eaf1;
        --color-background: #fff8fb;
        --color-brand-primary: #8a0f4d;
        background-color: #fff8fb;
    }

    body.yuktione-auth-page.auth-recovery-page > main {
        min-height: 100vh;
        min-height: 100dvh;
        background-image: linear-gradient(
            135deg,
            #fff8fb 0%,
            #f8eaf1 46%,
            #ffffff 100%
        );
    }
</style>
