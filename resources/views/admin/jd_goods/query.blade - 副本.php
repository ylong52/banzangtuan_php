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

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title " style="padding: 10px;">
                         京东商品查询
                    </h3>
                    <a href="/admin/jdgoods" class="btn btn-sm btn-success">
                        <i class="fa fa-list"></i> 商品列表
                </a>
                </div>
              
                <div class="card-body">
                    <!-- 商品链接输入区域 -->
                    <div class="form-group">
                        <label for="goodsKeyword">商品链接或关键词：</label>
                        <textarea class="form-control" id="goodsKeyword" rows="3"  placeholder="【京东】https://u.jd.com/YO58R3K「爱他美澳洲白金2段6罐 社群领券」">【京东】https://u.jd.com/YO58R3K「爱他美澳洲白金2段6罐 社群领券」</textarea>
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
                                <div id="goodsDetails"></div>
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

