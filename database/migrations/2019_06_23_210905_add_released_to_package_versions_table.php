<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddReleasedToPackageVersionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('package_versions', function (Blueprint $table) {
            $table->string('name')->after('package_id')->nullable();
            $table->boolean('released')->after('version')->default(false);
            $table->renameColumn('download_url', 'repo_zip_url')->change();

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('package_versions', function (Blueprint $table) {
            $table->renameColumn('repo_zip_url', 'download_url');
            $table->dropColumn(['released', 'name']);
        });
    }
}
