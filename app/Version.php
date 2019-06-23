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

    public function __construct()
    {
        $this->tempPath = storage_path('temp/packages');
    }


    public function createRelease(Package $package)
    {
        if ($this->released) {
            throw new \Exception("A release has already been created for this version");
        }


    }

    protected function generateDownloadPath()
    {
        // code...
    }
}
