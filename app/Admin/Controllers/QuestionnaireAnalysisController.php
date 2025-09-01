<?php

namespace App\Admin\Controllers;

use App\Models\QuestionnaireAnalysis;
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
class QuestionnaireAnalysisController extends AdminController
{
    protected $title = '问卷分析';

    protected function grid()
    {
        $grid = new Grid(new QuestionnaireAnalysis());
        // 添加status = 1的过滤条件
        $grid->model()->where('status', 1);

        $grid->column('id', __('ID'))->sortable();
        $grid->column('survey_number','问卷编号');
        $grid->column('survey_subject','调查主题')->display(function ($value) {
            $newValue ='';
            if ($value) {
                $newValue =  $value;
            }
            if ($this->like_surveyId) {
                $newValue .=   "||". $this->like_surveyId;
            }
            return $newValue?$newValue:'-';
        });
        
        $grid->column('created_at','时间')->display(function ($value) {
            return $value ? \Carbon\Carbon::parse($value)->format('Y-m-d H:i:s') : '-';
        });

        // 添加日期范围过滤器
        $grid->filter(function($filter){
            // 移除默认的id过滤器
            $filter->disableIdFilter();            
            // 添加问卷编号过滤器
            $filter->like('survey_number', '问卷编号');
            // 添加调查主题过滤器
            $filter->like('survey_subject', '调查主题');                 
        });

        // 只保留删除操作，禁用其他操作
        $grid->actions(function ($actions) {
            $actions->disableEdit();
            $actions->disableView();
        });

        return $grid;
    }



}