import request from '@/utils/request'
import router from '../../router'

const state = {
  token: null,
  user: null,
  loading: false,
  error: null
}

const getters = {
  isLoggedIn: state => !!state.token,
  currentUser: state => state.user,
  loading: state => state.loading,
  error: state => state.error
}

const actions = {
  // Register a new user
  async register({ commit }, userData) {
    commit('setLoading', true)
    commit('clearError')
    
    try {
      const response = await request.post('/api/user/register', userData)
      
      if (response.status === 'success') {
        // Automatically login after successful registration
        router.push('/login')
        commit('setLoading', false)
        return true
      }
    } catch (error) {
      commit('setError', error.response ? error.response.message : 'Registration failed')
      commit('setLoading', false)
      return false
    }
  },
  
  // Login user
  async login({ commit }, credentials) {
    commit('setLoading', true)
    commit('clearError')
    
    try {
      const response = await request.post('/api/user/login', credentials)
      console.log('Login response:', JSON.stringify(response, null, 2))
      
      if (response.status === 'success') {
        console.log('Login successful')
        const token = response.data.token
        const user = response.user
        console.log(token)
        try {
          // 保存token到localStorage
          localStorage.setItem('electric-scooter-app.token', token)
          
          // Save token to request headers for future requests
          request.defaults.headers.common['Authorization'] = `Bearer ${token}`
          
          // 更新 Vuex 状态
          commit('setToken', token)
          commit('setUser', user)
          commit('setLoading', false)
          
          // 确保在下一个 tick 后再进行路由跳转
          await new Promise(resolve => setTimeout(resolve, 100))
          await router.push('/home')
          
          return true
        } catch (error) {
          console.error('Navigation error:', error)
          commit('setError', '导航失败，请重试')
          commit('setLoading', false)
          return false
        }
      } else {
        // Handle login failure
        const errorMessage = response.data?.message || '登录失败'
        commit('setError', errorMessage)
        commit('setLoading', false)
        return false
      }
    } catch (error) {
      console.error('Login error:', error)
      const errorMessage = error.response?.data?.message || '登录失败'
      commit('setError', errorMessage)
      commit('setLoading', false)
      return false
    }
  },
  
  // Logout user
  logout({ commit }) {
    // Remove token from request headers
    delete request.defaults.headers.common['Authorization']
    
    commit('clearToken')
    commit('clearUser')
    
    router.push('/login')
  },
  
  // Get user profile
  async getProfile({ commit, state }) {
    if (!state.token) return
    
    commit('setLoading', true)
    
    try {
      const response = await request.get('/api/user/profile')
      
      if (response.status === 'success') {
        commit('setUser', response.data)
      }
      
      commit('setLoading', false)
    } catch (error) {
      commit('setError', error.response ? error.response.message : 'Failed to get profile')
      commit('setLoading', false)
      
      // If token is invalid, logout
      if (error.response && error.response.status === 401) {
        commit('clearToken')
        commit('clearUser')
        router.push('/login')
      }
    }
  },
  
  // Update user profile
  async updateProfile({ commit }, userData) {
    commit('setLoading', true)
    commit('clearError')
    
    try {
      const response = await request.put('/api/user/update', userData)
      
      if (response.status === 'success') {
        commit('setUser', response.data)
        commit('setLoading', false)
        return true
      }
    } catch (error) {
      commit('setError', error.response ? error.response.message : 'Failed to update profile')
      commit('setLoading', false)
      return false
    }
  },
  
  // Change password
  async changePassword({ commit }, passwordData) {
    commit('setLoading', true)
    commit('clearError')
    
    try {
      const response = await request.put('/api/user/change-password', passwordData)
      
      if (response.status === 'success') {
        commit('setLoading', false)
        return true
      }
    } catch (error) {
      commit('setError', error.response ? error.response.message : 'Failed to change password')
      commit('setLoading', false)
      return false
    }
  },
  
  // Initialize auth from stored token
  initAuth({ commit, dispatch }) {
    const token = localStorage.getItem('electric-scooter-app.token')
    
    if (token) {
      request.defaults.headers.common['Authorization'] = `Bearer ${token}`
      dispatch('getProfile')
    }
  }
}

const mutations = {
  setToken(state, token) {
    state.token = token
  },
  clearToken(state) {
    state.token = null
  },
  setUser(state, user) {
    state.user = user
  },
  clearUser(state) {
    state.user = null
  },
  setLoading(state, status) {
    state.loading = status
  },
  setError(state, error) {
    state.error = error
  },
  clearError(state) {
    state.error = null
  }
}

export default {
  namespaced: true,
  state,
  getters,
  actions,
  mutations
}
