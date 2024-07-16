import Vue from "vue";
import App from "@/App.vue";
import "@/config/bus"; //全局总线
import "@/config/base"; //全局变量
import "@/config/math"; //高精度计算
import "@/config/axios"; //网络请求
import "@/config/moment"; //日期组件
import "@/assets/js/lib"; //公用库JS
import "@/config/ag-grid"; //数据表格
import "@/config/elementUi"; //UI组件
import "@/config/directives"; //扩展命令
import "@/assets/css/lib.css"; //公用库CSS
import "@/assets/css/lib.css"; //公用库CSS
import store from "@/config/store"; //数据中心
import router from "@/config/router"; //路由配置
Vue.config.productionTip = false;
new Vue({
  router,
  store,
  render: (h) => h(App),
}).$mount("#app");
