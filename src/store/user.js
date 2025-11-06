import { defineStore } from 'pinia'
import { userApi } from '@/api/modules/user'

export const useUserStore = defineStore('user', {
  state: () => ({
    userInfo: null,
    token: localStorage.getItem('token') || '',
    permissions: []
  }),

  getters: {
    isLoggedIn: (state) => !!state.token,
    userName: (state) => state.userInfo?.name || '',
    hasPermission: (state) => (permission) => {
      return state.permissions.includes(permission)
    }
  },

  actions: {
    async login(credentials) {
      try {
        const { token, user } = await userApi.login(credentials)
        this.token = token
        this.userInfo = user
        localStorage.setItem('token', token)
        return true
      } catch (error) {
        console.error('Login failed:', error)
        return false
      }
    },

    async logout() {
      this.token = ''
      this.userInfo = null
      this.permissions = []
      localStorage.removeItem('token')
    },

    async fetchUserInfo() {
      try {
        this.userInfo = await userApi.getCurrentUser()
      } catch (error) {
        console.error('Fetch user info failed:', error)
      }
    }
  }
})

