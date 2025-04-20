import request from '@/utils/request'

const state = {
  paymentMethods: [],
  defaultPaymentMethod: null
}

const mutations = {
  SET_PAYMENT_METHODS(state, methods) {
    console.log('SET_PAYMENT_METHODS mutation:', methods)
    state.paymentMethods = methods
  },
  SET_DEFAULT_PAYMENT_METHOD(state, method) {
    console.log('SET_DEFAULT_PAYMENT_METHOD mutation:', method)
    state.defaultPaymentMethod = method
  },
  ADD_PAYMENT_METHOD(state, method) {
    console.log('ADD_PAYMENT_METHOD mutation:', method)
    state.paymentMethods.push(method)
    if (method.is_default) {
      state.defaultPaymentMethod = method
    }
  },
  REMOVE_PAYMENT_METHOD(state, id) {
    console.log('REMOVE_PAYMENT_METHOD mutation:', id)
    state.paymentMethods = state.paymentMethods.filter(method => method.id !== id)
    if (state.defaultPaymentMethod && state.defaultPaymentMethod.id === id) {
      state.defaultPaymentMethod = state.paymentMethods[0] || null
    }
  }
}

const actions = {
  // 获取用户的支付方式列表
  async fetchPaymentMethods({ commit }) {
    console.log('fetchPaymentMethods action called')
    try {
      const response = await request.get('/api/payment-methods')
      console.log('fetchPaymentMethods response:', response)
      commit('SET_PAYMENT_METHODS', response.data)
      const defaultMethod = response.data.find(method => method.is_default)
      if (defaultMethod) {
        commit('SET_DEFAULT_PAYMENT_METHOD', defaultMethod)
      }
    } catch (error) {
      console.error('Failed to fetch payment methods:', error)
      throw error
    }
  },

  // 添加新的支付方式
  async addPaymentMethod({ commit }, paymentMethod) {
    try {
      const response = await request.post('/api/payment-methods/add', paymentMethod)
      if (response.data) {
        commit('ADD_PAYMENT_METHOD', response.data)
        return response.data
      }
      throw new Error('添加支付方式失败')
    } catch (error) {
      console.error('添加支付方式失败:', error)
      throw error
    }
  },

  // 设置默认支付方式
  async setDefaultPaymentMethod({ commit, state }, id) {
    console.log('setDefaultPaymentMethod action called with ID:', id)
    try {
      const response = await request.post('/api/payment-methods/set-default', {
        payment_method_id: id
      })
      console.log('setDefaultPaymentMethod response:', response)
      
      // 直接更新本地状态，而不是重新获取
      const methods = state.paymentMethods.map(method => ({
        ...method,
        is_default: method.id === id ? 1 : 0
      }))
      commit('SET_PAYMENT_METHODS', methods)
      const newDefaultMethod = methods.find(method => method.id === id)
      if (newDefaultMethod) {
        commit('SET_DEFAULT_PAYMENT_METHOD', newDefaultMethod)
      }
      
      return response.data
    } catch (error) {
      console.error('Failed to set default payment method:', error)
      throw error
    }
  },

  // 删除支付方式
  async deletePaymentMethod({ commit }, id) {
    console.log('deletePaymentMethod action called with ID:', id)
    try {
      await request.post('/api/payment-methods/delete', {
        payment_method_id: id
      })
      commit('REMOVE_PAYMENT_METHOD', id)
    } catch (error) {
      console.error('Failed to delete payment method:', error)
      throw error
    }
  },

  // 处理支付
  async processPayment({ state }, { orderId, paymentMethodId }) {
    console.log('processPayment action called:', { orderId, paymentMethodId })
    try {
      const response = await request.post('/api/payment/process', {
        order_id: orderId,
        payment_method_id: paymentMethodId
      })
      console.log('processPayment response:', response)
      return response.data
    } catch (error) {
      console.error('Failed to process payment:', error)
      throw error
    }
  }
}

const getters = {
  paymentMethods: state => state.paymentMethods,
  defaultPaymentMethod: state => state.defaultPaymentMethod
}

export default {
  namespaced: true,
  state,
  mutations,
  actions,
  getters
} 