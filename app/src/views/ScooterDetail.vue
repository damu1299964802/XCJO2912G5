<template>
  <div class="scooter-detail-container">
    <van-nav-bar
      title="Scooter Details"
      left-arrow
      @click-left="$router.push('/home')"
    />
    
    <div class="scooter-info" v-if="scooter">
      <div class="scooter-header">
        <div class="scooter-code">{{ scooter.scooter_code }}</div>
        <van-tag :type="getStatusType(scooter.status)">{{ getStatusText(scooter.status) }}</van-tag>
      </div>
      
      <van-cell-group inset>
        <van-cell title="Battery Level">
          <template #right-icon>
            <div class="battery-indicator">
              <van-icon name="battery-charge" :color="getBatteryColor(scooter.battery_level)" />
              <span>{{ scooter.battery_level }}%</span>
            </div>
          </template>
        </van-cell>
        <van-cell title="Location" :value="scooter.location || 'Not specified'" />
        <van-cell title="Last Maintenance" :value="formatDate(scooter.last_maintenance_date)" />
        <van-cell title="Hourly Rate" :value="`${hourlyRate} €/h`" />
      </van-cell-group>
      
      <div class="rental-section" v-if="scooter.status === 'available'">
        <h3>Rent this scooter</h3>
        
        <van-cell-group inset>
          <van-cell title="Rental Duration">
            <template #right-icon>
              <van-radio-group v-model="rentalDuration" direction="horizontal">
                <van-radio name="1">1h</van-radio>
                <van-radio name="8">8h</van-radio>
                <van-radio name="24">1d</van-radio>
              </van-radio-group>
            </template>
          </van-cell>
          
          <van-cell title="Start Now">
            <template #right-icon>
              <van-switch v-model="startNow" size="24" />
            </template>
          </van-cell>
          
          <van-cell v-if="rentalDuration">
            <template #title>
              <div class="rental-price">
                <span>Total Price:</span>
                <span class="price">{{ calculatePrice(rentalDuration) }} €</span>
              </div>
            </template>
          </van-cell>
          
          <van-cell title="Payment Method">
            <template #right-icon>
              <van-button 
                size="small" 
                type="primary" 
                plain 
                @click="showPaymentProcess = true"
              >
                {{ selectedPaymentMethod ? 'Change' : 'Select' }}
              </van-button>
            </template>
            <template #value>
              <span v-if="selectedPaymentMethod">
                {{ selectedPaymentMethod.card_type }} ****{{ selectedPaymentMethod.card_number_last4 }}
              </span>
              <span v-else class="no-payment-method">No payment method selected</span>
            </template>
          </van-cell>
        </van-cell-group>
        
        <div class="action-buttons">
          <van-button 
            type="primary" 
            block 
            @click="rentScooter" 
            :loading="loading"
            :disabled="!selectedPaymentMethod"
          >
            Rent Now
          </van-button>
        </div>
      </div>
      
      <div class="unavailable-message" v-else>
        <van-empty 
          description="This scooter is currently unavailable for rental" 
          image="error" 
        />
      </div>
    </div>
    
    <div class="loading-container" v-else-if="loading">
      <van-loading type="spinner" color="#1989fa" />
    </div>
    
    <div class="error-container" v-else-if="error">
      <van-empty 
        description="Failed to load scooter details" 
        image="error" 
      />
      <van-button plain type="primary" @click="fetchScooterData">
        Retry
      </van-button>
    </div>
    
    <!-- 添加支付处理组件 -->
    <payment-process 
      v-model:modelValue="showPaymentProcess" 
      :amount="parseFloat(calculatePrice(rentalDuration))"
      :order-id="tempOrderId"
      @payment-success="onPaymentSuccess"
      @payment-cancel="onPaymentCancel"
      @payment-error="onPaymentError"
      @select-payment-method="onSelectPaymentMethod"
    />
    
    <van-toast id="van-toast" />
  </div>
</template>

<script>
import { ref, computed, onMounted, watch, onActivated } from 'vue'
import { useStore } from 'vuex'
import { useRoute, useRouter } from 'vue-router'
import { Toast, Dialog } from 'vant'
import PaymentProcess from '@/components/PaymentProcess.vue'

export default {
  name: 'ScooterDetail',
  components: {
    PaymentProcess
  },
  setup() {
    const store = useStore()
    const route = useRoute()
    const router = useRouter()
    
    const scooterId = ref(route.params.id)
    const rentalDuration = ref('1') // Default to 1 hour
    const startNow = ref(false)
    const showPaymentProcess = ref(false)
    const tempOrderId = ref(null)
    
    const scooter = computed(() => store.getters['scooter/currentScooter'])
    const loading = computed(() => store.getters['scooter/loading'])
    const error = computed(() => store.getters['scooter/error'])
    
    const hourlyRate = ref(10) // 每小时10元
    const selectedPaymentMethod = ref(null)
    
    // Fetch scooter data
    const fetchScooterData = async () => {
      // 在获取新数据前先清除当前滑板车数据，防止显示旧数据
      store.commit('scooter/setCurrentScooter', null)
      
      console.log('Fetching scooter with ID:', scooterId.value)
      await store.dispatch('scooter/fetchScooterById', scooterId.value)
      
      // 添加调试信息，显示获取结果
      console.log('Fetch result for ID', scooterId.value, ':', store.getters['scooter/currentScooter'])
      
      if (store.getters['scooter/error']) {
        Toast.fail(store.getters['scooter/error'])
      } else if (!store.getters['scooter/currentScooter']) {
        Toast.fail(`Failed to load scooter #${scooterId.value}`)
      } else {
        Toast.success(`Loaded scooter #${store.getters['scooter/currentScooter'].scooter_code}`)
      }
    }
    
    // 监听路由参数变化，确保滑板车ID变化时重新加载数据
    watch(() => route.params.id, (newId) => {
      if (route.path.startsWith('/scooter/')) {
        console.log('Scooter ID changed from', scooterId.value, 'to', newId)
        if (newId && newId !== scooterId.value) {
          scooterId.value = newId
          fetchScooterData()
        }
      }
    })
    
    // Format date
    const formatDate = (dateString) => {
      if (!dateString) return 'Not available'
      
      const date = new Date(dateString)
      return date.toLocaleDateString()
    }
    
    // Get scooter status type for tag
    const getStatusType = (status) => {
      switch (status) {
        case 'available':
          return 'success'
        case 'maintenance':
          return 'warning'
        case 'disabled':
          return 'danger'
        default:
          return 'default'
      }
    }
    
    // Get scooter status text
    const getStatusText = (status) => {
      switch (status) {
        case 'available':
          return 'Available'
        case 'maintenance':
          return 'Maintenance'
        case 'disabled':
          return 'Disabled'
        default:
          return status
      }
    }
    
    // Get battery color based on level
    const getBatteryColor = (level) => {
      if (level >= 70) return '#07c160'
      if (level >= 30) return '#ff976a'
      return '#ee0a24'
    }
    
    // Calculate rental price based on duration
    const calculatePrice = (duration) => {
      const hours = parseInt(duration)
      
      // Apply discount for longer rentals
      if (hours >= 24) {
        return (hours * hourlyRate.value * 0.8).toFixed(2) // 20% discount for 1 day or more
      } else if (hours >= 4) {
        return (hours * hourlyRate.value * 0.9).toFixed(2) // 10% discount for 4 hours or more
      }
      
      return (hours * hourlyRate.value).toFixed(2)
    }
    
    // Rent scooter
    const rentScooter = async () => {
      if (!scooter.value || scooter.value.status !== 'available') {
        Toast.fail('This scooter is not available for rental')
        return
      }
      
      // 检查是否有可用的支付方式
      const paymentMethods = store.getters['payment/paymentMethods']
      if (!paymentMethods || paymentMethods.length === 0) {
        Dialog.confirm({
          title: 'No Payment Method',
          message: 'You need to add a payment method before renting a scooter.',
          confirmButtonText: 'Add Payment Method',
          cancelButtonText: 'Cancel',
          confirmButtonColor: '#ee0a24',
          cancelButtonColor: '#ee0a24'
        })
          .then(() => {
            router.push('/payment/methods')
          })
          .catch(() => {
            // User canceled
          })
        return
      }
      
      // 先检查用户是否有pending订单
      const pendingCheck = await store.dispatch('order/checkPendingOrder')
      console.log('Pending order check result:', pendingCheck)
      
      if (pendingCheck.hasPending) {
        // 有pending订单，不允许租借
        Toast.fail('You already have a pending order. Please complete or cancel it first.')
        
        // 导航到订单详情页
        if (pendingCheck.order) {
          Dialog.confirm({
            title: 'Pending Order',
            message: 'You already have a pending order. Would you like to view it?',
            confirmButtonText: 'View Order',
            cancelButtonText: 'Cancel'
          })
            .then(() => {
              router.push(`/orders/${pendingCheck.order.id}`)
            })
            .catch(() => {
              // User canceled
            })
        }
        return
      }
      
      // 确认租借
      Dialog.confirm({
        title: 'Confirm Rental',
        message: `Rent scooter ${scooter.value.scooter_code} for ${rentalDuration.value} ${rentalDuration.value === '1' ? 'hour' : rentalDuration.value === '24' ? 'day' : 'hours'}?`,
        confirmButtonText: 'Confirm',
        cancelButtonText: 'Cancel',
      })
        .then(async () => {
          // 先创建临时订单
          const orderData = {
            scooter_id: scooter.value.id,
            rental_duration: parseInt(rentalDuration.value),
            start_now: startNow.value,
            payment_method_id: selectedPaymentMethod.value.id
          }
          
          try {
            // 显示创建订单中的加载状态
            Toast.loading({
              message: 'Creating order...',
              forbidClick: true,
            })
            
            // 创建临时订单
            const response = await store.dispatch('scooter/createOrder', orderData)
            
            if (response === true) {
              // 订单创建成功，获取最新订单
              await store.dispatch('order/fetchCurrentOrder')
              const currentOrders = store.getters['order/currentOrders']
              
              if (currentOrders && currentOrders.length > 0) {
                // 找到刚创建的订单
                const newOrder = currentOrders[0]
                
                // 关闭loading
                Toast.clear()
                
                // 不需要再次显示支付界面，因为创建订单时已经使用了选定的支付方式
                // showPaymentProcess.value = true
                
                // 直接完成订单处理
                Toast.success('Order created successfully!')
                
                // 导航到订单页面
                router.push('/orders')
              } else {
                Toast.fail('Failed to retrieve order information')
              }
            } else {
              Toast.fail(store.getters['scooter/error'] || 'Failed to create order')
            }
          } catch (error) {
            console.error('Error creating order:', error)
            Toast.fail('Failed to create order')
          }
        })
        .catch(() => {
          // User canceled
        })
    }
    
    // 支付成功处理
    const onPaymentSuccess = async () => {
      Toast.success('Payment method selected!')
      // 处理订单
      // if (tempOrderId.value) {
      //   // 更新订单状态
      //   await store.dispatch('order/startOrder', tempOrderId.value)
      //   router.push('/orders')
      // }
    }
    
    // 支付取消处理
    const onPaymentCancel = () => {
      Toast.fail('Payment cancelled')
      // 可以选择取消订单或保留订单
      Dialog.confirm({
        title: 'Cancel Order',
        message: 'Do you want to cancel the unpaid order?',
        confirmButtonText: 'Yes',
        cancelButtonText: 'No'
      })
        .then(async () => {
          // 取消订单
          if (tempOrderId.value) {
            await store.dispatch('order/cancelOrder', tempOrderId.value)
            Toast.success('Order cancelled')
          }
        })
        .catch(() => {
          // 用户选择保留订单
          Toast.info('Order remains unpaid')
        })
    }
    
    // 支付错误处理
    const onPaymentError = (error) => {
      console.error('Payment error:', error)
      Toast.fail('Payment failed')
    }
    
    // 选择支付方式处理
    const onSelectPaymentMethod = (method) => {
      selectedPaymentMethod.value = method
      console.log('Selected payment method:', method)
    }
    
    // Lifecycle hooks
    onMounted(() => {
      fetchScooterData()
      // 加载支付方式
      if (!store.getters['payment/paymentMethods'].length) {
        store.dispatch('payment/fetchPaymentMethods')
      }
    })
    
    // 页面被重新激活时刷新数据
    onActivated(() => {
      console.log('ScooterDetail activated, refreshing data for ID:', scooterId.value)
      fetchScooterData()
    })
    
    return {
      scooter,
      loading,
      error,
      rentalDuration,
      startNow,
      showPaymentProcess,
      tempOrderId,
      formatDate,
      getStatusType,
      getStatusText,
      getBatteryColor,
      calculatePrice,
      rentScooter,
      fetchScooterData,
      onPaymentSuccess,
      onPaymentCancel,
      onPaymentError,
      hourlyRate,
      selectedPaymentMethod,
      onSelectPaymentMethod
    }
  }
}
</script>

<style scoped>
.scooter-detail-container {
  padding-top: 46px;
  height: 100vh;
  display: flex;
  flex-direction: column;
}

.scooter-info {
  padding: 16px;
}

.scooter-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}

.scooter-code {
  font-size: 20px;
  font-weight: bold;
}

.battery-indicator {
  display: flex;
  align-items: center;
}

.battery-indicator span {
  margin-left: 5px;
}

.rental-section {
  margin-top: 24px;
}

.rental-section h3 {
  margin-bottom: 16px;
  font-size: 18px;
  font-weight: normal;
}

.rental-price {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.price {
  font-weight: bold;
  color: #f44336;
  font-size: 18px;
}

.action-buttons {
  margin-top: 24px;
}

.unavailable-message {
  margin-top: 24px;
  text-align: center;
}

.loading-container, .error-container {
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  height: 100%;
  padding: 20px;
}

.error-container button {
  margin-top: 16px;
}

.no-payment-method {
  color: #969799;
  font-size: 14px;
}
</style>

