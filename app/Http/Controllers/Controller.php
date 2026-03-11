<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

abstract class Controller
{
    use AuthorizesRequests;

    /**
     * Get a safe per_page value capped at 100 to prevent memory exhaustion.
     */
    protected function getPerPage(Request $request, int $default = 15, int $max = 100): int
    {
        return min(max(1, (int) $request->input('per_page', $default)), $max);
    }
}
