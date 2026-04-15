<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminController extends Controller
{
    /**
     * AU-006: Admin dashboard — only accessible to users with role:admin.
     */
    public function dashboard(): View
    {
        return view('admin.dashboard');
    }
}
