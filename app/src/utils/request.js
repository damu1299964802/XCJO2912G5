import axios from 'axios'
import store from '@/store'
import { Toast } from 'vant'
import router from '@/router'

// 创建axios实例
const service = axios.create({
  baseURL: process.env.VUE_APP_API_BASE_URL || '/', // 使用环境变量中的API基础URL
  timeout: 10000 // 请求超时时间
})

// 请求拦截器
service.interceptors.request.use(
  config => {
    // 如果有token，添加到请求头
    const token = store.state.auth.token
    if (token) {
      config.headers['Authorization'] = `Bearer ${token}`
    }
    
    // 调试信息
    console.log(`API Request to: ${config.baseURL}${config.url}`, config);
    
    return config
  },
  error => {
    console.error('请求错误:', error)
    return Promise.reject(error)
  }
)

// 响应拦截器
service.interceptors.response.use(
  response => {
    const res = response.data
    if (res.status === 'success') {
      return res
    } else {
      Toast.fail(res.message || '请求失败')
      return Promise.reject(new Error(res.message || '请求失败'))
    }
  },
  error => {
    console.error('请求错误:', error)
    if (error.response) {
      switch (error.response.status) {
        case 401:
          store.dispatch('user/logout')
          router.push('/login')
          break
        default:
          Toast.fail(error.response.data.message || '请求失败')
      }
    } else {
      Toast.fail('网络错误，请稍后重试')
    }
    return Promise.reject(error)
  }
)

// 输出当前环境和API基础URL到控制台
console.log('Current Environment:', process.env.NODE_ENV);
console.log('API Base URL:', process.env.VUE_APP_API_BASE_URL);

export default service
