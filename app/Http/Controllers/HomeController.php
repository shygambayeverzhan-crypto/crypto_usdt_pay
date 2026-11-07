<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Features;

class HomeController extends Controller
{
    /**
     * Display the welcome page.
     */
    public function index(): Response
    {
        return $this->inertia('Welcome', [
            'canRegister' => Features::enabled(Features::registration()),
        ]);
    }
}


