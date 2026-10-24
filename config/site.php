<?php
/*
 * Operator and public-contact settings for Uhai Bora.
 *
 * Every value that describes the real organisation running this service is
 * deliberately left empty. Fill these in only with verified information.
 * Pages show a neutral "not yet published" message when a value is missing,
 * so nothing has to be guessed.
 *
 * To keep deployment-specific values out of version control, copy the keys you
 * need into config/site.local.php (ignored by git); they are merged over this file.
 */
return [
    'name' => 'Uhai Bora',

    // Bump when the Privacy notice or Terms change materially. Stored with each
    // patient's consent so you can show which version they accepted.
    'policy_version' => '2026-10-24',
    'policy_effective_date' => '2026-10-24',

    'operator' => [
        // Registered legal name of the organisation that controls patient data.
        'legal_name' => null,
        // e.g. health facility licence or KMPDC registration reference.
        'facility_registration' => null,
        // ODPC data controller registration certificate number.
        'odpc_registration' => null,
        // Physical or postal address shown on Contact and in the Privacy notice.
        'address' => null,
    ],

    'contact' => [
        'phone' => null,
        'email' => null,
        // Hours when the phone or email above is monitored, e.g. "Mon–Fri, 8am–5pm".
        'hours' => null,
    ],

    'privacy' => [
        // Data protection officer or privacy contact for data-rights requests.
        'email' => null,
        // Plain-language description of where data is hosted (country/provider).
        'hosting' => null,
        // Plain-language description of retention periods.
        'retention' => null,
    ],

    'accessibility' => [
        // Contact for accessibility feedback; falls back to contact.email.
        'email' => null,
        // Date of the most recent independent accessibility audit, if any.
        'last_audit' => null,
    ],

    // Publish only numbers verified against an official source.
    // Example shape: [['label' => 'National emergency line', 'number' => '...']]
    'emergency_numbers' => [],
];
