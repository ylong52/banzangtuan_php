<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQuestionnaireAnalysisTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('questionnaire_analysis', function (Blueprint $table) {
            // 表的自增主键 ID
            $table->id();
            // 用户 ID，关联到 users 表的主键
            $table->unsignedBigInteger('user_id')->nullable();
            // $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            // 国家，字符串类型
            $table->string('country')->nullable();
            // 金额，假设为十进制数字类型，可根据金额范围调整精度
            $table->decimal('amount', 10, 2)->nullable();
            // 调查主题，字符串类型
            $table->string('survey_subject')->nullable();
            // 创建人，字符串类型
            $table->string('creator')->nullable();
            // 首次完成时间，时间戳类型
            $table->timestamp('first_completion_time')->nullable();
            // 变动历史，文本类型，用于存储JSON格式或文本格式的变动记录
            $table->integer('questionnaire_analysis_change_history_id', false, true)->length(11)->nullable();
            // Laravel默认的创建时间和更新时间字段
            $table->timestamps();
        });

        // 添加表注释
        DB::statement("ALTER TABLE questionnaire_analysis COMMENT = '问卷分析相关数据表'");
        // 添加字段注释
        DB::statement("ALTER TABLE questionnaire_analysis MODIFY COLUMN id bigint(20) unsigned AUTO_INCREMENT COMMENT '表的自增主键 ID'");
        DB::statement("ALTER TABLE questionnaire_analysis MODIFY COLUMN user_id bigint(20) unsigned NULL COMMENT '用户 ID，关联到 users 表的主键'");
        DB::statement("ALTER TABLE questionnaire_analysis MODIFY COLUMN country varchar(255) NULL COMMENT '国家，字符串类型'");
        DB::statement("ALTER TABLE questionnaire_analysis MODIFY COLUMN amount decimal(10,2) NULL COMMENT '金额，假设为十进制数字类型，可根据金额范围调整精度'");
        DB::statement("ALTER TABLE questionnaire_analysis MODIFY COLUMN survey_subject varchar(255) NULL COMMENT '调查主题，字符串类型'");
        DB::statement("ALTER TABLE questionnaire_analysis MODIFY COLUMN creator varchar(255) NULL COMMENT '创建人，字符串类型'");
        DB::statement("ALTER TABLE questionnaire_analysis MODIFY COLUMN first_completion_time timestamp NULL COMMENT '首次完成时间，时间戳类型'");
        DB::statement("ALTER TABLE questionnaire_analysis MODIFY COLUMN questionnaire_analysis_change_history_id int(11) NULL COMMENT '变动历史，整数类型，用于存储相关变动记录的标识'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('questionnaire_analysis');
    }
}
