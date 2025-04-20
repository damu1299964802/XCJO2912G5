// rem.js - 用于设置响应式布局
// 基准大小
const baseSize = 37.5 // 基于iPhone 6的设计稿375px宽度

// 设置 rem 函数
function setRem() {
  // 当前页面宽度相对于 375 宽的缩放比例，可根据自己需要修改
  const scale = document.documentElement.clientWidth / 375
  
  // 设置页面根节点字体大小
  document.documentElement.style.fontSize = baseSize * Math.min(scale, 2) + 'px'
}

// 初始化
setRem()

// 改变窗口大小时重新设置 rem
window.onresize = function() {
  setRem()
}

// 页面加载时设置一次
window.addEventListener('pageshow', function (e) {
  if (e.persisted) {
    setRem()
  }
})

// 设置视口缩放
function setViewport() {
  const viewport = document.querySelector('meta[name="viewport"]')
  if (viewport) {
    viewport.content = 'width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'
  } else {
    const metaEl = document.createElement('meta')
    metaEl.setAttribute('name', 'viewport')
    metaEl.setAttribute('content', 'width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no')
    document.head.appendChild(metaEl)
  }
}

// 初始化视口
setViewport()

export default {
  setRem,
  setViewport
}
