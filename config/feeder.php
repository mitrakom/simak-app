<?php

declare(strict_types=1);

return [
    // Global safeguard: block ALL write operations to Feeder (Insert/Update/Delete/etc)
    // This should remain true in production per data governance policy.
    'read_only' => env('FEEDER_READ_ONLY', true),
];
