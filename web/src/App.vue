<template>
	<div class="App">
		<router-view v-if="exist"/>
	</div>
</template>
<script>
	export default {
		name: "App",
		created(){
			this.$bus.on('rootReload', this.rootReload);
		},
		data() {
			return {
				exist:true
			};
		},
		methods: {
			//组件刷新|总线事件
			rootReload() {
				this.exist=false;
				this.$nextTick(()=>{
					this.exist = true;
				});
			}
		},
		beforeDestroy() {
			this.$bus.off('rootReload', this.rootReload);
		}
	};
</script>

<style>
	* {
		margin: 0;
		padding: 0;
	}
	body {
		
		overflow: hidden;
		font: 14px "Helvetica Neue", Helvetica, "PingFang SC", "Hiragino Sans GB", "Microsoft YaHei", "微软雅黑", Arial, sans-serif;
	}
	a {
		text-decoration: none;
	}
</style>
