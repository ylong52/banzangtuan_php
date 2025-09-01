@extends('admin::layouts.content')

@section('content')
<div class="row">
    <div class="col-md-12">
        {!! $content !!}
    </div>
</div>

<!-- 详情弹窗 -->
<div class="modal fade" id="promotionDetailModal" tabindex="-1" role="dialog" aria-labelledby="promotionDetailModalLabel">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="promotionDetailModalLabel">推荐奖励详情</h4>
            </div>
            <div class="modal-body" id="promotionDetailContent">
                <!-- 内容将通过 AJAX 加载 -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">关闭</button>
            </div>
        </div>
    </div>
</div>

<script>
function showPromotionDetail(id) {
    // 显示加载状态
    $('#promotionDetailContent').html('<div class="text-center"><i class="fa fa-spinner fa-spin"></i> 加载中...</div>');
    $('#promotionDetailModal').modal('show');
    
    // 通过 AJAX 加载详情
    $.get('{{ admin_url("promotion") }}/' + id, function(data) {
        $('#promotionDetailContent').html(data);
    }).fail(function() {
        $('#promotionDetailContent').html('<div class="alert alert-danger">加载失败，请重试</div>');
    });
}

// 页面加载完成后初始化
$(document).ready(function() {
    // 可以在这里添加其他初始化代码
});
</script>
@endsection 