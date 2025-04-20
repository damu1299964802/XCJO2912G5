<template>
  <div class="personal-info">
    <div class="header">
      <button class="back-btn" @click="$router.go(-1)">
        <span>&larr;</span> Back
      </button>
      <h2>Personal Information</h2>
    </div>

    <div class="info-container" v-if="profile">
      <div class="info-item">
        <label>Username:</label>
        <span>{{ profile.username }}</span>
      </div>
      <div class="info-item">
        <label>Email:</label>
        <span>{{ profile.email }}</span>
      </div>
      <div class="info-item">
        <label>Phone:</label>
        <span>{{ profile.phone }}</span>
      </div>
      <div class="info-item">
        <label>Account Status:</label>
        <span>{{ profile.status }}</span>
      </div>
      <div class="info-item">
        <label>Created At:</label>
        <span>{{ formatDate(profile.created_at) }}</span>
      </div>
    </div>
    <div class="loading" v-if="loading">Loading...</div>
    <div class="error-message" v-if="error">
      {{ error }}
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue'
import { useStore } from 'vuex'
import { Toast } from 'vant'

export default {
  name: 'PersonalInfo',
  setup() {
    const store = useStore()
    const loading = ref(false)
    const error = ref('')
    
    // 从store中获取用户信息
    const profile = computed(() => store.state.user.profile)
    
    const fetchUserInfo = async () => {
      loading.value = true
      error.value = ''
      
      try {
        await store.dispatch('user/getProfile')
        if (!profile.value) {
          error.value = 'Unable to load profile data'
        }
      } catch (err) {
        console.error('Error in PersonalInfo:', err)
        error.value = err.message || 'Failed to load user information'
        Toast.fail(error.value)
      } finally {
        loading.value = false
      }
    }

    const formatDate = (dateString) => {
      if (!dateString) return 'N/A'
      return new Date(dateString).toLocaleString()
    }

    onMounted(() => {
      fetchUserInfo()
    })

    return {
      profile,
      error,
      loading,
      formatDate
    }
  }
}
</script>

<style scoped>
.personal-info {
  max-width: 600px;
  margin: 0 auto;
  padding: 20px;
}

.header {
  display: flex;
  align-items: center;
  margin-bottom: 20px;
}

.back-btn {
  background: none;
  border: none;
  color: #666;
  cursor: pointer;
  font-size: 14px;
  padding: 5px 10px;
  display: flex;
  align-items: center;
  gap: 5px;
}

.back-btn:hover {
  color: #333;
}

h2 {
  color: #333;
  margin: 0 auto;
  font-size: 18px;
}

.info-container {
  background: #fff;
  border-radius: 8px;
  padding: 20px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.info-item {
  display: flex;
  margin-bottom: 15px;
  padding: 10px 0;
  border-bottom: 1px solid #eee;
}

.info-item:last-child {
  border-bottom: none;
}

.info-item label {
  width: 120px;
  color: #666;
  font-weight: 500;
  font-size: 14px;
}

.info-item span {
  flex: 1;
  color: #333;
  font-size: 14px;
}

.loading {
  text-align: center;
  color: #666;
  margin: 20px 0;
  font-size: 14px;
}

.error-message {
  color: #dc3545;
  margin-top: 20px;
  text-align: center;
  font-size: 13px;
}
</style> 