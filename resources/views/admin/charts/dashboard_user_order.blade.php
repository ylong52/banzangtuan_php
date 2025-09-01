<style>
.dashboard-card {
    max-width: 800px;
    margin: 30px auto 0;
    background: rgba(255,255,255,0.95);
    border-radius: 18px;
    box-shadow: 0 4px 24px rgba(0,0,0,0.10);
    padding: 24px 18px 18px 18px;
    font-family: 'San Francisco', 'Arial', 'PingFang SC', 'Hiragino Sans GB', 'Microsoft YaHei', sans-serif;
}
.dashboard-title {
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    font-weight: 600;
    color: #222;
    margin-bottom: 20px;
    letter-spacing: 1px;
}
.dashboard-title .icon {
    width: 22px;
    height: 22px;
    margin-right: 8px;
    vertical-align: middle;
}
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}
.stat-item {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 15px;
    border-radius: 12px;
    text-align: center;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}
.stat-item.users {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}
.stat-item.orders {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
}
.stat-number {
    font-size: 24px;
    font-weight: bold;
    margin-bottom: 5px;
}
.stat-label {
    font-size: 12px;
    opacity: 0.9;
}
</style>

<div class="dashboard-card">
    <div class="dashboard-title">
        <svg class="icon" viewBox="0 0 24 24" fill="#007AFF">
            <path d="M16.365 1.43c0 1.14-.93 2.07-2.07 2.07-1.14 0-2.07-.93-2.07-2.07 0-1.14.93-2.07 2.07-2.07 1.14 0 2.07.93 2.07 2.07zm-2.07 3.45c2.28 0 4.14 1.86 4.14 4.14 0 2.28-1.86 4.14-4.14 4.14-2.28 0-4.14-1.86-4.14-4.14 0-2.28 1.86-4.14 4.14-4.14zm7.2 7.2c.12.18.18.39.18.6 0 .33-.13.65-.36.88l-2.13 2.13c-.23.23-.55.36-.88.36-.21 0-.42-.06-.6-.18l-2.13-2.13c-.23-.23-.36-.55-.36-.88 0-.21.06-.42.18-.6l2.13-2.13c.23-.23.55-.36.88-.36.21 0 .42.06.6.18l2.13 2.13zm-7.2 2.07c-2.28 0-4.14 1.86-4.14 4.14 0 2.28 1.86 4.14 4.14 4.14 2.28 0 4.14-1.86 4.14-4.14 0-2.28-1.86-4.14-4.14-4.14zm-7.2-7.2c-.12-.18-.18-.39-.18-.6 0-.33.13-.65.36-.88l2.13-2.13c.23-.23.55-.36.88-.36.21 0 .42.06.6.18l2.13 2.13c.23.23.36.55.36.88 0 .21-.06.42-.18.6l-2.13 2.13c-.23.23-.55.36-.88.36-.21 0-.42-.06-.6-.18l-2.13-2.13zm7.2-2.07c2.28 0 4.14 1.86 4.14 4.14 0 2.28-1.86 4.14-4.14 4.14-2.28 0-4.14-1.86-4.14-4.14 0-2.28 1.86-4.14 4.14-4.14z"/>
        </svg>
        系统统计概览
    </div>
    
    <div class="stats-grid">
        <div class="stat-item users">
            <div class="stat-number">{{ $todayUsers ?? 0 }}</div>
            <div class="stat-label">今日新增会员</div>
        </div>
        <div class="stat-item users">
            <div class="stat-number">{{ $yesterdayUsers ?? 0 }}</div>
            <div class="stat-label">昨日新增会员</div>
        </div>
        <div class="stat-item users">
            <div class="stat-number">{{ $dayBeforeYesterdayUsers ?? 0 }}</div>
            <div class="stat-label">前日新增会员</div>
        </div>
        <div class="stat-item users">
            <div class="stat-number">{{ $lastWeekUsers ?? 0 }}</div>
            <div class="stat-label">上周新增会员</div>
        </div>
        <div class="stat-item orders">
            <div class="stat-number">{{ $todayOrders ?? 0 }}</div>
            <div class="stat-label">今日新增订单</div>
        </div>
        <div class="stat-item orders">
            <div class="stat-number">{{ $yesterdayOrders ?? 0 }}</div>
            <div class="stat-label">昨日新增订单</div>
        </div>
        <div class="stat-item orders">
            <div class="stat-number">{{ $dayBeforeYesterdayOrders ?? 0 }}</div>
            <div class="stat-label">前日新增订单</div>
        </div>
        <div class="stat-item orders">
            <div class="stat-number">{{ $lastWeekOrders ?? 0 }}</div>
            <div class="stat-label">上周新增订单</div>
        </div>
    </div>
    
    <canvas id="dashboardChart" width="700" height="200"></canvas>
</div>

<script>
setTimeout(function() {
    if (typeof Chart !== 'undefined') {
        var ctx = document.getElementById('dashboardChart');
        if (ctx) {
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['今日', '昨日', '前日', '上周'],
                    datasets: [{
                        label: '新增会员',
                        data: [
                            {{ $todayUsers ?? 0 }}, 
                            {{ $yesterdayUsers ?? 0 }}, 
                            {{ $dayBeforeYesterdayUsers ?? 0 }}, 
                            {{ $lastWeekUsers ?? 0 }}
                        ],
                        backgroundColor: 'rgba(255, 99, 132, 0.8)',
                        borderRadius: 8,
                        barPercentage: 0.6,
                        categoryPercentage: 0.7
                    }, {
                        label: '新增订单',
                        data: [
                            {{ $todayOrders ?? 0 }}, 
                            {{ $yesterdayOrders ?? 0 }}, 
                            {{ $dayBeforeYesterdayOrders ?? 0 }}, 
                            {{ $lastWeekOrders ?? 0 }}
                        ],
                        backgroundColor: 'rgba(54, 162, 235, 0.8)',
                        borderRadius: 8,
                        barPercentage: 0.6,
                        categoryPercentage: 0.7
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { 
                            display: true,
                            position: 'top',
                            labels: {
                                font: {
                                    family: 'San Francisco,Arial,sans-serif',
                                    size: 12
                                }
                            }
                        },
                        title: { display: false }
                    },
                    layout: {
                        padding: { top: 10, bottom: 10, left: 10, right: 10 }
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            ticks: { 
                                color: '#666', 
                                font: { 
                                    family: 'San Francisco,Arial,sans-serif', 
                                    size: 12 
                                } 
                            }
                        },
                        y: {
                            beginAtZero: true,
                            grid: { color: 'rgba(200,200,200,0.2)' },
                            ticks: { 
                                color: '#666', 
                                font: { 
                                    family: 'San Francisco,Arial,sans-serif', 
                                    size: 11 
                                }, 
                                stepSize: 1 
                            }
                        }
                    }
                }
            });
        }
    }
}, 500);
</script> 