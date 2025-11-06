import apiClient from '../request'

export const userApi = {
  // 获取用户列表
  getList(params) {
    return apiClient.get('/user/list', params)
  },

  // 获取用户详情
  getDetail(id) {
    return apiClient.get(`/user/${id}`)
  },

  // 创建用户
  create(data) {
    return apiClient.post('/user/create', data)
  },

  // 更新用户
  update(id, data) {
    return apiClient.put(`/user/${id}`, data)
  },

  // 删除用户
  delete(id) {
    return apiClient.delete(`/user/${id}`)
  },

  // 用户登录
  login(credentials) {
    return apiClient.post('/auth/login', credentials)
  },

  // 获取当前用户信息
  getCurrentUser() {
    return apiClient.get('/auth/me')
  }
}

