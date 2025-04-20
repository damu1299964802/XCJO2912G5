<template>
  <div class="change-password">
    <div class="header">
      <button class="back-btn" @click="$router.go(-1)">
        <span>&larr;</span> Back
      </button>
      <h2>Change Password</h2>
    </div>
    <div class="form-container">
      <div class="form-group">
        <label for="oldPassword">Current Password</label>
        <input
          type="password"
          id="oldPassword"
          v-model="oldPassword"
          placeholder="Enter your current password"
        />
      </div>
      <div class="form-group">
        <label for="newPassword">New Password</label>
        <input
          type="password"
          id="newPassword"
          v-model="newPassword"
          placeholder="Enter your new password"
        />
      </div>
      <div class="form-group">
        <label for="confirmPassword">Confirm New Password</label>
        <input
          type="password"
          id="confirmPassword"
          v-model="confirmPassword"
          placeholder="Confirm your new password"
        />
      </div>
      <div class="error-message" v-if="error">
        {{ error }}
      </div>
      <div class="success-message" v-if="success">
        {{ success }}
      </div>
      <button class="submit-btn" @click="handleSubmit" :disabled="isLoading">
        {{ isLoading ? 'Updating...' : 'Update Password' }}
      </button>
    </div>
  </div>
</template>

<script>
import { ref, computed } from 'vue'
import { useStore } from 'vuex'
import { useRouter } from 'vue-router'
import { Toast } from 'vant'

export default {
  name: 'ChangePassword',
  setup() {
    const store = useStore()
    const router = useRouter()
    const oldPassword = ref('')
    const newPassword = ref('')
    const confirmPassword = ref('')
    const error = ref('')
    const success = ref('')
    const isLoading = computed(() => store.state.user.loading)

    const validateForm = () => {
      if (!oldPassword.value || !newPassword.value || !confirmPassword.value) {
        error.value = 'Please fill in all fields'
        return false
      }
      if (newPassword.value !== confirmPassword.value) {
        error.value = 'New passwords do not match'
        return false
      }
      if (newPassword.value.length < 6) {
        error.value = 'New password must be at least 6 characters long'
        return false
      }
      return true
    }

    const handleSubmit = async () => {
      error.value = ''
      success.value = ''
      
      if (!validateForm()) return

      try {
        const response = await store.dispatch('user/updatePassword', {
          old_password: oldPassword.value,
          new_password: newPassword.value
        })
        
        console.log('Password update response:', response)
        success.value = 'Password updated successfully'
        Toast.success('Password updated successfully')
        
        // 清空表单
        oldPassword.value = ''
        newPassword.value = ''
        confirmPassword.value = ''
        
        // 延迟导航，让用户看到成功消息
        setTimeout(() => {
          router.push('/profile')
        }, 1500)
      } catch (err) {
        console.error('Error updating password:', err)
        const errorMessage = err.message || 'Failed to update password'
        error.value = errorMessage
        Toast.fail(errorMessage)
      }
    }

    return {
      oldPassword,
      newPassword,
      confirmPassword,
      error,
      success,
      isLoading,
      handleSubmit
    }
  }
}
</script>

<style scoped>
.change-password {
  max-width: 400px;
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

.form-container {
  background: #fff;
  border-radius: 8px;
  padding: 20px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.form-group {
  margin-bottom: 20px;
}

label {
  display: block;
  margin-bottom: 8px;
  color: #666;
  font-weight: 500;
  font-size: 14px;
}

input {
  width: 100%;
  padding: 8px;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 13px;
}

input:focus {
  outline: none;
  border-color: #4CAF50;
}

.error-message {
  color: #dc3545;
  margin-bottom: 15px;
  font-size: 13px;
}

.success-message {
  color: #28a745;
  margin-bottom: 15px;
  font-size: 13px;
}

.submit-btn {
  width: 100%;
  padding: 10px;
  background: #4CAF50;
  color: white;
  border: none;
  border-radius: 4px;
  font-size: 14px;
  cursor: pointer;
  transition: background-color 0.3s;
}

.submit-btn:hover {
  background: #45a049;
}

.submit-btn:disabled {
  background: #cccccc;
  cursor: not-allowed;
}
</style>
