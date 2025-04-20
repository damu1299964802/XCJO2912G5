import request from '@/utils/request'
import { Toast } from 'vant'

const state = {
  currentOrder: null,
  currentOrders: [],
  orderHistory: [],
  loading: false,
  error: null
}

const getters = {
  hasActiveOrder: state => !!state.currentOrder,
  currentOrder: state => state.currentOrder,
  currentOrders: state => state.currentOrders || [],
  orderHistory: state => state.orderHistory,
  loading: state => state.loading,
  error: state => state.error
}

const actions = {
  // 获取当前活动订单
  async fetchCurrentOrder({ commit }) {
    commit('setLoading', true)
    try {
      // 添加时间戳以避免缓存
      const timestamp = new Date().getTime()
      const response = await request.get(`/api/orders/current`)
      console.log('API Response for current order:', response)
      
      if (response.status === 'success') {
        // 检查数据格式，如果是单个订单，直接设置；如果是数组，取第一个
        if (Array.isArray(response.data)) {
          commit('setCurrentOrders', response.data)
          commit('setCurrentOrder', response.data.length > 0 ? response.data[0] : null)
        } else {
          commit('setCurrentOrders', response.data ? [response.data] : [])
          commit('setCurrentOrder', response.data)
        }
      } else {
        commit('setCurrentOrder', null)
        commit('setCurrentOrders', [])
      }
      commit('setLoading', false)
    } catch (error) {
      console.error('Error fetching current order:', error)
      commit('setError', error.response?.data?.message || 'Failed to get current order')
      commit('setLoading', false)
    }
  },

  // 获取订单详情
  async fetchOrderById({ commit, state }, orderId) {
    // 如果已经有相同ID的订单在state中，不需要再次获取
    if (state.currentOrder && state.currentOrder.id == orderId) {
      console.log('Order already in state, returning cached data for ID:', orderId);
      return state.currentOrder;
    }
    
    // 检查历史订单中是否有该ID的订单
    if (state.orderHistory && state.orderHistory.length > 0) {
      const foundOrder = state.orderHistory.find(order => order.id == orderId);
      if (foundOrder) {
        console.log('Order found in history, returning cached data for ID:', orderId);
        commit('setCurrentOrder', foundOrder);
        return foundOrder;
      }
    }
    
    // 都没找到，则从API获取
    commit('setLoading', true);
    
    try {
      const response = await request.get(`/api/orders/${orderId}`);
      console.log('API Response for order detail:', response);
      
      if (response.status === 'success') {
        commit('setCurrentOrder', response.data);
        commit('setLoading', false);
        return response.data;
      } else {
        commit('setCurrentOrder', null);
        commit('setError', 'Order not found');
        commit('setLoading', false);
        return null;
      }
    } catch (error) {
      console.error('Error fetching order by ID:', error);
      commit('setError', error.response?.data?.message || 'Failed to get order details');
      commit('setLoading', false);
      return null;
    }
  },

  // 还车
  async returnScooter({ commit }, { order_id, end_time }) {
    commit('setLoading', true)
    try {
      const response = await request.post('/api/orders/return', {
        order_id,
        end_time
      })

      if (response.status === 'success') {
        commit('setCurrentOrder', null)
        Toast.success('Return successful')
        commit('setLoading', false)
        return true
      }
    } catch (error) {
      commit('setError', error.response?.data?.message || 'Failed to return scooter')
      commit('setLoading', false)
      return false
    }
  },

  // 取消订单
  async cancelOrder({ commit }, orderId) {
    commit('setLoading', true)
    commit('clearError')
    
    try {
      const response = await request.put('/api/orders/cancel', {
        id: orderId
      })
      
      if (response.status === 'success') {
        // 更新currentOrder状态
        commit('updateOrderStatus', 'cancelled')
        Toast.success('Order cancelled successfully')
        commit('setLoading', false)
        return true
      } else {
        commit('setError', response.message || 'Failed to cancel order')
        commit('setLoading', false)
        return false
      }
    } catch (error) {
      console.error('Error cancelling order:', error)
      commit('setError', error.response?.data?.message || 'Failed to cancel order')
      commit('setLoading', false)
      return false
    }
  },
  
  // 更新订单时长
  async updateOrderDuration({ commit, state }, { orderId, duration }) {
    if (!state.currentOrder || state.currentOrder.id != orderId) {
      commit('setError', 'Order not found')
      return false
    }
    
    commit('setLoading', true)
    commit('clearError')
    
    try {
      const response = await request.put('/api/orders/update', {
        id: orderId,
        rental_duration: duration
      })
      
      if (response.status === 'success') {
        // 更新currentOrder
        commit('updateOrderDuration', duration)
        Toast.success('Order duration updated')
        commit('setLoading', false)
        return true
      } else {
        commit('setError', response.message || 'Failed to update order duration')
        commit('setLoading', false)
        return false
      }
    } catch (error) {
      console.error('Error updating order duration:', error)
      commit('setError', error.response?.data?.message || 'Failed to update order duration')
      commit('setLoading', false)
      return false
    }
  },
  
  // 开始租赁
  async startOrder({ commit, state }, orderId) {
    if (!state.currentOrder || state.currentOrder.id != orderId) {
      commit('setError', 'Order not found')
      return false
    }
    
    commit('setLoading', true)
    commit('clearError')
    
    try {
      const response = await request.put('/api/orders/update', {
        id: orderId,
        start: true
      })
      
      if (response.status === 'success') {
        // 更新currentOrder
        commit('updateOrderStatus', 'ongoing')
        commit('setCurrentOrder', response.data)
        Toast.success('Rental started')
        commit('setLoading', false)
        return true
      } else {
        commit('setError', response.message || 'Failed to start rental')
        commit('setLoading', false)
        return false
      }
    } catch (error) {
      console.error('Error starting rental:', error)
      commit('setError', error.response?.data?.message || 'Failed to start rental')
      commit('setLoading', false)
      return false
    }
  },
  
  // 结束租赁
  async endOrder({ commit, state }, orderId) {
    if (!state.currentOrder || state.currentOrder.id != orderId) {
      commit('setError', 'Order not found')
      return false
    }
    
    commit('setLoading', true)
    commit('clearError')
    
    try {
      const response = await request.put('/api/orders/update', {
        id: orderId,
        end: true
      })
      
      if (response.status === 'success') {
        // 更新currentOrder
        commit('updateOrderStatus', 'completed')
        commit('setCurrentOrder', response.data)
        Toast.success('Rental ended')
        commit('setLoading', false)
        return true
      } else {
        commit('setError', response.message || 'Failed to end rental')
        commit('setLoading', false)
        return false
      }
    } catch (error) {
      console.error('Error ending rental:', error)
      commit('setError', error.response?.data?.message || 'Failed to end rental')
      commit('setLoading', false)
      return false
    }
  },

  // 获取订单历史
  async fetchOrderHistory({ commit }) {
    commit('setLoading', true)
    try {
      // 添加时间戳以避免缓存
      const timestamp = new Date().getTime()
      const response = await request.get(`/api/orders/history`)
      console.log('API Response for order history:', response)
      
      if (response.status === 'success') {
        // 确保订单历史总是以数组形式存储
        const historyData = Array.isArray(response.data) ? response.data : 
                           (response.data ? [response.data] : [])
        commit('setOrderHistory', historyData)
      } else {
        commit('setOrderHistory', [])
      }
      commit('setLoading', false)
    } catch (error) {
      console.error('Error fetching order history:', error)
      commit('setError', error.response?.data?.message || 'Failed to get order history')
      commit('setLoading', false)
    }
  },
  
  // 检查用户是否有pending订单
  async checkPendingOrder({ commit, state }) {
    // 先检查当前订单状态
    if (state.currentOrder && state.currentOrder.status === 'pending') {
      return { hasPending: true, order: state.currentOrder }
    }
    
    // 在订单历史中检查
    if (state.orderHistory && state.orderHistory.length > 0) {
      const pendingOrder = state.orderHistory.find(order => order.status === 'pending')
      if (pendingOrder) {
        return { hasPending: true, order: pendingOrder }
      }
    }
    
    // 如果本地没找到，从服务器获取最新订单状态
    commit('setLoading', true)
    try {
      const response = await request.get('/api/orders/check-pending')
      console.log('API Response for pending order check:', response)
      
      if (response.status === 'success' && response.data) {
        // 如果有pending订单，将其设置为currentOrder
        if (response.data.hasPending) {
          commit('setCurrentOrder', response.data.order)
        }
        commit('setLoading', false)
        return response.data
      } else {
        commit('setLoading', false)
        return { hasPending: false, order: null }
      }
    } catch (error) {
      console.error('Error checking pending order:', error)
      commit('setError', error.response?.data?.message || 'Failed to check pending orders')
      commit('setLoading', false)
      return { hasPending: false, order: null, error: true }
    }
  }
}

const mutations = {
  setCurrentOrder(state, order) {
    state.currentOrder = order
  },
  setCurrentOrders(state, orders) {
    state.currentOrders = orders
  },
  setOrderHistory(state, orders) {
    state.orderHistory = orders
  },
  setLoading(state, status) {
    state.loading = status
  },
  setError(state, error) {
    state.error = error
  },
  clearError(state) {
    state.error = null
  },
  updateOrderStatus(state, status) {
    if (state.currentOrder) {
      state.currentOrder.status = status
    }
  },
  updateOrderDuration(state, duration) {
    if (state.currentOrder) {
      state.currentOrder.rental_duration = duration
    }
  }
}

export default {
  namespaced: true,
  state,
  getters,
  actions,
  mutations
}
