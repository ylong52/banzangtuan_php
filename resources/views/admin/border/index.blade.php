@section('content')
<div>
    <div class="card">
        <div class="yield" id="yield-section-1">
            <!-- 第一组数据将通过Ajax加载 -->
        </div>
        <div class="yield" id="yield-section-2">
            <!-- 第二组数据将通过Ajax加载 -->
        </div>
        <div class="yield" id="yield-section-3">
            <!-- 第三组数据将通过Ajax加载 -->
        </div>
    </div>
    <div class="dashboard-container">
        <!-- Top排行榜表格 -->
        <div class="table-container usertotal">
            <h3>Top排行榜</h3>
            <table>
                <thead>
                    <tr style="background-color: #f5f7fa;">
                        <th class="name">用户名</th>
                        <th class="team">团队</th>
                        <th class="amount">完成金额</th>
                        <th class="count">完成份数</th>
                        <th class="average">平均</th>
                        <th class="click">点击</th>
                        <th class="rate">成功率</th>
                    </tr>
                </thead>
                <tbody id="user-top-table-body">
                    <!-- 数据将通过Ajax加载 -->
                </tbody>
            </table>
            <div class="pagination">
                <div class="pagination-controls">
                    <select id="user-page-size" onchange="changeUserPageSize()">
                        <option value="10">10</option>
                        <option value="15" selected>15</option>
                        <option value="20">20</option>
                        <option value="30">30</option>
                        <option value="50">50</option>
                    </select>
                    <button onclick="prevUserPage()" id="user-prev-btn">上一页</button>
                    <span id="user-page-info">第 1 页</span>
                    <button onclick="nextUserPage()" id="user-next-btn">下一页</button>
                </div>
            </div>
        </div>

        <!-- 历史问卷统计表格 -->
        <div class="table-container surveyNumberList">
            <h3>历史问卷统计</h3>
            <table>
                <thead>
                    <tr>
                        <th>问卷编号</th>
                        <th>总金额</th>
                        <th>今日完成数量</th>
                        <th>累计完成数量</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody id="survey-table-body">
                    <!-- 数据将通过Ajax加载 -->
                </tbody>
            </table>
            <div class="pagination">
                <div class="pagination-controls">
                    <select id="survey-page-size" onchange="changeSurveyPageSize()">
                        <option value="10">10</option>
                        <option value="15" selected>15</option>
                        <option value="20">20</option>
                        <option value="30">30</option>
                        <option value="50">50</option>
                    </select>
                    <button onclick="prevSurveyPage()" id="survey-prev-btn">上一页</button>
                    <span id="survey-page-info">第 1 页</span>
                    <button onclick="nextSurveyPage()" id="survey-next-btn">下一页</button>
                </div>
            </div>
        </div>

        <!-- 问卷用户统计表格 -->
        <div class="table-container SurveyNumberIdxUsersList">
            <div class="input-container" style="text-align: right;">
                <input type="text" id="input-survey-number" placeholder="输入问卷编号" 
                       oninput="onSurveyNumberInput(this.value)" style="width: 200px;" />
            </div>
            <table>
                <thead>
                    <tr>
                        <th>用户名</th>
                        <th>今日完成</th>
                        <th>累计完成</th>
                    </tr>
                </thead>
                <tbody id="survey-users-table-body">
                    <!-- 数据将通过Ajax加载 -->
                </tbody>
            </table>
            <div class="pagination">
                <div class="pagination-controls">
                    <select id="survey-users-page-size" onchange="changeSurveyUsersPageSize()">
                        <option value="10">10</option>
                        <option value="15" selected>15</option>
                        <option value="20">20</option>
                        <option value="30">30</option>
                        <option value="50">50</option>
                    </select>
                    <button onclick="prevSurveyUsersPage()" id="survey-users-prev-btn">上一页</button>
                    <span id="survey-users-page-info">第 1 页</span>
                    <button onclick="nextSurveyUsersPage()" id="survey-users-next-btn">下一页</button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.card {
    background: rgba(255, 255, 255, 0.85);
    backdrop-filter: blur(20px);
    border-radius: 16px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
    padding: 24px;
    margin-bottom: 24px;
    transition: all 0.3s ease;
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.12);
}

.yield {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: 12px;
    margin-bottom: 12px;
}

.yield:last-child {
    margin-bottom: 0;
}

.yield-item {
    background: linear-gradient(145deg, #f8f9fa, #ffffff);
    border-radius: 8px;
    padding: 12px 14px;
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.04);
    transition: all 0.2s ease;
    min-height: 45px;
}

.yield-item:hover {
    background: linear-gradient(145deg, #ffffff, #f8f9fa);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    transform: translateY(-1px);
}

.yield-item .label {
    display: block;
    font-size: 12px;
    color: #8c8c8c;
    margin-bottom: 4px;
    font-weight: normal;
}

.yield-item .value {
    display: block;
    font-size: 16px;
    color: #1f1f1f;
    font-weight: 500;
    line-height: 1.2;
}

.dashboard-container {
    display: flex;
    gap: 16px;
    height: calc(100vh - 100px);
    font-size: 1rem;
}

.table-container {
    flex: 1;
    background: rgba(255, 255, 255, 0.95);
    border-radius: 12px;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
    overflow-y: auto;
    max-height: 100%;
    font-size: 0.9rem;
    transition: all 0.3s ease;
}

.usertotal {
    flex: 1.5;
    background: linear-gradient(to bottom right, #f0f9ff, #e6f7ff);
}

.surveyNumberList {
    flex: 1.5;
    background: linear-gradient(to bottom right, #f0f5ff, #e6f0ff);
}

.SurveyNumberIdxUsersList {
    flex: 0.8;
    background: linear-gradient(to bottom right, #f4f4f5, #e9e9eb);
}

.usertotal table th {
    white-space: nowrap;
    padding: 12px 8px;
    font-size: 0.85rem;
}

.usertotal table td {
    white-space: nowrap;
    padding: 8px;
    font-size: 0.85rem;
}

.table-container:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.12);
}

.table-container::-webkit-scrollbar {
    width: 6px;
}

.table-container::-webkit-scrollbar-thumb {
    background-color: rgba(144, 147, 153, 0.3);
    border-radius: 6px;
    transition: all 0.3s ease;
}

.table-container::-webkit-scrollbar-thumb:hover {
    background-color: rgba(144, 147, 153, 0.5);
}

.table-container::-webkit-scrollbar-track {
    background-color: rgba(245, 247, 250, 0.5);
}

table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    margin: 12px 0;
}

th {
    background: rgba(245, 245, 245, 0.8);
    color: #606266;
    font-weight: 600;
    padding: 12px 8px;
    text-align: center;
    border-bottom: 2px solid #ebeef5;
    transition: all 0.3s ease;
}

tr {
    height: 40px;
    text-align: center;
    transition: all 0.2s ease;
}

td {
    padding: 8px;
    border-bottom: 1px solid #ebeef5;
    color: #606266;
}

tr:hover {
    background-color: rgba(245, 247, 250, 0.7);
}

.striped {
    background-color: rgba(250, 250, 250, 0.5);
}

.pagination {
    display: flex;
    justify-content: flex-end;
    padding: 16px 20px;
    border-top: 1px solid #ebeef5;
}

.pagination-controls {
    display: flex;
    align-items: center;
    gap: 10px;
}

.pagination-controls button {
    padding: 5px 10px;
    border: 1px solid #ddd;
    background: #fff;
    cursor: pointer;
    border-radius: 4px;
}

.pagination-controls button:hover {
    background: #f5f5f5;
}

.pagination-controls button:disabled {
    background: #f5f5f5;
    cursor: not-allowed;
    opacity: 0.6;
}

.pagination-controls select {
    padding: 5px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.cursor-pointer {
    cursor: pointer;
    color: #409eff;
    transition: color 0.2s ease;
}

.cursor-pointer:hover {
    color: #66b1ff;
}

h3 {
    margin: 0;
    padding: 16px 20px;
    font-size: 1.1rem;
    color: #303133;
    border-bottom: 1px solid #ebeef5;
}

.input-container {
    padding: 16px 20px;
    background: rgba(255, 255, 255, 0.8);
    border-bottom: 1px solid #ebeef5;
}

.input-container input {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.loading {
    text-align: center;
    padding: 20px;
    color: #999;
}
</style>

<script>
// 全局变量
let yieldDataList = [];
let tableData = [];
let surveyNumberList = [];
let surveyNumberIdxUsersList = [];
let currentPage = 1;
let pageSize = 15;
let currentSurveyPage = 1;
let surveyPageSize = 15;
let currentSurveyNumberIdxPage = 1;
let surveyNumberIdxPageSize = 15;
let totalSurveyNumberIdxPages = 0;
let surveyNumber = '';
let isLoading = false;

// Ajax请求函数
function ajaxPost(url, data, callback) {
    const xhr = new XMLHttpRequest();
    xhr.open('POST', url, true);
    xhr.setRequestHeader('Content-Type', 'application/json');
    xhr.setRequestHeader('X-CSRF-TOKEN', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            if (xhr.status === 200) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    callback(null, response);
                } catch (e) {
                    callback('解析响应失败', null);
                }
            } else {
                callback('请求失败', null);
            }
        }
    };
    
    xhr.send(JSON.stringify(data));
}

// 显示加载状态
function showLoading(containerId) {
    document.getElementById(containerId).innerHTML = '<tr><td colspan="100%" class="loading">数据加载中...</td></tr>';
}

function getUserTopTotalHandel() {
    // 第407行 - 获取用户Top排行榜数据
    ajaxPost('/admin/bordertotal/getusertoptotal', {
        page: currentPage,
        limit: pageSize
    }, function(error, response) {

    if (error) {
        console.error('获取用户排行榜数据失败:', error);
        return;
    }

    if (response.data && response.data.data) {
        tableData = response.data.data;
        renderUserTopTable();
    } else {
        tableData = [];
        document.getElementById('user-top-table-body').innerHTML = '<tr><td colspan="7">暂无数据</td></tr>';
    }
    updateUserPagination();
    });
}

// 渲染用户Top排行榜表格
function renderUserTopTable() {
    const tbody = document.getElementById('user-top-table-body');
    let html = '';

    tableData.forEach((item, index) => {
          
        const rowClass = index % 2 === 1 ? 'striped' : '';
        html += `
            <tr class="${rowClass}" style="height: 40px;">
                <td class="name">${item.user.name}</td>
                <td class="team">${item.user.team.name}</td>
                <td class="amount">${item.completed_amount}</td>
                <td class="count">${item.completed_copies}</td>
                <td class="average">${item.average}</td>
                <td class="click">${item.clicks}</td>
                <td class="rate">${item.success_rate}</td>
            </tr>
        `;
    });
    
    tbody.innerHTML = html;
}

// 获取问卷统计数据
function getSurveyNumberTotalHandel() {
    showLoading('survey-table-body');
    
    ajaxPost('/admin/bordertotal/getsurveynumbertotal', {
        page: currentSurveyPage,
        limit: surveyPageSize
    }, function(error, response) {
        if (error) {
            console.error('获取问卷统计数据失败:', error);
            return;
        }
        
        if (response.data && response.data.data) {
            surveyNumberList = response.data.data;
            renderSurveyTable();
            if (surveyNumberList.length > 0) {
                surveyNumber = surveyNumberList[0].survey_number;
                viewSurveyNumberIdxUsers();
            }
        } else {
            surveyNumberList = [];
            document.getElementById('survey-table-body').innerHTML = '<tr><td colspan="5">暂无数据</td></tr>';
        }
        updateSurveyPagination();
    });
}

// 渲染问卷统计表格
function renderSurveyTable() {
    const tbody = document.getElementById('survey-table-body');
    let html = '';
    
    surveyNumberList.forEach((item, index) => {
        html += `
            <tr>
                <td>${item.survey_number}</td>
                <td>${item.total_amount}</td>
                <td>${item.today_completed}</td>
                <td>${item.cumulative_completed}</td>
                <td class="cursor-pointer" onclick="viewSurveyNumber('${item.survey_number}')">查看</td>
            </tr>
        `;
    });
    
    tbody.innerHTML = html;
}

// 获取问卷用户统计数据
function viewSurveyNumberIdxUsers() {
    if (isLoading) return;
    isLoading = true;
    
    showLoading('survey-users-table-body');
    
    ajaxPost('/admin/bordertotal/getsurveynumberidxusers', {
        page: currentSurveyNumberIdxPage,
        limit: surveyNumberIdxPageSize,
        survey_number: surveyNumber
    }, function(error, response) {
        isLoading = false;
        
        if (error) {
            console.error('获取问卷用户统计数据失败:', error);
            return;
        }
        
        if (response.data && response.data.list) {
            surveyNumberIdxUsersList = response.data.list;
            totalSurveyNumberIdxPages = response.data.total;
            renderSurveyUsersTable();
        } else {
            surveyNumberIdxUsersList = [];
            document.getElementById('survey-users-table-body').innerHTML = '<tr><td colspan="3">暂无数据</td></tr>';
        }
        updateSurveyUsersPagination();
    });
}

// 渲染问卷用户统计表格
function renderSurveyUsersTable() {
    const tbody = document.getElementById('survey-users-table-body');
    let html = '';
    
    surveyNumberIdxUsersList.forEach((item, index) => {
        html += `
            <tr>
                <td>${item.user_name}</td>
                <td>${item.today}</td>
                <td>${item.all}</td>
            </tr>
        `;
    });
    
    tbody.innerHTML = html;
}

// 获取产出统计数据
function getYieldTotal() {
    ajaxPost('/admin/bordertotal/yieldtotal', {}, function(error, response) {
        if (error) {
            console.error('获取产出统计数据失败:', error);
            return;
        }
        
        if (response.data) {
            yieldDataList = response.data;
            renderYieldData();
        }
    });
}

// 渲染产出统计数据
function renderYieldData() {
    for (let i = 0; i < 3; i++) {
        const section = document.getElementById(`yield-section-${i + 1}`);
        if (yieldDataList[i]) {
            let html = '';
            yieldDataList[i].forEach(item => {
                html += `
                    <div class="yield-item">
                        <span class="label">${item.label}</span>
                        <span class="value">${item.value}</span>
                    </div>
                `;
            });
            section.innerHTML = html;
        }
    }
}

// 分页控制函数
function changeUserPageSize() {
    pageSize = parseInt(document.getElementById('user-page-size').value);
    currentPage = 1;
    getUserTopTotalHandel();
}

function prevUserPage() {
    if (currentPage > 1) {
        currentPage--;
        getUserTopTotalHandel();
    }
}

function nextUserPage() {
    currentPage++;
    getUserTopTotalHandel();
}

function updateUserPagination() {
    document.getElementById('user-page-info').textContent = `第 ${currentPage} 页`;
    document.getElementById('user-prev-btn').disabled = currentPage <= 1;
}

function changeSurveyPageSize() {
    surveyPageSize = parseInt(document.getElementById('survey-page-size').value);
    currentSurveyPage = 1;
    getSurveyNumberTotalHandel();
}

function prevSurveyPage() {
    if (currentSurveyPage > 1) {
        currentSurveyPage--;
        getSurveyNumberTotalHandel();
    }
}

function nextSurveyPage() {
    currentSurveyPage++;
    getSurveyNumberTotalHandel();
}

function updateSurveyPagination() {
    document.getElementById('survey-page-info').textContent = `第 ${currentSurveyPage} 页`;
    document.getElementById('survey-prev-btn').disabled = currentSurveyPage <= 1;
}

function changeSurveyUsersPageSize() {
    surveyNumberIdxPageSize = parseInt(document.getElementById('survey-users-page-size').value);
    currentSurveyNumberIdxPage = 1;
    viewSurveyNumberIdxUsers();
}

function prevSurveyUsersPage() {
    if (currentSurveyNumberIdxPage > 1) {
        currentSurveyNumberIdxPage--;
        viewSurveyNumberIdxUsers();
    }
}

function nextSurveyUsersPage() {
    if (currentSurveyNumberIdxPage < Math.ceil(totalSurveyNumberIdxPages / surveyNumberIdxPageSize)) {
        currentSurveyNumberIdxPage++;
        viewSurveyNumberIdxUsers();
    }
}

function updateSurveyUsersPagination() {
    const totalPages = Math.ceil(totalSurveyNumberIdxPages / surveyNumberIdxPageSize);
    document.getElementById('survey-users-page-info').textContent = `第 ${currentSurveyNumberIdxPage} 页`;
    document.getElementById('survey-users-prev-btn').disabled = currentSurveyNumberIdxPage <= 1;
    document.getElementById('survey-users-next-btn').disabled = currentSurveyNumberIdxPage >= totalPages;
}

// 事件处理函数
function onSurveyNumberInput(value) {
    if (value.length > 4) {
        surveyNumber = value;
        viewSurveyNumberIdxUsers();
    }
}

function viewSurveyNumber(surveyNum) {
    surveyNumber = surveyNum;
    document.getElementById('input-survey-number').value = surveyNum;
    currentSurveyNumberIdxPage = 1;
    viewSurveyNumberIdxUsers();
}

// 页面加载完成后初始化
document.addEventListener('DOMContentLoaded', function() {
    getUserTopTotalHandel();
    getSurveyNumberTotalHandel();
    getYieldTotal();
});
</script>
@endsection
