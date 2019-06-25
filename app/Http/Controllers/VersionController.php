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

    public function create(Request $request, $id)
    {

        $package = Package::exists($id);
        $secret = $package->repo_private_key;

        $postBody = file_get_contents('php://input');

        $generatedSignature = 'sha1=' . hash_hmac('sha1', $postBody, $secret);
        $headerSignature = $request->header('X_HUB_SIGNATURE');

        if (!hash_equals($headerSignature, $generatedSignature)) {
            return response()->json(['error' => 'Signature does not match. Request not trusted'], 401);
        }

        $payload = json_decode($request->payload);

        $payload = $payload->release;

        $version = new Version();

        $version->package_id = $package->id;
        $version->version = $payload->tag_name;
        $version->release_type = ($payload->prerelease ? 1 : 2);
        $version->is_prerelease = ($payload->prerelease ? true : false);
        $version->repo_zip_url = $payload->zipball_url;
        $version->changelog_url = $payload->html_url;
        $version->release_author = $payload->author->login;

        $version->save();

        $version->refresh();

        $filePath = $version->createRelease($package);

        return response()->json([$version, $filePath]);
    }
}
