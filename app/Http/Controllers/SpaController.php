<?php

namespace App\Http\Controllers;

use App\Services\SocialMeta;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Serves the single-page app, with link-preview tags resolved for the paths
 * worth describing.
 */
class SpaController extends Controller
{
    public function __invoke(Request $request, SocialMeta $meta): View
    {
        return view('app', ['meta' => $meta->forPath($request->path())]);
    }
}
