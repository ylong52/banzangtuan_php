<?php

namespace App\Http\Controllers\Api;

use App\Models\GlobalConfig;
use App\Models\User;
use App\Models\Orders;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use SebastianBergmann\CliParser\AmbiguousOptionException;
use Illuminate\Support\Facades\Http;  
use Illuminate\Support\Str;
use App\Http\Controllers\ApiController;
use App\Services\JdUnionClient;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx\Rels;
use App\Models\Goods;

class JdGoodsController extends ApiController
{

    public function index(Request $request) {
 
        $page = $request->input('page', 1);
        $perPage = $request->input('per_page', 20);
        // $perPage = 1;
        $keyword = $request->input('keyword', '');
        $query = Goods::query() 
            ->orderByDesc('created_at');
        if (!empty($keyword)) {
            $query->where('goodsname', 'like', '%'.$keyword.'%');
        }
        $paginator = $query->paginate($perPage, ['*'], 'page', $page);
        //整理数据
        $lists = $paginator->items();
        foreach($lists as $key => &$val) {
            $val['white_image'] = $val['white_image'] ?? '';
            $val['priceinfo'] = json_decode($val['priceinfo'],true) ?? 0;
            $val['commission_info'] = json_decode($val['commission_info'], true) ?? [];
        }
        $pagination = [
            'total' => $paginator->total(),
            'per_page' => $paginator->perPage(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
        ];

        return response()->json([
            'status' => 'success',
            'msg' => 'success',
            'lists' => $paginator->items(),
            'pagination' => $pagination
        ]);
    }
    
    public function show(Request $request, $id) {
        $goods = Goods::find($id);
        return response()->json(['status' => 'success','msg' => 'success','data' => $goods]);
    }

}