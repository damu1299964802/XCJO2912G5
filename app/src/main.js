import { createApp } from 'vue'
import App from './App.vue'
import router from './router'
import store from './store'
import { Toast, Dialog, Button, Field, Form, NavBar, Tabbar, TabbarItem, Icon, Cell, CellGroup, List, PullRefresh, Tab, Tabs, Badge, Popup, Picker, Switch, Rate, RadioGroup, Radio, Uploader, Empty, Tag } from 'vant'
import 'vant/lib/index.css'
import './assets/css/global.css'
import './utils/rem'

const app = createApp(App)

// Register Vant components
const vantComponents = [
  Toast, Dialog, Button, Field, Form, NavBar, Tabbar, TabbarItem, 
  Icon, Cell, CellGroup, List, PullRefresh, Tab, Tabs, Badge, 
  Popup, Picker, Switch, Rate, RadioGroup, Radio, Uploader, Empty, Tag
]

vantComponents.forEach(component => {
  app.use(component)
})

app.use(store).use(router).mount('#app')
