<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // 首先删除不需要的字段
            $table->dropColumn([
                'owner',
                'sub_side_rate',
                'subsidy_rate',
                'final_rate',
                'actual_cos_price',
                'actual_fee',
                'request_time',
                'order_json',
                'express_status',
            ]);
            
            // 修改现有字段的类型和约束
            $table->decimal('price', 10, 2)->default(0.00)->change();
            $table->decimal('total_price', 10, 2)->default(0.00)->change();
            $table->integer('sku_num')->default(0)->change();
            $table->integer('valid_code')->default(0)->change();
            
            // 确保user_id字段存在且可为空
            if (!Schema::hasColumn('orders', 'user_id')) {
                $table->integer('user_id')->nullable()->comment('用户ID');
            }
            
            // 确保sub_union_id字段存在
            if (!Schema::hasColumn('orders', 'sub_union_id')) {
                $table->string('sub_union_id', 80)->comment('子渠道标识');
            }
            
            // 修改字段注释
            $table->string('id', 50)->comment('唯一标识')->change();
            $table->string('sku_name', 255)->comment('标题')->change();
            $table->string('order_id', 30)->comment('订单号')->change();
            $table->string('sku_id', 30)->comment('SKU ID')->change();
            $table->string('image_url', 255)->comment('SKU主图链接')->change();
            $table->string('shop_name', 255)->comment('店铺名称')->change();
            $table->decimal('commission_rate', 10, 2)->nullable()->default(0.00)->comment('佣金比例(%)')->change();
            $table->decimal('estimate_cos_price', 15, 2)->nullable()->default(0.00)->comment('预估计佣金额')->change();
            $table->decimal('estimate_fee', 15, 2)->nullable()->default(0.00)->comment('推客的预估佣金')->change();
            $table->dateTime('order_time')->comment('下单时间')->change();
            $table->dateTime('modify_time')->nullable()->comment('更新时间')->change();
            $table->dateTime('finish_time')->nullable()->comment('完成时间')->change();
            $table->dateTime('updated_at')->comment('修改时间')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // 恢复删除的字段
            $table->string('owner', 255)->nullable()->comment('所有者');
            $table->decimal('sub_side_rate', 10, 2)->nullable()->default(0.00)->comment('分成比例(%)');
            $table->decimal('subsidy_rate', 10, 2)->nullable()->default(0.00)->comment('补贴比例(%)');
            $table->decimal('final_rate', 10, 2)->nullable()->default(0.00)->comment('最终分佣比例(%)');
            $table->decimal('actual_cos_price', 15, 2)->nullable()->default(0.00)->comment('实际计算佣金的金额');
            $table->decimal('actual_fee', 15, 2)->nullable()->default(0.00)->comment('推客分得的实际佣金');
            $table->dateTime('request_time')->nullable()->comment('请求时间');
            $table->longText('order_json')->nullable()->comment('订单JSON数据');
            $table->integer('express_status')->nullable()->default(0)->comment('快递状态');
            
            // 恢复字段类型
            $table->decimal('price', 15, 2)->default(0.00)->change();
            $table->decimal('total_price', 15, 2)->default(0.00)->change();
            $table->integer('sku_num')->default(0)->change();
            $table->integer('valid_code')->default(0)->change();
        });
    }
};
