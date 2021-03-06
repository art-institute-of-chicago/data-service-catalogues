<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Sections extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::create('sections', function (Blueprint $table) {
            $table->bigInteger('id')->unsigned()->primary();
            $table->string('title')->nullable();
            $table->string('accession')->nullable()->index();
            $table->integer('citi_id')->nullable()->index();
            $table->integer('revision')->nullable();
            $table->integer('source_id')->nullable()->index();
            $table->integer('weight')->nullable()->index();
            $table->integer('publication_id')->nullable()->unsigned()->index();
            $table->foreign('publication_id')->references('id')->on('publications')->onDelete('cascade');
            $table->longText('content')->nullable();
            $table->timestamps();
        });

        // Because these are self-referential, the table must be created first
        Schema::table('sections', function (Blueprint $table) {
            $table->bigInteger('parent_id')->nullable()->unsigned()->index();
            $table->foreign('parent_id')->references('id')->on('sections')->onDelete('cascade');
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sections');
    }
}
