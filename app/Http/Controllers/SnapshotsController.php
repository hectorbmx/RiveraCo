<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SnapshotsController extends Controller
{
    //
     public function store(Request $request)
        {
            dd('snapshots.store OK', $request->all());
        }
}
