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
 
use App\Models\AyqyOrders;
use App\Models\User;
use App\Models\Products;
use App\Models\GlobalConfig;


class GlobalConfigController extends AdminController
{
    protected $title = '全局配置';

    protected function grid()
    {
        $grid = new Grid(new GlobalConfig());

        // 只显示 status=1 的记录
        $grid->model()->where('status', 1);

        // 关闭新增按钮
        $grid->disableCreateButton();
        
        $grid->column('id', 'ID')->sortable();
        $grid->column('name', '名称');
        $grid->column('key', '键');
        $grid->column('sort', '排序')->sortable();
        
        // 根据 itype 显示不同的值输入控件
        $grid->column('value', '值')->display(function ($value) {
            // 检查是否是开关类型的配置
            $name = $this->getAttribute('name') ?? '';
            $key = $this->getAttribute('key') ?? '';
            $isSwitch = (strpos($name, '开关') !== false) || (strpos($key, 'switch') !== false) || (strpos($key, 'enable') !== false);
            
            if ($isSwitch) {
                // 显示开关状态 - 更直观的判断逻辑
                
                
                return '<div>
                     
                    <div style="margin-top: 3px; font-size: 11px; color: #999;">
                        当前值: ' . htmlspecialchars($value) .  ')
                    </div>
                </div>';
            }
            
            $itype = $this->getAttribute('itype');
            switch ($itype) {
                case 1: // input
                    return "<input type='text' class='form-control' value='" . htmlspecialchars($value) . "' readonly style='max-width: 200px;'>";
                case 2: // textarea
                    $displayValue = mb_strlen($value) > 50 ? mb_substr($value, 0, 50) . '...' : $value;
                    return "<textarea class='form-control' readonly style='max-width: 200px; height: 60px;'>" . htmlspecialchars($displayValue) . "</textarea>";
                case 3: // select
                    // 获取原始JSON字符串
                    $rawOptions = $this->getAttributes()['options'] ?? null;
                    if ($rawOptions && is_string($rawOptions)) {
                        $optionsArray = json_decode($rawOptions, true);
                        if (json_last_error() === JSON_ERROR_NONE && is_array($optionsArray)) {
                            $displayText = isset($optionsArray[$value]) ? $optionsArray[$value] : $value;
                            return "<select class='form-control' disabled style='max-width: 200px;'><option selected>" . htmlspecialchars($displayText) . "</option></select>";
                        }
                    }
                    return "<span class='label label-info'>" . htmlspecialchars($value) . "</span>";
                case 4: // checkbox
                    // 获取原始JSON字符串
                    $rawOptions = $this->getAttributes()['options'] ?? null;
                    if ($rawOptions && is_string($rawOptions)) {
                        $optionsArray = json_decode($rawOptions, true);
                        if (json_last_error() === JSON_ERROR_NONE && is_array($optionsArray)) {
                            // 处理checkbox的值 - 与表单逻辑保持一致
                            $checkboxValues = [];
                            
                            if (is_string($value) && !empty($value)) {
                                // 如果是JSON字符串，尝试解码
                                $decodedValue = json_decode($value, true);
                                if (json_last_error() === JSON_ERROR_NONE && is_array($decodedValue)) {
                                    // JSON数组格式：["apliay","wechat"]
                                    $checkboxValues = $decodedValue;
                                } else {
                                    // 单个字符串值："apliay" 或逗号分隔的字符串
                                    if (strpos($value, ',') !== false) {
                                        // 逗号分隔：apliay,wechat
                                        $checkboxValues = array_filter(explode(',', $value));
                                    } else {
                                        // 单个值："apliay"
                                        $checkboxValues = [$value];
                                    }
                                }
                            } elseif (is_array($value)) {
                                // 如果已经是数组
                                $checkboxValues = $value;
                            }
                            
                            // 清理值并确保只包含有效的选项键
                            $checkboxValues = array_filter(array_map('trim', $checkboxValues), function($val) use ($optionsArray) {
                                return isset($optionsArray[$val]);
                            });
                            
                            // 显示所有选项的状态
                            $statusItems = [];
                            foreach ($optionsArray as $key => $label) {
                                $isSelected = in_array($key, $checkboxValues);
                                $statusIcon = $isSelected ? 'check-circle' : 'times-circle';
                                $statusClass = $isSelected ? 'success' : 'danger';
                                $statusText = $isSelected ? '使用' : '停用';
                                
                                $statusItems[] = '<span class="label label-' . $statusClass . '" style="margin-right: 5px;">
                                    <i class="fa fa-' . $statusIcon . '"></i> ' . htmlspecialchars($label) . '【' . $statusText . '】
                                </span>';
                            }
                            
                            return '<div class="checkbox-display">' . implode('', $statusItems) . '</div>';
                        }
                    }
                    // 如果无法解析，显示原始值
                    return "<span class='label label-default'><i class='fa fa-square-o'></i> " . htmlspecialchars($value) . "</span>";
                default:
                    return htmlspecialchars($value);
            }
        });
        
        // $grid->column('itype', '输入类型')->display(function ($itype) {
        //     $types = [
        //         1 => '<span class="label label-primary">Input</span>',
        //         2 => '<span class="label label-info">Textarea</span>',
        //         3 => '<span class="label label-warning">Markdown</span>'
        //     ];
        //     return $types[$itype] ?? '<span class="label label-default">未知</span>';
        // });
        
        $grid->column('description', '描述')->limit(50);
        
        $grid->column('status', '状态')->display(function ($status) {
            return $status == 1 ? 
                '<span class="label label-success">启用</span>' : 
                '<span class="label label-danger">禁用</span>';
        });
        
        // $grid->column('updated_at', '更新时间')->sortable();

        // 禁用批量删除
        $grid->disableBatchActions();
        
        // 只保留编辑操作
        $grid->actions(function ($actions) {
            $actions->disableDelete();
        });

        return $grid;
    }

    protected function form()
    {
        $form = new Form(new GlobalConfig());

        // 禁用新增操作
        $form->disableCreatingCheck();
        $form->disableEditingCheck();
        
        // 不显示ID字段
        // $form->display('id', 'ID');
        $form->text('name', '名称')->required();
        $form->text('key', '键')->disable();
        // 获取当前配置信息
        $globalConfigInfo = request()->route()->parameter('global_config') ? 
                           GlobalConfig::find(request()->route()->parameter('global_config')) : null;
    
        // 根据 itype 显示不同的值输入控件
        $itype = $globalConfigInfo ? $globalConfigInfo->itype ?? 1 : 1;
        
        // 检查是否是开关类型的配置
        $name = $globalConfigInfo ? $globalConfigInfo->name ?? '' : '';
        $key = $globalConfigInfo ? $globalConfigInfo->key ?? '' : '';
        $isSwitch = (strpos($name, '开关') !== false) || (strpos($key, 'switch') !== false) || (strpos($key, 'enable') !== false);
        
        if ($isSwitch && $itype == 1) {
            // 为开关类型提供更友好的编辑界面
            $currentValue = $globalConfigInfo ? $globalConfigInfo->value : '0';
            $form->radio('value', '状态')->options([
                '1' => '启用',
                '0' => '停用'
            ])->value($currentValue)->required();
        } else {
            switch ($itype) {
                case 1: // input
                    $form->text('value', '值')->required();
                    break;
                case 2: // textarea
                    $form->textarea('value', '值')->rows(20)->required();
                    break;
            case 3: // select
                if ($globalConfigInfo) {
                    // 获取原始JSON字符串
                    $rawOptions = $globalConfigInfo->getAttributes()['options'] ?? null;
                    
                    if ($rawOptions && is_string($rawOptions)) {
                        // 解码JSON字符串为数组
                        $optionsArray = json_decode($rawOptions, true);
                        
                        if (json_last_error() === JSON_ERROR_NONE && is_array($optionsArray)) {
                            $form->select('value', '值')
                                ->options($optionsArray)
                                ->value($globalConfigInfo->value)
                                ->required();
                        } else {
                            // JSON解码失败，回退到文本输入
                            $form->text('value', '值')->required();
                        }
                    } else {
                        $form->text('value', '值')->required();
                    }
                } else {
                    $form->text('value', '值')->required();
                }
                break;
            case 4: // checkbox
                if ($globalConfigInfo) {
                    // 获取原始JSON字符串
                    $rawOptions = $globalConfigInfo->getAttributes()['options'] ?? null;
                    
                    if ($rawOptions && is_string($rawOptions)) {
                        // 解码JSON字符串为数组
                        $optionsArray = json_decode($rawOptions, true);
                        
                        if (json_last_error() === JSON_ERROR_NONE && is_array($optionsArray)) {
                            // 处理checkbox的值
                            $checkboxValue = $globalConfigInfo->value;
                            $selectedValues = [];
                            
                            if (is_string($checkboxValue) && !empty($checkboxValue)) {
                                // 如果是JSON字符串，尝试解码
                                $decodedValue = json_decode($checkboxValue, true);
                                if (json_last_error() === JSON_ERROR_NONE && is_array($decodedValue)) {
                                    // JSON数组格式：["apliay","wechat"]
                                    $selectedValues = $decodedValue;
                                } else {
                                    // 单个字符串值："apliay" 或逗号分隔的字符串
                                    if (strpos($checkboxValue, ',') !== false) {
                                        // 逗号分隔：apliay,wechat
                                        $selectedValues = array_filter(explode(',', $checkboxValue));
                                    } else {
                                        // 单个值："apliay"
                                        $selectedValues = [$checkboxValue];
                                    }
                                }
                            } elseif (is_array($checkboxValue)) {
                                // 如果已经是数组
                                $selectedValues = $checkboxValue;
                            }
                            
                            // 清理值并确保只包含有效的选项键
                            $selectedValues = array_filter(array_map('trim', $selectedValues), function($value) use ($optionsArray) {
                                return isset($optionsArray[$value]);
                            });
                            
                            $form->checkbox('value', '值')
                                ->options($optionsArray)
                                ->value($selectedValues);
                        } else {
                            // JSON解码失败，回退到文本输入
                            $form->text('value', '值')->required();
                        }
                    } else {
                        $form->text('value', '值')->required();
                    }
                } else {
                    $form->text('value', '值')->required();
                }
                break;
            default:
                $form->text('value', '值')->required();
            }
        }
        
        $form->textarea('description', '描述')->rows(3);
        
        // 不显示输入类型和状态字段
        // $form->display('itype', '输入类型')->with(function ($itype) {
        //     $types = [
        //         1 => 'Input',
        //         2 => 'Textarea', 
        //         3 => 'Markdown'
        //     ];
        //     return $types[$itype] ?? '未知';
        // });
        
        // $form->radio('status', '状态')->options([
        //     1 => '启用',
        //     0 => '禁用'
        // ])->default(1);

        // 禁用删除操作
        $form->disableViewCheck();
        $form->tools(function (Form\Tools $tools) {
            $tools->disableDelete();
        });

        return $form;
    }

    protected function detail($id)
    {
        $show = new Show(GlobalConfig::findOrFail($id));

        $show->field('id', 'ID');
        $show->field('name', '名称');
        $show->field('key', '键');
        $show->field('value', '值')->as(function ($value) {
            // 检查是否是开关类型的配置（基于名称或key包含"开关"、"switch"等关键词）
            $name = $this->getAttribute('name') ?? '';
            $key = $this->getAttribute('key') ?? '';
            $isSwitch = (strpos($name, '开关') !== false) || (strpos($key, 'switch') !== false) || (strpos($key, 'enable') !== false);
            
            if ($isSwitch) {
                // 显示开关状态 - 更直观的判断逻辑
                $isEnabled = ($value == '1' || $value == 1);
                $statusLabel = $isEnabled ? 
                    '<span class="label label-success"><i class="fa fa-toggle-on"></i> 启用</span>' : 
                    '<span class="label label-danger"><i class="fa fa-toggle-off"></i> 停用</span>';
                return $statusLabel . '<div style="margin-top: 5px;"><small class="text-muted">原始值: ' . htmlspecialchars($value) . '</small></div>';
            }
            
            if ($this->getAttribute('itype') == 3) {
                // 如果是select，显示选项文本
                $rawOptions = $this->getAttributes()['options'] ?? null;
                if ($rawOptions && is_string($rawOptions)) {
                    $optionsArray = json_decode($rawOptions, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($optionsArray)) {
                        $displayText = isset($optionsArray[$value]) ? $optionsArray[$value] : $value;
                        return '<span class="label label-info">' . htmlspecialchars($displayText) . '</span> (值: ' . htmlspecialchars($value) . ')';
                    }
                }
            } elseif ($this->getAttribute('itype') == 4) {
                // 如果是checkbox，显示所有选项的状态
                $rawOptions = $this->getAttributes()['options'] ?? null;
                if ($rawOptions && is_string($rawOptions)) {
                    $optionsArray = json_decode($rawOptions, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($optionsArray)) {
                        // 处理checkbox的值
                        $checkboxValues = $value;
                        if (is_string($checkboxValues)) {
                            $decodedValues = json_decode($checkboxValues, true);
                            if (json_last_error() === JSON_ERROR_NONE && is_array($decodedValues)) {
                                $checkboxValues = $decodedValues;
                            } else {
                                // 如果不是JSON数组，可能是单个值
                                $checkboxValues = [$checkboxValues];
                            }
                        }
                        
                        // 确保是数组
                        if (!is_array($checkboxValues)) {
                            $checkboxValues = [];
                        }
                        
                        // 显示所有选项的状态
                        $statusItems = [];
                        foreach ($optionsArray as $key => $label) {
                            $isSelected = in_array($key, $checkboxValues);
                            $statusIcon = $isSelected ? 'check-circle' : 'times-circle';
                            $statusClass = $isSelected ? 'success' : 'danger';
                            $statusText = $isSelected ? '使用' : '停用';
                            
                            $statusItems[] = '<div style="margin-bottom: 5px;">
                                <span class="label label-' . $statusClass . '">
                                    <i class="fa fa-' . $statusIcon . '"></i> ' . htmlspecialchars($label) . '【' . $statusText . '】
                                </span>
                            </div>';
                        }
                        
                        return '<div><strong>选项状态:</strong></div>' . implode('', $statusItems) . '<div style="margin-top: 10px;"><strong>原始值:</strong> ' . htmlspecialchars($value) . '</div>';
                    }
                }
                // 如果无法解析，显示原始值
                return '<div><strong>选项状态:</strong> 无法解析</div><div><strong>原始值:</strong> ' . htmlspecialchars($value) . '</div>';
            }
            return $value;
        });
        $show->field('itype', '输入类型')->as(function ($itype) {
            $types = [1 => 'Input', 2 => 'Textarea', 3 => 'Select', 4 => 'Checkbox'];
            return $types[$itype] ?? '未知';
        });
        $show->field('status', '状态')->as(function ($status) {
            return $status == 1 ? '启用' : '禁用';
        });
        $show->field('description', '描述');
        $show->field('created_at', '创建时间');
        $show->field('updated_at', '更新时间');

        // 禁用编辑、删除、列表按钮
        $show->panel()->tools(function ($tools) {
            $tools->disableEdit();
            $tools->disableDelete();
            $tools->disableList();
        });

        return $show;
    }
}