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
class QuestionnaireController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '调查参数管理';

    /**

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Team());
        $grid->column('id', __('ID'))->sortable();
        $grid->column('created_at', __('Created at'));
        $grid->column('updated_at', __('Updated at'));

        return $grid;
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(Team::findOrFail($id));
        
        $show->field('id', __('ID'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));
        
        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Team());
        
        return $form;
    }

    public function index(\Encore\Admin\Layout\Content $content)
    {
        
        //  dd(__LINE__);
        
        
        // 或者使用默认的 grid 视图
        // return $content->title($this->title)
        //     ->description($this->description['index'] ?? trans('admin.list'))
        //     ->body($this->grid());
    }


    /**
     * Show interface.
     *
     * @param mixed $id
     * @return Content
     */
    public function show($id, \Encore\Admin\Layout\Content $content)
    {
        return $this->detail($id);
    }

    /**
     * 新增或修改问卷数据
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store()
    {
        // 获取请求数据        
        $request = request();
        $data = $request->all();
        // 验证请求数据
        $request->validate([
            'teams_id' => 'required|integer',
            'title' => 'required|string|max:255',
            'rev' => 'required|numeric|gt:0',
            'min_amount' => 'required|numeric|gt:0',
            'questionnaire_id' => 'nullable|integer'
        ]);
        
        // 获取请求数据
        $data = $request->only(['teams_id', 'title', 'rev', 'min_amount']);
        $questionnaireId = $request->input('questionnaire_id', 0);                        
        try {
            // 判断是新增还是修改
            if ($questionnaireId > 0) {
                // 修改现有记录
                $questionnaire = \App\Models\Questionnaire::findOrFail($questionnaireId);
                $questionnaire->update($data);                
                return response()->json([
                    'status' => 'success',
                    'message' => '问卷数据更新成功' 
                ], 200);
            } else {
                // 新增记录
                $exists = \App\Models\Questionnaire::where([
                    ['teams_id', '=', $data['teams_id']],
                    ['title', '=', $data['title']],
                    ['is_deleted', '=', 0] // 只检查未删除的记录
                ])->exists();
                
                if ($exists) {
                    return response()->json([
                        'status' => 'error',
                        'message' => '该团队下已存在相同标题的问卷'
                    ], 422);
                }
                $questionnaire = \App\Models\Questionnaire::create($data);
                return response()->json([
                    'status' => 'success',
                    'message' => '问卷数据更新成功' 
                ], 200);
            }
        }catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => '问卷数据更新失败',
            ],500);
        
        }
    }
    
    /**
     * 切换问卷的显示状态
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleShow()
    {
        $request = request();
        $id = $request->input('id');
        
        try {
            $questionnaire = \App\Models\Questionnaire::findOrFail($id);
            // 切换显示状态
            $questionnaire->is_show = $questionnaire->is_show == 1 ? 0 : 1;
            $questionnaire->save();
            
            return response()->json([
                'status' => true,
                'message' => '状态已更新',
                'is_show' => $questionnaire->is_show
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => '更新失败: ' . $e->getMessage()
            ], 500);
        }
    }

    public function deleteQuestionnaire()
    {
        $request = request();
        $id = $request->input('id');
        
        try {
            $questionnaire = \App\Models\Questionnaire::findOrFail($id);
            // 设置删除标记
            $questionnaire->is_deleted = 1;
            $questionnaire->save();
            
            return response()->json([
                'status' => true,
                'message' => '问卷已删除'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => '删除失败: ' . $e->getMessage()
            ], 500);
        }
    }

}
