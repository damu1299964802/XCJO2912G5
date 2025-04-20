<template>
  <div class="payment-process">
    <van-popup v-model:show="visible" round position="bottom" :close-on-click-overlay="false">
      <div class="payment-header">
        <div class="title">Payment</div>
        <div class="amount">{{ formatPrice(amount) }}</div>
      </div>
      
      <div class="payment-methods-section">
        <div class="section-title">Select Payment Method</div>
        
        <div v-if="loading" class="loading-container">
          <van-loading type="spinner" color="#1989fa" />
        </div>
        
        <template v-else>
          <van-radio-group v-model="selectedPaymentId">
            <van-cell-group v-if="paymentMethods.length > 0" class="payment-methods-list">
              <van-cell v-for="method in paymentMethods" :key="method.id" clickable @click="selectedPaymentId = method.id" class="payment-method-cell">
                <template #title>
                  <div class="payment-method-item">
                    <div class="card-icon">
                      <van-icon :name="getCardIcon(method.card_type)" size="24" color="#1989fa" />
                    </div>
                    <div class="card-info">
                      <div class="card-header">
                        <span class="card-type">{{ method.card_type }}</span>
                        <van-tag v-if="method.is_default == 1" type="success" round size="mini">Default</van-tag>
                      </div>
                      <div class="card-number">**** {{ method.card_number_last4 }}</div>
                    </div>
                  </div>
                </template>
                <template #right-icon>
                  <van-radio :name="method.id" checked-color="#1989fa" />
                </template>
              </van-cell>
            </van-cell-group>
            
            <div v-else class="no-methods">
              <p>No payment methods available</p>
              <van-button type="primary" size="small" @click="goToAddPayment" round>Add Payment Method</van-button>
            </div>
          </van-radio-group>
          
          <div class="payment-actions">
            <van-button 
              block 
              type="primary" 
              :disabled="!selectedPaymentId" 
              @click="selectPaymentMethod"
              round
            >
              Confirm
            </van-button>
            <van-button block plain type="default" @click="cancel" round>
              Cancel
            </van-button>
          </div>
        </template>
      </div>
    </van-popup>
  </div>
</template>

<script>
import { ref, computed, watch, onMounted } from 'vue'
import { useStore } from 'vuex'
import { useRouter } from 'vue-router'
import { Toast } from 'vant'

export default {
  name: 'PaymentProcess',
  props: {
    modelValue: {
      type: Boolean,
      default: false
    },
    amount: {
      type: Number,
      required: true
    },
    orderId: {
      type: [String, Number],
      default: null
    }
  },
  emits: ['update:modelValue', 'payment-success', 'payment-cancel', 'payment-error', 'select-payment-method'],
  setup(props, { emit }) {
    const store = useStore()
    const router = useRouter()
    const selectedPaymentId = ref(null)
    const loading = ref(true)
    
    const visible = computed({
      get: () => props.modelValue,
      set: (value) => emit('update:modelValue', value)
    })
    
    const paymentMethods = computed(() => store.getters['payment/paymentMethods'])
    const defaultPaymentMethod = computed(() => store.getters['payment/defaultPaymentMethod'])
    
    // 加载支付方式
    const loadPaymentMethods = async () => {
      loading.value = true
      try {
        // 只有当没有支付方式时才重新获取
        if (!paymentMethods.value || paymentMethods.value.length === 0) {
          await store.dispatch('payment/fetchPaymentMethods')
        }
        
        // 获取支付方式列表
        const methods = store.getters['payment/paymentMethods']
        if (methods && methods.length > 0) {
          const defaultMethod = store.getters['payment/defaultPaymentMethod']
          if (defaultMethod) {
            selectedPaymentId.value = defaultMethod.id
          } else {
            selectedPaymentId.value = methods[0].id
          }
        } else {
          selectedPaymentId.value = null
        }
      } catch (error) {
        console.error('Failed to load payment methods:', error)
        Toast.fail('Failed to load payment methods')
        selectedPaymentId.value = null
      } finally {
        loading.value = false
      }
    }
    
    // 选择支付方式
    const selectPaymentMethod = () => {
      if (!selectedPaymentId.value) {
        Toast.fail('Please select a payment method')
        return
      }
      
      // 找到选中的支付方式
      const selectedMethod = paymentMethods.value.find(method => method.id === selectedPaymentId.value)
      
      if (selectedMethod) {
        // 向父组件传递选中的支付方式
        emit('select-payment-method', selectedMethod)
        
        // 如果不是默认支付方式，则设置为默认
        if (selectedMethod.is_default !== 1) {
          store.dispatch('payment/setDefaultPaymentMethod', selectedMethod.id)
        }
        
        // 通知支付成功
        emit('payment-success')
        
        // 关闭弹窗
        emit('update:modelValue', false)
        
        Toast.success('Payment method selected')
      }
    }
    
    // 取消支付
    const cancel = () => {
      emit('payment-cancel')
      emit('update:modelValue', false)
    }
    
    // 前往添加支付方式页面
    const goToAddPayment = () => {
      router.push('/payment/methods')
      emit('update:modelValue', false)
    }
    
    // 格式化价格
    const formatPrice = (price) => {
      return `€${price.toFixed(2)}`
    }
    
    // 获取卡图标
    const getCardIcon = (cardType) => {
      const icons = {
        'Visa': 'credit-pay',
        'MasterCard': 'credit-pay',
        'American Express': 'credit-pay',
        'Unknown': 'card'
      }
      return icons[cardType] || 'card'
    }
    
    // 监听显示状态变化
    watch(() => props.modelValue, (newVal) => {
      if (newVal) {
        loadPaymentMethods()
      }
    })
    
    onMounted(() => {
      if (props.modelValue) {
        loadPaymentMethods()
      }
    })
    
    return {
      visible,
      selectedPaymentId,
      paymentMethods,
      loading,
      selectPaymentMethod,
      cancel,
      goToAddPayment,
      formatPrice,
      getCardIcon
    }
  }
}
</script>

<style scoped>
.payment-header {
  padding: 20px;
  border-bottom: 1px solid #ebedf0;
  text-align: center;
  background-color: #fff;
}

.title {
  font-size: 16px;
  margin-bottom: 8px;
  color: #323233;
}

.amount {
  font-size: 24px;
  font-weight: bold;
  color: #ee0a24;
}

.payment-methods-section {
  padding: 20px;
  background-color: #f7f8fa;
}

.section-title {
  font-size: 14px;
  color: #969799;
  margin-bottom: 12px;
  font-weight: 500;
}

.payment-methods-list {
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 4px 16px rgba(100, 101, 102, 0.12);
  margin-bottom: 16px;
}

.payment-method-cell {
  background-color: #fff;
  transition: background-color 0.2s;
}

.payment-method-cell:active {
  background-color: #f2f3f5;
}

.payment-method-item {
  display: flex;
  align-items: center;
}

.card-icon {
  margin-right: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 40px;
  height: 40px;
  background-color: rgba(25, 137, 250, 0.1);
  border-radius: 50%;
  transition: all 0.3s ease;
}

.card-info {
  display: flex;
  flex-direction: column;
}

.card-header {
  display: flex;
  align-items: center;
  gap: 8px;
}

.card-type {
  font-size: 14px;
  font-weight: bold;
  color: #323233;
}

.card-number {
  font-size: 12px;
  color: #969799;
  letter-spacing: 1px;
}

.loading-container {
  display: flex;
  justify-content: center;
  align-items: center;
  height: 120px;
}

.no-methods {
  padding: 32px 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  background-color: #fff;
  border-radius: 12px;
  box-shadow: 0 4px 12px rgba(100, 101, 102, 0.12);
}

.no-methods p {
  margin-bottom: 16px;
  color: #969799;
}

.payment-actions {
  margin-top: 24px;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.payment-actions .van-button {
  height: 44px;
  transition: opacity 0.2s;
}

.payment-actions .van-button:active {
  opacity: 0.9;
}
</style> 