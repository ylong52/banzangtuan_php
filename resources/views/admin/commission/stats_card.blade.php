{{-- 查询按钮区域 --}}
<div  style="margin-bottom: 20px;margin-left: 42%;">
     
        <div class="form-group">
            
            <div>
                <button type="button" id="query_user_stats" class="btn btn-primary">
                    <i class="fa fa-search"></i> 查询用户统计
                </button>
            </div>
        </div>
    
</div>

{{-- 查询按钮的JavaScript代码 --}}
<script>
$(document).ready(function() {
    // 监听查询按钮点击事件
    // 防止重复点击的标记
    var isQuerying = false;
    
    $(document).on("click", "#query_user_stats", function(e) {
        // 阻止事件冒泡
        e.preventDefault();
        e.stopPropagation();
        
        // 如果正在查询中，直接返回
        if (isQuerying) {
            return false;
        }
        
        var userId = $("select[name=user_id]").val();
        if (!userId) {
            // 如果没有选择用户，显示提示
            toastr.error("请先选择用户");
            return false;
        }
        
        // 设置查询状态
        isQuerying = true;
        
        // 显示加载状态
        var $btn = $(this);
        var originalText = $btn.html();
        $btn.html('<i class="fa fa-spinner fa-spin"></i> 查询中...');
        $btn.prop("disabled", true);
        
        $.post("/admin/user-commission-settlement/getUserStats", {
            q: userId,
            _token: $("meta[name=csrf-token]").attr("content")
        }, function(data) {
            if (data.html) {
                $("#stats_card").html(data.html);
                toastr.success("查询成功");
            }
        }).fail(function(xhr, status, error) {
            console.error("获取用户统计失败:", error);
            toastr.error("查询失败，请重试");
        }).always(function() {
            // 恢复按钮状态
            $btn.html(originalText);
            $btn.prop("disabled", false);
            // 重置查询状态
            isQuerying = false;
        });
        
        return false;
    });
});
</script>

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-bar-chart"></i> {{ date('Y-m', strtotime('last month')) }}佣金统计概览</h3>
    </div>
    <div class="box-body">
        <div class="row" style="margin-top: 20px;">
            <div class="col-md-6">
                <div class="info-box">
                    <span class="info-box-icon bg-blue"><i class="fa fa-line-chart"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text" style="font-size: 14px; color: #0073aa; font-weight: bold; display: block; height: 30px; line-height: 30px;">
                            上月预估总佣金&nbsp;&nbsp;&nbsp;¥{{ number_format($stats['last_month_estimate_fee'], 2) }}
                        </span>
                        <span class="info-box-text" style="font-size: 14px; color: #0073aa; font-weight: bold; display: block; height: 30px; line-height: 30px;">
                            上月实际总佣金&nbsp;&nbsp;&nbsp;¥{{ number_format($stats['last_month_actual_fee'], 2) }}
                        </span>
                        <span class="info-box-text" style="font-size: 14px; color: #0073aa; font-weight: bold; display: block; height: 30px; line-height: 30px;">
                            上月订单总数&nbsp;&nbsp;&nbsp;{{ $stats['last_month_order_count'] }}
                        </span>
                    </div>
                </div>
            </div>
         
            <div class="col-md-6">
                <div class="info-box">
                    <span class="info-box-icon bg-blue"><i class="fa fa-line-chart"></i></span>
                    <div class="info-box-content">
                        
                        <span class="info-box-text" style="font-size: 14px; color: #0073aa; font-weight: bold; display: block; height: 30px; line-height: 30px;">
                            身份证号&nbsp;&nbsp;&nbsp;{{ empty($userinfo) ? '-' : ($userinfo['id_card']) }}
                        </span>
                        <span class="info-box-text" style="font-size: 14px; color: #0073aa; font-weight: bold; display: block; height: 30px; line-height: 30px;">
                            开户行姓名&nbsp;&nbsp;&nbsp;{{ empty($userinfo) ? '-' : ($userinfo['bank_real_name']) }}
                        </span>
                        <span class="info-box-text" style="font-size: 14px; color: #0073aa; font-weight: bold; display: block; height: 30px; line-height: 30px;">
                            银行卡号&nbsp;&nbsp;&nbsp;{{ empty($userinfo) ? '-' : ($userinfo['bank_card']) }}
                        </span>
                        <span class="info-box-text" style="font-size: 14px; color: #0073aa; font-weight: bold; display: block; height: 30px; line-height: 30px;">
                            银行预留电话&nbsp;&nbsp;&nbsp;{{ empty($userinfo) ? '-' : ($userinfo['bank_phone']) }}
                        </span>
                      
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
