@section('content')
<div class="box">
    <div class="box-header with-border">
        <h3 class="box-title">调查参数管理</h3>
        <div class="box-tools pull-right">
            <a href="{{ admin_url('surveyarg/create') }}" class="btn btn-sm btn-success">
                <i class="fa fa-plus"></i> 新增
            </a>
            <a href="{{ admin_url('surveyarg/rpi-commission-rate') }}" class="btn btn-sm btn-info">
                <i class="fa fa-cog"></i> 员工折算比例
            </a>
        </div>
    </div>
    
    <div class="box-body">
        <table class="table table-hover">
            <thead>
                <tr>      
                <th>ID</th>              
                    <th>名称</th>
                    <th>用户数</th>
                    <th>状态</th>
                    <th>更新时间</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                @if(isset($teams) && count($teams) > 0)
                    @foreach($teams as $team)
                    <tr>                      
                        <td>{{ $team->id }}</td>
                        <td>{{ $team->name }}</td>                    
                        <td>{{ $team->teamCount }}</td>
                        <td><span class="{{ $team->is_deleted == 1 ? 'text-danger' : 'text-success' }}">{{ $team->is_deleted == 1 ? '停用' : '正常' }}</span></td>
                        <td>{{ $team->updated_at }}</td>
                        <td>                        
                            <a href="{{ admin_url('surveyarg/'.$team->id.'/edit') }}" class="btn btn-primary btn-xs">
                                <i class="fa fa-edit"></i> 编辑
                            </a>
                            <a href="javascript:void(0);" data-id="{{ $team->id }}" data-status="{{ $team->is_deleted }}" class="btn {{ $team->is_deleted == 1 ? 'btn-success' : 'btn-danger' }} btn-xs delete-team">
                                <i class="fa {{ $team->is_deleted == 1 ? 'fa-check' : 'fa-trash' }}"></i> {{ $team->is_deleted == 1 ? '启用' : '停用' }}
                            </a>
                            <a href="javascript:void(0);" class="btn btn-info btn-xs toggle-price" data-target="price-row-{{ $team->id }}">
                                <i class="fa fa-eye"></i> WebApi价格
                            </a>
                        </td>
                    </tr>
                    <tr id="price-row-{{ $team->id }}" class="price-row" style="display: none;">
                        <td colspan="6">
                            <table>
                                <tr class="apple-header">
                                    <th>WebApi名称</th>
                                    <th>rev</th>
                                    <th>最低价格</th>
                                    <th>状态</th>
                                    <th>操作</th>
                                </tr>
                                @foreach($team->questionnairelist as $questionnaire)
                                <tr>
                                    <td> 
                                    <input class="title-input" type="text" value="{{ $questionnaire->title }}" min="0" step="0.01" style="width: 80px;">
                                    </td>
                                    <td>
                                        <input type="number" value="{{ $questionnaire->rev }}" min="0" step="0.01" style="width: 80px;">
                                    </td>
                                    <td>
                                    <input type="number" value="{{ $questionnaire->min_amount }}" min="0" step="0.01" style="width: 80px;">
                                    </td>
                                    <td>
                                        <button class="btn btn-xs toggle-status" data-id="{{ $questionnaire->id }}" data-status="{{ $questionnaire->is_show }}">
                                            {{ $questionnaire->is_show == 1? '显示' : '隐藏' }}
                                        </button>
                                    </td>
                                    <td>
                                        <a href="javascript:void(0);" data-id="{{ $questionnaire->id }}" data-team-id="{{ $team->id }}" class="btn btn-success btn-xs save-price">
                                            <i class="fa fa-save"></i> 保存
                                        </a>
                                        <a href="javascript:void(0);" data-id="{{ $questionnaire->id }}" data-team-id="{{ $team->id }}" class="btn btn-danger btn-xs delete-questionnaire">
                                            <i class="fa fa-trash"></i> 作废
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                                <tr>
                                    <td colspan="4" class="text-right">
                                        <a href="javascript:void(0);" data-team-id="{{ $team->id }}" class="btn btn-primary btn-xs add-new-row">
                                            <i class="fa fa-plus"></i> 添加新项目
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>                    
                    </tr>
                    @endforeach
                @else
                    <tr>
                        <td colspan="5" class="text-center">暂无数据</td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>
    
    @if(isset($teams) && method_exists($teams, 'render'))
    <div class="box-footer clearfix">
        {!! $teams->render() !!}
    </div>
    @endif
</div>

<style>
.table > thead > tr > th {
    border-bottom: 2px solid #f4f4f4;
    font-weight: 600;
}
.table > tbody > tr > td {
    border-top: 1px solid #f4f4f4;
    vertical-align: middle;
}
.btn-xs {
    margin-right: 5px;
}
.price-row {
    background-color: #f9f9f9;
}
.price-row td {
    padding: 15px;
}
/* 新增样式 */
.price-header td {
    background-color: #3c8dbc;
    color: white;
    font-weight: bold;
    padding: 8px;
    text-align: center;
    border-bottom: 2px solid #367fa9;
}
/* 苹果风格表头 */
.apple-header {
    background: linear-gradient(to bottom, #f7f7f7, #e5e5e5);
    border-radius: 8px 8px 0 0;
    overflow: hidden;
}

.apple-header th {
    color: #333;
    font-weight: 500;
    padding: 12px 15px;
    text-align: center;
    border-bottom: 1px solid #d1d1d1;
    font-size: 14px;
    letter-spacing: 0.3px;
    box-shadow: 0 1px 0 rgba(255,255,255,0.8) inset;
    transition: background-color 0.2s ease;
}

.apple-header th:hover {
    background-color: rgba(0,0,0,0.02);
}
</style>

<script>
$(function () {
    $('.delete').click(function() {
        var id = $(this).data('id');
        if(confirm('确定要删除这条记录吗？')) {
            $.ajax({
                method: 'post',
                url: '{{ admin_url("surveyarg/") }}' + id,
                data: {
                    _method: 'delete',
                    _token: LA.token,
                },
                success: function (data) {
                    $.pjax.reload('#pjax-container');
                    toastr.success('删除成功！');
                }
            });
        }
    });
    
    // 添加价格行的显示/隐藏功能
    $('.toggle-price').click(function() {
        var targetId = $(this).data('target');
        $('#' + targetId).toggle();
        
        // 切换图标
        var icon = $(this).find('i');
        if (icon.hasClass('fa-eye')) {
            icon.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            icon.removeClass('fa-eye-slash').addClass('fa-eye');
        }
    });
    
    // 保存价格按钮点击事件
    $(document).on('click', '.save-price', function() {
        var row = $(this).closest('tr');
        var questionnaireId = $(this).data('id');
        var teamId = $(this).data('team-id');
        var title = row.find('input.title-input').val() || row.find('td:first').text();
        var rev = row.find('input[type="number"]:eq(0)').val();
        var minAmount = row.find('input[type="number"]:eq(1)').val();
        // 判断questionnaireId是否为undefined，如果是则设置为0
        if (questionnaireId === undefined) {
            questionnaireId = 0;
        }

        // 打印值到控制台
        console.log('提交数据:', {
            questionnaire_id: questionnaireId,
            teams_id: teamId,
            title: title,
            rev: rev,
            min_amount: minAmount
        });
       //提交到后台地址
        // POST                                   | admin/questionnaire                      | admin.questionnaire.store
        $.ajax({
            url: '{{ admin_url("questionnaire") }}',
            type: 'POST',
            data: {
                questionnaire_id: questionnaireId,
                teams_id: teamId,
                title: title,
                rev: rev,
                min_amount: minAmount,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                // 如果是新增行（没有questionnaire_id），则显示不同的消息
                if (!questionnaireId) {
                    toastr.success('新项目添加成功！');
                    // 刷新页面或更新UI
                    // location.reload();
                } else {
                    toastr.success('保存成功！');
                }
            },
            error: function(xhr) {
                toastr.error('操作失败：' + (xhr.responseJSON ? xhr.responseJSON.message : '未知错误'));
            }
        });

        
    });
    
    // 添加新行按钮点击事件
    $(document).on('click', '.add-new-row', function() {
        var teamId = $(this).data('team-id');
        var newRow = `
            <tr>
                <td>
                    <input type="text" class="title-input" placeholder="输入名称" style="width: 100%;">
                </td>
                <td>
                    <input type="number" value="0" min="0" step="0.01" style="width: 80px;">
                </td>
                <td>
                    <input type="number" value="0" min="0" step="0.01" style="width: 80px;">
                </td>
                <td>
                    <a href="javascript:void(0);" data-team-id="${teamId}" class="btn btn-success btn-xs save-price">
                        <i class="fa fa-save"></i> 保存
                    </a>
                    <a href="javascript:void(0);" class="btn btn-danger btn-xs cancel-new-row">
                        <i class="fa fa-times"></i> 取消
                    </a>
                </td>
            </tr>
        `;
        
        // 在"添加新项目"按钮所在行之前插入新行
        $(this).closest('tr').before(newRow);
    });
    
    // 取消新增行按钮点击事件
    $(document).on('click', '.cancel-new-row', function() {
        $(this).closest('tr').remove();
    });

    // 状态切换处理
    $('.toggle-status').on('click', function() {
        var id = $(this).data('id');
        var currentStatus = $(this).data('status');
        var newStatus = currentStatus == 1 ? 0 : 1;
        var $btn = $(this);
        console.log("256 >>>>>",id,currentStatus,newStatus);
        $.ajax({
            url: '{{ admin_url("questionnaire/toggle-show") }}',
            type: 'POST',
            data: {
                id: id,
                is_show: newStatus,
                _token: LA.token
            },
            success: function(response) {
                if (response.status) {
                    // 更新按钮状态
                    var newStatus = response.is_show;
                    $btn.data('show', newStatus);
                    
                    if (newStatus == 1) {
                        $btn.removeClass('btn-warning').addClass('btn-success');
                        $btn.html('<i class="fa fa-eye"></i> 显示');
                    } else {
                        $btn.removeClass('btn-success').addClass('btn-warning');
                        $btn.html('<i class="fa fa-eye-slash"></i> 隐藏');
                    }
                    
                    toastr.success('状态已更新');
                } else {
                    toastr.error('更新失败');
                }
            },
            error: function() {
                toastr.error('服务器错误');
            }
        });
    });

    // 删除问卷按钮点击事件
$(document).on('click', '.delete-questionnaire', function() {
    var id = $(this).data('id');
    var $row = $(this).closest('tr');
    
    if(confirm('确定要删除这个问卷吗？')) {
        $.ajax({
            url: '{{ admin_url("questionnaire/delete") }}',
            type: 'POST',
            data: {
                id: id,
                _token: LA.token
            },
            success: function(response) {
                if (response.status) {
                    // 从DOM中移除该行
                    $row.fadeOut(300, function() {
                        $(this).remove();
                    });
                    toastr.success('问卷已删除');
                } else {
                    toastr.error('删除失败');
                }
            },
            error: function() {
                toastr.error('服务器错误');
            }
        });
    }
});


// 停用团队按钮点击事件
$('.delete-team').click(function() {
    var id = $(this).data('id');
    var currentStatus = $(this).data('status');
    var actionText = currentStatus == 1 ? '启用' : '停用';
    
    if(confirm('确定要停用这个团队吗？')) {
        $.ajax({
            url: '{{ admin_url("teams/toggle-delete") }}',
            type: 'POST',
            data: {
                id: id,
                status: currentStatus,
                _token: LA.token
            },
            success: function(response) {
                if (response.status) {
                    toastr.success('团队已停用');
                    // 刷新页面以显示更新后的状态
                    $.pjax.reload('#pjax-container');
                } else {
                    toastr.error('停用失败：' + response.message);
                }
            },
            error: function() {
                toastr.error('服务器错误');
            }
        });
    }
});

/////
});
</script>
@endsection