<template>
  <div class="payment-container">
    <van-nav-bar 
      title="Payment Methods"
      left-arrow
      @click-left="$router.go(-1)"
    />
    
    <van-pull-refresh v-model="refreshing" @refresh="refreshPaymentMethods">
      <div class="payment-list" v-if="paymentMethods.length > 0">
        <transition-group name="card-list">
          <van-cell-group v-for="method in paymentMethods" :key="method.id" class="card-item">
            <van-cell>
              <template #icon>
                <div class="card-icon">
                  <van-icon :name="getCardIcon(method.card_type)" size="24" color="#1989fa" />
                </div>
              </template>
              <template #title>
                <div class="card-header">
                  <span class="card-title">{{ method.card_holder_name || 'Card Owner' }}</span>
                  <van-tag v-if="method.is_default == 1" type="success" round>Default</van-tag>
                </div>
                <div class="card-info">
                  <p class="card-type">{{ method.card_type }}</p>
                  <p class="card-number">**** **** **** {{ method.card_number_last4 || '1234' }}</p>
                  <p class="expiry">Expires: {{ method.expiration_month || '12' }}/{{ method.expiration_year || '2025' }}</p>
                </div>
              </template>
            </van-cell>
            
            <van-cell>
              <div class="button-group">
                <van-button 
                  v-if="method.is_default != 1" 
                  size="small" 
                  type="primary" 
                  plain
                  @click="setAsDefault(method.id)"
                  icon="success"
                >
                  Set as Default
                </van-button>
                <van-button 
                  size="small" 
                  type="danger" 
                  plain
                  @click="confirmDelete(method.id)"
                  icon="delete"
                >
                  Delete
                </van-button>
              </div>
            </van-cell>
          </van-cell-group>
        </transition-group>
      </div>
      <van-empty v-else description="No payment methods found" />
    </van-pull-refresh>
    
    <div class="action-button">
      <van-button 
        type="primary" 
        round 
        icon="plus" 
        size="normal" 
        @click="showAddForm = true"
      >
        Add New Card
      </van-button>
    </div>
    
    <!-- 添加表单弹窗 -->
    <van-dialog
      v-model:show="showAddForm"
      title="Add Payment Method"
      show-cancel-button
      :confirm-button-color="'#1989fa'"
      :cancel-button-color="'#ffffff'"
      cancel-button-text="Cancel"
      confirm-button-text="Save"
      @confirm="saveCard"
      class="card-form-dialog"
    >
      <div class="form-content">
        <van-field
          v-model="newCard.card_holder_name"
          label="Card Holder"
          placeholder="Enter card holder name"
          :rules="[{ required: true, message: 'Card holder name is required' }]"
        />
        <van-field
          v-model="newCard.card_number"
          label="Card Number"
          placeholder="Enter card number"
          :rules="[
            { required: true, message: 'Card number is required' },
            { validator: validateCardNumber, message: 'Invalid card number' }
          ]"
          maxlength="19"
          @input="formatCardNumber"
        />
        
        <div class="field-row">
          <div class="field-label">Expiry Date</div>
          <div class="expiry-field-group">
            <van-field
              v-model="newCard.expiration_month"
              placeholder="MM"
              :rules="[
                { required: true, message: 'Month is required' },
                { validator: validateMonth, message: 'Invalid month (01-12)' }
              ]"
              maxlength="2"
              class="expiry-field"
            />
            <span class="expiry-separator">/</span>
            <van-field
              v-model="newCard.expiration_year"
              placeholder="YY"
              :rules="[
                { required: true, message: 'Year is required' },
                { validator: validateYear, message: 'Invalid year' }
              ]"
              maxlength="2"
              class="expiry-field"
            />
          </div>
        </div>
        
        <van-field
          v-model="newCard.cvv"
          label="Security Code"
          placeholder="Enter CVV"
          type="password"
          :rules="[
            { required: true, message: 'Security code is required' },
            { validator: validateCVV, message: 'CVV must be 3-4 digits' }
          ]"
          maxlength="4"
        />
        
        <div class="default-switch">
          <span>Set as default payment method</span>
          <van-switch v-model="newCard.is_default" size="20" />
        </div>
      </div>
    </van-dialog>
    
    <van-toast id="van-toast" />
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue'
import { useStore } from 'vuex'
import { Dialog, Toast } from 'vant'

export default {
  name: 'PaymentMethods',
  setup() {
    const store = useStore()
    const loading = ref(true)
    const refreshing = ref(false)
    const paymentMethods = ref([])
    const showAddForm = ref(false)
    const newCard = ref({
      card_holder_name: '',
      card_number: '',
      expiration_month: '',
      expiration_year: '',
      cvv: '',
      is_default: false
    })
    
    // 加载支付方式
    const loadPaymentMethods = async () => {
      loading.value = true
      try {
        await store.dispatch('payment/fetchPaymentMethods')
        paymentMethods.value = store.getters['payment/paymentMethods']
        console.log('Payment methods loaded:', paymentMethods.value)
      } catch (error) {
        console.error('Failed to load payment methods:', error)
        Toast('Failed to load payment methods')
      } finally {
        loading.value = false
      }
    }
    
    // 刷新支付方式
    const refreshPaymentMethods = async () => {
      try {
        await store.dispatch('payment/fetchPaymentMethods')
      } catch (err) {
        console.error('Error refreshing payment methods:', err)
        Toast.fail(err.message || 'Failed to load payment methods')
      } finally {
        refreshing.value = false
      }
    }
    
    // 设置为默认
    const setAsDefault = async (id) => {
      try {
        await store.dispatch('payment/setDefaultPaymentMethod', id)
        Toast.success('Set as default payment method')
        await loadPaymentMethods()
      } catch (error) {
        console.error('Failed to set default payment method:', error)
        Toast.fail('Failed to set default')
      }
    }
    
    // 确认删除
    const confirmDelete = (id) => {
      Dialog.confirm({
        title: 'Delete Payment Method',
        message: 'Are you sure you want to delete this payment method?',
      })
        .then(async () => {
          try {
            await store.dispatch('payment/deletePaymentMethod', id)
            Toast.success('Payment method deleted')
            await loadPaymentMethods()
          } catch (error) {
            console.error('Failed to delete payment method:', error)
            Toast.fail('Failed to delete')
          }
        })
        .catch(() => {
          // 取消删除
        })
    }
    
    // 验证卡号
    const validateCardNumber = (val) => {
      return /^[0-9]{13,19}$/.test(val.replace(/\s/g, ''))
    }
    
    // 验证月份
    const validateMonth = (val) => {
      const month = parseInt(val, 10)
      return month >= 1 && month <= 12
    }
    
    // 验证年份
    const validateYear = (val) => {
      if (!val || val.length !== 2) return false;
      
      const year = parseInt(val, 10);
      const currentYear = new Date().getFullYear() % 100; // 获取当前年份的后两位
      
      // 年份必须是当前年份或之后的20年内
      return year >= currentYear && year <= currentYear + 20;
    }
    
    // 验证CVV
    const validateCVV = (val) => {
      return /^[0-9]{3,4}$/.test(val)
    }
    
    // 格式化卡号，添加空格
    const formatCardNumber = (value) => {
      if (!value) return
      // 移除所有非数字字符
      let v = value.replace(/\D/g, '')
      // 添加空格分组
      v = v.replace(/(\d{4})(?=\d)/g, '$1 ')
      // 更新输入值
      newCard.value.card_number = v
    }
    
    // 保存新卡
    const saveCard = async () => {
      // 创建验证错误信息
      const errors = [];
      
      if (!newCard.value.card_holder_name) {
        errors.push('Card holder name is required');
      }
      
      if (!newCard.value.card_number) {
        errors.push('Card number is required');
      } else if (!validateCardNumber(newCard.value.card_number)) {
        errors.push('Invalid card number format');
      }
      
      if (!newCard.value.expiration_month) {
        errors.push('Expiration month is required');
      } else if (!validateMonth(newCard.value.expiration_month)) {
        errors.push('Invalid month (must be 01-12)');
      }
      
      if (!newCard.value.expiration_year) {
        errors.push('Expiration year is required');
      } else if (!validateYear(newCard.value.expiration_year)) {
        errors.push('Invalid year (must be current year or later)');
      }
      
      if (!newCard.value.cvv) {
        errors.push('Security code is required');
      } else if (!validateCVV(newCard.value.cvv)) {
        errors.push('CVV must be 3-4 digits');
      }
      
      // 如果有错误，显示第一个错误
      if (errors.length > 0) {
        Toast.fail(errors[0]);
        return false;
      }
      
      try {
        // 处理保存前的数据
        const cardData = {
          ...newCard.value,
          card_number: newCard.value.card_number.replace(/\s/g, ''),
          is_default: newCard.value.is_default ? 1 : 0
        }
        
        console.log('Saving card:', cardData);
        await store.dispatch('payment/addPaymentMethod', cardData);
        Toast.success('Payment method added successfully');
        
        // 重置表单
        newCard.value = {
          card_holder_name: '',
          card_number: '',
          expiration_month: '',
          expiration_year: '',
          cvv: '',
          is_default: false
        }
        
        await loadPaymentMethods();
        return true;
      } catch (error) {
        console.error('Failed to add payment method:', error);
        // 显示API返回的错误信息或默认错误信息
        const errorMessage = error.response?.data?.message || 'Failed to add payment method';
        Toast.fail(errorMessage);
        return false;
      }
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
    
    // 生命周期钩子
    onMounted(() => {
      loadPaymentMethods()
    })
    
    return {
      loading,
      refreshing,
      paymentMethods,
      showAddForm,
      newCard,
      setAsDefault,
      confirmDelete,
      saveCard,
      refreshPaymentMethods,
      getCardIcon,
      validateCardNumber,
      validateMonth,
      validateYear,
      validateCVV,
      formatCardNumber
    }
  }
}
</script>

<style scoped>
.payment-container {
  padding-top: 46px;
  padding-bottom: 50px;
  min-height: 100vh;
  background-color: #f7f8fa;
  display: flex;
  flex-direction: column;
}

.payment-list {
  padding-bottom: 16px;
}

.card-item {
  margin: 16px;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 4px 16px rgba(100, 101, 102, 0.12);
  background-color: #fff;
  transition: all 0.3s ease;
}

.card-item:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(100, 101, 102, 0.15);
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 8px;
}

.card-title {
  font-size: 16px;
  font-weight: bold;
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
  gap: 4px;
}

.card-type {
  font-size: 14px;
  color: #323233;
  margin: 0;
}

.card-number {
  font-weight: bold;
  font-size: 16px;
  margin: 4px 0;
  letter-spacing: 1px;
}

.expiry {
  color: #969799;
  font-size: 12px;
  margin: 0;
}

.button-group {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
}

.button-group .van-button {
  border-radius: 6px;
  transition: all 0.2s ease;
}

.button-group .van-button:hover {
  opacity: 0.9;
  transform: scale(1.02);
}

.form-content {
  padding: 16px;
}

.field-row {
  position: relative;
  display: flex;
  flex-direction: column;
  padding: 10px 16px;
  background-color: #fff;
  box-sizing: border-box;
  line-height: 24px;
  border-bottom: 1px solid #ebedf0;
}

.field-label {
  flex: 1;
  font-size: 14px;
  color: #646566;
  margin-bottom: 8px;
}

.expiry-field-group {
  display: flex;
  align-items: center;
}

.expiry-field {
  flex: 1;
  padding: 0;
}

.expiry-field :deep(.van-field__label) {
  display: none;
}

.expiry-field :deep(.van-cell) {
  padding: 5px 0;
  background-color: transparent;
}

.expiry-separator {
  padding: 0 6px;
  color: #323233;
  font-size: 14px;
}

.action-button {
  position: fixed;
  right: 20px;
  bottom: 80px;
  z-index: 999;
}

.action-button .van-button {
  width: auto;
  height: auto;
  padding: 10px 20px;
  box-shadow: 0 6px 16px rgba(25, 137, 250, 0.3);
  transition: all 0.3s ease;
}

.action-button .van-button:active {
  transform: scale(0.95);
}

/* 添加列表动画 */
.card-list-enter-active,
.card-list-leave-active {
  transition: all 0.5s ease;
}

.card-list-enter-from,
.card-list-leave-to {
  opacity: 0;
  transform: translateY(30px);
}

.card-list-move {
  transition: transform 0.5s ease;
}

.card-form-dialog .van-dialog__content {
  padding-top: 8px;
}

.default-switch {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 10px 16px;
  background-color: #fff;
  line-height: 24px;
  font-size: 14px;
  color: #323233;
}

.card-form-dialog :deep(.van-dialog__content) {
  padding-top: 0;
  overflow-y: auto;
  max-height: 70vh;
}

.card-form-dialog :deep(.van-dialog__header) {
  padding: 18px 16px;
  font-weight: 500;
  text-align: center;
  border-bottom: 1px solid #ebedf0;
}

.card-form-dialog :deep(.van-dialog__footer) {
  padding: 8px 16px;
}

.card-form-dialog :deep(.van-dialog__cancel) {
  color: #1989fa;
}

.card-form-dialog :deep(.van-button--default) {
  border: none;
}

.card-form-dialog :deep(.van-dialog__confirm) {
  color: #ffffff;
}
</style> 