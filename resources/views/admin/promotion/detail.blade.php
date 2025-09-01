<div class="table-responsive">
    <table class="table table-bordered table-striped">
        <tbody>
            <tr>
                <th style="width: 150px;">序号</th>
                <td>{{ $promotion->id }}</td>
            </tr>
            <tr>
                <th>注册用户</th>
                <td>{{ $promotion->user->username ?? '未知用户' }}</td>
            </tr>
            <tr>
                <th>推荐人</th>
                <td>{{ $promotion->referrer->username ?? '未知推荐人' }}</td>
            </tr>
            <tr>
                <th>推荐码</th>
                <td>{{ $promotion->referral_code }}</td>
            </tr>
            <tr>
                <th>注册时间</th>
                <td>{{ $promotion->registration_time ? date('Y-m-d H:i:s', strtotime($promotion->registration_time)) : '-' }}</td>
            </tr>
            <tr>
                <th>奖励金额</th>
                <td>¥{{ number_format($promotion->reward_amount, 2) }}</td>
            </tr>
            <tr>
                <th>奖励状态</th>
                <td>
                    @switch($promotion->reward_status)
                        @case(0)
                            <span class="label label-warning">待发放</span>
                            @break
                        @case(1)
                            <span class="label label-success">已发放</span>
                            @break
                        @case(2)
                            <span class="label label-danger">已失效</span>
                            @break
                        @default
                            <span class="label label-default">未知</span>
                    @endswitch
                </td>
            </tr>
            <tr>
                <th>奖励发放时间</th>
                <td>{{ $promotion->reward_time ? date('Y-m-d H:i:s', strtotime($promotion->reward_time)) : '-' }}</td>
            </tr>
            <tr>
                <th>创建时间</th>
                <td>{{ $promotion->created_at ? date('Y-m-d H:i:s', strtotime($promotion->created_at)) : '' }}</td>
            </tr>
            <tr>
                <th>更新时间</th>
                <td>{{ $promotion->updated_at ? date('Y-m-d H:i:s', strtotime($promotion->updated_at)) : '' }}</td>
            </tr>
        </tbody>
    </table>
</div> 