<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class Controller
{
    // Laravel 12 ships a bare base controller; $this->authorize() needs this trait.
    use AuthorizesRequests;
}
