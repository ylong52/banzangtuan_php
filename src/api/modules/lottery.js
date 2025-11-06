import apiClient from '../request'

export const lotteryApi = {
  // 获取奖品列表
  getList() {
    return apiClient.get('/h5/lottery/getPrizeList')
  },
  openLottery(data) {
    return apiClient.post('/h5/lottery/openLottery', data); //开奖
  },
}