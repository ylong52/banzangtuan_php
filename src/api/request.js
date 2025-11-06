class ApiClient {
  constructor(baseURL) {
    this.baseURL = baseURL || '/api'
    this.timeout = 10000
  }

  async request(url, options = {}) {
    const controller = new AbortController()
    const timeoutId = setTimeout(() => controller.abort(), this.timeout)

    const token = localStorage.getItem('token')
    const headers = {
      'Content-Type': 'application/json',
      ...(token && { Authorization: `Bearer ${token}` }),
      ...options.headers
    }

    try {
      const response = await fetch(`${this.baseURL}${url}`, {
        ...options,
        headers,
        signal: controller.signal
      })

      clearTimeout(timeoutId)

      if (!response.ok) {
        if (response.status === 401) {
          localStorage.removeItem('token')
          window.location.href = '/login'
        }
        throw new Error(`HTTP ${response.status}: ${response.statusText}`)
      }

      const data = await response.json()
      
      if (data.code === 200) {
        return data.data
      } else {
        throw new Error(data.message || data.msg || '请求失败')
      }
    } catch (error) {
      clearTimeout(timeoutId)
      throw error
    }
  }

  get(url, params) {
    const queryString = new URLSearchParams(params).toString()
    const fullUrl = queryString ? `${url}?${queryString}` : url
    return this.request(fullUrl, { method: 'GET' })
  }

  post(url, data) {
    return this.request(url, {
      method: 'POST',
      body: JSON.stringify(data)
    })
  }

  put(url, data) {
    return this.request(url, {
      method: 'PUT',
      body: JSON.stringify(data)
    })
  }

  delete(url) {
    return this.request(url, { method: 'DELETE' })
  }
}

export default new ApiClient(import.meta.env.VITE_API_BASE_URL)

