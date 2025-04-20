<template>
  <div class="profile-container">
    <van-nav-bar title="Profile" />
    
    <!-- User Info Card -->
    <div class="user-card">
      <van-cell-group inset>
        <van-cell center>
          <template #title>
            <div class="user-info">
              <van-image
                round
                width="64"
                height="64"
                :src="userAvatar"
                fit="cover"
              />
              <div class="user-details">
                <div class="username">{{ user?.name || 'User' }}</div>
                <div class="email">{{ user?.email }}</div>
              </div>
            </div>
          </template>
        </van-cell>
      </van-cell-group>
    </div>

    <!-- Menu Items -->
    <van-cell-group inset class="menu-group">
      <!-- Orders -->
      <van-cell title="My Orders" is-link to="/orders" icon="orders-o">
        <template #right-icon>
          <van-badge :content="currentOrderCount" v-if="currentOrderCount > 0">
            <van-icon name="arrow" />
          </van-badge>
          <van-icon v-else name="arrow" />
        </template>
      </van-cell>
      
      <!-- Profile Settings -->
      <van-cell title="Personal Info" is-link @click="goToPersonalInfo" icon="contact" />
      <van-cell title="Change Password" is-link to="/change-password" icon="lock" />
      
      <!-- Feedback -->
      <van-cell title="Feedback" is-link to="/feedback" icon="comment-o" />
      
      <!-- Payment Methods -->
      <van-cell title="Payment Methods" is-link to="/payment/methods" icon="credit-pay" />
    </van-cell-group>

    <!-- Logout Button -->
    <div class="logout-button">
      <van-button block type="danger" @click="handleLogout">Logout</van-button>
    </div>
  </div>
</template>

<script>
import { computed, ref } from 'vue'
import { useStore } from 'vuex'
import { useRouter } from 'vue-router'
import { Dialog } from 'vant'

export default {
  name: 'Profile',
  setup() {
    const router = useRouter()
    const store = useStore()
    
    const user = computed(() => store.getters['auth/currentUser'])
    const currentOrderCount = computed(() => {
      const currentOrder = store.getters['order/currentOrder']
      return currentOrder ? 1 : 0
    })

    // Default avatar
    const userAvatar = ref('https://fastly.jsdelivr.net/npm/@vant/assets/cat.jpeg')

    const goToOrders = () => {
      router.push('/orders')
    }

    const goToPersonalInfo = () => {
      router.push('/personal-info')
    }

    const goToChangePassword = () => {
      router.push('/change-password')
    }

    const goToFeedback = () => {
      router.push('/feedback')
    }

    const handleLogout = async () => {
      await store.dispatch('auth/logout')
      router.push('/login')
    }

    return {
      user,
      userAvatar,
      currentOrderCount,
      goToOrders,
      goToPersonalInfo,
      goToChangePassword,
      goToFeedback,
      handleLogout
    }
  }
}
</script>

<style scoped>
.profile-container {
  padding-top: 46px;
  min-height: 100vh;
  background-color: #f7f8fa;
}

.user-card {
  margin: 16px 0;
}

.user-info {
  display: flex;
  align-items: center;
  padding: 8px 0;
}

.user-details {
  margin-left: 16px;
}

.username {
  font-size: 18px;
  font-weight: bold;
  margin-bottom: 4px;
}

.email {
  font-size: 14px;
  color: #969799;
}

.menu-group {
  margin-bottom: 16px;
}

.logout-button {
  margin: 24px 16px;
}

:deep(.van-cell-group) {
  margin: 0 16px;
}

:deep(.van-cell__title) {
  display: flex;
  align-items: center;
}

:deep(.van-cell__title .van-icon) {
  margin-right: 8px;
  font-size: 20px;
}
</style>
