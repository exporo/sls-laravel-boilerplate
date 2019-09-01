<?php

namespace App\Http\Controllers;

class KeepWarmController extends Controller
{
    public function index()
    {
        return response('keep warm', 200);
    }
}
