<?php

// Only the keys overridden here; everything else keeps Livewire's defaults
// (mergeConfigFrom merges top-level keys).
return [
    'payload' => [
        'max_size' => 1024 * 1024,
        // Rich editor documents are synced as nested arrays: text inside a
        // list nested three levels deep is already past Livewire's default of
        // 10, which made those edits fail with MaxNestingDepthExceededException.
        'max_nesting_depth' => 32,
        'max_calls' => 50,
        'max_components' => 20,
    ],
];
