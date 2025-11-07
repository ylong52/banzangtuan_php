<template>
  <div class="home-page">
    <!-- 中国风背景装饰 -->
    <div class="bg-decoration"></div>
    
    <div class="container">
      <div class="top-container">
        <div class="btn-group" style="width: 72%;">
          <router-link to="/my-prizes" class="btn">我的奖品</router-link>
          
        </div>
      </div>
      
      <div class="wheel-container">
        <div class="wheel-wrapper">
          <div class="pointer"></div>
          <div class="wheel-outer">
            <div class="wheel-inner">
              <canvas ref="wheelCanvas" id="wheelCanvas"></canvas>
            </div>
          </div>
          <div 
            class="wheel-center" 
            :class="{ spinning: isSpinning }"
            @click="startSpin"
          >
            <span>开始</span>
          </div>
        </div>
        <!-- 结果弹窗已移至下方 -->
      </div>
      
      <div class="content-container">
        <div class="bottom-container">
          <div>
            <label>抽奖码</label>
            <input 
              type="text" 
              class="input" 
              placeholder="请输入抽奖码"
              v-model="lotteryCode"
            />
            <input 
              type="text" 
              class="input" 
              placeholder="你的订单号"
              v-model="orderNumber"
            />
          </div>
        </div>
        
        <div class="rule" style="padding: 12px 12px 6px 12px; font-size: 14px; line-height: 1.5;">
          <ul class="rule-list" style="margin:0; padding-left:0; list-style:none;">
            <li style="margin:0 0 5px 0; padding-left:1.2em; text-indent:-1em;">
              抽奖码使用：需从活动指定渠道获取，每个仅可参与一次抽奖。提交后无论是否中奖均不可重复使用，重复提交将被系统驳回。​
            </li>
            <li style="margin:0 0 5px 0; padding-left:1.2em; text-indent:-1em;">
              禁止违规：不得篡改、伪造、倒卖抽奖码，冒用他人信息或用作弊工具批量获取资格，违者取消资格，情节严重者追究法律责任。​
            </li>
            <li style="margin:0 0 2px 0; padding-left:1.2em; text-indent:-1em;">
              兑奖说明：中奖后需将截图发送至客服的微信号，并注明抽奖码和年月日。
            </li>
          </ul>
        </div>
      </div>
    </div>
    
    <!-- 烟花效果 -->
    <div 
      v-for="(firework, index) in fireworks" 
      :key="index"
      class="firework"
      :style="firework.style"
    ></div>
    
    <!-- 右上角通知 -->
    <transition name="notification">
      <div v-if="notification.show" class="notification">
        <div class="notification-content">
          <span class="notification-icon">⚠️</span>
          <span class="notification-message">{{ notification.message }}</span>
        </div>
      </div>
    </transition>
    
    <!-- 中奖结果弹窗 -->
    <transition name="modal">
      <div v-if="prizeResult.show" class="modal-overlay" @click.self="closePrizeModal">
        <div class="modal-content">
          <div class="modal-header">
            <h2 class="modal-title">🎉 恭喜中奖 🎉</h2>
          </div>
          <div class="modal-body">
            <div class="prize-info">
              <div class="prize-label">恭喜获得：</div>
              <div class="prize-name">{{ prizeResult.prizeName }}</div>
            </div>
            <div class="prize-details">
              <div class="detail-row">
                <span class="detail-label">订单号：</span>
                <span class="detail-value">{{ prizeResult.orderNumber }}</span>
              </div>
              <div class="detail-row">
                <span class="detail-label">开奖码：</span>
                <span class="detail-value">{{ prizeResult.lotteryCode }}</span>
              </div>
              <div class="detail-row">
                <span class="detail-label">中奖时间：</span>
                <span class="detail-value">{{ prizeResult.time }}</span>
              </div>
            </div>
            <div class="prize-tip">
              <div class="tip-icon">💡</div>
              <div class="tip-text">温馨提示：请在中奖2小时以内截屏截图发给客服，帮你抵换</div>
            </div>
          </div>
          <div class="modal-footer">
            <button class="modal-btn" @click="closePrizeModal">确定</button>
          </div>
        </div>
      </div>
    </transition>
    
    <!-- 历史中奖结果弹窗 -->
    <transition name="modal">
      <div v-if="historyPrizeResult.show" class="modal-overlay" @click.self="closeHistoryPrizeModal">
        <div class="modal-content">
          <div class="modal-header">
            <h2 class="modal-title">📋 历史中奖信息 📋</h2>
          </div>
          <div class="modal-body">
            <div class="prize-info">
              <div class="prize-label">已中奖品：</div>
              <div class="prize-name">{{ historyPrizeResult.prizeName }}</div>
            </div>
            <div class="prize-details">
              <div class="detail-row">
                <span class="detail-label">订单号：</span>
                <span class="detail-value">{{ historyPrizeResult.orderNumber }}</span>
              </div>
              <div class="detail-row">
                <span class="detail-label">开奖码：</span>
                <span class="detail-value">{{ historyPrizeResult.lotteryCode }}</span>
              </div>
              <div class="detail-row">
                <span class="detail-label">中奖时间：</span>
                <span class="detail-value">{{ historyPrizeResult.time }}</span>
              </div>
            </div>
            <div class="prize-tip">
              <div class="tip-icon">⚠️</div>
              <div class="tip-text">温馨提示：你已经中过奖了，请勿重复抽奖</div>
            </div>
          </div>
          <div class="modal-footer">
            <button class="modal-btn" @click="closeHistoryPrizeModal">确定</button>
          </div>
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, nextTick } from 'vue'
import { lotteryApi } from '@/api'

const prizes = [
  { name: '', color: '#FF6B6B', textColor: '#fff' },
  { name: '', color: '#FFD93D', textColor: '#333' },
  { name: '', color: '#6BCB77', textColor: '#fff' },
  { name: '', color: '#4D96FF', textColor: '#fff' },
  { name: '', color: '#FF6B9D', textColor: '#fff' },
  { name: '', color: '#C3ACD0', textColor: '#333' },
  { name: '', color: '#F7931E', textColor: '#fff' },
  { id: -1, name: '谢谢参与', color: '#00D9FF', textColor: '#333' }
]

const getPrizeList = async () => {
  try {
    const res = await lotteryApi.getList()
    console.log('API返回数据:', res)
    // 将prizes数组中的name替换为res中的name，还有id要加上
    // 注意：request.js 已经处理了响应，返回的是 data.data
    if (res && Array.isArray(res)) {
      prizes.forEach((prize, index) => {
        const apiPrize = res[index]
        if (apiPrize) {
          prize.id = apiPrize.id
          prize.name = apiPrize.prize_name || prize.name
          prize.color = apiPrize.color || prize.color
          prize.textColor = apiPrize.textColor || prize.textColor
        }
      })
    }
    console.log('更新后的prizes:', prizes)
    // 数据更新后重新绘制转盘
    if (canvas && ctx) {
      drawWheel()
    }
  } catch (error) {
    console.error('获取奖品列表失败:', error)
  }
}

const wheelCanvas = ref(null)
const isSpinning = ref(false)
const currentRotation = ref(0)
const resultText = ref('')
const lotteryCode = ref('hq8tcw')
const orderNumber = ref('338194088429')
const fireworks = ref([])
const notification = ref({
  show: false,
  message: ''
})
const prizeResult = ref({
  show: false,
  prizeName: '',
  time: '',
  orderNumber: '',
  lotteryCode: ''
})
const historyPrizeResult = ref({
  show: false,
  prizeName: '',
  time: '',
  orderNumber: '',
  lotteryCode: ''
})
let canvas = null
let ctx = null
let animationFrameId = null
let notificationTimer = null

// 设置canvas尺寸
function resizeCanvas() {
  if (!canvas) return
  const size = 300
  canvas.width = size
  canvas.height = size
  drawWheel()
}

// 绘制转盘
function drawWheel() {
  if (!canvas || !ctx) return
  
  const centerX = canvas.width / 2
  const centerY = canvas.height / 2
  const radius = canvas.width / 2
  const anglePerPrize = (2 * Math.PI) / prizes.length

  ctx.clearRect(0, 0, canvas.width, canvas.height)
  ctx.save()
  ctx.translate(centerX, centerY)
  ctx.rotate(currentRotation.value)

  prizes.forEach((prize, index) => {
    const startAngle = index * anglePerPrize - Math.PI / 2
    const endAngle = startAngle + anglePerPrize

    // 绘制扇形
    ctx.beginPath()
    ctx.moveTo(0, 0)
    ctx.arc(0, 0, radius, startAngle, endAngle)
    ctx.closePath()
    ctx.fillStyle = prize.color
    ctx.fill()

    // 绘制边框
    ctx.strokeStyle = '#fff'
    ctx.lineWidth = 2
    ctx.stroke()

    // 绘制文字
    ctx.save()
    ctx.rotate(startAngle + anglePerPrize / 2)
    ctx.textAlign = 'center'
    ctx.textBaseline = 'middle'
    ctx.fillStyle = prize.textColor
    ctx.font = 'bold 12px Microsoft YaHei'
    ctx.fillText(prize.name, radius * 0.65, 0)
    ctx.restore()
  })

  ctx.restore()
}

// 显示通知
function showNotification(message) {
  notification.value.show = true
  notification.value.message = message
  
  // 清除之前的定时器
  if (notificationTimer) {
    clearTimeout(notificationTimer)
  }
  
  // 3秒后自动隐藏
  notificationTimer = setTimeout(() => {
    notification.value.show = false
  }, 3000)
}

// 格式化时间
function formatDateTime(date) {
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  const hours = String(date.getHours()).padStart(2, '0')
  const minutes = String(date.getMinutes()).padStart(2, '0')
  return `${year}年${month}月${day}日 ${hours}:${minutes}`
}

// 显示中奖弹窗
function showPrizeModal(prizeName, orderNumber, lotteryCode) {
  prizeResult.value.prizeName = prizeName
  prizeResult.value.time = formatDateTime(new Date())
  prizeResult.value.orderNumber = orderNumber || ''
  prizeResult.value.lotteryCode = lotteryCode || ''
  prizeResult.value.show = true
}

// 关闭中奖弹窗
function closePrizeModal() {
  prizeResult.value.show = false
}

// 显示历史中奖弹窗
function showHistoryPrizeModal(prizeName, orderNumber, lotteryCode, time) {
  historyPrizeResult.value.prizeName = prizeName || ''
  historyPrizeResult.value.time = time || formatDateTime(new Date())
  historyPrizeResult.value.orderNumber = orderNumber || ''
  historyPrizeResult.value.lotteryCode = lotteryCode || ''
  historyPrizeResult.value.show = true
}

// 关闭历史中奖弹窗
function closeHistoryPrizeModal() {
  historyPrizeResult.value.show = false
}

// 创建烟花效果
function createFirework(x, y) {
  const colors = ['#FF6B6B', '#FFD93D', '#6BCB77', '#4D96FF', '#FF6B9D']
  for (let i = 0; i < 30; i++) {
    const tx = (Math.random() - 0.5) * 200
    const ty = (Math.random() - 0.5) * 200
    
    const firework = {
      style: {
        left: x + 'px',
        top: y + 'px',
        backgroundColor: colors[Math.floor(Math.random() * colors.length)],
        '--tx': tx + 'px',
        '--ty': ty + 'px',
        animation: 'fireworkExplode 1s ease-out forwards'
      }
    }
    
    fireworks.value.push(firework)
    
    setTimeout(() => {
      const index = fireworks.value.indexOf(firework)
      if (index > -1) {
        fireworks.value.splice(index, 1)
      }
    }, 1000)
  }
}

// 开始抽奖
async function startSpin() {
  if (isSpinning.value) return
  
  // 验证抽奖码和订单号
  if (!lotteryCode.value || !lotteryCode.value.trim()) {
    showNotification('请输入抽奖码')
    return
  }
  if (!orderNumber.value || !orderNumber.value.trim()) {
    showNotification('请输入订单号')
    return
  }
  
  isSpinning.value = true
  resultText.value = ''

  try {
    // 调用开奖API
    const res = await lotteryApi.openLottery({
      lottery_code: lotteryCode.value.trim(),
      order_number: orderNumber.value.trim()
    })
    
    console.log('开奖API返回:', res)
    
    // 根据API返回的奖品ID找到对应的奖品索引
    let targetPrizeIndex = 0
    if (res && res.is_history==1) {
      // 显示历史中奖信息弹窗
      isSpinning.value = false
      const prizeName = res?.prize_name || ''
      const historyOrderNumber = res?.order_number || orderNumber.value
      const historyLotteryCode = res?.lottery_code || lotteryCode.value
      const time = res?.draw_time || res?.created_at || ''
      showHistoryPrizeModal(prizeName, historyOrderNumber, historyLotteryCode, time)
      return
    }
    if (res && res.prize_id !== undefined) {
      const prizeIndex = prizes.findIndex(p => p.id === res.prize_id)
      if (prizeIndex !== -1) {
        targetPrizeIndex = prizeIndex
      } else {
        // 如果找不到对应的奖品，使用默认索引0
        console.warn('未找到对应的奖品ID:', res.prize_id)
      }
    } else {
      // 如果API没有返回奖品ID，使用随机（兼容旧逻辑）
      targetPrizeIndex = Math.floor(Math.random() * prizes.length)
    }
    
    // 开始转盘动画
    const anglePerPrize = 360 / prizes.length
    const targetAngle = 360 - (targetPrizeIndex * anglePerPrize + anglePerPrize / 2)
    const totalRotation = 360 * 5 + targetAngle // 转5圈加目标角度
    
    const duration = 4000
    const startTime = Date.now()
    
    function animate() {
      const now = Date.now()
      const elapsed = now - startTime
      const progress = Math.min(elapsed / duration, 1)
      
      // 缓动函数
      const easeOut = 1 - Math.pow(1 - progress, 3)
      const rotation = (totalRotation * easeOut * Math.PI) / 180
      
      currentRotation.value = rotation
      drawWheel()
      
      if (progress < 1) {
        animationFrameId = requestAnimationFrame(animate)
      } else {
        // 抽奖结束
        isSpinning.value = false
        
        const prize = prizes[targetPrizeIndex]
        // 使用API返回的奖品名称，如果没有则使用本地名称
        const prizeName = res?.prize_name || prize.name
        resultText.value = `🎉 恭喜获得：${prizeName}！🎉`
        
        // 显示中奖弹窗
        setTimeout(() => {
          showPrizeModal(prizeName, orderNumber.value, lotteryCode.value)
        }, 500)
        
        // 触发烟花效果
        if (prizeName !== '谢谢参与' && prize.id !== -1) {
          setTimeout(() => {
            for (let i = 0; i < 3; i++) {
              setTimeout(() => {
                createFirework(
                  Math.random() * window.innerWidth,
                  Math.random() * window.innerHeight / 2
                )
              }, i * 200)
            }
          }, 300)
        }
      }
    }
    
    animate()
  } catch (error) {
    // 处理错误
    isSpinning.value = false
    console.error('抽奖失败:', error)
    
    // 根据错误信息显示右上角通知
    const errorMsg = error.message || '抽奖失败，请稍后重试'
    showNotification(errorMsg)
  }
}

// 处理窗口大小变化
function handleResize() {
  resizeCanvas()
}

onMounted(async () => {
  await nextTick()
  // 先初始化canvas
  if (wheelCanvas.value) {
    canvas = wheelCanvas.value
    ctx = canvas.getContext('2d')
    resizeCanvas() // 先绘制一次（使用默认数据）
  }
  // 然后获取API数据并更新转盘
  await getPrizeList() // 等待数据获取完成，getPrizeList内部会重新绘制
  window.addEventListener('resize', handleResize)
})

onUnmounted(() => {
  if (animationFrameId) {
    cancelAnimationFrame(animationFrameId)
  }
  if (notificationTimer) {
    clearTimeout(notificationTimer)
  }
  window.removeEventListener('resize', handleResize)
})
</script>

<style scoped>
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

.home-page {
  width: 100%;
  min-height: 100vh;
  background: #ffffff; /* PC端两侧留白色 */
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  font-family: "Microsoft YaHei", Arial, sans-serif;
  overflow: hidden;
  position: relative;
}

/* 中国风背景装饰 */
.bg-decoration {
  content: '';
  position: absolute;
  top: -50%;
  left: -50%;
  width: 200%;
  height: 200%;
  background-image: 
    radial-gradient(circle, rgba(255, 215, 0, 0.1) 1px, transparent 1px);
  background-size: 30px 30px;
  animation: float 20s linear infinite;
  pointer-events: none;
}

@keyframes float {
  0% { transform: translate(0, 0); }
  100% { transform: translate(50px, 50px); }
}

.container {
  position: relative;
  z-index: 200;
  width: 100%;
  max-width: 393px;
  margin: 0 auto;
  display: flex;
  flex-direction: column;
  justify-content: center;
  top: 0;
  background-color: #FF6B35;
  overflow-y: auto;
}

.title {
  text-align: center;
  margin-bottom: 20px;
  color: #FFD700;
  font-size: 32px;
  font-weight: bold;
  text-shadow: 3px 3px 6px rgba(0, 0, 0, 0.5);
  animation: glow 2s ease-in-out infinite;
}

@keyframes glow {
  0%, 100% { text-shadow: 3px 3px 6px rgba(0, 0, 0, 0.5), 0 0 10px rgba(255, 215, 0, 0.5); }
  50% { text-shadow: 3px 3px 6px rgba(0, 0, 0, 0.5), 0 0 20px rgba(255, 215, 0, 0.8); }
}

.wheel-wrapper {
  position: relative;
  width: 320px;
  height: 320px;
  margin: 0 auto; 
}

.wheel-outer {
  position: absolute;
  width: 100%;
  height: 100%;
  border-radius: 50%;
  background: linear-gradient(45deg, #FFD700, #FFA500);
  box-shadow: 0 0 30px rgba(255, 215, 0, 0.6), 
              inset 0 0 20px rgba(255, 255, 255, 0.3);
  padding: 10px;
}

.wheel-inner {
  position: relative;
  width: 100%;
  height: 100%;
  border-radius: 50%;
  background: #fff;
  overflow: hidden;
  box-shadow: inset 0 0 20px rgba(0, 0, 0, 0.2);
}

canvas {
  width: 100%;
  height: 100%;
  border-radius: 50%;
}

.wheel-center {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 80px;
  height: 80px;
  border-radius: 50%;
  background: linear-gradient(135deg, #FFD700, #FF6347);
  box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
  display: flex;
  justify-content: center;
  align-items: center;
  cursor: pointer;
  transition: all 0.3s;
  z-index: 10;
}

.wheel-center:active {
  transform: translate(-50%, -50%) scale(0.95);
}

.wheel-center span {
  color: white;
  font-size: 18px;
  font-weight: bold;
  text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.5);
}

.pointer {
  position: absolute;
  top: -15px;
  left: 50%;
  transform: translateX(-50%);
  width: 0;
  height: 0;
  border-left: 15px solid transparent;
  border-right: 15px solid transparent;
  border-top: 30px solid #FF6347;
  filter: drop-shadow(0 3px 5px rgba(0, 0, 0, 0.3));
  z-index: 5;
}

/* 中奖结果弹窗样式 - 中国风 */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  backdrop-filter: blur(3px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 10001;
  overflow-y: auto;
}

.modal-content {
  background: linear-gradient(135deg, #DC143C 0%, #FF6B6B 50%, #FFD700 100%);
  border-radius: 15px;
  box-shadow: 
    0 15px 40px rgba(220, 20, 60, 0.5),
    0 0 0 3px #FFD700,
    0 0 20px rgba(255, 215, 0, 0.6),
    inset 0 0 30px rgba(255, 255, 255, 0.1);
  width: 50%;
  max-width: 350px;
  margin: 0 auto;
  position: relative;
  animation: modalSlideIn 0.3s ease-out;
  max-height: 60vh;
  overflow-y: auto;
  border: 2px solid #FFD700;
  padding: 2px;
}

.modal-content::before {
  content: '';
  position: absolute;
  top: -2px;
  left: -2px;
  right: -2px;
  bottom: -2px;
  background: linear-gradient(45deg, #DC143C, #FFD700, #DC143C, #FFD700);
  border-radius: 15px;
  z-index: -1;
  animation: borderGlow 3s ease-in-out infinite;
}

@keyframes borderGlow {
  0%, 100% {
    opacity: 0.8;
    filter: blur(2px);
  }
  50% {
    opacity: 1;
    filter: blur(4px);
  }
}

@keyframes modalSlideIn {
  from {
    transform: translateY(-50px);
    opacity: 0;
  }
  to {
    transform: translateY(0);
    opacity: 1;
  }
}

.modal-header {
  padding: 15px 15px 10px;
  text-align: center;
  border-bottom: 2px solid rgba(255, 215, 0, 0.5);
  background: rgba(255, 255, 255, 0.1);
  border-radius: 13px 13px 0 0;
}

.modal-title {
  font-size: 18px;
  font-weight: bold;
  color: #FFD700;
  margin: 0;
  text-shadow: 
    2px 2px 4px rgba(220, 20, 60, 0.8),
    0 0 10px rgba(255, 215, 0, 0.8);
  letter-spacing: 2px;
}

.modal-body {
  padding: 15px;
  background: rgba(255, 255, 255, 0.95);
  margin: 2px;
  border-radius: 10px;
}

.prize-info {
  margin-bottom: 12px;
  text-align: center;
}

.prize-label {
  font-size: 12px;
  color: #DC143C;
  margin-bottom: 6px;
  font-weight: 600;
}

.prize-name {
  font-size: 20px;
  font-weight: bold;
  color: #DC143C;
  background: linear-gradient(135deg, #DC143C 0%, #FF6B6B 50%, #FFD700 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  padding: 6px 0;
  word-break: break-word;
  text-shadow: 0 0 10px rgba(220, 20, 60, 0.3);
}

.prize-details {
  margin-bottom: 12px;
  padding: 10px;
  background: linear-gradient(135deg, #FFF9E6 0%, #FFE5B4 100%);
  border-radius: 8px;
  border: 1px solid #FFD700;
  box-shadow: inset 0 2px 4px rgba(255, 215, 0, 0.2);
}

.detail-row {
  display: flex;
  align-items: center;
  margin-bottom: 8px;
  font-size: 13px;
  line-height: 1.5;
}

.detail-row:last-child {
  margin-bottom: 0;
}

.detail-label {
  font-size: 13px;
  color: #DC143C;
  font-weight: 600;
  white-space: nowrap;
  margin-right: 8px;
  flex-shrink: 0;
}

.detail-value {
  font-size: 13px;
  font-weight: 600;
  color: #DC143C;
  word-break: break-all;
  flex: 1;
}

.prize-tip {
  display: flex;
  align-items: flex-start;
  gap: 8px;
  padding: 8px;
  background: linear-gradient(135deg, #FFE5E5 0%, #FFF0F0 100%);
  border-radius: 8px;
  border: 1px solid #FF6B6B;
  box-shadow: inset 0 2px 4px rgba(220, 20, 60, 0.1);
}

.tip-icon {
  font-size: 14px;
  flex-shrink: 0;
  margin-top: 2px;
}

.tip-text {
  font-size: 11px;
  color: #DC143C;
  line-height: 1.5;
  flex: 1;
  font-weight: 500;
}

.modal-footer {
  padding: 10px 15px 15px;
  text-align: center;
  border-top: 2px solid rgba(255, 215, 0, 0.5);
  background: rgba(255, 255, 255, 0.1);
  border-radius: 0 0 13px 13px;
}

.modal-btn {
  background: linear-gradient(135deg, #DC143C 0%, #FF6B6B 50%, #FFD700 100%);
  color: #FFFFFF;
  border: 2px solid #FFD700;
  border-radius: 20px;
  padding: 8px 30px;
  font-size: 14px;
  font-weight: bold;
  cursor: pointer;
  transition: all 0.3s ease;
  box-shadow: 
    0 4px 15px rgba(220, 20, 60, 0.5),
    0 0 10px rgba(255, 215, 0, 0.5),
    inset 0 1px 0 rgba(255, 255, 255, 0.3);
  text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.3);
  letter-spacing: 1px;
}

.modal-btn:hover {
  transform: translateY(-2px) scale(1.05);
  box-shadow: 
    0 6px 20px rgba(220, 20, 60, 0.6),
    0 0 15px rgba(255, 215, 0, 0.7),
    inset 0 1px 0 rgba(255, 255, 255, 0.4);
  background: linear-gradient(135deg, #FF6B6B 0%, #DC143C 50%, #FFD700 100%);
}

.modal-btn:active {
  transform: translateY(0) scale(1);
  box-shadow: 
    0 2px 8px rgba(220, 20, 60, 0.4),
    0 0 5px rgba(255, 215, 0, 0.4);
}

/* 弹窗动画 */
.modal-enter-active {
  animation: modalSlideIn 0.3s ease-out;
}

.modal-leave-active {
  animation: modalSlideOut 0.3s ease-in;
}

@keyframes modalSlideOut {
  from {
    transform: translateY(0);
    opacity: 1;
  }
  to {
    transform: translateY(-50px);
    opacity: 0;
  }
}

@media (max-width: 450px) {
  .modal-content {
    width: 72%;
    max-width: 350px;
    border-radius: 12px;
  }
  
  .modal-header {
    padding: 12px 12px 8px;
  }
  
  .modal-title {
    font-size: 16px;
  }
  
  .modal-body {
    padding: 12px;
  }
  
  .prize-name {
    font-size: 18px;
  }
  
  .modal-footer {
    padding: 8px 12px 12px;
  }
  
  .modal-btn {
    padding: 6px 25px;
    font-size: 12px;
  }
}

/* 烟花效果 */
.firework {
  position: fixed;
  width: 4px;
  height: 4px;
  border-radius: 50%;
  pointer-events: none;
  z-index: 999;
}

@keyframes fireworkExplode {
  0% {
    transform: translate(0, 0) scale(1);
    opacity: 1;
  }
  100% {
    transform: translate(var(--tx), var(--ty)) scale(0);
    opacity: 0;
  }
}

.spinning {
  pointer-events: none;
}

        .top-container {
          position: absolute;
          top: 0;
          left: 0;
          background: url(/img/top-bg.png);  
          background-size: 100% 100%;
          background-repeat: no-repeat;
          background-position: center center;
          max-width: 393px;
          width: 100%;
          height: 225px;
          display: flex;
          justify-content: flex-end;
          align-items: flex-start;
          padding: 10px 20px;
          z-index: 100;
        }

.top-container .btn-group {
  display: flex;
  flex-direction: column;
  gap: 10px;
  align-items: flex-end;
  position: relative;
  z-index: 101;
  pointer-events: auto;
}

.top-container .btn-group .btn,
.top-container .btn-group a.btn {
  width: auto;
  min-width: 100px;
  height: 40px;
  background-color: #FFD700;
  border: 1px solid rgba(255, 255, 255, 0.8);
  border-radius: 20px;
  color: #333;
  font-size: 14px;
  font-weight: 500;
  padding: 0 20px;
  cursor: pointer;
  transition: all 0.3s ease;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
  white-space: nowrap;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  text-decoration: none;
  position: relative;
  z-index: 101;
  pointer-events: auto;
}

.top-container .btn-group .btn:hover,
.top-container .btn-group a.btn:hover {
  background-color: #FFC700;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

.top-container .btn-group .btn:active,
.top-container .btn-group a.btn:active {
  transform: translateY(0);
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
}

.wheel-container {
  position: absolute;
  top: 225px;
  left: 50%;
  transform: translateX(-50%);
  width: 100%;
  max-width: 393px;
  z-index: 10;
}

.content-container {
  width: 100%;
  max-width: 393px;
  margin: 0 auto;
  padding-top: 580px;
  padding-bottom: 20px;
  position: relative;
  min-height: 600px;
}

.bottom-container {
  margin-top: -20px;            
  display: flex;
  flex-direction: column;
  align-items: center;
  position: relative;
  z-index: 1;
  min-height: 130px;
}

.bottom-container > div {
  width: 100%;
  max-width: 300px;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.bottom-container label {
  color: #fff;
  font-size: 16px;
  font-weight: 500;
  text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.3);
}

.bottom-container .input {
  width: 100%;
  height: 44px;
  padding: 0 16px;
  border: 2px solid rgba(255, 255, 255, 0.6);
  border-radius: 22px;
  background-color: rgba(255, 255, 255, 0.95);
  font-size: 16px;
  color: #333;
  outline: none;
  transition: all 0.3s ease;
}

.bottom-container .input:focus {
  border-color: #FFD700;
  background-color: #fff;
  box-shadow: 0 0 0 3px rgba(255, 215, 0, 0.2);
}

.bottom-container .input::placeholder {
  color: #999;
}

.rule {
  background: linear-gradient(135deg, #FFF9E5 60%, #FFEDD6 100%);
  border: 1.5px solid #FFC85B;
  border-radius: 14px;
  margin: 18px 0 0 0;
  box-shadow: 0 6px 18px rgba(255, 205, 70, 0.13), 0 1.5px 3px #F6C150;
  padding: 22px 18px 18px 24px;
  color: #7A4300;
  font-size: 16px;
  line-height: 2;
  position: relative;
}

.rule:before {
  content: "活动规则";
  display: inline-block;
  position: absolute;
  top: -17px;
  left: 24px;
  background: #FFD85F;
  color: #B36A12;
  font-weight: bold;
  font-size: 17px;
  border-radius: 10px 10px 10px 0;
  padding: 4px 14px 2px 14px;
  box-shadow: 0 2px 8px rgba(255, 215, 0, 0.18);
  letter-spacing: 1px;
}

.rule-list {
  margin: 0;
  padding-left: 0;
  list-style: none;
}

.rule-list li {
  margin: 0 0 9px 0;
  padding-left: 1.4em;
  text-indent: -1.1em;
  position: relative;
}

.rule-list li:before {
  content: "●";
  color: #FFA500;
  font-size: 1.13em;
  vertical-align: middle;
  margin-right: 0.6em;
}

@media (max-width: 450px) {
  .rule { font-size: 14px; padding: 18px 8px 12px 12px; }
  .rule:before { font-size: 15px; top: -15px; padding: 3px 10px 2px 10px; }
}

/* 右上角通知样式 */
.notification {
  position: fixed;
  top: 20px;
  right: 20px;
  z-index: 10000;
  min-width: 280px;
  max-width: 400px;
  background: linear-gradient(135deg, #4D96FF 0%, #B5D0FE 100%);
  /* border: 2px solid #4D96FF; */
  border-radius: 12px;
  box-shadow: 0 8px 24px rgba(77, 150, 255, 0.15), 0 0 0 1px rgba(77, 150, 255, 0.1);
  padding: 16px 20px;
  color:white;
  animation: slideInRight 0.3s ease-out;
}

.notification-content {
  display: flex;
  align-items: center;
  gap: 12px;
}

.notification-icon {
  font-size: 20px;
  flex-shrink: 0;
}

.notification-message {
  color: #333;
  font-size: 14px;
  font-weight: 500;
  line-height: 1.5;
  word-break: break-word;
}

/* 通知动画 */
@keyframes slideInRight {
  from {
    transform: translateX(100%);
    opacity: 0;
  }
  to {
    transform: translateX(0);
    opacity: 1;
  }
}

.notification-enter-active {
  animation: slideInRight 0.3s ease-out;
}

.notification-leave-active {
  animation: slideOutRight 0.3s ease-in;
}

@keyframes slideOutRight {
  from {
    transform: translateX(0);
    opacity: 1;
  }
  to {
    transform: translateX(100%);
    opacity: 0;
  }
}

@media (max-width: 450px) {
  .notification {
    top: 10px;
    right: 10px;
    left: 10px;
    min-width: auto;
    max-width: none;
  }
}
</style>
