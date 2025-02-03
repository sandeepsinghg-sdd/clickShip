<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('shopify_apps', function (Blueprint $table) {
            $table->id();
            $table->string('shop'); // To store the Shopify shop domain
            $table->string('access_token'); // To store the access token
            $table->timestamps();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('shopify_apps');
    }
};
