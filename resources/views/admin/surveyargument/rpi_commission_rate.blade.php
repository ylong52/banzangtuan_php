@extends('admin::index')

@section('content')
<div class="box box-info">
    <div class="box-header with-border">
        <h3 class="box-title">员工折算比例设置(全局)</h3>
    </div>
    
    <div class="box-body">
        <form id="rpi-commission-form" class="form-horizontal">
            {{ csrf_field() }}
            <div class="form-group">
                <label for="rpi_commission_rate" class="col-sm-2 control-label">员工折算比例</label>
                <div class="col-sm-8">
                    <input type="number" step="0.01" min="0" class="form-control" id="rpi_commission_rate" name="rpi_commission_rate" value="{{ $currentValue }}" placeholder="请输入员工折算比例">
                </div>
            </div>
            
            <div class="form-group">
                <div class="col-sm-offset-2 col-sm-8">
                    <button type="submit" class="btn btn-primary">保存</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
$(function() {
    $('#rpi-commission-form').submit(function(e) {
        e.preventDefault();
        
        $.ajax({
            url: '{{ admin_url("surveyarg/save-rpi-commission-rate") }}',
            type: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                if (response.status) {
                    toastr.success(response.message);
                } else {
                    toastr.error(response.message);
                }
            },
            error: function() {
                toastr.error('服务器错误');
            }
        });
    });
});
</script>
@endsection