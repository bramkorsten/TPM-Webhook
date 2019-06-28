<?php

namespace App\Http\Controllers;

use App\Package;

use Illuminate\Support\Str;
use Illuminate\Http\Request;

class PackageController extends Controller
{

    public function index($value='')
    {
        $packages = Package::all();

        return response()->json($packages);
    }

    public function create(Request $request)
    {
        $package = new Package();

        $package->name = $request->name;
        $package->repo_name = $request->repo_name;
        $package->repo_url = $request->repo_url;
        $package->repo_private_key = Str::random(30);

        $package->save();

        return response()->json($package);
    }

    public function show($id)
    {
        $package = Package::exists($id);
        return response()->json($package);
    }

    public function update(Request $request)
    {
        $package = new Package();

        $package->name = $request->name;
        $package->repo_name = $request->repo_name;
        $package->repo_url = $request->repo_url;
        $package->latest_version = $request->latest_version;
        $package->latest_download_url = $request->latest_download_url;
        $package->latest_changelog_link = $request->latest_changelog_link;
        $package->latest_release_date = $request->latest_release_date;

        $package->save();

        return response()->json($package);
    }

    public function destroy($id)
    {
        $package = Package::find($id);
        $package->delete();
         return response()->json('Done!');
    }

}
