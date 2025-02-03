<?php

// database/migrations/xxxx_xx_xx_create_shipping_settings_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShippingSettingsTable extends Migration
{
    public function up()
    {
        Schema::create('shipping_settings', function (Blueprint $table) {
            $table->id();
            $table->string('shipping_name');
            $table->decimal('shipping_price', 8, 2);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('shipping_settings');
    }
}
