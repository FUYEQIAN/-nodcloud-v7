<template>
	<el-date-picker ref="datePicker" v-model="value" type="date" :editable="false" :clearable="false" @change="change" value-format="yyyy-MM-dd" placeholder="点击选择"></el-date-picker>
</template>
<script>
	//内容组件|有效期|生产日期
	import Vue from 'vue';
    export default Vue.extend({
        name: 'Validity',
        data() {
            return {
                value: ''
            }
        },
        created(){
			this.value = this.params.value;
        },
		computed: {
			hasData(){
				let data=this.params.node.data;
				if(data.hasOwnProperty('validityType') && data.validityType){
					return true;
				}else{
					return false;
				}
			},
		},
        methods:{
			change(){
				this.params.api.stopEditing();
			},
			//返回内容
            getValue(){
				return this.value;
            },
			//编辑状态
			isCancelBeforeStart() {
				return !this.hasData;
			}
        },
		mounted() {
			if(this.hasData){
				this.$refs.datePicker.pickerVisible=true;
			}
		}
    })
</script>
<style scoped>
	.el-date-editor{
		width: 100%;
		height: 100%;
	}
	.el-date-editor >>> .el-input__inner{
		width: 100%;
		height: 100%;
		padding: 0;
		border:none;
		border-radius:0;
		text-align: center;
		font-size: 12px;
		color: #000000;
	}
	.el-date-editor >>> .el-input__prefix , .el-date-editor >>> .el-input__suffix{
		display: none;
	}
</style>