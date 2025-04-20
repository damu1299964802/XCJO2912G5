<template>
  <div class="order-detail-container">
    <van-nav-bar
      title="Order Details"
      left-arrow
      @click-left="$router.push('/orders')"
    />
    
    <div class="order-info" v-if="order">
      <van-cell-group inset>
        <van-cell title="Order ID" :value="'#' + order.id" />
        <van-cell title="Scooter" :value="order.scooter_code" />
        <van-cell title="Status">
          <template #right-icon>
            <van-tag :type="getOrderStatusType(order.status)">
              {{ getOrderStatusText(order.status) }}
            </van-tag>
          </template>
        </van-cell>
        <van-cell title="Start Time" :value="formatDate(order.start_time || order.created_at)" />
        <van-cell title="End Time" :value="order.end_time ? formatDate(order.end_time) : 'Not ended'" />
        <van-cell title="Duration" :value="formatDuration(order.rental_duration)" />
        <van-cell title="Price" :value="order.price ? order.price + ' €' : 'Not calculated'" />
        <van-cell v-if="order.battery_level" title="Battery Level" :value="order.battery_level + '%'" />
      </van-cell-group>
      
      <div class="action-buttons">
        <template v-if="['ongoing', 'available'].includes(order.status)">
          <van-button type="danger" block @click="endRental" :loading="loading">
            End Rental
          </van-button>
          
          <van-button plain type="primary" block @click="showDurationPicker = true" style="margin-top: 10px">
            Change Duration
          </van-button>
        </template>
        
        <template v-if="order.status === 'pending'">
          <van-button type="primary" block @click="startOrder" :loading="loading">
            Start Rental
          </van-button>
          
          <van-button type="danger" block @click="cancelOrder" :loading="loading" style="margin-top: 10px">
            Cancel Order
          </van-button>
          
          <van-button plain type="primary" block @click="showDurationPicker = true" style="margin-top: 10px">
            Change Duration
          </van-button>
        </template>
      </div>
      
      <van-popup v-model:show="showDurationPicker" position="bottom">
        <van-picker
          title="Select Duration"
          :columns="durationOptions"
          @confirm="changeDuration"
          @cancel="showDurationPicker = false"
          show-toolbar
        />
      </van-popup>
    </div>
    
    <div class="loading-container" v-else-if="loading">
      <van-loading type="spinner" color="#1989fa" />
    </div>
    
    <div class="error-container" v-else>
      <van-empty 
        description="Failed to load order details" 
        image="error" 
      />
      <van-button plain type="primary" @click="fetchOrderData">
        Retry
      </van-button>
    </div>
    
    <van-toast id="van-toast" />
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue'
import { useStore } from 'vuex'
import { useRoute, useRouter } from 'vue-router'
import { Toast, Dialog } from 'vant'

export default {
  name: 'OrderDetail',
  setup() {
    const store = useStore()
    const route = useRoute()
    const router = useRouter()
    
    const orderId = route.params.id
    const showDurationPicker = ref(false)
    
    const order = computed(() => store.state.order.currentOrder)
    const loading = computed(() => store.state.order.loading)
    const error = computed(() => store.state.order.error)
    
    // Duration options for picker
    const durationOptions = [
      { text: '1 hour', value: 1 },
      { text: '8 hours', value: 8 },
      { text: '1 day (24 hours)', value: 24 }
    ]
    
    // Fetch order data if not already in store
    const fetchOrderData = async () => {
      console.log('Current order in store:', order.value);
      console.log('Order ID from route:', orderId);
      
      // 如果已经有订单数据且ID匹配，直接使用已有数据不发请求
      if (order.value && order.value.id == orderId) {
        console.log('Using existing order data from store');
        return order.value;
      }
      
      try {
        // 使用fetchOrderById方法获取订单
        const result = await store.dispatch('order/fetchOrderById', orderId);
        console.log('Order after fetch:', result);
        
        // 如果获取后没有订单，返回订单列表
        if (!result) {
          Toast.fail('Order not found');
          setTimeout(() => {
            router.push('/orders');
          }, 1500);
        }
        
        return result;
      } catch (error) {
        console.error('Error fetching order:', error);
        Toast.fail('Failed to load order details');
        return null;
      }
    }
    
    // Format date
    const formatDate = (dateString) => {
      if (!dateString) return 'Not available'
      
      const date = new Date(dateString)
      return date.toLocaleString()
    }
    
    // Format duration
    const formatDuration = (hours) => {
      if (!hours) return 'Not specified'
      
      if (hours >= 24 && hours % 24 === 0) {
        const days = hours / 24
        return days === 1 ? '1 day' : `${days} days`
      }
      
      return hours === 1 ? '1 hour' : `${hours} hours`
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
    
    // End rental
    const endRental = () => {
      Dialog.confirm({
        title: 'End Rental',
        message: 'Are you sure you want to end this rental?',
        confirmButtonText: 'End Rental',
        cancelButtonText : "Cancel",
        confirmButtonColor: '#ee0a24',
      })
        .then(async () => {
          try {
            // 使用新的endOrder方法
            const success = await store.dispatch('order/endOrder', order.value.id)
            
            if (success) {
              Toast.success('Rental ended successfully')
              // 重新获取订单数据
              fetchOrderData()
              // 注释掉自动刷新代码
              // store.dispatch('order/fetchCurrentOrder')
              // store.dispatch('order/fetchOrderHistory')
            }
          } catch (error) {
            console.error('Error ending rental:', error);
            Toast.fail(store.state.order.error || 'Failed to end rental')
          }
        })
        .catch(() => {
          // User canceled
        })
    }
    
    // 开始租赁
    const startOrder = () => {
      Dialog.confirm({
        title: 'Start Rental',
        message: 'Are you sure you want to start this rental now?',
        confirmButtonText: 'Start Now',
        cancelButtonText: 'Cancel',
      })
        .then(async () => {
          try {
            const success = await store.dispatch('order/startOrder', order.value.id)
            
            if (success) {
              Toast.success('Rental started successfully')
              // 重新获取订单数据
              fetchOrderData()
              // 注释掉自动刷新代码
              // store.dispatch('order/fetchCurrentOrder')
              // store.dispatch('order/fetchOrderHistory')
            }
          } catch (error) {
            console.error('Error starting rental:', error);
            Toast.fail(store.state.order.error || 'Failed to start rental')
          }
        })
        .catch(() => {
          // User canceled
        })
    }
    
    // Cancel order
    const cancelOrder = () => {
      Dialog.confirm({
        title: 'Cancel Order',
        message: 'Are you sure you want to cancel this order?',
        confirmButtonText: 'Cancel Order',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#ee0a24',
      })
        .then(async () => {
          try {
            // 使用新的cancelOrder方法
            const success = await store.dispatch('order/cancelOrder', order.value.id)
            
            if (success) {
              Toast.success('Order cancelled successfully')
              // 重新获取订单数据
              fetchOrderData()
              // 注释掉自动刷新代码
              // store.dispatch('order/fetchCurrentOrder')
              // store.dispatch('order/fetchOrderHistory')
            }
          } catch (error) {
            console.error('Error cancelling order:', error);
            Toast.fail(store.state.order.error || 'Failed to cancel order')
          }
        })
        .catch(() => {
          // User canceled
        })
    }
    
    // Change duration
    const changeDuration = async (option) => {
      showDurationPicker.value = false
      
      if (!['pending', 'ongoing', 'available'].includes(order.value.status)) {
        Toast.fail('Cannot change duration for completed or cancelled orders')
        return
      }
      
      try {
        // 使用新的updateOrderDuration方法
        const success = await store.dispatch('order/updateOrderDuration', {
          orderId: order.value.id,
          duration: option.value
        })
        
        if (success) {
          Toast.success(`Duration changed to ${formatDuration(option.value)}`)
          // 重新获取订单数据
          fetchOrderData()
        }
      } catch (error) {
        console.error('Error changing duration:', error)
        Toast.fail(store.state.order.error || 'Failed to update duration')
      }
    }
    
    // Lifecycle hooks
    onMounted(() => {
      fetchOrderData()
    })
    
    return {
      order,
      loading,
      error,
      showDurationPicker,
      durationOptions,
      formatDate,
      formatDuration,
      getOrderStatusType,
      getOrderStatusText,
      endRental,
      startOrder,
      cancelOrder,
      changeDuration,
      fetchOrderData
    }
  }
}
</script>

<style scoped>
.order-detail-container {
  padding-top: 46px;
  height: 100vh;
  display: flex;
  flex-direction: column;
}

.order-info {
  padding: 16px;
}

.action-buttons {
  margin-top: 20px;
}

.loading-container, .error-container {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
}

.error-container {
  gap: 16px;
}
</style>
