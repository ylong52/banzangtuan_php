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
use App\Models\Goods;
use App\Services\JdGoodsSevice;
use Illuminate\Http\Request;
/*
* 京东的商品
*/

class JdGoodsController extends AdminController
{
    protected $title = '京东商品管理';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Goods());

        $grid->column('id', __('ID'))->sortable();
        $grid->column('goodsname', __('商品名称')); 

                
        // 添加新增按钮，链接到查询页面
        $grid->tools(function ($tools) {
            $tools->append('<a href="' . admin_url('jdgoods/query') . '" class="btn btn-sm btn-success"><i class="fa fa-plus"></i> 新增商品</a>');
        });
        
        $grid->disableExport();
        $grid->disableBatchActions();
        $grid->disableRowSelector();
        $grid->disableCreateButton();
        
        $grid->actions(function ($actions) {
            $actions->disableDelete();
            $actions->disableEdit();
        });
        return $grid;
    }

    

    protected function form()
    {
        $form = new Form(new Goods());
        $form->textarea('goodsname', '商品名称')->rules('required|max:255');
        
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

    /**
     * 商品查询页面
     */
    public function goodsQueryPage()
    {
        // // 检查用户是否已登录
        // if (!Admin::user()) {
        //     return redirect('/admin/auth/login');
        // }
        
        return Admin::content(function ($content) {
            $content->header('商品查询');
            $content->description('查询京东商品信息');
            
            $content->body(view('admin.jd_goods.query'));
        });
    }

    /**
     * AJAX处理商品查询
     */
    public function queryGoods(Request $request)
    {
        try {
            $keyword = $request->input('keyword');
            if (empty($keyword)) {
                return response()->json(['status' => 'error', 'msg' => '请输入商品链接或关键词']);
            }

            $jdService = new JdGoodsSevice();
            $goodsResult = $jdService->goodsQuery($keyword);
           
            $goods = [];
            $imageList = $goodsResult['imageInfo']['imageList'];
            if (is_array($imageList)) {
                $goods['imagelist'] = json_encode($imageList);
            }
            $goods['goodsname'] = $goodsResult['skuName'];
            $goods['priceinfo'] = json_encode($goodsResult['priceInfo']);
            $goods['shopinfo'] = json_encode($goodsResult['shopInfo']);
            $goods['skutaglist'] = json_encode($goodsResult['skuTagList']);
            $goods['keyword'] = $goodsResult['keyword'];
            $goods['json'] = json_encode($goodsResult['priceInfo']);
            $goods['white_image'] = $goodsResult['imageInfo']['whiteImage'] ?? '';
            $goodsModel = new Goods();
            $goodsModel->fill($goods)->save();
            return response()->json(['status' => 'success', 'msg' => 'success','goodsResult'=>$goodsResult]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'msg' => $e->getMessage()]);
        }
    }

    /**
     * 保存商品信息和分享文案
     */
    public function save(Request $request)
    {
        try {
            $goodsInfo = $request->input('goodsInfo');
            $shareCopywriting = $request->input('shareCopywriting');
           
            if (empty($goodsInfo)) {
                return response()->json(['status' => 'error', 'msg' => '商品信息不能为空']);
            }

            // 准备商品数据
            $goods = [];
            if (isset($goodsInfo['imageInfo']['imageList']) && is_array($goodsInfo['imageInfo']['imageList'])) {
                $goods['imagelist'] = json_encode($goodsInfo['imageInfo']['imageList']);
            }
            $goods['goodsname'] = $goodsInfo['skuName'] ?? '';
            $goods['priceinfo'] = json_encode($goodsInfo['priceInfo'] ?? []);
            $goods['shopinfo'] = json_encode($goodsInfo['shopInfo'] ?? []);
            $goods['skutaglist'] = json_encode($goodsInfo['skuTagList'] ?? []);
            $goods['keyword'] = $goodsInfo['keyword'] ?? '';
            $goods['json'] = json_encode($goodsInfo ?? []);
            $goods['share_copywriting'] = $shareCopywriting ?? '';
            $goods['commission_info'] = json_encode($goodsInfo['commissionInfo'] ?? []);
            $goods['white_image'] = $goodsInfo['imageInfo']['whiteImage'] ?? '';
            // 检查是否已存在相同商品
            $existingGoods = Goods::where('keyword', $goods['keyword'])->first();
            
            if ($existingGoods) {
                // 更新现有商品
                $existingGoods->update($goods);
                $message = '商品信息更新成功';
            } else {
                // 创建新商品
                $goodsModel = new Goods();
                $goodsModel->fill($goods)->save();
                $message = '商品信息保存成功';
            }

            return response()->json([
                'status' => 'success', 
                'msg' => $message,
                'data' => $goods
            ]);
            
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'msg' => '保存失败：' . $e->getMessage()]);
        }
    }
}