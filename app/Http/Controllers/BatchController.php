<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use Illuminate\Http\Request;

class BatchController extends Controller
{
    public function index()
    {
        $batches = Batch::with(['product', 'warehouse', 'import'])->latest('id')->get();
        return view('batches.index', compact('batches'));
    }
}
