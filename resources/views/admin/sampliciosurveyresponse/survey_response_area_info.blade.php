@section('content')
<div class="box">
    <div class="box-header with-border">
        <h3 class="box-title">个人信息</h3>
    </div>
    <div class="box-body">
        <div id="area-info-container">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>姓名</th>
                        <th>调查编号</th>
                        <th>年龄</th>
                        <th>城市</th>
                        <th>国家</th>
                        <th>州</th>
                        <th>性别</th> 
                        <th>种族</th> 
                    </tr>
                </thead>
                <tbody id="area-info-tbody">
                    <!-- 数据行将通过AJAX动态加载 -->
                </tbody>
            </table>
            
            <!-- 无数据时的提示 -->
            <div id="no-data-message" class="text-center" style="display: none; padding: 20px;">
                <p class="text-muted">暂无数据</p>
            </div>
            
            <!-- 加载状态提示 -->
            <div id="loading" class="text-center" style="display: none; padding: 20px;">
                <i class="fa fa-spinner fa-spin"></i> 正在加载数据...
            </div>
        </div>
        
        <!-- 添加分页容器 -->
        <div class="box-footer">
            <div id="pagination-info" class="pull-left"></div>
            <div id="pagination-container" class="pull-right"></div>
            <div class="clearfix"></div>
        </div>
    </div>
</div>

<script>
// 页面加载时调用
$(document).ready(function() {
    loadSurveyResponseAreaInfo();
});

// 带参数调用
$('#search-btn').click(function() {
    const params = {
        survey_number: $('#survey_number').val(),
        mid: $('#mid').val(),
        pageSize: 20,
        page: 1
    };
    loadSurveyResponseAreaInfo(params);
});

// 分页点击事件 - 使用事件委托
$(document).on('click', '#pagination-container .page-link', function(e) {
    e.preventDefault();
    const page = $(this).data('page');
    if (page) {
        const params = {
            survey_number: $('#survey_number').val(),
            mid: $('#mid').val(),
            page: page,
            pageSize: 15
        };
        loadSurveyResponseAreaInfo(params);
    }
});

function loadSurveyResponseAreaInfo(params = {}) {
    // 显示加载状态
    $('#loading').show();
    
    $.ajax({
        url: '/admin/sampliciosurveyresponse/survey_response_area_info',
        type: 'POST',
        dataType: 'json',
        data: {
            survey_number: params.survey_number || '',
            mid: params.mid || '',
            pageSize: params.pageSize || 15,
            page: params.page || 1,
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        beforeSend: function() {
            // 请求发送前的处理
            console.log('正在加载区域信息...');
        },
        success: function(response) {
            console.log('区域信息加载成功:', response);
            
            if (response.success) {
                // 处理成功响应
                updateAreaInfoDisplay(response.data);
                
                // 启用分页信息更新
                if (response.current_page && response.last_page && response.total) {
                    updatePagination(response.current_page, response.last_page, response.total);
                }
            } else {
                console.warn('响应状态为失败:', response.message);
            }
        },
        error: function(xhr, status, error) {
            console.error('加载区域信息失败:', {
                status: status,
                error: error,
                responseText: xhr.responseText
            });
            
            // 显示错误信息给用户
            showErrorMessage('加载数据失败，请稍后重试');
        },
        complete: function() {
            // 隐藏加载状态
            $('#loading').hide();
        }
    });
}

// 更新区域信息显示的辅助函数
function updateAreaInfoDisplay(data) {
    const tbody = $('#area-info-tbody');
    const noDataMessage = $('#no-data-message');
    
    // 清空现有数据
    tbody.empty();
    
    if (Array.isArray(data) && data.length > 0) {
        // 隐藏无数据提示
        noDataMessage.hide();
        
        // 生成表格行
        let html = '';
        data.forEach(function(item, index) {
            html += `
                <tr>
                    <td>${item.id || (index + 1)}</td>
                    <td>${item.name}</td>
                    <td>${item.survey_number || '-'}</td>
                    <td>${item.age}</td>
                    <td>${item.city || '-'}</td>
                    <td>${item.country || '-'}</td>
                    <td>${item.subdivision || '-'}</td>                    
                    <td>${item['43'] || '-'}</td>
                    <td>${item['113'] || '-'}</td>
                </tr>
            `;
        });
        tbody.html(html);
    } else {
        // 显示无数据提示
        noDataMessage.show();
    }
}

// 显示错误信息的辅助函数
function showErrorMessage(message) {
    // 可以使用alert、toast或其他方式显示错误
    alert(message);
}

// 更新分页信息的函数
function updatePagination(currentPage, lastPage, total) {
    // 更新分页显示
    const paginationHtml = generatePaginationHtml(currentPage, lastPage, total);
    $('#pagination-container').html(paginationHtml);
    
    // 更新分页信息文本
    $('#pagination-info').text(`第 ${currentPage} 页，共 ${lastPage} 页，总计 ${total} 条记录`);
}

// 生成分页HTML的辅助函数
function generatePaginationHtml(currentPage, lastPage, total) {
    let html = '<nav aria-label="Page navigation">';
    html += '<ul class="pagination">';
    
    // 上一页按钮
    if (currentPage > 1) {
        html += `<li class="page-item"><a class="page-link" href="#" data-page="${currentPage - 1}">上一页</a></li>`;
    } else {
        html += '<li class="page-item disabled"><span class="page-link">上一页</span></li>';
    }
    
    // 页码按钮
    const startPage = Math.max(1, currentPage - 2);
    const endPage = Math.min(lastPage, currentPage + 2);
    
    if (startPage > 1) {
        html += '<li class="page-item"><a class="page-link" href="#" data-page="1">1</a></li>';
        if (startPage > 2) {
            html += '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
    }
    
    for (let i = startPage; i <= endPage; i++) {
        if (i === currentPage) {
            html += `<li class="page-item active"><span class="page-link">${i}</span></li>`;
        } else {
            html += `<li class="page-item"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`;
        }
    }
    
    if (endPage < lastPage) {
        if (endPage < lastPage - 1) {
            html += '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
        html += `<li class="page-item"><a class="page-link" href="#" data-page="${lastPage}">${lastPage}</a></li>`;
    }
    
    // 下一页按钮
    if (currentPage < lastPage) {
        html += `<li class="page-item"><a class="page-link" href="#" data-page="${currentPage + 1}">下一页</a></li>`;
    } else {
        html += '<li class="page-item disabled"><span class="page-link">下一页</span></li>';
    }
    
    html += '</ul>';
    html += '</nav>';
    
    return html;
}

</script>
@endsection