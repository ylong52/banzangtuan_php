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
/*
* 系统参数管理  
*/
class ProxyPoolController extends AdminController
{
    protected $title = '代理管理';

    protected function grid()
    {
        $grid = new Grid(new ProxyPool());
        $grid->column('id', __('ID'))->sortable();
        $grid->column('proxy_name','代理名称');
        $grid->column('host','HOST');
        $grid->column('port','端口');
        $grid->column('account','账户');
        $grid->column('password','密码');
        $grid->column('type','TYPE');
        $grid->column('proxy_soft','PROXY_SOFT');
        $grid->column('status','状态')->display(function($status) {
            return $status == 1 ? '有效' : '禁用';
        });

        return $grid;
    }

    protected function detail($id)
    {
        $show = new Show(ProxyPool::findOrFail($id));
        $show->field('id', __('ID'));
        $show->field('proxy_name','代理名称');
        $show->field('host','HOST');
        $show->field('port','端口');
        $show->field('account','账户');
        $show->field('password','密码');
        $show->field('type','TYPE');
        $show->field('proxy_soft','PROXY_SOFT');
        $show->field('status','状态')->as(function($status) {
            return $status == 1 ? '有效' : '禁用';
        });
        return $show;
    }

    protected function form()
    {
        $form = new Form(new ProxyPool());
        
        $form->text('proxy_name', '代理名称')->rules('required');
        $form->text('host', 'HOST')->rules('required');
        $form->number('port', '端口')->rules('required|numeric|min:1|max:65535');
        $form->text('account', '账户')->rules('required');
        $form->text('password', '密码')->rules('required');
        $form->text('type', 'TYPE')->rules('required');
        $form->text('proxy_soft', 'PROXY_SOFT')->rules('required');
        $form->switch('status', '状态')->default(1);
        
        return $form;
    }

}