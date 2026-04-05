<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

/**
 * Base Web Controller
 * All web controllers should extend this.
 */
abstract class WebController extends Controller
{
    /**
     * Render a view with common data.
     */
    protected function view(string $view, array $data = []): View
    {
        return view($view, $data);
    }
}
