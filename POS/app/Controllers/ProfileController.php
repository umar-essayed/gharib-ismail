<?php

namespace App\Controllers;

use App\Core\Controller;

class ProfileController extends Controller
{
    public function index(): void
    {
        $user = current_user();
        $this->view('users/profile', compact('user'));
    }
}
