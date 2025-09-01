<?php
namespace App\Http\Controllers\Api;

use App\Models\Products;
use Illuminate\Http\Request;
use App\Http\Controllers\ApiCommand;
use App\Services\AyqySignService;

class ProductsController extends ApiCommand
{
    // 列表输出
    public function index(Request $request)
    {         
        $query = Products::with('productDetail');
        // name 模糊查询
        if ($request->has('name') && $request->input('name') !== '') {
            $query->where('name', 'like', '%' . $request->input('name') . '%');
        }
       
        if ($request->has('category') && $request->input('category') != '') {
            $query->where('dirId1', $request->input('category'));
        } 

        if ($request->has('sort') && $request->input('sort') != '') {
            $sort = $request->input('sort');            
            $query->orderBy($sort,'asc');            
        }

        $query->where('deleted_at',null);
        $query->where('status', 1);
        // 分页
        $limit = $request->input('limit', 15);
        $products = $query->paginate($limit);
        
        // 格式化数据
        $formattedProducts = [];
        foreach ($products->items() as $product) {
            $item = $product->toArray();
            
            // // 添加销价
            // if (isset($product->productDetail) && $product->productDetail) {
            //     $item['salePrice'] = number_format($product->productDetail->money, 2, '.', '');
            // } else {
            //     $item['salePrice'] = number_format($product->money, 2, '.', '');
            // }
            
            // 移除敏感字段
            unset($item['price']);
            unset($item['money']);
            
            $formattedProducts[] = $item;
        }
        
        return response()->json([
            'code' => 200,
            'status' => 'success',
            'data' => $formattedProducts,
            'current_page' => $products->currentPage(),
            'last_page' => $products->lastPage(),
            'per_page' => $products->perPage(),
            'total' => $products->total(),
        ]);
    }
    
    // 可重写的数据整理函数 - 不再使用此方法
    protected function formatData($list)
    {
        //去掉几个字段，如：price，money
        
        return $list;
    }

 

} 