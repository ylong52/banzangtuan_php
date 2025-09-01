<meta name="csrf-token" content="{{ csrf_token() }}">

<!-- Toast 提示框容器 -->
<div id="toastContainer" style="position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px;">
    <div id="toastAlert" class="alert alert-dismissible fade" role="alert" style="display: none;">
        <span id="toastMessage"></span>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
</div>

<div class="box">
    <div class="box-header with-border">
        <h3 class="box-title">京东商品查询</h3>
        <div class="box-tools pull-right">
                    <a href="/admin/jdgoods" class="btn btn-sm btn-success">
                        <i class="fa fa-list"></i> 商品列表
                </a>
        </div>
                </div>
              
    <div class="box-body">
                    <!-- 商品链接输入区域 -->
                    <div class="form-group">
                        <label for="goodsKeyword">商品链接或关键词：</label>
                        <textarea class="form-control" id="goodsKeyword" rows="3"  placeholder="【京东】https://u.jd.com/YO58R3K「爱他美澳洲白金2段6罐 社群领券」"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <button type="button" class="btn btn-primary" id="queryBtn">
                            <i class="fa fa-search"></i> 查询商品
                        </button>
                    </div>

                    <!-- 加载提示 -->
                    <div id="loadingDiv" class="text-center" style="display: none;">
                        <i class="fa fa-spinner fa-spin fa-2x"></i>
                        <p>正在查询商品信息...</p>
                    </div>

                    <!-- 商品信息显示区域 -->
                    <div id="goodsInfoDiv" style="display: none;">
                        <div class="card mt-4">
                            <div class="card-header">
                                <h5 class="card-title  " style="padding: 10px;">
                                     商品详情
                                </h5>
                            </div>
                            <div class="card-body p-0">
                                <div id="goodsDetails">
                                    <!-- 商品详情模板 -->
                                    <div class="card border-0 shadow-sm" id="goodsDetailsTemplate" style="display: none;">
                                        <div class="card-body p-4">
                                            <div class="row">
                                                <div class="col-md-3 col-sm-4">
                                                    <div class="text-center mb-3">
                                                        <img id="goodsImage" src="" alt="商品主图" 
                                                             class="img-fluid rounded shadow-sm" 
                                                             style="max-width:500px;max-height:50px;object-fit:cover;"
                                                             onerror="this.style.display='none'">
                                                    </div>
                                                </div>
                                                <div class="col-md-9 col-sm-8">
                                                    <h5 class="card-title text-primary mb-3 fw-bold" id="goodsTitle">商品名称</h5>
                                                    
                                                    <div class="row g-3">
                                                        <div class="col-md-6">
                                                            <div class="d-flex align-items-center">
                                                                <span class="badge bg-danger me-2">现价</span>
                                                                <span class="h5 text-danger mb-0 fw-bold" id="currentPrice">¥0</span>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="d-flex align-items-center">
                                                                <span class="badge bg-secondary me-2">原价</span>
                                                                <span class="h6 text-muted mb-0 text-decoration-line-through" id="originalPrice">¥0</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="mt-3" id="shopInfo" style="display: none;">
                                                        <div class="d-flex align-items-center">
                                                            <i class="fas fa-store text-info me-2"></i>
                                                            <span class="text-muted" id="shopName"></span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
 

                    <div class="shareCopywritingDiv mt-4" style="display: none;">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title  " style="padding: 10px;">
                                    分享文案
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label for="shareCopywriting" class="form-label fw-bold">文案内容：</label>
                                    <textarea class="form-control" id="shareCopywriting" rows="4" 
                                              placeholder="请输入商品分享文案，支持使用商品信息变量...">请输入商品分享文案，支持使用商品信息变量</textarea>
                                </div>
                                <div class="mt-3">
                                    <button type="button" class="btn btn-primary" id="saveBtn">
                                        <i class="fas fa-save me-2"></i>保存商品信息
                                    </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 错误提示模态框 -->
<div class="modal fade" id="errorModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">错误提示</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p id="errorMessage"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">关闭</button>
            </div>
        </div>
    </div>
</div>


<script>
 

$(document).ready(function() {
    // 添加全局错误处理
    window.addEventListener('error', function(e) {
        console.error('JavaScript错误:', e.error);
        console.error('错误文件:', e.filename);
        console.error('错误行号:', e.lineno);
        console.error('错误列号:', e.colno);
    });

    // 添加未处理的Promise拒绝处理
    window.addEventListener('unhandledrejection', function(e) {
        console.error('未处理的Promise拒绝:', e.reason);
    });

    console.log('页面加载完成，开始初始化...');
    
    // 查询商品按钮点击事件
    $('#queryBtn').click(function() {
        const keyword = $('#goodsKeyword').val().trim();
       
        if (!keyword) {
            showError('请输入商品链接或关键词');
            return;
        }

        // 显示加载状态
        $('#loadingDiv').show();
        $('#goodsInfoDiv').hide();

        // 发送AJAX请求
        $.ajax({
            url: '/admin/jdgoods/query',
            type: 'POST',
            data: {
                keyword: keyword,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            dataType: 'json',
            success: function(response) {
                $('#loadingDiv').hide();
                if (response.status === 'success') {
                    displayGoodsInfo(response.goodsResult);
                } else {
                    showError(response.msg);
                }
            },
            error: function(xhr, status, error) {
                $('#loadingDiv').hide();
                showError('网络错误，请稍后重试');
            }
        });
    });

   
    // 显示错误信息
    function showError(message) {
        $('#errorMessage').text(message);
        $('#errorModal').modal('show');
    }

    // 显示提示消息
    function showToast(message, type = 'info') {
        // 设置消息内容
        $('#toastMessage').text(message);
        
        // 设置提示框样式类型
        const $toastAlert = $('#toastAlert');
        $toastAlert.removeClass('alert-success alert-danger alert-info');
        
        const alertClass = type === 'success' ? 'alert-success' : 
                          type === 'error' ? 'alert-danger' : 'alert-info';
        $toastAlert.addClass(alertClass);
        
        // 显示提示框
        $toastAlert.addClass('show').show();
        
        // 3秒后自动消失
        setTimeout(function() {
            $toastAlert.fadeOut(500, function() {
                $toastAlert.removeClass('show').hide();
            });
        }, 3000);
    }

    // 回车键触发查询
    $('#goodsKeyword').keypress(function(e) {
        if (e.which === 13) {
            $('#queryBtn').click();
        }
    });

    // 保存按钮点击事件
    $('#saveBtn').click(function() {
        try {
            // 检查是否有商品信息
            if (!$('#goodsInfoDiv').is(':visible')) {
                showError('请先查询商品信息');
                return;
            }

            // 获取分享文案
            const shareCopywriting = $('#shareCopywriting').val().trim();
            if (!shareCopywriting) {
                showError('请输入分享文案');
                return;
            }

            // 获取商品信息（从全局变量或DOM中获取）
            let goodsInfo = null;
            
            // 尝试从全局变量获取商品信息
            if (typeof window.currentGoodsInfo !== 'undefined' && window.currentGoodsInfo) {
                goodsInfo = window.currentGoodsInfo;
                console.log('获取到商品信息:', goodsInfo); // 调试信息
            } else {
                showError('商品信息获取失败，请重新查询');
                return;
            }

            // 验证商品信息的完整性
            if (!goodsInfo.skuName || !goodsInfo.purchasePriceInfo) {
                showError('商品信息不完整，请重新查询');
                return;
            }

            console.log('准备保存的数据:', { goodsInfo, shareCopywriting }); // 调试信息

            // 显示保存中状态
            const $saveBtn = $(this);
            const originalText = $saveBtn.html();
            $saveBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>保存中...');

            // 发送保存请求
            $.ajax({
                url: '/admin/jdgoods/save',
                type: 'POST',
                data: {
                    goodsInfo: goodsInfo,
                    shareCopywriting: shareCopywriting,
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                dataType: 'json',
                timeout: 30000, // 30秒超时
                success: function(response) {
                    console.log('保存响应:', response); // 调试信息
                    $saveBtn.prop('disabled', false).html(originalText);
              
                    if (response.status === 'success') {
                        // showToast(response.msg, 'success');
                        alert(response.msg);
                        // 保存成功后清空分享文案
                        $('#shareCopywriting').val('');
                        // 可选：隐藏分享文案区域
                        $("div.shareCopywritingDiv").hide();
                    } else {
                        showError(response.msg || '保存失败');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('保存请求错误:', { xhr, status, error }); // 调试信息
                    $saveBtn.prop('disabled', false).html(originalText);
                    
                    if (status === 'timeout') {
                        showError('请求超时，请稍后重试');
                    } else if (xhr.status === 0) {
                        showError('网络连接失败，请检查网络');
                    } else {
                        showError('网络错误，请稍后重试');
                    }
                }
            });
        } catch (error) {
            console.error('保存按钮点击事件发生错误:', error);
            showError('保存过程中发生错误: ' + error.message);
        }
    });
});

 
 
 // 显示商品信息
 function displayGoodsInfo(goodsInfo) {
        try {
            // 将商品信息保存到全局变量，供保存按钮使用
            window.currentGoodsInfo = goodsInfo;
            
            // 验证商品信息
            if (!goodsInfo || typeof goodsInfo !== 'object') {
                console.error('商品信息无效:', goodsInfo);
                showError('商品信息格式错误');
                return;
            }
            
            console.log('开始显示商品信息:', goodsInfo);
      
            // 使用模板显示商品信息
            $('#goodsImage').attr('src', goodsInfo.imageInfo && goodsInfo.imageInfo.whiteImage ? goodsInfo.imageInfo.whiteImage : '');
 
            $('#goodsImage').attr('style', 'display:block;width:100px;height:100px;');
            $('#goodsTitle').text(goodsInfo.skuName || '未知商品');
            $('#currentPrice').text('¥' + (goodsInfo.purchasePriceInfo && goodsInfo.purchasePriceInfo.purchasePrice ? goodsInfo.purchasePriceInfo.purchasePrice : '0'));
            $('#originalPrice').text('¥' + (goodsInfo.purchasePriceInfo && goodsInfo.purchasePriceInfo.thresholdPrice ? goodsInfo.purchasePriceInfo.thresholdPrice : '0'));
            
            // 处理店铺信息
            if (goodsInfo.shopInfo && goodsInfo.shopInfo.shopName) {
                $('#shopName').text(goodsInfo.shopInfo.shopName);
                $('#shopInfo').show();
            } else {
                $('#shopInfo').hide();
            }
            
            // 显示模板和相关区域
            $('#goodsDetailsTemplate').show();
            $('#goodsInfoDiv').show();
            $("div.shareCopywritingDiv").show();
            
            console.log('商品信息显示完成');
        } catch (error) {
            console.error('显示商品信息时发生错误:', error);
            showError('显示商品信息时发生错误: ' + error.message);
        }
    }


 
</script>

<style>
.card {
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    border: none;
}

.card-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 12px 12px 0 0 !important;
    border: none;
}

.card-title {
    margin-bottom: 0;
    font-weight: 600;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    border-radius: 8px;
    padding: 10px 24px;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
}

.form-control {
    border-radius: 8px;
    border: 2px solid #e9ecef;
    transition: all 0.3s ease;
}

.form-control:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}

.badge {
    border-radius: 6px;
    font-weight: 500;
}

.shadow-sm {
    box-shadow: 0 2px 8px rgba(0,0,0,0.1) !important;
}

.alert {
    border-radius: 8px;
    border: none;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.btn-outline-primary, .btn-outline-success {
    border-radius: 6px;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn-outline-primary:hover, .btn-outline-success:hover {
    transform: translateY(-1px);
}

#loadingDiv {
    padding: 40px 0;
}

#loadingDiv .fa-spinner {
    color: #667eea;
}

.text-primary {
    color: #667eea !important;
}

.text-danger {
    color: #dc3545 !important;
}

.bg-danger {
    background-color: #dc3545 !important;
}

.bg-secondary {
    background-color: #6c757d !important;
}
</style>