import request from '@/utils/request'

const state = {
  feedbacks: [],
  currentFeedback: null,
  loading: false,
  error: null
}

const getters = {
  allFeedbacks: state => state.feedbacks,
  currentFeedback: state => state.currentFeedback,
  loading: state => state.loading,
  error: state => state.error
}

const actions = {
  // Get user feedbacks
  async fetchFeedbacks({ commit }) {
    commit('setLoading', true)
    
    try {
      const response = await request.get('/api/feedbacks')
      
      if (response.status === 'success') {
        commit('setFeedbacks', response.data)
      }
      
      commit('setLoading', false)
    } catch (error) {
      console.error('Error fetching feedbacks:', error)
      commit('setError', error.response?.data?.message || error.message || 'Failed to fetch feedbacks')
      commit('setLoading', false)
    }
  },
  
  // Create new feedback
  async createFeedback({ commit, dispatch }, feedbackData) {
    commit('setLoading', true)
    commit('clearError')
    
    try {
      console.log('Sending feedback data to API:', feedbackData)
      const response = await request.post('/api/feedbacks/create', feedbackData)
      console.log('API response for feedback creation:', response)
      
      if (response.status === 'success') {
        // Refresh feedbacks after creating a new one
        dispatch('fetchFeedbacks')
        commit('setLoading', false)
        return true
      } else {
        const errorMsg = response.message || 'Unknown error'
        console.error('API returned error:', errorMsg)
        commit('setError', errorMsg)
        commit('setLoading', false)
        return false
      }
    } catch (error) {
      console.error('Error creating feedback:', error)
      commit('setError', error.response?.data?.message || error.message || 'Failed to create feedback')
      commit('setLoading', false)
      return false
    }
  },
  
  // Set current feedback
  setCurrentFeedback({ commit }, feedback) {
    commit('setCurrentFeedback', feedback)
  },
  
  // Clear current feedback
  clearCurrentFeedback({ commit }) {
    commit('setCurrentFeedback', null)
  }
}

const mutations = {
  setFeedbacks(state, feedbacks) {
    state.feedbacks = feedbacks
  },
  setCurrentFeedback(state, feedback) {
    state.currentFeedback = feedback
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
