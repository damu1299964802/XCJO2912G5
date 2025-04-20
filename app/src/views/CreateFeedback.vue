<template>
  <div class="create-feedback">
    <van-nav-bar
      title="Submit Feedback"
      left-arrow
      @click-left="$router.go(-1)"
    />
    
    <van-form @submit="onSubmit">
      <van-cell-group inset>
        <van-field
          v-model="feedback.title"
          name="title"
          label="Title"
          placeholder="Enter feedback title"
          :rules="[{ required: true, message: 'Please enter a title' }]"
        />
        
        <van-field
          v-model="feedback.type"
          is-link
          readonly
          name="type"
          label="Feedback Type"
          placeholder="Select feedback type"
          @click="showTypePicker = true"
          :rules="[{ required: true, message: 'Please select a feedback type' }]"
        />
        <van-popup v-model:show="showTypePicker" position="bottom">
          <van-picker
            :columns="typeOptions"
            @confirm="onTypeConfirm"
            @cancel="showTypePicker = false"
          />
        </van-popup>
        
        <van-field
          v-if="feedback.type === 'Equipment Fault'"
          v-model="feedback.scooterDisplay"
          is-link
          readonly
          name="scooter"
          label="Select Scooter"
          placeholder="Select related scooter"
          @click="showScooterPicker = true"
        />
        <van-popup v-model:show="showScooterPicker" position="bottom">
          <van-picker
            :columns="scooterOptions"
            @confirm="onScooterConfirm"
            @cancel="showScooterPicker = false"
          />
        </van-popup>
        
        <van-field
          v-model="feedback.content"
          rows="4"
          autosize
          type="textarea"
          name="content"
          label="Content"
          placeholder="Please describe your issue or suggestion in detail"
          :rules="[{ required: true, message: 'Please enter feedback content' }]"
        />
      </van-cell-group>
      
      <div style="margin: 16px;">
        <van-button round block type="primary" native-type="submit" :loading="loading">
          Submit Feedback
        </van-button>
      </div>
    </van-form>
  </div>
</template>

<script>
import { ref, computed, onMounted, watch } from 'vue'
import { useStore } from 'vuex'
import { useRouter } from 'vue-router'
import { Toast } from 'vant'

export default {
  name: 'CreateFeedback',
  setup() {
    const store = useStore()
    const router = useRouter()
    const loading = computed(() => store.state.feedback.loading)
    
    const feedback = ref({
      title: '',
      type: '',
      content: '',
      scooter_id: null,
      scooterDisplay: ''
    })
    
    // 滑板车列表相关
    const showScooterPicker = ref(false)
    const scooters = ref([])
    const scooterOptions = computed(() => {
      return scooters.value.map(scooter => ({
        text: `${scooter.scooter_code} (Battery: ${scooter.battery_level}%)`,
        value: scooter.id
      }))
    })
    
    // 加载滑板车列表
    const loadScooters = async () => {
      try {
        await store.dispatch('scooter/fetchScooters')
        scooters.value = store.getters['scooter/allScooters']
      } catch (error) {
        console.error('Failed to load scooters:', error)
        Toast.fail('Failed to load scooters')
      }
    }
    
    // 类型选择相关
    const showTypePicker = ref(false)
    const typeOptions = ['Equipment Fault', 'Software Issue', 'Service Suggestion', 'Other']
    
    const onTypeConfirm = (value) => {
      feedback.value.type = value
      showTypePicker.value = false
      
      // 如果类型变成设备故障，但还没有加载滑板车，则加载滑板车列表
      if (value === 'Equipment Fault' && scooters.value.length === 0) {
        loadScooters()
      }
      
      // 如果类型不是设备故障，清除滑板车选择
      if (value !== 'Equipment Fault') {
        feedback.value.scooter_id = null
        feedback.value.scooterDisplay = ''
      }
    }
    
    const onScooterConfirm = (value) => {
      feedback.value.scooter_id = value.value
      feedback.value.scooterDisplay = value.text
      showScooterPicker.value = false
    }
    
    const onSubmit = async () => {
      // 将前端显示的类型映射到后端需要的类型格式
      let typeValue = '';
      switch(feedback.value.type) {
        case 'Equipment Fault':
          typeValue = 'scooter_fault';
          break;
        case 'Software Issue':
          typeValue = 'app_issue';
          break;
        case 'Service Suggestion':
          typeValue = 'suggestion';
          break;
        case 'Other':
          typeValue = 'other';
          break;
        default:
          typeValue = 'other';
      }
      
      const feedbackData = {
        title: feedback.value.title,
        type: typeValue,
        content: feedback.value.content
      }
      
      // 如果是设备故障且选择了滑板车，添加滑板车ID
      if (typeValue === 'scooter_fault' && feedback.value.scooter_id) {
        feedbackData.scooter_id = feedback.value.scooter_id
      }
      
      console.log('Submitting feedback data:', feedbackData);
      
      try {
        const success = await store.dispatch('feedback/createFeedback', feedbackData);
        console.log('Feedback submission result:', success);
        
        if (success) {
          Toast.success('Feedback submitted successfully');
          router.push('/feedback');
        } else {
          Toast.fail('Failed to submit feedback');
        }
      } catch (error) {
        console.error('Error submitting feedback:', error);
        Toast.fail('Failed to submit feedback: ' + (error.message || 'Unknown error'));
      }
    }
    
    // 组件挂载时，预加载滑板车列表
    onMounted(() => {
      // 预加载滑板车列表，以便用户选择设备故障类型时能立即显示
      loadScooters()
    })
    
    return {
      feedback,
      showTypePicker,
      typeOptions,
      showScooterPicker,
      scooterOptions,
      loading,
      onTypeConfirm,
      onScooterConfirm,
      onSubmit
    }
  }
}
</script>

<style scoped>
.create-feedback {
  padding-bottom: 50px;
}
</style>
