<template>
	<div class="scheme">
		<div class="title">
			<span>{{title}}</span>
			<el-tooltip placement="top" effect="light" :content="state?'扫码模式':'常规模式'">
				<el-switch v-model="state" :width="24" @change="change"></el-switch>
			</el-tooltip>
		</div>
	</div>
</template>
<script>
	//头组件|模式切换
	import Vue from 'vue';
    export default Vue.extend({
        name: 'Scheme',
        data() {
            return {
				title:'',
				state:false
            }
        },
        created(){
			this.title=this.params.dispose.title;
        },
        methods:{
			change(){
				this.$parent.$emit('schemeChange',this.state);
			}
        }
    })
</script>
<style scoped>
	.scheme{
		width: 100%;
	}
	.title{
		text-align: center;
	}
	.el-switch{
		height: 12px;
		margin-left: 6px;
		line-height: 12px;
	}
	.el-switch >>> .el-switch__core{
		height: 12px;
	}
	.el-switch >>> .el-switch__core:after{
		width: 8px;
		height: 8px;
	}
	.el-switch.is-checked >>> .el-switch__core{
		border-color: #9E9E9E;
		background-color: #9E9E9E;
	}
	.el-switch.is-checked >>> .el-switch__core::after{
	    left: 100%;
	    margin-left: -9px;
	}
</style>