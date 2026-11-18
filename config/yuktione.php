<?php

return [
    'name' => 'YuktiOne',

    /*
    | Official public inbox for the contact page.
    */
    'email' => 'info@yuktione.in',

    /*
    | Phone and hours are the values stored on the contact details record.
    | No street address is published there, so the contact page must not invent one.
    */
    'phone_display' => '+91 96050 08040',
    'phone_tel' => '+919605008040',
    'hours' => 'Monday to Friday, 9am–6pm. Saturday, 9am–2pm.',
    'address' => null,

    /*
    | Public programmes listing and programme cards. Keep the code in place;
    | flip this to true when programmes should appear again.
    */
    'programmes_enabled' => false,

    /*
    |--------------------------------------------------------------------------
    | Placeholder expert ratings
    |--------------------------------------------------------------------------
    | TEMPORARY. While real ratings are not live, expert cards without approved
    | reviews show a stable placeholder rating so the card design is complete.
    | Set YUKTIONE_PLACEHOLDER_RATINGS=false (or this to false) to show only
    | real ratings.
    */
    'placeholder_ratings' => env('YUKTIONE_PLACEHOLDER_RATINGS', true),
];
