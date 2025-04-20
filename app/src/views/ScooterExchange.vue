<template>
  <div class="exchange-container">
    <van-nav-bar
      title="换车"
      left-text="返回"
      left-arrow
      @click-left="onClickLeft"
    />
    
    <div class="current-order" v-if="currentOrder">
      <van-cell-group inset>
        <van-cell title="当前车辆">
          <template #value>
            <div class="scooter-info">
              <span>{{ currentOrder.scooter.scooter_code }}</span>
              <van-tag type="warning">使用中</van-tag>
            </div>
          </template>
        </van-cell>
        <van-cell title="电量" :value="currentOrder.scooter.battery_level + '%'" />
      </van-cell-group>
    </div>

    <div class="available-scooters">
      <div class="section-title">可更换车辆</div>
      <van-pull-refresh v-model="refreshing" @refresh="onRefresh">
        <van-list
          v-model:loading="loading"
          :finished="finished"
          finished-text="没有更多车辆"
          @load="onLoad"
        >
          <van-cell-group inset v-for="scooter in availableScooters" :key="scooter.id">
            <van-cell :title="scooter.scooter_code">
              <template #value>
                <div class="scooter-info">
                  <van-tag type="success">可用</van-tag>
                </div>
              </template>
            </van-cell>
            <van-cell title="电量">
              <template #value>
                <div class="battery-info">
                  <van-icon name="battery-charge" :color="getBatteryColor(scooter.battery_level)" />
                  <span>{{ scooter.battery_level }}%</span>
                </div>
              </template>
            </van-cell>
            <van-cell>
              <template #title>
                <van-button type="primary" block @click="handleExchange(scooter.id)">
                  选择此车
                </van-button>
              </template>
            </van-cell>
          </van-cell-group>
        </van-list>
      </van-pull-refresh>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue'
import { useStore } from 'vuex'
import { useRouter } from 'vue-router'
import { Toast } from 'vant'

export default {
  name: 'ScooterExchange',
  setup() {
    const store = useStore()
    const router = useRouter()
    
    const refreshing = ref(false)
    const loading = ref(false)
    const finished = ref(false)
    
    const currentOrder = computed(() => store.getters['order/currentOrder'])
    const availableScooters = computed(() => 
      store.getters['scooter/availableScooters'].filter(
        s => s.id !== currentOrder.value?.scooter.id
      )
    )
    
    // 返回上一页
    const onClickLeft = () => {
      router.back()
    }
    
    // 获取电池电量颜色
    const getBatteryColor = (level) => {
      if (level >= 70) return '#07c160'
      if (level >= 30) return '#ff976a'
      return '#ee0a24'
    }
    
    // 刷新列表
    const onRefresh = async () => {
      await store.dispatch('scooter/fetchAvailableScooters')
      refreshing.value = false
    }
    
    // 加载更多
    const onLoad = () => {
      loading.value = false
      finished.value = true // 一次性加载所有数据
    }
    
    // 处理换车
    const handleExchange = async (newScooterId) => {
      const success = await store.dispatch('order/createExchangeOrder', newScooterId)
      if (success) {
        router.push('/home')
      }
    }
    
    // 页面加载时获取数据
    onMounted(async () => {
      if (!currentOrder.value) {
        Toast('没有活动订单')
        router.push('/home')
        return
      }
      await store.dispatch('scooter/fetchAvailableScooters')
    })
    
    return {
      currentOrder,
      availableScooters,
      refreshing,
      loading,
      finished,
      onClickLeft,
      getBatteryColor,
      onRefresh,
      onLoad,
      handleExchange
    }
  }
}
</script>

<style scoped>
.exchange-container {
  padding-top: 46px;
  min-height: 100vh;
  background-color: #f7f8fa;
}

.current-order {
  margin: 16px 0;
}

.section-title {
  margin: 16px;
  font-size: 14px;
  color: #969799;
}

.scooter-info {
  display: flex;
  align-items: center;
  gap: 8px;
}

.battery-info {
  display: flex;
  align-items: center;
  gap: 4px;
}

:deep(.van-cell-group) {
  margin: 8px 16px;
}
</style> 