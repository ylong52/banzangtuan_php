<?php

namespace App\Admin\Controllers;

use App\Models\SurveyRecord;
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
class IpsManageController extends AdminController
{
    protected $title = 'IP评分管理';

    protected function grid()
    {
        $grid = new Grid(new SurveyRecord());
        $grid->column('id', __('ID'))->sortable();
        $grid->column('ip_address','IP地址');
        $grid->column('city','城市');
        $grid->column('country','国家');
        $grid->column('subdivision','州');
        $grid->column('env1_score','检测环境1');
        $grid->column('env2_score','检测环境2');
        $grid->column('created_at','时间')->display(function ($value) {
            return $value ? \Carbon\Carbon::parse($value)->format('Y-m-d H:i:s') : '-';
        });

        // 添加日期范围过滤器
        $grid->filter(function($filter){
            // 移除默认的id过滤器
            $filter->disableIdFilter();            
            // 添加日期范围过滤器
            $filter->between('created_at', '创建时间')->date();            
        });

         // 禁用编辑和删除，保留查看
         // 完全禁用操作列
         $grid->disableActions();


        return $grid;
    }



}