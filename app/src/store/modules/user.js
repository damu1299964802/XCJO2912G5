import request from '@/utils/request'

const state = {
  profile: null,
  loading: false,
  error: null
}

const mutations = {
  SET_PROFILE(state, profile) {
    state.profile = profile
  },
  SET_LOADING(state, loading) {
    state.loading = loading
  },
  SET_ERROR(state, error) {
    state.error = error
  }
}

const actions = {
  async getProfile({ commit }) {
    commit('SET_LOADING', true)
    commit('SET_ERROR', null)
    try {
      const response = await request.get('/api/user/profile')
      if (response.status === 'success' && response.data) {
        commit('SET_PROFILE', response.data)
        return response
      } else {
        throw new Error(response.message || 'Failed to get profile')
      }
    } catch (error) {
      console.error('Error fetching profile:', error)
      commit('SET_ERROR', error.message || 'Failed to load user profile')
      throw error
    } finally {
      commit('SET_LOADING', false)
    }
  },

  async updatePassword({ commit }, { old_password, new_password }) {
    commit('SET_LOADING', true)
    commit('SET_ERROR', null)
    try {
      const response = await request.put('/api/user/password', {
        old_password,
        new_password
      })
      if (response.status === 'success') {
        return response
      } else {
        throw new Error(response.message || 'Failed to update password')
      }
    } catch (error) {
      console.error('Error updating password:', error)
      commit('SET_ERROR', error.message || 'Failed to update password')
      throw error
    } finally {
      commit('SET_LOADING', false)
    }
  }
}

const getters = {
  profile: state => state.profile,
  loading: state => state.loading,
  error: state => state.error
}

export default {
  namespaced: true,
  state,
  mutations,
  actions,
  getters
} 