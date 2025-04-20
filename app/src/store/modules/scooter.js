import request from '@/utils/request'

const state = {
  scooters: [],
  currentScooter: null,
  loading: false,
  error: null
}

const getters = {
  allScooters: state => state.scooters,
  availableScooters: state => state.scooters.filter(scooter => scooter.status === 'available'),
  currentScooter: state => state.currentScooter,
  loading: state => state.loading,
  error: state => state.error
}

const actions = {
  // Get all scooters
  async fetchScooters({ commit }) {
    commit('setLoading', true)
    console.log('Fetching scooters...')
    
    try {
      // 添加时间戳以避免缓存
      const timestamp = new Date().getTime()
      const response = await request.get(`/api/scooters`)
      console.log('Response:', response)
      
      if (response.status === 'success') {
        commit('setScooters', response.data)
        console.log('Scooters fetched successfully.')
      } else {
        commit('setError', 'Failed to fetch scooters')
        console.error('Failed to fetch scooters.')
      }
      
      commit('setLoading', false)
    } catch (error) {
      commit('setError', error.response ? error.response.message : 'Failed to fetch scooters')
      console.error('Error fetching scooters:', error)
      commit('setLoading', false)
    }
  },
  
  // Get available scooters
  async fetchAvailableScooters({ commit }) {
    commit('setLoading', true)
    
    try {
      // 添加时间戳以避免缓存
      const timestamp = new Date().getTime()
      const response = await request.get(`/api/scooters/available`)
      
      if (response.status === 'success') {
        commit('setScooters', response.data)
      } else {
        commit('setError', response.message || 'Failed to fetch available scooters')
      }
      
      commit('setLoading', false)
    } catch (error) {
      commit('setError', error.response ? error.response.message : 'Failed to fetch available scooters')
      commit('setLoading', false)
    }
  },
  
  // Get scooter by ID
  async fetchScooterById({ commit }, scooterId) {
    commit('setLoading', true)
    commit('setCurrentScooter', null) // 重置当前滑板车数据
    
    try {
      console.log(`Fetching scooter with ID: ${scooterId}`)
      // 添加时间戳以避免缓存
      const timestamp = new Date().getTime()
      const response = await request.get(`/api/scooters/${scooterId}`)
      
      if (response.status === 'success') {
        commit('setCurrentScooter', response.data)
        console.log('Fetched scooter details:', response.data)
      } else {
        commit('setError', response.message || 'Failed to fetch scooter details')
        console.error('Failed to fetch scooter details:', response.message)
      }
      
      commit('setLoading', false)
    } catch (error) {
      commit('setError', error.response ? error.response.message : 'Failed to fetch scooter details')
      console.error('Error fetching scooter details:', error)
      commit('setLoading', false)
    }
  },
  
  // Clear current scooter
  clearCurrentScooter({ commit }) {
    commit('setCurrentScooter', null)
  },
  
  // Create rental order
  async createOrder({ commit }, orderData) {
    commit('setLoading', true)
    commit('clearError')
    
    // 默认start_now为false，可以通过orderData传入
    if (orderData.start_now === undefined) {
      orderData.start_now = false
    }
    
    try {
      const response = await request.post('/api/orders/create', orderData)
      
      if (response.status === 'success') {
        commit('setLoading', false)
        return true
      } else {
        commit('setError', response.message || 'Failed to create order')
        commit('setLoading', false)
        return false
      }
    } catch (error) {
      commit('setError', error.response ? error.response.message : 'Failed to create order')
      commit('setLoading', false)
      return false
    }
  }
}

const mutations = {
  setScooters(state, scooters) {
    state.scooters = scooters
  },
  setCurrentScooter(state, scooter) {
    state.currentScooter = scooter
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
