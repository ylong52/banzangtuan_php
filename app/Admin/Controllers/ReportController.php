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
use App\Models\SamplicioSurveyResponse;
use App\Models\UsedIpRecords;
use Encore\Admin\Controllers\AuthController as BaseAuthController;
/*
* 系统参数管理  
*/
class ReportController extends AdminController
{
    protected $title = '报告列表';
    
    protected function grid()
    {
        $grid = new Grid(new SamplicioSurveyResponse());

        $grid->model()->select([
            'samplicio_survey_response.*',
            'surveyrecord.city',
            'surveyrecord.ip_address',
            'surveyrecord.country',
            'surveyrecord.subdivision',
            'surveyrecord.env1_score',
            'surveyrecord.env2_score',
            'samplicio_survey_response.created_at as response_end_time',
            'surveyrecord.created_at as send_time',
            'users.name as username',
            'teams.name as teamname',
        ])
        ->join('surveyrecord', 'surveyrecord.mid', '=', 'samplicio_survey_response.mid')
        ->join('users','users.id', '=','surveyrecord.user_id')
        ->join('teams','teams.id', '=','users.teams_id')
        ->where(['surveyrecord.response_flag' => 1])
        ->where(['samplicio_survey_response.survey_status' => 1])
        ->orderBy('surveyrecord.created_at', 'desc');

        $grid->column('id', 'ID')->sortable();
        $grid->column('survey_number', '问卷ID');
        $grid->column('pid', 'PID');
        $grid->column('mid', 'MID');
        $grid->column('teamname', '团队名称');
        $grid->column('username', '用户名');
        $grid->column('country', '国家');        
        $grid->column('env1_score', '环境评分1');
        $grid->column('env2_score', '环境评分2');
        $grid->column('rpi_commission_amount','员工金额');
        $grid->column('rpi','RPI金额');
        $grid->column('response_end_time', '完成时间');
        $grid->column('ip_address', '发送的IP');
        $grid->column('complete_ip', '完成IP')->display(function () {
            $mid = $this->getAttribute('mid');
            $complete_ip_address = DB::table('used_ip_records')->where(['mid'=>$mid,'flow_position'=>2])->value('ip_address');
            return $complete_ip_address?$complete_ip_address:'-';
        });
        $grid->column('refund_amount', '退款金额')->display(function () {
            $list = DB::table('refund')->where(['mid'=>$this->getAttribute('mid')])->get();
            if ($list) {
                $arr = [];
                foreach ($list as $key => $value) {
                    if ($value->rpi >0 ) {
                        $arr[] = '回正金额:'.$value->rpi;
                    }elseif ($value->rpi <0 ) {
                        $arr[] = '退款金额:'.$value->rpi;
                    }                    
                }
                return implode('||', $arr);
            }
        });
        $grid->column('time_difference', '耗时')->display(function () {
            $responseTime = strtotime($this->getAttribute('response_end_time'));
            $sendTime = strtotime($this->getAttribute('send_time'));
            $timeDiff = $responseTime - $sendTime;
            $hours = floor($timeDiff / 3600);
            $minutes = floor(($timeDiff % 3600) / 60);
            return $hours > 0 ? "{$hours}小时{$minutes}分钟" : "{$minutes}分钟";
        });
        $grid->filter(function($filter){
            $filter->between('surveyrecord.created_at', '报表时间')->datetime();
        });
        $grid->disableCreateButton();
        $grid->disableActions();
        return $grid;
    }


}