<template>
  <div class="feedback-container">
    <van-nav-bar 
      title="My Feedback"
      left-arrow
      @click-left="$router.go(-1)"
    />
    
    <van-pull-refresh v-model="refreshing" @refresh="refreshFeedbacks">
      <div class="feedback-list" v-if="feedbacks.length > 0">
        <van-cell 
          v-for="feedback in feedbacks" 
          :key="feedback.id" 
          :title="getFeedbackTypeText(feedback.type)"
        >
          <template #label>
            <div class="feedback-info">
              <div class="feedback-content">{{ feedback.content }}</div>
              <div class="feedback-time">{{ formatDate(feedback.created_at) }}</div>
              <div class="feedback-status">
                <van-tag :type="getFeedbackStatusType(feedback.status)">
                  {{ getFeedbackStatusText(feedback.status) }}
                </van-tag>
              </div>
              <div class="feedback-response" v-if="feedback.response">
                <div class="response-label">Admin Response:</div>
                <div class="response-content">{{ feedback.response }}</div>
              </div>
            </div>
          </template>
        </van-cell>
      </div>
      <van-empty v-else description="No feedback submitted yet" />
    </van-pull-refresh>
    
    <div class="action-button">
      <van-button type="primary" round icon="plus" size="small" @click="$router.push('/feedback/create')">
        New Feedback
      </van-button>
    </div>
    
    <van-toast id="van-toast" />
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue'
import { useStore } from 'vuex'
import { Toast } from 'vant'

export default {
  name: 'Feedback',
  setup() {
    const store = useStore()
    
    const refreshing = ref(false)
    
    const feedbacks = computed(() => store.getters['feedback/allFeedbacks'])
    const loading = computed(() => store.getters['feedback/loading'])
    const error = computed(() => store.getters['feedback/error'])
    
    // Format date
    const formatDate = (dateString) => {
      if (!dateString) return 'Not available'
      
      const date = new Date(dateString)
      return date.toLocaleString()
    }
    
    // Get feedback type text
    const getFeedbackTypeText = (type) => {
      switch (type) {
        case 'scooter_issue':
          return 'Scooter Issue'
        case 'location_error':
          return 'Location Error'
        case 'app_issue':
          return 'App Issue'
        case 'suggestion':
          return 'Suggestion'
        case 'other':
          return 'Other'
        default:
          return type ? type.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase()) : 'Unknown'
      }
    }
    
    // Get feedback status type for tag
    const getFeedbackStatusType = (status) => {
      switch (status) {
        case 'pending':
          return 'warning'
        case 'in_progress':
          return 'primary'
        case 'resolved':
          return 'success'
        case 'rejected':
          return 'danger'
        default:
          return 'default'
      }
    }
    
    // Get feedback status text
    const getFeedbackStatusText = (status) => {
      switch (status) {
        case 'pending':
          return 'Pending'
        case 'in_progress':
          return 'In Progress'
        case 'resolved':
          return 'Resolved'
        case 'rejected':
          return 'Rejected'
        default:
          return status ? status.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase()) : 'Unknown'
      }
    }
    
    // Refresh feedbacks
    const refreshFeedbacks = async () => {
      try {
        await store.dispatch('feedback/fetchFeedbacks')
      } catch (err) {
        console.error('Error refreshing feedbacks:', err)
        Toast.fail(err.message || 'Failed to load feedbacks')
      } finally {
        refreshing.value = false
      }
    }
    
    // Lifecycle hooks
    onMounted(async () => {
      try {
        await store.dispatch('feedback/fetchFeedbacks')
      } catch (err) {
        console.error('Error loading feedbacks:', err)
        Toast.fail(err.message || 'Failed to load feedbacks')
      }
    })
    
    return {
      refreshing,
      feedbacks,
      loading,
      formatDate,
      getFeedbackTypeText,
      getFeedbackStatusType,
      getFeedbackStatusText,
      refreshFeedbacks
    }
  }
}
</script>

<style scoped>
.feedback-container {
  padding-top: 46px;
  padding-bottom: 50px;
  height: 100vh;
  display: flex;
  flex-direction: column;
}

.feedback-list {
  padding-bottom: 16px;
}

.feedback-info {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.feedback-content {
  margin-bottom: 5px;
}

.feedback-time {
  font-size: 14px;
  color: #646566;
}

.feedback-status {
  margin-top: 5px;
  margin-bottom: 5px;
}

.feedback-response {
  margin-top: 10px;
  background-color: #f7f8fa;
  padding: 8px;
  border-radius: 4px;
}

.response-label {
  font-weight: bold;
  margin-bottom: 4px;
}

.action-button {
  position: fixed;
  right: 16px;
  bottom: 70px;
  z-index: 999;
}
</style>
