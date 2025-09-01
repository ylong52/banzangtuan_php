<?php
namespace App\Http\Controllers\Api;

use App\Models\ProductCategory;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\ApiCommand;
use App\Services\AyqySignService;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx\Rels;
use Illuminate\Support\Facades\Log;

class ProductCategoryController extends ApiCommand
{
    
    public function index(Request $request) {
        // 获取请求参数，设置默认值
        $category = $request->input('category', '');
        $category_id = $request->input('category_id');
        $parentLimit = $request->input('parent_limit', 5); // 主类型数量变量
        $childLimit = $request->input('child_limit',9);   // 子类型数量变量

        // 构建顶级分类查询
        $query = ProductCategory::where('parent_id', 0)
            ->where('status', 1)
            ->where('level', 1)
            ->orderBy('sort', 'asc');

        // 添加category字段模糊查询
        if (!empty($category)) {
            $query->where('category', 'like', '%' . $category . '%');
        }
        if (!empty($category_id)) {
            $query->where('id', $category_id);
        }

        // 获取顶级分类列表并限制数量
        $parentCategories = $query->limit($parentLimit)->get();

        // 为每个顶级分类获取子分类
        $result = $parentCategories->map(function ($parent) use ($childLimit) {
            // 获取当前顶级分类的子分类
            $children = ProductCategory::where('parent_id', $parent->id)
                ->where('status', 1)
                ->where('level', 2)
                ->orderBy('sort', 'asc')
                ->limit($childLimit)
                ->get();

            // 将子分类添加到顶级分类对象中
            $parent->children = $children;
            return $parent;
        });

        return response()->json([
            'code' => 200,
            'status' => 'success',
            'data' => $result
        ]);

    }

    

    // 列表输出
    public function getLevelOne(Request $request)
    {
        $query = ProductCategory::query();

        // 加入 category 的模糊查询
        if ($request->has('category') && $request->input('category') !== '') {
            $category = $request->input('category');
            $query->where('category', 'like', '%' . $category . '%');
        }
        $query->where('status', 1);
        // 不分页，直接获取全部数据
        $list = $query->get();
        // 格式化数据
        $data = $this->formatData($list);

        return response()->json([
            'code' => 200,
            'status' => 'success',
            'data' => $data
        ]);
    }

    // 可重写的数据整理函数
    protected function formatData($list)
    {
        // 默认直接返回原始数据
        // 可在此处对字段进行改名或替换
        return $list;
    }

    public function level2(Request $request)
    {
        
        $type = $request->input('type',961);
        $list = ProductCategory::where('parent_id', $type)
        ->where('status', 1)
        ->where('level', 2)
        ->orderBy('sort', 'asc')
        ->get();
        return response()->json([  'code' => 200,'status' => 'success','data' => $list]);
    }

    public function getFromAyqyApi()
    {
        $domain =  env('AYQY_KAMI99_DOMAIN');
        $url = $domain . "/api/v2/getDirs";
         
        // 初始化 Guzzle Client，设置默认配置
        $signService = new AyqySignService();
        $sign = $signService->generateSign();

      
        $response = \Illuminate\Support\Facades\Http::asForm()             
            ->withOptions([
                'verify' => false,
            ])
            ->post($url, [
                'userNo' => env('AYQY_KAMI99_USERNO'),
                'sign' => $sign,
            ]);
       
        $data = $response->json();
        $this->storeAyqyApi($data['data']);
        return response()->json(['msg' => '保存成功', 'count' => count($data['data'])]);


    }

    protected function storeAyqyApi($data)
    {
 
        $saveList = [];
        $sort1 = 1;
        foreach ($data as $top) {
            // 顶级分类
            $saveList[] = [
                'id'        => $top['id'],
                'category'  => $top['name'],
                'img'       => $top['img'] ?? '',
                'brands'    => json_encode($top['brands'] ?? []),
                'parent_id' => 0,
                'level'     => 1,
                'sort'      => $sort1++,
                'status'    => 1,
            ];
            // 子分类
            if (!empty($top['children'])) {
                $sort2 = 1;
                foreach ($top['children'] as $child) {
                    $saveList[] = [
                        'id'        => $child['id'],
                        'category'  => $child['name'],
                        'img'       => $child['img'] ?? '',
                        'brands'    => json_encode($child['brands'] ?? []),
                        'parent_id' => $top['id'],
                        'level'     => 2,
                        'sort'      => $sort2++,
                        'status'    => 1,
                    ];
                    // 三级分类
                    if (!empty($child['children'])) {
                        $sort3 = 1;
                        foreach ($child['children'] as $child3) {
                            $saveList[] = [
                                'id'        => $child3['id'],
                                'category'  => $child3['name'],
                                'img'       => $child3['img'] ?? '',
                                'brands'    => json_encode($child3['brands'] ?? []),
                                'parent_id' => $child['id'],
                                'level'     => 3,
                                'sort'      => $sort3++,
                                'status'    => 1,
                            ];
                        }
                    }
                }
            }
        }

        // 过滤重复id
        $saveList = collect($saveList)->unique('id')->values()->all();

        // 批量 upsert（有则更新，无则新增）
        \App\Models\ProductCategory::upsert(
            $saveList,
            ['id'], // 唯一键
            ['category', 'img', 'brands', 'parent_id', 'level', 'sort', 'status'] // 需要更新的字段
        );

        //统计新增数量
        $count = count($saveList);
        echo '\n 新增分类：' . $count . '条';
        Log::info('新增分类：' . $count . '条');
    }
    
}