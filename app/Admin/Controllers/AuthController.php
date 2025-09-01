<?php

namespace App\Admin\Controllers;
use App\Models\Administrator;
use App\Models\Role;
use App\Models\UserActivation;
 
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Form\Footer;
use Encore\Admin\Form\Tools;
use Encore\Admin\Layout\Content;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

use Encore\Admin\Controllers\AuthController as BaseAuthController;

class AuthController extends BaseAuthController
{
    protected $loginView = 'admin.auth.login';
    
    public function getLogin ()
    {
        if ($this->guard()->check()) {
            return redirect($this->redirectPath());
        }
        $site = [];
        $site['sitename'] = '后台管理端';
        return view('admin.auth.login',$site );
    }

    public function postLogin(Request $request) 
    {
        // 验证验证码
        $captcha = $request->input('captcha');
       
        if (!$captcha || !CaptchaController::validateCaptcha($captcha)) {
            return back()->withErrors(['captcha' => '验证码错误'])->withInput();
        }
        
        // 验证post的参数
        $request->validate([
            'username' => function ($attribute, $value, $fail) {              
                $user = new Administrator();
                $userInfo = $user->where('username', $value)->first();
                if (empty($userInfo)) {                   
                    return $fail(__('auth.failed'));                    
                }             
            }
        ]);
        
        // 验证码已在验证过程中清除
        
        $result = parent::postLogin($request);
        
        // 如果登录成功，重定向到 home 页面
        if ($this->guard()->check()) {
            return redirect(admin_url('/'));
        }
        
        return $result;
    }
    
    /**
     * Get the post login redirect path.
     *
     * @return string
     */
    protected function redirectPath()
    {
        return admin_url('home');
    }

    /**
     * User setting page.
     *
     * @param Content $content
     *
     * @return Content
     */
    public function getSetting(Content $content)
    {
        $form = $this->settingForm();
        $form->tools(
            function (Form\Tools $tools) {
                $tools->disableList();
                $tools->disableDelete();
                $tools->disableView();
            }
        );

        return $content
            ->title(trans('admin.user_setting'))
            ->body($form->edit(Admin::user()->id));
    }

    /**
     * Update user setting.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function putSetting()
    {
        return $this->settingForm()->update(Admin::user()->id);
    }

    /**
     * Model-form for user setting.
     *
     * @return Form
     */
    protected function settingForm()
    {
        $class = config('admin.database.users_model');

        $form = new Form(new $class());

        $form->display('username', trans('admin.username'));
        $form->text('name', trans('admin.name'))->rules('required');
        // 删除了头像上传字段 $form->image('avatar', trans('admin.avatar'));
        $form->password('password', trans('admin.password'))->rules('confirmed|required');
        $form->password('password_confirmation', trans('admin.password_confirmation'))->rules('required')
            ->default(function ($form) {
                return $form->model()->password;
            });

        $form->setAction(admin_url('auth/setting'));

        $form->ignore(['password_confirmation']);

        $form->saving(function (Form $form) {
            if ($form->password && $form->model()->password != $form->password) {
                $form->password = Hash::make($form->password);
            }
        });

        $form->saved(function () {
            admin_toastr(trans('admin.update_succeeded'));

            return redirect(admin_url('auth/setting'));
        });

        return $form;
    }
}
