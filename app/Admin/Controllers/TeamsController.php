<?php

namespace App\Admin\Controllers;

use App\Models\Questionnaire;
use App\Models\Team;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Grid\Filter;
use Encore\Admin\Facades\Admin;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Encore\Admin\Controllers\AuthController as BaseAuthController;
/*
* 系统参数管理  
*/
class TeamsController extends AdminController
{
     
   
    public function toggleDelete(\Illuminate\Http\Request $request)
    {
        $id = $request->input('id');
        $currentStatus = $request->input('status');        
        try {
            $team = Team::findOrFail($id);
            // 如果当前状态是1(停用)，则设置为0(正常)，否则设置为1(停用)
            $team->is_deleted = $currentStatus == 1 ? 0 : 1;
            $team->save();
            
            return response()->json([
                'status' => true,
                'message' => $team->is_deleted == 1 ? '团队状态已更新为停用' : '团队状态已更新为正常'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ]);
        }

    }

    

}
