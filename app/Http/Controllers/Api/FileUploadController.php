<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\ApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class FileUploadController extends ApiController
{
     
    public function upload(Request $request)
    {
 
        // 检查并修复代码
        $validator = Validator::make($request->all(), [
            'file' => 'required|mimes:doc,docx,xls,xlsx,pdf,jpeg,png,jpg,gif|max:2048',
        ], [
            'file.required' => '文件不能为空',
            'file.mimes' => '文件类型不支持，仅支持doc, docx, xls, xlsx, pdf, jpeg, png, jpg, gif格式',
            'file.max' => '文件大小不能超过2MB',
        ]);

        if ($validator->fails()) {
            // 只取第一个错误信息
            $firstError = collect($validator->errors()->all())->first();
            return response()->json([
                'code' => 422,
                'msg' => $firstError
            ], 422);
        }


        // try {
 
 
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
                return response()->json([
                    'status' => 'success',
                    'msg' => '文件上传成功',
                    'file_name' => $fileName,
                    'file_path' => $domain . Storage::url($filePath)                     
                ]);
            }
                       
            throw new \ErrorException('文件上传失败',401);  
        // } catch (\Exception $e) {
        //     return response()->json(['status' => 'error','msg' => $e->getMessage()],500);
          
        // }
         
    }
}