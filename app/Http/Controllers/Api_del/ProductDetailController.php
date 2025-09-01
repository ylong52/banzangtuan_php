<?php
namespace App\Http\Controllers\Api;

use App\Models\ProductDetail;
use Illuminate\Http\Request;
use App\Http\Controllers\ApiCommand;
use App\Services\AyqySignService;

class ProductDetailController extends ApiCommand
{
    // 列表输出
    public function index(Request $request,$id)
    {        
        // $id = 692;
        $product_id = $id;
        if (!$product_id) {
            return response()->json([
                'code' => 400,
                'status' => 'error',
                'message' => '商品id不能为空'   //换成中文
            ]);
        }
        $query = ProductDetail::with('product')->where('id', $product_id);
        // 可加筛选、模糊查询等
        $productDetail = $query->first();
        $data = $this->formatData($productDetail);
        return response()->json([
            'code' => 200,
            'status' => 'success',
            'data' => $data
        ]);
    }

    // 可重写的数据整理函数
    protected function formatData($data){
    
        // 默认直接返回原始数据
        // 可在此处对字段进行改名或替换
        //  "imgs": "[{\"img\":\"https:\\/\\/ayqy.kami99.cn\\/attached\\/images\\/10243\\/public\\/951fd01d903947e48216823926ae0ad0.png\"}]"

        $data['imgs'] = json_decode($data['imgs'], true);
        $data['imgs'] = array_map(function($item){
            return $item['img'];
        }, $data['imgs']);
        $data['cover'] = $data['imgs'][0];        
        // $data['salePrice'] = $data['money'];
     
        $data['platform_price'] = $data['product']['platform_price'];
     
        unset($data['price'],$data['money']);
        return $data;
    }

 

} 