import Vue from "vue";
import axios from "axios";
import {Loading} from "element-ui";
import store from "@/config/store";

let load; //初始弹层状态
axios.defaults.baseURL = Vue.prototype.$base.web; //通用跨域配置

//请求开始配置
axios.interceptors.request.use(config => {
	config.headers.Token = store.state.token;
	//请求开始|展示Load
	load = Loading.service({
		lock: true,
		text: "数据加载中...",
		background: "rgba(0, 0, 0, 0.3)"
	});
	return config;
});

//请求返回配置
axios.interceptors.response.use(
	response => {
		load.close(); //请求成功|关闭Load
		return response.data; //返回对象data
	},
	error => {
		load.close(); //请求失败|关闭Load
		(error.response!=undefined&&error.response.status == 401) && (window.location.href = "/"); //登录失效|跳转首页
		return error;
	}
);
Vue.prototype.$axios = axios; //配置Vue
