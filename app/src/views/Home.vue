<template>
  <div class="home-container">
    <van-nav-bar title="Electric Scooter Rental" />
    
    <div class="map-container" ref="mapContainer"></div>
    
    <div class="scooter-list">
      <van-pull-refresh v-model="refreshing" @refresh="onRefresh">
        <van-list
          v-model:loading="listLoading"
          :finished="finished"
          finished-text="No more scooters"
          @load="onLoad"
        >
          <van-cell v-for="scooter in scooters" :key="scooter.id" @click="viewScooterDetail(scooter.id)">
            <template #title>
              <div class="scooter-item">
                <div class="scooter-info">
                  <div class="scooter-code">{{ scooter.scooter_code }}</div>
                  <div class="scooter-status">
                    <van-tag :type="getStatusType(scooter.status)">{{ getStatusText(scooter.status) }}</van-tag>
                  </div>
                </div>
                <div class="scooter-battery">
                  <van-icon name="battery-charge" :color="getBatteryColor(scooter.battery_level)" />
                  <span>{{ scooter.battery_level }}%</span>
                </div>
              </div>
            </template>
          </van-cell>
        </van-list>
      </van-pull-refresh>
    </div>
  </div>
</template>

<script>
import { ref, onMounted, computed, onUnmounted, onActivated, onBeforeUnmount } from 'vue'
import { useStore } from 'vuex'
import { useRouter } from 'vue-router'
import { Toast } from 'vant'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'

export default {
  name: 'Home',
  setup() {
    const store = useStore()
    const router = useRouter()
    const mapContainer = ref(null)
    const map = ref(null)
    const markers = ref([])
    
    const scooters = computed(() => store.getters['scooter/allScooters'])
    const loading = computed(() => store.getters['scooter/loading'])
    const hasActiveOrder = computed(() => store.getters['order/hasActiveOrder'])
    
    const refreshing = ref(false)
    const listLoading = ref(false)
    const finished = ref(false)
    let refreshInterval = null
    
    // Initialize map
    const initMap = () => {
      // Import Leaflet dynamically to avoid SSR issues
      import('leaflet').then(L => {
        // Fix Leaflet's default icon path issues
        delete L.Icon.Default.prototype._getIconUrl
        L.Icon.Default.mergeOptions({
          iconRetinaUrl: require('leaflet/dist/images/marker-icon-2x.png'),
          iconUrl: require('leaflet/dist/images/marker-icon.png'),
          shadowUrl: require('leaflet/dist/images/marker-shadow.png')
        })
        
        // Default center (can be adjusted based on user location)
        const center = [53.8073, -1.5553] // 利兹大学Sir William Henry Bragg Building的坐标
        
        // Create map
        map.value = L.map(mapContainer.value).setView(center, 13)
        
        // Add tile layer (OpenStreetMap)
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
          attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map.value)
        
        // Add markers for scooters
        addScooterMarkers()
      })
    }
    
    // Add markers for scooters
    const addScooterMarkers = () => {
      if (!map.value) return
      
      // Clear existing markers
      markers.value.forEach(marker => {
        map.value.removeLayer(marker)
      })
      markers.value = []
      
      // Add new markers
      import('leaflet').then(L => {
        scooters.value.forEach(scooter => {
          if (scooter.latitude && scooter.longitude) {
            const marker = L.marker([scooter.latitude, scooter.longitude])
              .addTo(map.value)
              .bindPopup(`
                <div style="text-align:center;">
                  <b>${scooter.scooter_code}</b><br>
                  <small>Status: ${getStatusText(scooter.status)}<br>
                  Battery: ${scooter.battery_level}%</small><br>
                  <button class="popup-button" onclick="window.viewScooterDetail(${scooter.id})">Details</button>
                </div>
              `, {
                closeButton: false,
                className: 'custom-popup'
              })
            
            markers.value.push(marker)
          }
        })
        
        // Make the viewScooterDetail function available to the popup
        window.viewScooterDetail = viewScooterDetail
      })
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
    
    // View scooter detail
    const viewScooterDetail = (scooterId) => {
      // 先清除当前滑板车状态，确保详情页能加载新数据
      store.commit('scooter/setCurrentScooter', null)
      router.push(`/scooter/${scooterId}`)
    }
    
    // Refresh scooters
    const onRefresh = async () => {
      await store.dispatch('scooter/fetchScooters')
      refreshing.value = false
      
      // Update map markers
      addScooterMarkers()
    }
    
    // 设置自动刷新
    const setupAutoRefresh = () => {
      // 清除可能已存在的定时器
      clearInterval(refreshInterval)
      
      // 注释掉自动刷新代码
      /*
      // 设置定时刷新（每30秒刷新一次）
      refreshInterval = setInterval(() => {
        console.log('Auto refreshing scooters...')
        store.dispatch('scooter/fetchScooters').then(() => {
          // 更新地图标记
          addScooterMarkers()
        })
      }, 30000)
      */
    }
    
    // Load scooters (for van-list)
    const onLoad = () => {
      listLoading.value = false
      finished.value = true // We load all scooters at once
    }
    
    // Lifecycle hooks
    onMounted(async () => {
      // Fetch scooters
      await store.dispatch('scooter/fetchScooters')
      
      // Initialize map after scooters are loaded
      initMap()
      
      // Show error if any
      if (store.getters['scooter/error']) {
        Toast.fail(store.getters['scooter/error'])
      }
      
      // 注释掉自动刷新设置
      // setupAutoRefresh()
    })
    
    // 组件激活时刷新数据
    onActivated(() => {
      console.log('Home component activated, refreshing scooters...')
      store.dispatch('scooter/fetchScooters').then(() => {
        // 更新地图标记
        if (map.value) {
          addScooterMarkers()
        }
      })
      
      // 注释掉自动刷新设置
      // setupAutoRefresh()
    })
    
    onUnmounted(() => {
      // Clean up map
      if (map.value) {
        map.value.remove()
      }
      
      // Remove global function
      delete window.viewScooterDetail
      
      // 清除定时器
      clearInterval(refreshInterval)
    })
    
    // 组件卸载前清除定时器
    onBeforeUnmount(() => {
      clearInterval(refreshInterval)
    })
    
    return {
      mapContainer,
      scooters,
      loading,
      refreshing,
      listLoading,
      finished,
      hasActiveOrder,
      getStatusType,
      getStatusText,
      getBatteryColor,
      viewScooterDetail,
      onRefresh,
      onLoad
    }
  }
}
</script>

<style scoped>
.home-container {
  display: flex;
  flex-direction: column;
  height: 100vh;
  padding-top: 46px;
}

.map-container {
  flex: 1;
  min-height: 300px;
}

.scooter-list {
  height: 40vh;
  overflow-y: auto;
}

.scooter-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.scooter-info {
  display: flex;
  flex-direction: column;
}

.scooter-code {
  font-weight: bold;
  margin-bottom: 5px;
}

.scooter-battery {
  display: flex;
  align-items: center;
}

.scooter-battery span {
  margin-left: 5px;
}

/* Popup button style */
:global(.popup-button) {
  background-color: #1989fa;
  color: white;
  border: none;
  padding: 5px 10px;
  border-radius: 4px;
  cursor: pointer;
  margin-top: 5px;
}

.leaflet-popup-content {
  font-size: 13px;
  margin: 8px 10px;
  width: auto !important;
  max-width: 180px;
}

.leaflet-popup-content-wrapper {
  padding: 0;
  border-radius: 6px;
}

.leaflet-popup-tip {
  width: 12px;
  height: 12px;
}

.popup-button {
  background-color: #4CAF50;
  color: white;
  border: none;
  border-radius: 3px;
  padding: 3px 6px;
  margin-top: 5px;
  font-size: 12px;
  cursor: pointer;
  display: block;
  width: 100%;
}

.popup-button:hover {
  background-color: #45a049;
}
</style>
