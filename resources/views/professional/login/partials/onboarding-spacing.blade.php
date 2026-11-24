{{--
  Task 03: Professional onboarding spacing tighten (~15%).
  Scoped to professional registration / resubmit pages only.
  Does not use zoom/scale; leaves font sizes and button min-heights alone.
--}}
<style>
    body.yuktione-auth-page.professional-registration-page,
    body.yuktione-auth-page.professional-rejected-page {
        --yk-onboard-page-y: 1.0625rem;   /* was 1.25rem (py-5) */
        --yk-onboard-page-x: 0.75rem;     /* was 0.75rem (px-3); sm bumped below */
        --yk-onboard-page-x-sm: 1.0625rem;/* was 1.25rem (sm:px-5) */
        --yk-onboard-card: 0.85rem;       /* was 1rem (p-4) */
        --yk-onboard-card-sm: 1.275rem;   /* was 1.5rem (sm:p-6) */
        --yk-onboard-card-md: 1.7rem;     /* was 2rem (md:p-8) */
        --yk-onboard-header-mb: 1.0625rem;/* was 1.25rem (mb-5) */
        --yk-onboard-header-mb-sm: 1.4875rem; /* was ~1.75rem (sm:mb-7) / 1.5rem (sm:mb-6) */
        --yk-onboard-logo-mb: 0.85rem;    /* was 1rem (mb-4) */
        --yk-onboard-logo-mb-sm: 1.275rem;/* was 1.5rem (sm:mb-6) */
        --yk-onboard-progress-mt: 0.6375rem; /* was 0.75rem (mt-3) */
        --yk-onboard-stack: 0.85rem;      /* was 1rem (space-y-4) */
        --yk-onboard-stack-sm: 0.85rem;
        --yk-onboard-stack-tight: 0.6375rem; /* was 0.75rem (space-y-3) */
        --yk-onboard-field-gap: 0.31875rem;  /* was 0.375rem (space-y-1.5) */
        --yk-onboard-inset: 0.6375rem;    /* was 0.75rem (p-3) */
        --yk-onboard-inset-lg: 0.74375rem;/* was 0.875rem (p-3.5) */
        --yk-onboard-footer-mt: 0.85rem;  /* was 1rem (mt-4) */
        --yk-onboard-footer-mt-sm: 1.0625rem; /* was 1.25rem (sm:mt-5) */
        --yk-onboard-upload-min: 4.25rem; /* was 5rem (min-h-20), ~15% */
        --yk-onboard-upload-min-sm: 3.4rem; /* was 4rem (min-h-16) */
    }

    body.yuktione-auth-page.professional-registration-page > main,
    body.yuktione-auth-page.professional-rejected-page > main {
        padding-block: var(--yk-onboard-page-y);
        padding-inline: var(--yk-onboard-page-x);
    }

    @media (min-width: 640px) {
        body.yuktione-auth-page.professional-registration-page > main,
        body.yuktione-auth-page.professional-rejected-page > main {
            padding-inline: var(--yk-onboard-page-x-sm);
        }
    }

    body.yuktione-auth-page.professional-registration-page > main > section,
    body.yuktione-auth-page.professional-rejected-page > main > section {
        padding: var(--yk-onboard-card);
    }

    @media (min-width: 640px) {
        body.yuktione-auth-page.professional-registration-page > main > section,
        body.yuktione-auth-page.professional-rejected-page > main > section {
            padding: var(--yk-onboard-card-sm);
        }
    }

    @media (min-width: 768px) {
        body.yuktione-auth-page.professional-registration-page > main > section,
        body.yuktione-auth-page.professional-rejected-page > main > section {
            padding: var(--yk-onboard-card-md);
        }
    }

    body.yuktione-auth-page.professional-registration-page > main > section > header,
    body.yuktione-auth-page.professional-rejected-page > main > section > header {
        margin-bottom: var(--yk-onboard-header-mb);
    }

    @media (min-width: 640px) {
        body.yuktione-auth-page.professional-registration-page > main > section > header,
        body.yuktione-auth-page.professional-rejected-page > main > section > header {
            margin-bottom: var(--yk-onboard-header-mb-sm);
        }
    }

    body.yuktione-auth-page.professional-registration-page > main > section > header > div:first-child,
    body.yuktione-auth-page.professional-rejected-page > main > section > header > div:first-child {
        margin-bottom: var(--yk-onboard-logo-mb);
    }

    @media (min-width: 640px) {
        body.yuktione-auth-page.professional-registration-page > main > section > header > div:first-child,
        body.yuktione-auth-page.professional-rejected-page > main > section > header > div:first-child {
            margin-bottom: var(--yk-onboard-logo-mb-sm);
        }
    }

    body.yuktione-auth-page.professional-registration-page > main > section > header [role="progressbar"],
    body.yuktione-auth-page.professional-rejected-page > main > section > header [role="progressbar"] {
        margin-top: var(--yk-onboard-progress-mt);
    }

    /* Step stacks: space-y utilities → ~15% tighter */
    body.yuktione-auth-page.professional-registration-page [data-registration-step="1"] > :not([hidden]):not(.hidden) ~ :not([hidden]):not(.hidden),
    body.yuktione-auth-page.professional-rejected-page .form-step > :not([hidden]):not(.hidden) ~ :not([hidden]):not(.hidden) {
        margin-top: 0.74375rem; /* was space-y-3.5 (0.875rem) */
    }

    body.yuktione-auth-page.professional-registration-page [data-registration-step="2"] > :not([hidden]):not(.hidden) ~ :not([hidden]):not(.hidden),
    body.yuktione-auth-page.professional-registration-page [data-registration-step="3"] > :not([hidden]):not(.hidden) ~ :not([hidden]):not(.hidden) {
        margin-top: var(--yk-onboard-stack); /* was space-y-4 (1rem) */
    }

    @media (min-width: 640px) {
        body.yuktione-auth-page.professional-registration-page [data-registration-step] > :not([hidden]):not(.hidden) ~ :not([hidden]):not(.hidden),
        body.yuktione-auth-page.professional-rejected-page .form-step > :not([hidden]):not(.hidden) ~ :not([hidden]):not(.hidden) {
            margin-top: var(--yk-onboard-stack); /* was sm:space-y-4 (1rem) */
        }
    }

    body.yuktione-auth-page.professional-registration-page #otp-section > :not([hidden]):not(.hidden) ~ :not([hidden]):not(.hidden),
    body.yuktione-auth-page.professional-registration-page #personal-info-section > :not([hidden]):not(.hidden) ~ :not([hidden]):not(.hidden),
    body.yuktione-auth-page.professional-registration-page #gst-fields-container > :not([hidden]):not(.hidden) ~ :not([hidden]):not(.hidden) {
        margin-top: var(--yk-onboard-stack-tight);
    }

    body.yuktione-auth-page.professional-registration-page #personal-info-section {
        padding-top: 0.85rem; /* was pt-4 (1rem) */
    }

    body.yuktione-auth-page.professional-registration-page .space-y-1\.5 > :not([hidden]):not(.hidden) ~ :not([hidden]):not(.hidden),
    body.yuktione-auth-page.professional-rejected-page .space-y-1\.5 > :not([hidden]):not(.hidden) ~ :not([hidden]):not(.hidden) {
        margin-top: var(--yk-onboard-field-gap);
    }

    body.yuktione-auth-page.professional-registration-page #otp-section,
    body.yuktione-auth-page.professional-registration-page #gst-fields-container {
        padding: var(--yk-onboard-inset-lg);
    }

    body.yuktione-auth-page.professional-registration-page [data-registration-step="3"] > .rounded-control.border.border-divider.p-3 {
        padding: var(--yk-onboard-inset);
    }

    body.yuktione-auth-page.professional-rejected-page > main > section > aside {
        margin-bottom: var(--yk-onboard-header-mb);
        padding: var(--yk-onboard-inset-lg);
    }

    @media (min-width: 640px) {
        body.yuktione-auth-page.professional-rejected-page > main > section > aside {
            padding: var(--yk-onboard-card);
        }
    }

    body.yuktione-auth-page.professional-registration-page .file-upload-label.min-h-20,
    body.yuktione-auth-page.professional-rejected-page .file-upload-label.min-h-20 {
        min-height: var(--yk-onboard-upload-min);
        padding-block: 0.6375rem; /* was py-3 */
    }

    body.yuktione-auth-page.professional-registration-page .file-upload-label.min-h-16,
    body.yuktione-auth-page.professional-rejected-page .file-upload-label.min-h-16 {
        min-height: var(--yk-onboard-upload-min-sm);
    }

    body.yuktione-auth-page.professional-registration-page > main > section > p.text-center,
    body.yuktione-auth-page.professional-rejected-page > main > section > p.text-center {
        margin-top: var(--yk-onboard-footer-mt);
    }

    @media (min-width: 640px) {
        body.yuktione-auth-page.professional-registration-page > main > section > p.text-center,
        body.yuktione-auth-page.professional-rejected-page > main > section > p.text-center {
            margin-top: var(--yk-onboard-footer-mt-sm);
        }
    }

    /* Preserve accessible control sizes */
    body.yuktione-auth-page.professional-registration-page .min-h-11,
    body.yuktione-auth-page.professional-rejected-page .min-h-11 {
        min-height: 2.75rem;
    }

    body.yuktione-auth-page.professional-registration-page .min-h-10,
    body.yuktione-auth-page.professional-rejected-page .min-h-10 {
        min-height: 2.5rem;
    }
</style>
