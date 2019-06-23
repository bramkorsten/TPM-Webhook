<?php

namespace App;

use App\Version;
use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'packages';

    /**
     * Check whether a package exists by using the id or name
     * @param  mixed $id    The id or name of the package
     * @return package
     */
    static public function exists($id)
    {
        $package = array();
        try {
            $package = Self::findOrFail($id);
        } catch (\Exception $e) {
            $package = Self::where('name', $id)->firstOrFail();
        }

        return $package;
    }


    /**
     * Get the latest version of this package instance. Uses version_compare to sort all versions
     * @return version Returns the latest version
     */
    public function getLatestVersion()
    {
        $versions = Version::where('package_id', $this->id)->get();

        $latest_version = $versions->sort(function($a, $b) {
            return (version_compare($a->version, $b->version));
        })->values()->reverse()->first();

        return $latest_version;
    }


    /**
     * Get all versions of this package instance
     * @param integer $max  The amount of versions to get
     * @return version Collection of versions. Ordered by version with newest version first
     */
    public function getVersions($max = 10)
    {
        $versions = Version::where('package_id', $this->id)->get();

        $sorted_versions = $versions->sort(function($a, $b) {
            return (-version_compare($a->version, $b->version));
        });

        if ($max == 0) {
            $sorted_versions = $sorted_versions->values()->all();
        } else {
            $sorted_versions = $sorted_versions->values()->take($max)->all();
        }

        return $sorted_versions;
    }
}
