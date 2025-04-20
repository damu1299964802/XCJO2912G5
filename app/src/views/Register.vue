<template>
  <div class="register-container">
    <van-nav-bar
      title="Register"
      left-arrow
      @click-left="$router.push('/login')"
    />
    
    <van-form @submit="onSubmit">
      <van-cell-group inset>
        <van-field
          v-model="username"
          name="username"
          label="Username"
          placeholder="Your username"
          :rules="[
            { required: true, message: 'Username is required' },
            { min: 4, message: 'Username must be at least 4 characters' },
            { max: 20, message: 'Username cannot exceed 20 characters' },
            { pattern: /^[a-zA-Z0-9_]+$/, message: 'Username can only contain letters, numbers and underscores' }
          ]"
        >
          <template #extra>
            <span class="char-count" :class="{ 'error': username.length > 0 && (username.length < 4 || username.length > 20) }">
              {{ username.length }}/20
            </span>
          </template>
        </van-field>
        <div class="field-tip">Username must be 4-20 characters and contain only letters, numbers, and underscores</div>
        <van-field
          v-model="email"
          name="email"
          label="Email"
          placeholder="Your email"
          :rules="[
            { required: true, message: 'Email is required' },
            { pattern: /.+@.+\..+/, message: 'Please enter a valid email' }
          ]"
        />
        <van-field
          v-model="phone"
          name="phone"
          label="Phone"
          placeholder="Your phone number"
          :rules="[
            { required: true, message: 'Phone number is required' },
            { pattern: /^\d{11}$/, message: 'Please enter a valid phone number' }
          ]"
        />
        <van-field
          v-model="password"
          type="password"
          name="password"
          label="Password"
          placeholder="Your password"
          :rules="[
            { required: true, message: 'Password is required' },
            { min: 6, message: 'Password must be at least 6 characters' }
          ]"
        />
        <van-field
          v-model="confirmPassword"
          type="password"
          name="confirmPassword"
          label="Confirm Password"
          placeholder="Confirm your password"
          :rules="[
            { required: true, message: 'Please confirm your password' },
            { validator: validateConfirmPassword, message: 'Passwords do not match' }
          ]"
        />
      </van-cell-group>
      
      <div style="margin: 16px;">
        <van-button round block type="primary" native-type="submit" :loading="loading">
          Register
        </van-button>
        <div class="login-link">
          Already have an account? <router-link to="/login">Login</router-link>
        </div>
      </div>
    </van-form>
    
    <van-toast id="van-toast" />
  </div>
</template>

<script>
import { ref, computed } from 'vue'
import { useStore } from 'vuex'
import { Toast } from 'vant'

export default {
  name: 'Register',
  setup() {
    const store = useStore()
    const username = ref('')
    const email = ref('')
    const phone = ref('')
    const password = ref('')
    const confirmPassword = ref('')
    
    const loading = computed(() => store.getters['auth/loading'])
    const error = computed(() => store.getters['auth/error'])
    
    // Watch for errors and show toast
    if (error.value) {
      Toast.fail(error.value)
    }
    
    const validateConfirmPassword = () => {
      return password.value === confirmPassword.value
    }
    
    const onSubmit = async () => {
      const userData = {
        username: username.value,
        email: email.value,
        phone: phone.value,
        password: password.value
      }
      
      const success = await store.dispatch('auth/register', userData)
      
      if (success) {
        Toast.success('Registration successful! Please login.')
      } else if (store.getters['auth/error']) {
        Toast.fail(store.getters['auth/error'])
      }
    }
    
    return {
      username,
      email,
      phone,
      password,
      confirmPassword,
      loading,
      validateConfirmPassword,
      onSubmit
    }
  }
}
</script>

<style scoped>
.register-container {
  padding-top: 46px;
  height: 100vh;
  display: flex;
  flex-direction: column;
}

.login-link {
  margin-top: 16px;
  text-align: center;
  font-size: 14px;
}

.login-link a {
  color: #1989fa;
  text-decoration: none;
}

.field-tip {
  margin-top: 8px;
  margin-bottom: 16px;
  font-size: 12px;
  color: #909399;
}

.char-count {
  margin-left: 8px;
  color: #909399;
}

.error {
  color: #f56c6c;
}
</style>
