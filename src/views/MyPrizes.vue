<template>
  <div class="my-prizes-page">
    <div class="container">
      <!-- 返回首页按钮 -->
      <div class="back-button-container">
        <router-link to="/" class="back-button">
          <i class="fa fa-arrow-left"></i>
          <span>返回首页</span>
        </router-link>
      </div>

      <!-- 顶部查询区域 -->
      <header class="query-header">
        <div class="query-input-wrapper">
          <div class="relative flex-1">
            <input 
              type="text" 
              v-model="queryInput"
              @input="handleInputChange"
              @keyup.enter="performSearch"
              placeholder="输入订单号或中奖码" 
              class="query-input"
              aria-label="输入订单号或中奖码"
            >
            <button 
              v-if="queryInput"
              @click="clearInput"
              class="clear-button"
              aria-label="清除输入内容"
            >
              <i class="fa fa-times"></i>
            </button>
          </div>
          <button 
            @click="performSearch"
            :disabled="!queryInput.trim() || loading"
            class="search-button"
            :class="queryInput.trim() && !loading ? 'search-button-active' : 'search-button-disabled'"
            aria-label="查询中奖信息"
          >
            <span v-if="!loading">查询</span>
            <span v-else>
              <i class="fa fa-spinner fa-spin mr-1"></i> 加载中
            </span>
          </button>
        </div>
      </header>

      <!-- 主内容区域 -->
      <main class="content-main">
        <!-- 加载中提示 -->
        <div v-if="loading" class="loading-container">
          <div class="loading-spinner"></div>
          <p class="loading-text">查询中，请稍候...</p>
        </div>

        <!-- 无数据提示 -->
        <div v-else-if="!loading && !error && (!results || results.length === 0) && hasSearched" class="empty-container">
          <i class="fa fa-inbox empty-icon"></i>
          <p class="empty-text">暂未符合条件的数据</p>
        </div>

        <!-- 错误提示 -->
        <div v-else-if="error" class="error-container">
          <i class="fa fa-exclamation-circle error-icon"></i>
          <p class="error-text">{{ errorMessage }}</p>
          <button @click="performSearch" class="retry-button">
            重新查询
          </button>
        </div>

        <!-- 结果列表 -->
        <div v-else-if="results && results.length > 0" class="results-list">
          <div 
            v-for="(item, index) in results" 
            :key="index"
            class="result-card"
          >
            <div class="result-row">
              <div class="result-item">
                <span class="result-label">订单号：</span>
                <span class="result-value">{{ item.order_no}}</span>
              </div>
              <div class="result-item">
                <span class="result-label">开奖码：</span>
                <span class="result-value">{{ item.lottery_code}}</span>
              </div>
            </div>
            <div class="result-row">
              <div class="result-item">
                <span class="result-label">奖品名称：</span>
                <span class="result-value result-value-bold">{{ item.prize_name || item.prizeName }}</span>
              </div>
              <div class="result-item">
                <span class="result-label">中奖时间：</span>
                <span class="result-value">{{ item.draw_time || item.lotteryTime || item.created_at }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- 初始状态提示 -->
        <div v-else class="initial-container">
          <i class="fa fa-search initial-icon"></i>
          <p class="initial-text">请输入订单号或中奖码进行查询</p>
        </div>
      </main>
    </div>

    <!-- 弱网提示 -->
    <transition name="fade">
      <div v-if="weakNetworkShow" class="weak-network-toast">
        网络较慢，请耐心等待
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, onMounted, nextTick } from 'vue'
import { lotteryApi } from '@/api'

const queryInput = ref('')
const loading = ref(false)
const error = ref(false)
const errorMessage = ref('查询失败，请重试')
const results = ref([])
const hasSearched = ref(false)
const weakNetworkShow = ref(false)
let weakNetworkTimer = null

// 处理输入框变化
function handleInputChange() {
  if (!queryInput.value.trim()) {
    hasSearched.value = false
    results.value = []
    error.value = false
  }
}

// 清除输入
function clearInput() {
  queryInput.value = ''
  handleInputChange()
  // 聚焦输入框
  nextTick(() => {
    const input = document.querySelector('input[type="text"]')
    if (input) input.focus()
  })
}

// 执行查询
async function performSearch() {
  const query = queryInput.value.trim()
  if (!query || loading.value) return
  
  // 重置状态
  loading.value = true
  error.value = false
  errorMessage.value = '查询失败，请重试'
  results.value = []
  hasSearched.value = true
  
  // 随机决定是否显示弱网提示
  if (Math.random() > 0.5) {
    showWeakNetworkToast()
  }
  
  try {
    // 调用查询API
    const res = await lotteryApi.queryPrizes({ query })
    
    if (res && Array.isArray(res) && res.length > 0) {
      results.value = res
    } else {
      results.value = []
    }
  } catch (err) {
    console.error('查询失败:', err)
    error.value = true
    errorMessage.value = err.message || '查询失败，请重试'
    results.value = []
  } finally {
    loading.value = false
  }
}

// 显示弱网提示
function showWeakNetworkToast() {
  weakNetworkShow.value = true
  if (weakNetworkTimer) {
    clearTimeout(weakNetworkTimer)
  }
  weakNetworkTimer = setTimeout(() => {
    weakNetworkShow.value = false
  }, 3000)
}

// 组件挂载时聚焦输入框
onMounted(() => {
  const input = document.querySelector('input[type="text"]')
  if (input) input.focus()
})
</script>

<style scoped>
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

.my-prizes-page {
  width: 100%;
  min-height: 100vh;
  background: #ffffff; /* PC端两侧留白色，和Home.vue一致 */
  display: flex;
  flex-direction: column;
  justify-content: flex-start;
  align-items: center;
  font-family: "Microsoft YaHei", Arial, sans-serif;
  padding: 0;
}

.container {
  position: relative;
  width: 100%;
  max-width: 393px; /* 和Home.vue一致 */
  margin: 0 auto;
  background-color: #FF6B35; /* 和Home.vue一致 */
  min-height: 100vh;
  overflow-y: auto;
}

/* 返回首页按钮 */
.back-button-container {
  padding: 15px 20px 10px;
  display: flex;
  align-items: center;
}

.back-button {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  color: #fff;
  font-size: 14px;
  font-weight: 500;
  text-decoration: none;
  padding: 8px 16px;
  border-radius: 20px;
  background-color: rgba(255, 255, 255, 0.2);
  border: 1px solid rgba(255, 255, 255, 0.3);
  transition: all 0.3s ease;
  cursor: pointer;
  position: relative;
  z-index: 100;
  pointer-events: auto;
}

.back-button:hover {
  background-color: rgba(255, 255, 255, 0.3);
  transform: translateX(-2px);
}

.back-button:active {
  transform: translateX(0);
}

.back-button i {
  font-size: 12px;
}

/* 查询区域 */
.query-header {
  padding: 10px 20px 15px;
}

.query-input-wrapper {
  display: flex;
  align-items: center;
  gap: 10px;
}

.query-input {
  width: 100%;
  height: 44px;
  padding: 0 40px 0 16px;
  border: 2px solid rgba(255, 255, 255, 0.6);
  border-radius: 22px;
  background-color: rgba(255, 255, 255, 0.95);
  font-size: 16px;
  color: #333;
  outline: none;
  transition: all 0.3s ease;
}

.query-input:focus {
  border-color: #FFD700;
  background-color: #fff;
  box-shadow: 0 0 0 3px rgba(255, 215, 0, 0.2);
}

.query-input::placeholder {
  color: #999;
}

.clear-button {
  position: absolute;
  right: 12px;
  top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  color: #999;
  cursor: pointer;
  padding: 4px;
  font-size: 14px;
  transition: color 0.2s;
}

.clear-button:hover {
  color: #333;
}

.search-button {
  height: 44px;
  padding: 0 20px;
  border-radius: 22px;
  border: none;
  color: white;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.3s ease;
  white-space: nowrap;
  flex-shrink: 0;
  position: relative;
  z-index: 10;
  pointer-events: auto;
}

.search-button-active {
  background-color: #FFD700;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}

.search-button-active:hover {
  background-color: #FFC700;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

.search-button-active:active {
  transform: translateY(0);
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
}

.search-button-disabled {
  background-color: rgba(255, 255, 255, 0.3);
  cursor: not-allowed;
  opacity: 0.7;
  pointer-events: none;
}

/* 主内容区域 */
.content-main {
  padding: 0 20px 30px;
  min-height: calc(100vh - 150px);
}

/* 加载中提示 */
.loading-container {
  padding: 40px 0;
  text-align: center;
}

.loading-spinner {
  display: inline-block;
  width: 24px;
  height: 24px;
  border: 2px solid #1677FF;
  border-top-color: transparent;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 8px;
}

.loading-text {
  color: #999999;
  font-size: 14px;
}

/* 无数据提示 */
.empty-container {
  padding: 80px 0;
  text-align: center;
}

.empty-icon {
  font-size: 64px;
  color: #E5E5E5;
  margin-bottom: 16px;
}

.empty-text {
  color: #999999;
  font-size: 14px;
}

/* 错误提示 */
.error-container {
  padding: 80px 0;
  text-align: center;
}

.error-icon {
  font-size: 64px;
  color: #FF4D4F;
  margin-bottom: 16px;
}

.error-text {
  color: #FF4D4F;
  font-size: 14px;
  margin-bottom: 16px;
}

.retry-button {
  height: 40px;
  padding: 0 16px;
  border-radius: 8px;
  background-color: #1677FF;
  color: white;
  font-size: 14px;
  font-weight: 500;
  border: none;
  cursor: pointer;
  transition: all 0.1s ease;
}

.retry-button:active {
  transform: scale(0.98);
}

/* 结果列表 */
.results-list {
  padding: 0 0 0 0;
}

.result-card {
  background-color: #fff;
  border-radius: 12px;
  border: 1px solid rgba(255, 255, 255, 0.3);
  padding: 16px;
  margin-bottom: 12px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.result-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
  margin-bottom: 16px;
}

.result-row:last-child {
  margin-bottom: 0;
}

.result-item {
  display: flex;
  flex-direction: column;
}

.result-label {
  color: #999999;
  font-size: 14px;
  margin-bottom: 4px;
}

.result-value {
  color: #333333;
  font-size: 14px;
  word-break: break-all;
}

.result-value-bold {
  font-weight: 500;
}

/* 初始状态提示 */
.initial-container {
  padding: 80px 0;
  text-align: center;
}

.initial-icon {
  font-size: 64px;
  color: #E5E5E5;
  margin-bottom: 16px;
}

.initial-text {
  color: #999999;
  font-size: 14px;
}


/* 弱网提示 */
.weak-network-toast {
  position: fixed;
  top: 80px;
  left: 50%;
  transform: translateX(-50%);
  background-color: #FF9F43;
  color: white;
  font-size: 12px;
  padding: 8px 16px;
  border-radius: 20px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  z-index: 10000;
}

/* 动画 */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.animate-spin {
  animation: spin 1s linear infinite;
}

/* 响应式 */
@media (max-width: 450px) {
  .container {
    max-width: 100%;
  }
  
  .query-header {
    padding: 10px 15px 15px;
  }
  
  .content-main {
    padding: 0 15px 30px;
  }
  
  .back-button-container {
    padding: 15px 15px 10px;
  }
}
</style>

