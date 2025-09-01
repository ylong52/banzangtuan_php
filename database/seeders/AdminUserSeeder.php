<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Administrator;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // 检查是否已存在该用户
        $existingUser = Administrator::where('username', 'admin98')->first();
        
        if (!$existingUser) {
            Administrator::create([
                'username' => 'admin98',
                'password' => Hash::make('admin123'),
                'name' => '管理员',
                'avatar' => null,
            ]);
            
            $this->command->info('管理员用户 admin98 创建成功！');
        } else {
            // 更新密码
            $existingUser->update([
                'password' => Hash::make('admin123'),
            ]);
            
            $this->command->info('管理员用户 admin98 密码已更新！');
        }
    }
} 