<template>
  <div class="orders-container">
    <van-nav-bar title="My Orders" />
    
    <van-tabs v-model:active="activeTab" sticky @change="onTabChange">
      <van-tab title="Current Orders">
        <van-pull-refresh v-model="refreshingCurrent" @refresh="refreshCurrentOrders">
          <div class="order-list" v-if="currentOrders.length > 0">
            <van-cell 
              v-for="order in currentOrders" 
              :key="order.id" 
              :title="'Order #' + order.id"
              is-link
              @click="viewOrderDetail(order)"
            >
              <template #label>
                <div class="order-info">
                  <div class="order-scooter">Scooter: {{ order.scooter_code }}</div>
                  <div class="order-time">Start: {{ formatDate(order.start_time || order.created_at) }}</div>
                  <div class="order-status">
                    <van-tag :type="getOrderStatusType(order.status)">
                      {{ getOrderStatusText(order.status) }}
                    </van-tag>
                  </div>
                  <div v-if="order.battery_level" class="order-battery">
                    Battery: {{ order.battery_level }}%
                  </div>
                </div>
              </template>
            </van-cell>
          </div>
          <van-empty v-else description="No current orders" />
        </van-pull-refresh>
      </van-tab>
      
      <van-tab title="Order History">
        <van-pull-refresh v-model="refreshingHistory" @refresh="refreshOrderHistory">
          <div class="order-list" v-if="historyOrders.length > 0">
            <van-cell 
              v-for="order in historyOrders" 
              :key="order.id" 
              :title="'Order #' + order.id"
              is-link
              @click="viewOrderDetail(order)"
            >
              <template #label>
                <div class="order-info">
                  <div class="order-scooter">Scooter: {{ order.scooter_code }}</div>
                  <div class="order-time">
                    {{ formatDate(order.start_time || order.created_at) }} - {{ formatDate(order.end_time || order.updated_at) }}
                  </div>
                  <div class="order-price" v-if="order.price">Price: {{ order.price }} €</div>
                  <div class="order-status">
                    <van-tag :type="getOrderStatusType(order.status)">
                      {{ getOrderStatusText(order.status) }}
                    </van-tag>
                  </div>
                </div>
              </template>
            </van-cell>
          </div>
          <van-empty v-else description="No order history" />
        </van-pull-refresh>
      </van-tab>
    </van-tabs>
    
    <van-toast id="van-toast" />
  </div>
</template>

<script>
import { ref, computed, onMounted, watch, onActivated, onBeforeUnmount } from 'vue'
import { useStore } from 'vuex'
import { useRouter } from 'vue-router'
import { Toast } from 'vant'

export default {
  name: 'Orders',
  setup() {
    const store = useStore()
    const router = useRouter()
    
    const activeTab = ref(0)
    const refreshingCurrent = ref(false)
    const refreshingHistory = ref(false)
    let refreshInterval = null
    
    // 针对从后端返回的数据格式进行适配
    const currentOrders = computed(() => {
      const current = store.state.order.currentOrder
      
      // 如果current是数组，直接返回；如果不是，判断是否存在并封装成数组
      if (Array.isArray(current)) {
        return current
      } else if (current) {
        return [current]
      }
      return []
    })
    
    const historyOrders = computed(() => {
      const history = store.state.order.orderHistory
      return Array.isArray(history) ? history : (history ? [history] : [])
    })
    
    const loading = computed(() => store.state.order.loading)
    const error = computed(() => store.state.order.error)
    
    // Format date
    const formatDate = (dateString) => {
      if (!dateString) return 'Not available'
      
      const date = new Date(dateString)
      return date.toLocaleString()
    }
    
    // Get order status type for tag
    const getOrderStatusType = (status) => {
      switch (status) {
        case 'pending':
          return 'warning'
        case 'ongoing':
          return 'success'
        case 'available':
          return 'success'
        case 'completed':
          return 'primary'
        case 'cancelled':
          return 'danger'
        default:
          return 'default'
      }
    }
    
    // Get order status text
    const getOrderStatusText = (status) => {
      switch (status) {
        case 'pending':
          return 'Pending'
        case 'ongoing':
          return 'Ongoing'
        case 'available':
          return 'Available'
        case 'completed':
          return 'Completed'
        case 'cancelled':
          return 'Cancelled'
        default:
          return status.charAt(0).toUpperCase() + status.slice(1)
      }
    }
    
    // View order detail
    const viewOrderDetail = (order) => {
      // 保存当前订单到store，以便详情页使用
      // 确保设置完整的订单数据
      store.commit('order/setCurrentOrder', order)
      
      // 打印详细日志方便调试
      console.log('Navigating to order detail, saved order data:', order)
      console.log('Order ID being navigated to:', order.id)
      
      // 跳转到订单详情页
      router.push(`/orders/${order.id}`)
    }
    
    // Refresh current orders
    const refreshCurrentOrders = async () => {
      await store.dispatch('order/fetchCurrentOrder')
      refreshingCurrent.value = false
      
      // 打印当前订单数据以便调试
      console.log('Current order data:', store.state.order.currentOrder)
      console.log('Computed currentOrders:', currentOrders.value)
      
      if (store.state.order.error) {
        Toast.fail(store.state.order.error)
      }
    }
    
    // Refresh order history
    const refreshOrderHistory = async () => {
      await store.dispatch('order/fetchOrderHistory')
      refreshingHistory.value = false
      
      // 打印历史订单数据以便调试
      console.log('Order history data:', store.state.order.orderHistory)
      console.log('Computed historyOrders:', historyOrders.value)
      
      if (store.state.order.error) {
        Toast.fail(store.state.order.error)
      }
    }
    
    // Fetch data based on active tab
    const fetchTabData = async (tabIndex) => {
      if (tabIndex === 0) {
        await store.dispatch('order/fetchCurrentOrder')
      } else {
        await store.dispatch('order/fetchOrderHistory')
      }
      
      if (store.state.order.error) {
        Toast.fail(store.state.order.error)
      }
    }
    
    // 设置自动刷新
    const setupAutoRefresh = () => {
      // 清除可能已存在的定时器
      clearInterval(refreshInterval)
      
      // 注释掉自动刷新代码
      /* 
      // 设置定时刷新（每15秒刷新一次）
      refreshInterval = setInterval(() => {
        console.log('Auto refreshing orders...')
        fetchTabData(activeTab.value)
      }, 15000)
      */
    }
    
    // Lifecycle hooks
    onMounted(async () => {
      // Fetch current orders initially
      await fetchTabData(activeTab.value)
      // 注释掉自动刷新设置
      // setupAutoRefresh()
    })
    
    // 组件激活时刷新数据
    onActivated(() => {
      console.log('Orders component activated, refreshing data...')
      fetchTabData(activeTab.value)
      // 注释掉自动刷新设置
      // setupAutoRefresh()
    })
    
    // 组件卸载前清除定时器
    onBeforeUnmount(() => {
      clearInterval(refreshInterval)
    })
    
    // Watch for tab changes
    const onTabChange = (index) => {
      fetchTabData(index)
    }
    
    return {
      activeTab,
      refreshingCurrent,
      refreshingHistory,
      currentOrders,
      historyOrders,
      loading,
      formatDate,
      getOrderStatusType,
      getOrderStatusText,
      viewOrderDetail,
      refreshCurrentOrders,
      refreshOrderHistory,
      onTabChange
    }
  }
}
</script>

<style scoped>
.orders-container {
  padding-top: 46px;
  padding-bottom: 50px;
  height: 100vh;
  display: flex;
  flex-direction: column;
}

.order-list {
  padding-bottom: 16px;
}

.order-info {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.order-scooter {
  font-weight: bold;
}

.order-time, .order-price, .order-battery {
  font-size: 14px;
  color: #646566;
}

.order-status {
  margin-top: 5px;
}
</style>
