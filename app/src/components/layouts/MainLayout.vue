<template>
  <div class="main-layout">
    <div class="content">
      <slot></slot>
    </div>
    
    <van-tabbar v-model="activeTab" class="global-tabbar" v-if="showTabbar">
      <van-tabbar-item icon="home-o" to="/home">
        Home
      </van-tabbar-item>
      <van-tabbar-item icon="orders-o" to="/orders" :badge="currentOrderCount || ''">
        Orders
      </van-tabbar-item>
      <van-tabbar-item icon="user-o" to="/profile">
        Profile
      </van-tabbar-item>
    </van-tabbar>
  </div>
</template>

<script>
import { ref, computed, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useStore } from 'vuex'

export default {
  name: 'MainLayout',
  setup() {
    const route = useRoute()
    const store = useStore()
    const activeTab = ref(0)

    // Calculate current order count
    const currentOrderCount = computed(() => {
      const currentOrder = store.getters['order/currentOrder']
      return currentOrder ? 1 : 0
    })

    // Show/hide tabbar based on route
    const showTabbar = computed(() => {
      const hideTabbarRoutes = ['/login', '/register', '/scooter/return']
      return !hideTabbarRoutes.includes(route.path)
    })

    // Update active tab based on current route
    watch(() => route.path, (path) => {
      if (path.startsWith('/home')) {
        activeTab.value = 0
      } else if (path.startsWith('/orders')) {
        activeTab.value = 1
      } else if (path.startsWith('/profile')) {
        activeTab.value = 2
      }
    }, { immediate: true })

    return {
      activeTab,
      currentOrderCount,
      showTabbar
    }
  }
}
</script>

<style scoped>
.main-layout {
  min-height: 100vh;
  padding-bottom: 50px;
  box-sizing: border-box;
}

.content {
  height: 100%;
}

.global-tabbar {
  position: fixed;
  bottom: 0;
  width: 100%;
  background-color: #fff;
  box-shadow: 0 -2px 8px rgba(0, 0, 0, 0.1);
}
</style> 