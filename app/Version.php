<?php

namespace App;

use App\Package;
use Illuminate\Database\Eloquent\Model;

class Version extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'package_versions';

    protected $tempPath = '';

    protected $releasePath = '';

    protected $authenticationToken = '';

    public function __construct()
    {
        $this->tempPath = storage_path('temp/packages');
    }


    public function createRelease(Package $package)
    {
        if ($this->released) {
            throw new \Exception("A release has already been created for this version");
        }

        $this->authenticationToken = $package->token;

        $packageName = $this->generateName($package->name);
        $packageLocation = $this->tempPath . DIRECTORY_SEPARATOR . $package->name . DIRECTORY_SEPARATOR . $packageName;

        if ($this->downloadPackageFromUrl($this->repo_zip_url, $packageLocation)) {
            return $packageLocation;
        } else {
            return false;
        }


    }


    /**
     * Generate a package name based on version and time info.
     * @param  string $name   The name of the package
     * @param  string $prefix Prefix to add to the file name
     * @return string         The generated file name
     */
    protected function generateName(string $name, string $prefix = "") {
        $now = date("Ymd-Gi");
        $generatedName = $name . "-" . $this->version . "-" . $now . ".zip";
        if ($prefix) {
            $generatedName = $prefix . "-" . $generatedName;
        }
        return $generatedName;
    }

    /**
     * Download A package file from Github, using the parameters
     * @param  string $url         The CURL url for retreiving the file
     * @param  string $path        The path to save the file to
     * @param  array  $curlOptions Optional: Curloptions for downloading the file
     * @return mixed               Returns either the file location or false
     */
    protected function downloadPackageFromUrl(string $url, string $path, array $curlOptions = array())
    {
        set_time_limit(0);

        $this->createDirIfNeeded($path);
        $fileOnDisk = fopen($path, 'w');

        $header = array();
        $header[] = 'Authorization: token ' . $this->authenticationToken;

        $options = array(
            CURLOPT_USERAGENT => "Theme Package Manager by Bram Korsten",
            CURLOPT_FILE    => $fileOnDisk,
            CURLOPT_TIMEOUT =>  28800,
            CURLOPT_URL     => $url,
            CURLOPT_FOLLOWLOCATION => 1,
            CURLOPT_HTTPHEADER => $header,
            CURLOPT_VERBOSE => true
        );

        echo($url);

        echo($options[CURLOPT_URL]);

        $ch = curl_init();
        curl_setopt_array($ch, $options + $curlOptions);
        $response = curl_exec($ch);

        if ($response === false) {
          throw new \Exception(curl_error($ch), curl_errno($ch));

        }
        curl_close($ch);

        \fclose($fileOnDisk);

        return true;
    }


    /**
     * Create a directory for downloaded file if it doesn't exist
     * @param  string $path        The path of the file, or the filename
     * @param  string $permissions Optional: permissions for the path. Default is 0755
     * @return boolean             Returns true if succeeded
     */
    protected function createDirIfNeeded(string $path, string $permissions = "0755")
    {
        $dirname = dirname($path);
        if (!is_dir($dirname)) {
            mkdir($dirname, $permissions, true);
        }

        return true;
    }
}
