<?php

namespace App\Admin\Controllers;

use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Grid\Filter;
use Encore\Admin\Facades\Admin;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Encore\Admin\Controllers\AuthController as BaseAuthController;
use App\Models\User;
/*
* 系统参数管理  
*/

class UserController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '用户管理';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {       
        $grid = new Grid(new User());
         // 关闭新增按钮
         $grid->disableCreateButton();
        // 添加团队下拉过滤
        $grid->filter(function ($filter) {});
        //软删除的也要显示出来
        $grid->model()->withTrashed();
     
        $grid->column('id', __('ID'))->sortable();
        $grid->column('username', __('姓名'));

        $grid->column('phone', __('手机号'));
        $grid->column('avatar', __('头像'))->image('', 50, 50);
        $grid->column('status', __('状态'))->display(function ($value) {
            return $value == 1 ? '有效' : '禁用';
        });
        
        $grid->column('deleted_at', __('删除时间'))->display(function ($value) {
            return $value ? '删除时间:' . date('Y-m-d H:i:s', strtotime($value)) : '正常';
        });
      
        $grid->column('created_at', __('创建时间'))->display(function ($value) {
            return $value ? date('Y-m-d H:i:s', strtotime($value)) : '';
        });
        $grid->column('updated_at', __('更新时间'))->display(function ($value) {
            return $value ? date('Y-m-d H:i:s', strtotime($value)) : '';
        });
        // 禁用删除按钮
        $grid->actions(function ($actions) {
            $actions->disableDelete();
        });
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
        $show = new Show(User::withTrashed()->findOrFail($id));

        $show->field('id', __('ID'));
        $show->field('username', __('姓名'));
        $show->field('phone', __('手机号'));
        $show->field('status', __('状态'))->as(function ($value) {
            return $value == 1 ? '有效' : '禁用';
        });
        $show->field('deleted_at', __('删除时间'))->display(function ($value) {
            return $value ? '删除时间:' . date('Y-m-d H:i:s', strtotime($value)) : '正常';
        });

        $show->field('created_at', __('创建时间'));
        $show->field('updated_at', __('更新时间'));

        return $show;
    }

    protected function form()
    {
        $form = new Form(new User());
        $form->text('username', '用户名')->rules('required|max:255');
        $form->radio('status', '状态')->options([1 => '有效', 0 => '禁用'])->default(1);
        $form->text('phone', '手机号')->rules('nullable|max:18');
     
        //将$form deleted_at 设为radio 有效 禁用
        $form->radio('deleted_at', '软删除')
            ->options([0 => '有效', 1 => '软删除'])
            ->default(0);

        // 保存前处理软删除
        $form->saving(function (Form $form) {
            if ($form->deleted_at == 1) {
                $form->deleted_at = now();
            } else {
                $form->deleted_at = null;
            }
        });
        return $form;
    }
}
