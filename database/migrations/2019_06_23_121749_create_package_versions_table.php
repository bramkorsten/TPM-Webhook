<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePackageVersionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('package_versions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('package_id');
            $table->string('version');
            $table->string('release_type');
            $table->boolean('is_prerelease');
            $table->string('download_url');
            $table->string('changelog_url');
            $table->string('release_author');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('package_versions');
    }
}
