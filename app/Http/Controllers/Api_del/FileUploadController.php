<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\ApiCommand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class FileUploadController extends ApiCommand
{
    protected $noNeedLogin = ['*']; // 允许不需要登录

    public function upload(Request $request)
    {
dd("14 >>>>");    
        try {
            $request->validate([
                'file' => 'required|mimes:doc,docx,xls,xlsx,pdf,jpeg,png,jpg,gif|max:2048',
            ]);

            $channel = $request->input('channel');
            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $fileName = time() . '_' . $file->getClientOriginalName();
                // 检查目录是否存在，如果不存在则创建并设置权限
                $directory = storage_path('app/public/uploads');
                if (!is_dir($directory)) {
                    mkdir($directory, 0777, true);
                }
                $filePath = $file->storeAs('uploads', $fileName, 'public');

                DB::table('uploaded_files')->insert([
                    'file_name' => $fileName,
                    'file_path' => $filePath,
                    'file_type' => $file->getClientMimeType(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                // 获取域名
                $domain = request()->getSchemeAndHttpHost();

                if ($channel == 'wangeditor') {
                    return response()->json([
                        "errno" => 0,
                        "data" => [
                            "url" => $domain . Storage::url($filePath),
                            "alt" => $fileName,
                            "href" => $domain . Storage::url($filePath)
                        ]
                    ]);
                }

                return $this->success([
                    'file_name' => $fileName,
                    'file_path' => $domain . Storage::url($filePath)                     
                ]);
            }
            
            if ($channel == 'wangeditor') {
                return response()->json([
                    "errno" => 1,
                    "message" => "上传失败"
                ]);
            }
            return $this->error('文件上传失败', 401);
            
        } catch (\Exception $e) {
            \Log::error('文件上传错误: ' . $e->getMessage());
            return $this->error('文件上传失败: ' . $e->getMessage(), 500);
        }
    }
}