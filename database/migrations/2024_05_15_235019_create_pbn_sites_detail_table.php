<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('pbn_sites_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pbn_site_id')->constrained('pbn_sites')->onDelete('cascade');
            $table->string('url');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('pbn_sites_details');
    }
};
