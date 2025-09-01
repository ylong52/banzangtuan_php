<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGlobalConfigTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('global_config', function (Blueprint $table) {
            $table->id()->comment('ID');
            $table->string('name', 191)->default('')->comment('名称');
            $table->string('description', 191)->default('')->comment('描述');
            $table->string('key', 100)->default('')->comment('键');
            $table->text('value')->comment('值');
            $table->tinyInteger('itype')->default(1)->comment('输入类型:1-input,2-textarea,3-markdown');
            $table->tinyInteger('status')->default(1)->comment('状态:1-启用,0-禁用');
            $table->timestamps();
            $table->softDeletes();
            
            $table->comment('配置表');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('global_config');
    }
}
