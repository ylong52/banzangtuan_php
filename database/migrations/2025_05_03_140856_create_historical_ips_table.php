<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHistoricalIpsTable extends Migration
{
    public function up()
    {
        Schema::create('historical_ips', function (Blueprint $table) {
            $table->id();
            $table->string('ip');
            $table->timestamp('expired_at');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('historical_ips');
    }
}
