@section('content')
<div class="box">
    <div class="box-header with-border">
        <h3 class="box-title">最新消息</h3>
        <div class="row">
            <!-- 问卷查询区域 -->
            <div class="analysis-search-container" style="margin-bottom: 15px; padding: 10px; border: 1px solid #ddd; border-radius: 4px; background-color: #f9f9f9;">
                <div style="display: flex; gap: 15px; align-items: center; flex-wrap: wrap;">
                    <div>
                        <label for="analysis-survey-number" style="margin-right: 5px;">问卷编号:</label>
                        <input type="text" id="analysis-survey-number" placeholder="请输入问卷编号" style="padding: 5px; border: 1px solid #ccc; border-radius: 3px; width: 150px;">
                    </div>
                    <div>
                        <label for="analysis-mid" style="margin-right: 5px;">MID:</label>
                        <input type="text" id="analysis-mid" placeholder="请输入MID" style="padding: 5px; border: 1px solid #ccc; border-radius: 3px; width: 150px;">
                    </div>
                    <div>
                        <button id="analysis-search-btn" style="padding: 6px 12px; background-color: #007bff; color: white; border: none; border-radius: 3px; cursor: pointer;">查询</button>
                        <button id="analysis-reset-btn" style="padding: 6px 12px; background-color: #6c757d; color: white; border: none; border-radius: 3px; cursor: pointer; margin-left: 5px;">重置</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="left">
                <table>
                    <colgroup>
                        <col width="10%">                     
                        <col width="20%">                      
                        <col width="8%">
                        <col width="8%">
                        <col width="8%">
                        <col width="8%">
                        <col width="8%">
                        <col width="20%">
                    </colgroup>
                    <thead>
                        <th>问卷编号</th>                      
                        <th>MID</th>                        
                        <th>会员金额</th>
                        <th>RIP金额</th>
                        <th>用户名</th>
                        <th>团队</th>
                        <th>耗时</th>
                        <th>完成时间</th>
                    </thead>    
                    <tbody>
                        <!-- 数据将通过AJAX加载 -->
                        <tr><td colspan="8" style="text-align: center;">加载中...</td></tr>
                    </tbody>
                </table>

                <div id="pagination-container" class="pagination-container">
                    <button id="prev-page" class="pagination-btn">上一页</button>
                    <span id="page-info">第 <span id="current-page">1</span> 页，共 <span id="total-pages">0</span> 页</span>
                    <button id="next-page" class="pagination-btn">下一页</button>
                    <select id="page-size-selector">
                        <option value="15">15条/页</option>
                        <option value="30">30条/页</option>
                        <option value="50">50条/页</option>
                        <option value="100">100条/页</option>
                    </select>
                </div>
            </div>
            <div class="right">
                <table>
                    <colgroup>
                        <col width="20%">
                        <col width="20%">
                        <col width="10%">
                        <col width="10%">
                        <col width="20%">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>问卷编号</th>
                            <th>用户名</th>
                            <th>金额</th>
                            <th>原金额</th>
                            <th>完成时间</th>
                        </tr>
                    </thead>
                    <tbody> 
                        <!-- 数据将通过AJAX加载 -->
                        <tr><td colspan="5" style="text-align: center;">加载中...</td></tr>
                    </tbody>
                </table>
                <!-- 添加分页控件 -->
                <div id="analysis-pagination-container" class="pagination-container">
                    <button id="analysis-prev-page" class="pagination-btn">上一页</button>
                    <span id="analysis-page-info">第 <span id="analysis-current-page">1</span> 页，共 <span id="analysis-total-pages">0</span> 页</span>
                    <button id="analysis-next-page" class="pagination-btn">下一页</button>
                    <select id="analysis-page-size-selector">
                        <option value="15">15条/页</option>
                        <option value="30">30条/页</option>
                        <option value="50">50条/页</option>
                        <option value="100">100条/页</option>
                    </select>
                </div>
            </div>

            <script>
            document.addEventListener('DOMContentLoaded', function() {
                // 右侧分析表变量
                let analysisCurrentPage = 1;
                let analysisPageSize = 15;
                let analysisLastPage = 1;
                
                // 左侧主表变量
                let currentPage = 1;
                let pageSize = 15;
                let totalPages = 1;

                // 更新右侧分析表页面信息显示
                function updateAnalysisPageInfo() {
                    document.getElementById('analysis-current-page').textContent = analysisCurrentPage;
                    document.getElementById('analysis-total-pages').textContent = analysisLastPage;
                    document.getElementById('analysis-prev-page').disabled = analysisCurrentPage === 1;
                    document.getElementById('analysis-next-page').disabled = analysisCurrentPage === analysisLastPage;
                }
                
                // 更新左侧主表页面信息显示
                function updatePageInfo() {
                    document.getElementById('current-page').textContent = currentPage;
                    document.getElementById('total-pages').textContent = totalPages;
                    document.getElementById('prev-page').disabled = currentPage <= 1;
                    document.getElementById('next-page').disabled = currentPage >= totalPages;
                }

                // 加载右侧分析表数据
                function loadAnalysisData() {
                    const surveyNumber = document.getElementById('analysis-survey-number').value.trim();
                    const mid = document.getElementById('analysis-mid').value.trim();
                    
                    let queryParams = `page=${analysisCurrentPage}&pageSize=${analysisPageSize}&ajax=1`;
                    if (surveyNumber) {
                        queryParams += `&survey_number=${encodeURIComponent(surveyNumber)}`;
                    }
                    if (mid) {
                        queryParams += `&mid=${encodeURIComponent(mid)}`;
                    }
                    
                    fetch(`/admin/sampliciosurveyresponse/questionnaire_analysis_list?${queryParams}`)
                    .then(response => response.json())
                    .then(data => {
                        const analysisTbody = document.querySelector('.right table tbody');
                        if (data.success && analysisTbody) {
                            analysisTbody.innerHTML = '';
                            
                            if (data.data && data.data.length > 0) {
                                data.data.forEach(item => {
                                    analysisTbody.innerHTML += `
                                        <tr>
                                            <td>${item.survey_number || ''}</td>
                                            <td>${item.user_name || ''}</td>
                                            <td>${item.amount || ''}</td>
                                            <td>${item.rpi || ''}</td>
                                            <td>${item.created_at || ''}</td>
                                        </tr>
                                    `;
                                });
                            } else {
                                analysisTbody.innerHTML = '<tr><td colspan="5" style="text-align: center;">暂无数据</td></tr>';
                            }
                            
                            analysisLastPage = data.last_page || 1;
                            updateAnalysisPageInfo();
                        }
                    })
                    .catch(error => {
                        console.error('加载分析数据失败:', error);
                        const analysisTbody = document.querySelector('.right table tbody');
                        if (analysisTbody) {
                            analysisTbody.innerHTML = '<tr><td colspan="5" style="text-align: center; color: red;">加载数据失败</td></tr>';
                        }
                    });
                }
                
                // 加载左侧主表数据
                function loadMainData() {
                    const surveyNumber = document.getElementById('analysis-survey-number').value.trim();
                    const mid = document.getElementById('analysis-mid').value.trim();
                    
                    let queryParams = `page=${currentPage}&pageSize=${pageSize}&ajax=1`;
                    if (surveyNumber) {
                        queryParams += `&survey_number=${encodeURIComponent(surveyNumber)}`;
                    }
                    if (mid) {
                        queryParams += `&mid=${encodeURIComponent(mid)}`;
                    }
                    
                    fetch(`/admin/sampliciosurveyresponse/response_list?${queryParams}`)
                    .then(response => response.json())
                    .then(data => {
                        const mainTbody = document.querySelector('.left table tbody');
                        if (data.success && mainTbody) {
                            mainTbody.innerHTML = '';
                            
                            if (data.data && data.data.length > 0) {
                                data.data.forEach(item => {
                                    mainTbody.innerHTML += `
                                        <tr>
                                            <td>${item.survey_number || ''}</td>
                                            <td>${item.mid || ''}</td>
                                            <td>${item.rpi_commission_amount || ''}</td>
                                            <td>${item.rpi || ''}</td>
                                            <td>${item.user_name || ''}</td>
                                            <td>${item.user_team_name || ''}</td>
                                            <td>${item.time_difference || ''}</td>
                                            <td>${item.response_end_time || ''}</td>
                                        </tr>
                                    `;
                                });
                            } else {
                                mainTbody.innerHTML = '<tr><td colspan="8" style="text-align: center;">暂无数据</td></tr>';
                            }
                            
                            totalPages = data.last_page || 1;
                            updatePageInfo();
                        }
                    })
                    .catch(error => {
                        console.error('加载主表数据失败:', error);
                        const mainTbody = document.querySelector('.left table tbody');
                        if (mainTbody) {
                            mainTbody.innerHTML = '<tr><td colspan="8" style="text-align: center; color: red;">加载数据失败</td></tr>';
                        }
                    });
                }
                
                // 同时加载两个表格的数据
                function loadAllData() {
                    loadAnalysisData();
                    loadMainData();
                }

                // 查询按钮事件
                document.getElementById('analysis-search-btn').addEventListener('click', function() {
                    analysisCurrentPage = 1;
                    currentPage = 1;
                    loadAllData();
                });

                // 重置按钮事件
                document.getElementById('analysis-reset-btn').addEventListener('click', function() {
                    document.getElementById('analysis-survey-number').value = '';
                    document.getElementById('analysis-mid').value = '';
                    analysisCurrentPage = 1;
                    currentPage = 1;
                    loadAllData();
                });

                // 右侧分析表分页事件
                document.getElementById('analysis-prev-page').addEventListener('click', function() {
                    if (analysisCurrentPage > 1) {
                        analysisCurrentPage--;
                        loadAnalysisData();
                    }
                });

                document.getElementById('analysis-next-page').addEventListener('click', function() {
                    if (analysisCurrentPage < analysisLastPage) {
                        analysisCurrentPage++;
                        loadAnalysisData();
                    }
                });

                document.getElementById('analysis-page-size-selector').addEventListener('change', function() {
                    analysisPageSize = parseInt(this.value);
                    analysisCurrentPage = 1;
                    loadAnalysisData();
                });

                // 左侧主表分页事件
                document.getElementById('prev-page').addEventListener('click', function() {
                    if (currentPage > 1) {
                        currentPage--;
                        loadMainData();
                    }
                });

                document.getElementById('next-page').addEventListener('click', function() {
                    if (currentPage < totalPages) {
                        currentPage++;
                        loadMainData();
                    }
                });

                document.getElementById('page-size-selector').addEventListener('change', function() {
                    pageSize = parseInt(this.value);
                    currentPage = 1;
                    loadMainData();
                });

                // 回车键搜索
                document.getElementById('analysis-survey-number').addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        analysisCurrentPage = 1;
                        currentPage = 1;
                        loadAllData();
                    }
                });

                document.getElementById('analysis-mid').addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        analysisCurrentPage = 1;
                        currentPage = 1;
                        loadAllData();
                    }
                });

                // 页面加载完成后初始化数据
                loadAllData();
            });
            </script>
        </div>
    </div>
</div>

<!-- 保持原有的CSS样式 -->
<style>
.row {
    display: flex;
    width: 100%;
    margin: 0;
    padding: 0;
}

.left {
    width: 70%;
    background-color: #f5f5f7;
    overflow-x: auto;
    padding: 15px;
    border-radius: 8px 0 0 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.right {
    width: 30%;
    background-color: #e8e8ed;
    padding: 15px;
    border-radius: 0 8px 8px 0;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

table {
    width: 100%;
    border-collapse: collapse;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
}

th {
    background: linear-gradient(to bottom, #f7f7f7, #e5e5e5);
    color: #333;
    font-weight: 500;
    text-align: left;
    padding: 12px 15px;
    border-bottom: 1px solid #d1d1d6;
    font-size: 14px;
    letter-spacing: -0.01em;
    text-shadow: 0 1px 0 rgba(255,255,255,0.8);
    border-top: 1px solid #f8f8f8;
    border-radius: 4px 4px 0 0;
}

tr:hover {
    background-color: rgba(0, 122, 255, 0.05);
}

td {
    padding: 10px 15px;
    border-bottom: 1px solid #e6e6e6;
    color: #333;
    font-size: 13px;
}

.pagination-container {
    margin-top: 15px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.pagination-btn {
    padding: 5px 10px;
    border: 1px solid #ddd;
    background: #fff;
    cursor: pointer;
    border-radius: 3px;
}

.pagination-btn:disabled {
    background: #f5f5f5;
    cursor: not-allowed;
    color: #999;
}

.pagination-btn:hover:not(:disabled) {
    background: #f0f0f0;
}
</style>
@endsection