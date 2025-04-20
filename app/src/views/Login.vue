<template>
  <div class="login-container">
    <div class="logo">
      <img src="../assets/logo.png" alt="Logo">
      <h2>Electric Scooter Rental</h2>
    </div>
    
    <van-form @submit="onSubmit">
      <van-cell-group inset>
        <van-field
          v-model="email"
          name="email"
          label="Email"
          placeholder="Your email"
          :rules="[{ required: true, message: 'Email is required' }]"
        />
        <van-field
          v-model="password"
          type="password"
          name="password"
          label="Password"
          placeholder="Your password"
          :rules="[{ required: true, message: 'Password is required' }]"
        />
      </van-cell-group>
      
      <div style="margin: 16px;">
        <van-button round block type="primary" native-type="submit" :loading="loading">
          Login
        </van-button>
        <div class="register-link">
          Don't have an account? <router-link to="/register">Register</router-link>
        </div>
      </div>
    </van-form>
    
    <van-toast id="van-toast" />
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue'
import { useStore } from 'vuex'
import { useRoute, useRouter } from 'vue-router'
import { Toast } from 'vant'

export default {
  name: 'Login',
  setup() {
    const store = useStore()
    const router = useRouter()
    const route = useRoute()
    const email = ref('')
    // const email = ref('user1@example.com')
    // const password = ref('password')
    const password = ref('')
    
    const loading = computed(() => store.getters['auth/loading'])
    const error = computed(() => store.getters['auth/error'])
    
    // Watch for errors and show toast
    if (error.value) {
      Toast.fail(error.value)
    }
    
    // 检查是否已登录
    onMounted(() => {
      if (store.getters['auth/isLoggedIn']) {
        router.push('/home')
      }
    })
    
    const onSubmit = async () => {
      const success = await store.dispatch('auth/login', {
        email: email.value,
        password: password.value
      })
      
      if (!success && store.getters['auth/error']) {
        Toast.fail(store.getters['auth/error'])
      }
    }
    
    return {
      email,
      password,
      loading,
      onSubmit
    }
  }
}
</script>

<style scoped>
.login-container {
  padding: 20px;
  height: 100vh;
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.logo {
  text-align: center;
  margin-bottom: 30px;
}

.logo img {
  width: 80px;
  height: 80px;
}

.logo h2 {
  margin-top: 10px;
  font-size: 24px;
  color: #323233;
}

.register-link {
  margin-top: 16px;
  text-align: center;
  font-size: 14px;
}

.register-link a {
  color: #1989fa;
  text-decoration: none;
}
</style>
