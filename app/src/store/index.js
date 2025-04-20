import { createStore } from 'vuex'
import createPersistedState from 'vuex-persistedstate'
import auth from './modules/auth'
import scooter from './modules/scooter'
import order from './modules/order'
import feedback from './modules/feedback'
import user from './modules/user'
import payment from './modules/payment'

export default createStore({
  modules: {
    auth,
    scooter,
    order,
    feedback,
    user,
    payment
  },
  plugins: [
    createPersistedState({
      key: 'electric-scooter-app',
      paths: ['auth.token', 'auth.user']
    })
  ]
})
