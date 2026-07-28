<?php

// Branding. Rename the whole product from one place. These defaults can be
// overridden by env, and by DB settings applied at boot (the Branding settings
// screen) - matching the DB-driven config pattern.
return [
    'name' => env('BRAND_NAME', env('APP_NAME', 'SpamMGR')),
    'tagline' => env('BRAND_TAGLINE', 'Email Security Gateway'),
    // Accent hex; overrides the brand ramp at runtime. Settable in the UI.
    'accent' => env('BRAND_ACCENT', '#dc2626'),
    // Logo/favicon glyph (an x-icon name). Distinct per product.
    'icon' => env('BRAND_ICON', 'filter'),
];
