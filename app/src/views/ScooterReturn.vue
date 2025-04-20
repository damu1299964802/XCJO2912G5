<template>
  <div class="return-container">
    <van-nav-bar
      title="Return Scooter"
      left-text="Back"
      left-arrow
      @click-left="onClickLeft"
    />
    
    <div class="current-order" v-if="currentOrder">
      <van-cell-group inset>
        <van-cell title="Current Scooter">
          <template #value>
            <div class="scooter-info">
              <span>{{ currentOrder.scooter.scooter_code }}</span>
              <van-tag type="warning">In Use</van-tag>
            </div>
          </template>
        </van-cell>
        <van-cell title="Rental Duration" :value="formatDuration(currentOrder.start_time)" />
        <van-cell title="Estimated Fee" :value="'€' + calculateFee(currentOrder.start_time)" />
      </van-cell-group>

      <div class="return-notice">
        <van-notice-bar
          color="#1989fa"
          background="#ecf9ff"
          left-icon="info-o"
        >
          Please park the scooter in designated areas
        </van-notice-bar>
      </div>

      <div class="return-actions">
        <van-button type="primary" block @click="handleReturn" :loading="loading">
          Confirm Return
        </van-button>
      </div>
    </div>

    <van-empty v-else description="No active scooter rental" />
  </div>
</template>

<script>
import { computed, onMounted } from 'vue'
import { useStore } from 'vuex'
import { useRouter } from 'vue-router'
import { Toast } from 'vant'

export default {
  name: 'ScooterReturn',
  setup() {
    const store = useStore()
    const router = useRouter()
    
    const currentOrder = computed(() => store.getters['order/currentOrder'])
    const loading = computed(() => store.getters['order/loading'])
    
    // Return to previous page
    const onClickLeft = () => {
      router.back()
    }
    
    // Format duration
    const formatDuration = (startTime) => {
      const start = new Date(startTime)
      const now = new Date()
      const diffInMinutes = Math.floor((now - start) / (1000 * 60))
      
      if (diffInMinutes < 60) {
        return diffInMinutes + ' minutes'
      }
      
      const hours = Math.floor(diffInMinutes / 60)
      const minutes = diffInMinutes % 60
      return hours + ' hours ' + minutes + ' minutes'
    }
    
    // Calculate fee (example: 10 yuan per hour, minimum 1 hour)
    const calculateFee = (startTime) => {
      const start = new Date(startTime)
      const now = new Date()
      const diffInHours = Math.ceil((now - start) / (1000 * 60 * 60))
      return (diffInHours * 10).toFixed(2)
    }
    
    // Handle return
    const handleReturn = async () => {
      if (!currentOrder.value) {
        Toast('No active scooter rental')
        return
      }

      const success = await store.dispatch('order/returnScooter', {
        order_id: currentOrder.value.id,
        end_time: new Date().toISOString()
      })

      if (success) {
        Toast.success('Return successful')
        router.push('/home')
      }
    }
    
    // Fetch current order on mount
    onMounted(async () => {
      if (!currentOrder.value) {
        await store.dispatch('order/fetchCurrentOrder')
      }
      
      if (!currentOrder.value) {
        Toast('No active scooter rental')
        router.push('/home')
      }
    })
    
    return {
      currentOrder,
      loading,
      onClickLeft,
      formatDuration,
      calculateFee,
      handleReturn
    }
  }
}
</script>

<style scoped>
.return-container {
  padding-top: 46px;
  min-height: 100vh;
  background-color: #f7f8fa;
}

.current-order {
  margin: 16px 0;
}

.scooter-info {
  display: flex;
  align-items: center;
  gap: 8px;
}

.return-notice {
  margin: 16px;
}

.return-actions {
  margin: 24px 16px;
}

:deep(.van-cell-group) {
  margin: 0 16px;
}

:deep(.van-empty) {
  margin-top: 40%;
}
</style> 