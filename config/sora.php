<?php

declare(strict_types=1);

return [
    'featured_usernames' => array_filter(
        array_map('trim', explode(',', (string) env('SORA_FEATURED_USERNAMES', 'sora,sara,layla,nina,yusuf')))
    ),
];
