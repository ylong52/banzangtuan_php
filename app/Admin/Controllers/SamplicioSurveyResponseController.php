<?php

namespace App\Admin\Controllers;

use App\Models\ProxyPool;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Grid\Filter;
use Encore\Admin\Facades\Admin;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Encore\Admin\Controllers\AuthController as BaseAuthController;
use App\Models\SamplicioSurveyResponse;
/*
*  
*/
class SamplicioSurveyResponseController extends AdminController
{
    protected $title = '最新消息';

    protected function grid()
    {
        $grid = new Grid(new ProxyPool());

    }

    public function index(\Encore\Admin\Layout\Content $content)
    {   
          // 使用 content 对象加载视图
          return $content->view('admin.sampliciosurveyresponse.index');
    }


    public function response_list(\Encore\Admin\Layout\Content $content)
    {
        // 检查是否为AJAX请求
        if (request()->ajax() || request()->input('ajax') == 1) {
            //survey_number mid
            // 获取每页显示数量，默认为15
            $pageSize = request()->input('pageSize', 15);
            $surveyNumber = request()->input('survey_number');
            $mid = request()->input('mid');
    
            $query = SamplicioSurveyResponse::select(['samplicio_survey_response.*','surveyrecord.user_id','surveyrecord.age','surveyrecord.gender',
                'surveyrecord.city','surveyrecord.country','surveyrecord.subdivision','surveyrecord.env1_score','surveyrecord.env2_score',  'samplicio_survey_response.created_at as response_end_time',
                'surveyrecord.created_at as send_time'       
            ])
                ->join('surveyrecord', 'surveyrecord.mid', '=', 'samplicio_survey_response.mid')
                ->with(['user','user.team', 'lucidResponseCode'])
                ->where(['surveyrecord.response_flag' => 1])
                ->where(['samplicio_survey_response.survey_status' => 1]);
    
            
            // 添加查询条件
            if (!empty($surveyNumber)) {
                $query->where('samplicio_survey_response.survey_number', 'like', '%' . $surveyNumber . '%');
            }
    
            if (!empty($mid)) {
                $query->where('samplicio_survey_response.mid', 'like', '%' . $mid . '%');
            }    
            $surveys = $query->orderBy('created_at', 'desc')->paginate($pageSize);
    
            // 处理分页数据中的每个项目
            $surveys->getCollection()->map(function ($item) {
                // 确保 survey_info 虚拟字段被加载
                // 计算时间差并转换为小时和分钟格式
                $responseTime = strtotime($item->response_end_time);
                $sendTime = strtotime($item->send_time);
                $timeDiff = $responseTime - $sendTime;
                $hours = floor($timeDiff / 3600);
                $minutes = floor(($timeDiff % 3600) / 60);
                if ($hours > 0) {
                    $item->time_difference = $hours . '小时' . $minutes . '分钟';
                } else {
                    $item->time_difference = $minutes . '分钟';
                }
               //取出会员信息
                $user = $item->user;
                $item->user_name = $user->name;
                $item->user_team_name = $user->team->name;
                return $item;
            });
            
           
            return response()->json([
                'success' => true,
                'data' => $surveys->items(),
                'current_page' => $surveys->currentPage(),
                'last_page' => $surveys->lastPage(),
                'total' => $surveys->total(),
            ]);
        }
        
        // 非AJAX请求，返回空视图（仅用于初始页面加载）
        return $content->view('admin.sampliciosurveyresponse.index');
    }

    public function questionnaire_analysis_list()
    {
        // 获取每页显示数量，默认为15
        $pageSize = request()->input('pageSize', 15);
        
        // 获取查询条件
        $surveyNumber = request()->input('survey_number');
        $mid = request()->input('mid');
        
        // 使用查询构建器替代原始SQL
        $query = DB::table('questionnaire_analysis')
            ->select([
                'questionnaire_analysis.user_id',
                'questionnaire_analysis.survey_number',
                'questionnaire_analysis.created_at',
                'questionnaire_analysis.amount',
                'samplicio_survey_response.rpi',
                'samplicio_survey_response.rpi_commission_amount',
                'samplicio_survey_response.mid',
                'users.name as user_name'
            ])
            ->join('samplicio_survey_response', 
                DB::raw('BINARY questionnaire_analysis.survey_number COLLATE utf8mb4_unicode_ci'), 
                '=', 
                DB::raw('BINARY samplicio_survey_response.survey_number COLLATE utf8mb4_unicode_ci')
            )
            ->join('users', 'questionnaire_analysis.user_id', '=', 'users.id')
            ->where('samplicio_survey_response.survey_status', 1)
            ->where('samplicio_survey_response.is_delete', 0);
        
        // 添加查询条件
        if (!empty($surveyNumber)) {
            $query->where('questionnaire_analysis.survey_number', 'like', '%' . $surveyNumber . '%');
        }
        
        if (!empty($mid)) {
            $query->where('samplicio_survey_response.mid', 'like', '%' . $mid . '%');
        }
            
        // 添加排序（按创建时间倒序）
        $query->orderBy('questionnaire_analysis.created_at', 'desc');        
        // 执行分页查询
        $results = $query->paginate($pageSize);
        
        // 检查是否为AJAX请求
        if (request()->ajax() || request()->input('ajax') == 1) {
            return response()->json([
                'success' => true,
                'data' => $results->items(),
                'current_page' => $results->currentPage(),
                'last_page' => $results->lastPage(),
                'analysis_total' => $results->total(),
            ]);
        }
        
        // 准备视图数据
        $data = [
            'analysis_list' => $results,
            'analysis_total' => $results->total()
        ];
        
        // 返回视图
        return $data;
    }


    public function survey_response_area_info(\Encore\Admin\Layout\Content $content){
        
        //如果是get 
        if (request()->method() == 'GET') {
            return $content->view('admin.sampliciosurveyresponse.survey_response_area_info');
          
        }
 
        // 获取每页显示数量，默认为15
        $pageSize = request()->input('pageSize', 15);        
        // 获取查询条件（可选）
        $surveyNumber = request()->input('survey_number');
        $mid = request()->input('mid');
        
        // 构建查询
        $query = DB::table('samplicio_survey_response')
            ->select([
                'users.name',
                'samplicio_survey_response.survey_number',
                'surveyrecord.city',
                'surveyrecord.country',
                'surveyrecord.subdivision',
                'surveyrecord.age',
                'samplicio_survey_response.returndata'
            ])
            ->join('surveyrecord', 'samplicio_survey_response.mid', '=', 'surveyrecord.mid')
            ->join('users', 'surveyrecord.user_id', '=', 'users.id')
            ->where('samplicio_survey_response.is_delete', 0)
            ->where('samplicio_survey_response.survey_status', 1)
            ->where('surveyrecord.response_flag', 1);
        
        // 添加可选的查询条件
        if (!empty($surveyNumber)) {
            $query->where('samplicio_survey_response.survey_number', 'like', '%' . $surveyNumber . '%');
        }
        
        if (!empty($mid)) {
            $query->where('samplicio_survey_response.mid', 'like', '%' . $mid . '%');
        }
        // 添加排序
        $query->orderBy('samplicio_survey_response.created_at', 'desc');        
        // 执行分页查询
        $results = $query->paginate($pageSize);
        // $keys = [43,113,48741];
        $results->getCollection()->transform(function ($item) {
            // 处理返回数据
            $returnData = json_decode($item->returndata, true);
           // 加载个人数据配置文件
            $personal = include app_path('personal_data.php');
            $newData = []; 
            // 处理returnData中的数据，将数字代码转换为对应的文本
            if (is_array($returnData)) {
                foreach ($returnData as $key => $value) {
                    // 检查是否存在对应的映射
                    if (isset($personal[$key]) && isset($personal[$key][$value])) {
                        $item->$key = $personal[$key][$value];
                    }  
                }
            }
            // $item->personal = $newData;
            unset($item->returndata);
            return $item;
        });
        // dd($results->items());
        // 返回JSON格式数据
        return response()->json([
            'success' => true,
            'data' => $results->items(),
            'current_page' => $results->currentPage(),
            'last_page' => $results->lastPage(),
            'total' => $results->total(),
            'per_page' => $results->perPage(),
            'from' => $results->firstItem(),
            'to' => $results->lastItem()
        ]);
    }

}