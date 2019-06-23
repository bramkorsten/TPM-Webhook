<?php

namespace App\Http\Controllers;

use App\Package;
use App\Version;

use Illuminate\Http\Request;

class VersionController extends Controller
{
    public function index($id)
    {
        $package = Package::exists($id);

        $versions = $package->getVersions();
        return response()->json($versions);
    }

    public function latest($id)
    {
        $package = Package::exists($id);

        $latest_version = $package->getLatestVersion();

        return response()->json($latest_version);
    }

    public function create(Request $request)
    {
        $payload = json_decode($request->payload, true);
        return response()->json($payload);
    }
}
