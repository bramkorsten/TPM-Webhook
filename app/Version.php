<?php

namespace App;

use ZipArchive;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use FilesystemIterator;

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

    public $githubPackageName = '';

    public $downloadPackageName = '';

    protected $authenticationToken = '';

    public function __construct()
    {
        $this->tempPath = storage_path('temp' . DIRECTORY_SEPARATOR . 'packages');
    }


    public function createRelease(Package $package)
    {
        if ($this->released) {
            throw new \Exception("A release has already been created for this version");
        }

        $this->authenticationToken = $package->token;
        $this->releasePath = storage_path('packages' . DIRECTORY_SEPARATOR . $package->name . DIRECTORY_SEPARATOR);

        $packageName = $this->generateName($package->name);
        $packageLocation = $this->tempPath . DIRECTORY_SEPARATOR . $package->name . DIRECTORY_SEPARATOR . $packageName;

        if (!$this->downloadReleaseFromUrl($this->repo_zip_url, $packageLocation)) {
            throw new \Exception("There was an error while downloading the package from Github...", 1);
        }

        $finalPackageLocation = $this->assemblePackage($packageLocation);

        $this->download_url = $finalPackageLocation;
        $this->released = true;

        $this->save();

        return $finalPackageLocation;

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
     * Download a release zip from Github, using the parameters
     * @param  string $url         The CURL url for retreiving the file
     * @param  string $path        The path to save the file to
     * @param  array  $curlOptions Optional: Curloptions for downloading the file
     * @return mixed               Returns either the file location or false
     */
    protected function downloadReleaseFromUrl(string $url, string $path, array $curlOptions = array())
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
     * Unpack the downloaded archive, remove blacklisted files and assemble the final package for downloading.
     * @param  string $packageLocation The location of the zip archive from Github
     * @return string                  The location of the final downloadable archive
     */
    protected function assemblePackage(string $packageLocation)
    {
        $unpackDir = str_replace(".zip", "", $packageLocation);
        // $unpackDir = basename($packageLocation, ".zip");

        $this->createDirIfNeeded($unpackDir, "0655");

        $zip = new ZipArchive;
        if (!$zip->open($packageLocation)) {
            throw new \Exception("Error while opening zip archive", 1);
        }

        $zip->extractTo($unpackDir);
        $zip->close();

        $directories = glob($unpackDir . '/*' , GLOB_ONLYDIR);
        $rootPath = realpath($directories[0] . "/");

        $blackList = array(
          '/.gitignore',
          '/.gitattributes'
        );

        foreach ($blackList as $file) {
          if(is_file(\realpath($rootPath . $file))) {
            unlink(\realpath($rootPath . $file));
          }
        }

        $finalPackage = new ZipArchive();
        $finalPackageLocation = $this->releasePath . basename($packageLocation, ".zip") . '.zip';

        $this->createDirIfNeeded($finalPackageLocation);

        $finalPackage->open($finalPackageLocation, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($rootPath),
            RecursiveIteratorIterator::LEAVES_ONLY
        );
        foreach ($files as $name => $file)
        {
            // Skip directories (they will be added automatically)
            if (!$file->isDir())
            {
                // Get real and relative path for current file
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($rootPath) + 1);
                // Add current file to archive
                $finalPackage->addFile($filePath, $relativePath);
            }
        }
        $finalPackage->close();

        unlink($packageLocation);

        $this->rrmdir($unpackDir, false); // TODO: Fix root dir deletion error

        return $finalPackageLocation;
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


    /**
     * Recusively Remove a Directory and all it's files
     * @param  string $path             The path to the directory to remove.
     * @param  boolean $removeRootDir   Remove the root directory. Default: true
     * @return true
     */
    protected function rrmdir(string $path, bool $removeRootDir = true)
    {
        if ($path == '' || $path == NULL) {
            throw new \Exception("RecursiveRemoveDir cannot remove the root path! This would cause destruction", 1);
        }

        foreach( new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator( $path, FilesystemIterator::SKIP_DOTS | FilesystemIterator::UNIX_PATHS ),
            RecursiveIteratorIterator::CHILD_FIRST ) as $value ) {
                $value->isFile() ? unlink( $value ) : rmdir( $value );
        }

        if ($removeRootDir) {
            rmdir( $path );
        }

        return true;
    }
}
