<?php

namespace App\Admin\Controllers;

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
class SurveyArgumentController extends AdminController
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
    // protected function form()
    // {
    //     $form = new Form(new Team());
        
    //     return $form;
    // }

    protected function form()
    {
        $form = new Form(new Team());
        
        $form->text('name', '团队名称')->rules('required', [
            'required' => '团队名称不能为空',
        ]);
        
        $form->radio('is_deleted', '状态')->options([
            0 => '正常',
            1 => '停用',
        ])->default(0);
        
        return $form;
    }


    public function index(\Encore\Admin\Layout\Content $content)
    {
        // 移除调试语句
        //  dd(__LINE__);
        $teamList = new Team();
        $data['teams'] = $teamList
            ->get()->map(function ($item) { 
            $item->teamCount = DB::table('users')->where('teams_id',$item->id)->count();
            $item->questionnairelist = DB::table('questionnaire')->where('teams_id',$item->id)
            ->where('is_deleted',0)
            ->orderBy('id','asc')
            ->get();
            return $item;
        });
        
        // 使用 content 对象加载视图
        return $content->view('admin.surveyargument.index',$data);
        
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
     * 员工折算比例设置页面
     */
    public function rpi_commission_rate(\Encore\Admin\Layout\Content $content)
    {
  
        // 获取当前配置值
        $config = \App\Models\GlobalConfig::where('key', 'rpi_commission_rate')->first();
        $currentValue = $config ? $config->value : '0';
        
        return $content->title('员工折算比例设置')
            ->view('admin.surveyargument.rpi_commission_rate', ['currentValue' => $currentValue]);
    }
    
    /**
     * 保存员工折算比例
     */
    public function save_rpi_commission_rate(\Illuminate\Http\Request $request)
    {
        $value = $request->input('rpi_commission_rate');
        
        // 验证输入
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'rpi_commission_rate' => 'required|numeric|min:0',
        ], [
            'rpi_commission_rate.required' => '折算比例不能为空',
            'rpi_commission_rate.numeric' => '折算比例必须是数字',
            'rpi_commission_rate.min' => '折算比例不能小于0',
        ]);
        
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }
        
        // 更新或创建配置
        \App\Models\GlobalConfig::updateOrCreate(
            ['key' => 'rpi_commission_rate'],
            [                
                'value' => $value, 
                'type' => 0,
                'itype' => 0
            ]
        );
        
        return response()->json(['status' => true, 'message' => '保存成功']);
    }

}
