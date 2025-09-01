<?php

namespace App\Admin\Controllers;

use App\Models\Refund;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Grid\Filter;
use Encore\Admin\Facades\Admin;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Encore\Admin\Controllers\AuthController as BaseAuthController;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\RefundImport;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon; // 如果使用Carbon
use DateTime;      // 如果使用原生DateTime
/*
* 系统参数管理  
*/
class RefundController extends AdminController
{
    protected $title = '退款管理';

    protected function grid()
    {
        $grid = new Grid(new Refund());

        // 在查询时处理日期范围
        $grid->model()->when(request('created_at'), function ($query) {
            $dates = request('created_at');          
            if (isset($dates['start']) && isset($dates['end'])) {                  
                $dates['start'] .=' 00:00:00';
                $dates['end'] .=' 23:59:59';         
                $query->whereDate('created_at', '>=', $dates['start'])
                      ->whereDate('created_at', '<=', $dates['end']);         
            }
        });

        $grid->column('id', __('ID'))->sortable();         
        // 添加refund表的所有字段       
        $grid->column('pid', 'PID');
        $grid->column('mid', 'MID');
        $grid->column('survey_number', '调查<br>编号');
        $grid->column('survey_name', '调查名字');
        $grid->column('survey_account_name', '调查账户');
        $grid->column('supplier_name', '公司');
        $grid->column('survey_islive', '调查<br>状态')->display(function ($value) {
            return $value == 1 ? '<span class="label label-success">在线</span>' : '<span class="label label-danger">离线</span>';
        });
        $grid->column('respondent_router_entry_time', '进入时间')->display(function ($value) {
            return $value ? \Carbon\Carbon::parse($value)->format('Y-m-d H:i:s') : '-';
        });
        $grid->column('complete_time', '完成时间')->display(function ($value) {
            return $value ? \Carbon\Carbon::parse($value)->format('Y-m-d H:i:s') : '-';
        });
       
        $grid->column('client_status', '客户端状态');
        $grid->column('response_status_description', '状态描述');
        $grid->column('language_country', '国家');
        // $grid->column('final_status', '最终状态');
        // $grid->column('cost', 'cost')->display(function ($value) {
        //     return $value ? '$' . number_format($value, 2) : '-';
        // });
        $grid->column('rpi', 'RPI')->display(function ($value) {
            $color = $value >= 0 ? 'text-success' : 'text-danger';
            return '<span class="' . $color . '">$' . number_format($value, 2) . '</span>';
        });
        $grid->column('rpi_commission_amount', 'RPI提成退款金额')->display(function ($value) {
            return '$' . number_format($value, 2);
        });
         
        $grid->column('created_at','创建时间')->display(function ($value) {
            return $value ? \Carbon\Carbon::parse($value)->format('Y-m-d H:i:s') : '-';
        });

        // 添加日期范围过滤器
        $grid->filter(function($filter){
            // 移除默认的id过滤器
            $filter->disableIdFilter();            
            // 添加日期范围过滤器 - 修复：使用自定义处理确保全天查询
            $filter->between('created_at', '创建时间')->date();
            // 添加其他常用过滤器
            $filter->like('survey_number', '调查编号');
            $filter->like('survey_name', '调查名字');
            $filter->equal('client_status', '客户状态')->select([
                '11' => '回正',
                '26' => '退款',
                '28' => '退款',
                '38' => '退款'
            ]);
            $filter->like('language_country', '国家');
        });

        // 添加导入按钮
        $grid->tools(function ($tools) {
            $tools->append('<a href="#" class="btn btn-sm btn-success" onclick="importData()"><i class="fa fa-upload"></i> 导入</a>');
        });

        // 添加导入模态框的HTML和JavaScript
        $grid->tools(function ($tools) {
            $tools->append($this->renderImportModal());
        });

        return $grid;
    }

    /**
     * 渲染导入模态框
     */
    public function renderImportModal()
    {
        return '
        <!-- 导入模态框 -->
        <div class="modal fade" id="importModal" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">导入退款数据</h4>
                        <button type="button" class="close" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>
                    <form id="importForm" enctype="multipart/form-data">
                        <div class="modal-body">
                            <div class="form-group">
                                <label>选择Excel文件：</label>
                                <input type="file" name="import_file" id="import_file" class="form-control" accept=".xlsx,.xls,.csv" required>
                                <small class="text-muted">支持格式：Excel (.xlsx, .xls) 或 CSV (.csv)</small>
                            </div>
                            <div class="form-group">
                                <label>
                                    <input type="checkbox" id="skip_errors" name="skip_errors" value="1">
                                    跳过错误行继续导入
                                </label>
                            </div>
                            <div class="alert alert-info">
                                <strong>导入说明：</strong><br>
                                1. Excel文件第一行应为列标题<br>
                                2. 必填字段：pid, mid, survey_name, supplier_name, complete_time, fulcrum_status, client_status, rpi<br>
                                3. 时间格式：YYYY-MM-DD HH:MM:SS<br>
                                4. 金额格式：数字（可为负数）
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-default" data-dismiss="modal">取消</button>
                            <button type="submit" class="btn btn-primary" id="importBtn">
                                <i class="fa fa-upload"></i> 开始导入
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <script>
        function importData() {
            $("#importModal").modal("show");
        }

        $(document).ready(function() {
            $("#importForm").on("submit", function(e) {
                e.preventDefault();
                
                var formData = new FormData();
                var fileInput = document.getElementById("import_file");
                
                if (!fileInput.files[0]) {
                    toastr.error("请选择要导入的文件");
                    return;
                }
                
                formData.append("import_file", fileInput.files[0]);
                formData.append("skip_errors", $("#skip_errors").is(":checked") ? 1 : 0);
                formData.append("_token", $("meta[name=csrf-token]").attr("content"));
                
                var importBtn = $("#importBtn");
                importBtn.prop("disabled", true).html("<i class=\"fa fa-spinner fa-spin\"></i> 导入中...");
                
                $.ajax({
                    url: "/admin/refund/import",
                    type: "POST",
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.message);
                            $("#importModal").modal("hide");
                            location.reload(); // 刷新页面显示新数据
                        } else {
                            toastr.error(response.message || "导入失败");
                        }
                    },
                    error: function(xhr) {
                        var errorMsg = "导入失败";
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMsg = xhr.responseJSON.message;
                        } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                            var errors = [];
                            for (var key in xhr.responseJSON.errors) {
                                errors.push(xhr.responseJSON.errors[key].join(", "));
                            }
                            errorMsg = errors.join("<br>");
                        }
                        toastr.error(errorMsg);
                    },
                    complete: function() {
                        importBtn.prop("disabled", false).html("<i class=\"fa fa-upload\"></i> 开始导入");
                    }
                });
            });
        });
        </script>
        ';
    }

    /**
     * 处理文件导入
     */
    public function import(Request $request)
    {
        try {
            // 验证文件
            $validator = Validator::make($request->all(), [
                'import_file' => 'required|file|mimes:xlsx,xls,csv|max:10240', // 最大10MB
            ], [
                'import_file.required' => '请选择要导入的文件',
                'import_file.mimes' => '文件格式不支持，请上传Excel或CSV文件',
                'import_file.max' => '文件大小不能超过10MB',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first()
                ]);
            }

            $file = $request->file('import_file');
            $skipErrors = $request->input('skip_errors', false);

            // 开始导入
            DB::beginTransaction();
            
            $importedCount = 0;
            $errorCount = 0;
            $errors = [];

            // 读取文件内容
            $data = Excel::toArray(new RefundImport, $file);
            
            if (empty($data) || empty($data[0])) {
                throw new \Exception('文件内容为空或格式不正确');
            }

            $rows = $data[0];
            // 处理每一行数据
            foreach ($rows as $rowIndex => $row) {
                try {
                    // 跳过标题行
                    if ($rowIndex === 0) {
                        continue;
                    }
                    
                    // 初始化行数据数组
                    $rowData = [];
                    // 数据类型转换和验证
                    foreach ($row  as $field => $value) {
                        switch ($field) {
                            case 'survey_islive':
                            case 'is_exchange':
                                // 处理布尔值和字符串
                                if (is_bool($value)) {
                                    $rowData[$field] = $value ? 1 : 0;
                                } else {
                                    $rowData[$field] = in_array(strtolower($value), ['1', 'true', '是', '在线']) ? 1 : 0;
                                }
                                break;
                            case 'respondent_router_entry_time':
                            case 'complete_time':
                                if ($value && $value !== '-' && $value !== null) {
                                    // 处理ISO 8601格式的时间，使用Carbon来正确解析
                                    try {
                                        if (is_string($value)) {
                                            // 使用Carbon解析ISO 8601格式
                                            $carbonDate = \Carbon\Carbon::parse($value);
                                            $rowData[$field] = $carbonDate->format('Y-m-d H:i:s');
                                        } else {
                                            $rowData[$field] = date('Y-m-d H:i:s', strtotime($value));
                                        }
                                    } catch (\Exception $e) {
                                        // 如果解析失败，尝试其他方法
                                        $rowData[$field] = date('Y-m-d H:i:s', strtotime($value));
                                    }
                                } else {
                                    $rowData[$field] = null;
                                }
                                break;
                            case 'cost':
                            case 'rpi':
                            case 'rpi_commission_amount':
                                if ($value === null || $value === '') {
                                    $rowData[$field] = 0;
                                } else {
                                    $rowData[$field] = is_numeric($value) ? (float)$value : 0;
                                }
                                break;
                            case 'survey_number':
                            case 'fulcrum_status':
                            case 'client_status':
                                $rowData[$field] = is_numeric($value) ? (int)$value : ($value === null ? null : $value);
                                break;
                            default:
                                $rowData[$field] = $value;
                        }
                    }

                    $rowData['created_at'] = date('Y-m-d H:i:s');
                    $rowData['updated_at'] = date('Y-m-d H:i:s');
                    // 创建新记录
                    Refund::create($rowData);
                    $importedCount++;

                } catch (\Exception $e) {
                    $errorCount++;
                    $errors[] = "Row {$rowIndex}: " . $e->getMessage();
                    
                    if (!$skipErrors) {
                        DB::rollback();
                        return response()->json([
                            'success' => false,
                            'message' => '导入失败：' . $e->getMessage()
                        ]);
                    }
                }
            }

            DB::commit();
            $message = "导入完成！成功导入 {$importedCount} 条记录";
            if ($errorCount > 0) {
                $message .= "，跳过 {$errorCount} 条错误记录";
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'imported_count' => $importedCount,
                'error_count' => $errorCount,
                'errors' => $errors
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => '导入失败：' . $e->getMessage()
            ]);
        }
    }
}