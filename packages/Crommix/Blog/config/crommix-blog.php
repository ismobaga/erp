<?php

return [
    // Slug of the company whose blog is served on the public site. Leave empty
    // to match the request host against each company's website, falling back
    // to the first active company.
    'public_company' => env('BLOG_PUBLIC_COMPANY'),

    // Disk used for cover images and rich-editor attachments.
    'disk' => env('BLOG_DISK', 'public'),

    'per_page' => 9,

    // Number of items exposed in the RSS feed.
    'feed_limit' => 20,
];
