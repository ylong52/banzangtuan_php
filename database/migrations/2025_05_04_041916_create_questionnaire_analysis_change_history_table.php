<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQuestionnaireAnalysisChangeHistoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('questionnaire_analysis_change_history', function (Blueprint $table) {
            $table->id();
            // 用户 ID，关联到 users 表的主键
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamp('time')->nullable(); // 时间字段，允许为空
            $table->longText('changed_content'); // 改成的内容，类型为 long text
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
        Schema::dropIfExists('questionnaire_analysis_change_history');
    }
}
