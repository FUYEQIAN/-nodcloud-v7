<template>
	<span class="pageStatus">
		{{text}}
	</span>
</template>

<script>
	// [
	// 	{text:'汇总数据',type:'text'},
	// 	{text:'商品总数',type:'count'},
	// 	{text:'总数量',type:'sum',key:'nums'},
	// 	{text:'总价格',type:'sum',key:'total'},
	// 	{text:'平均价',type:'avg',key:'total',fun(text){retrn 1;}}
	// ]
    export default {
        name: 'PageStatus',
		props: {
			config:{
				default:()=>{
					return [{text:'HELLO NODCLOUD',type:'text'}];
				}
			},
			model:{
				default:''
			}
		},
		data() {
			return {
				text:[]
			}
		},
		created() {
			this.handle();
		},
		watch: {
			model(data){
				this.handle();
			}
		},
		methods:{
			//处理数据
			handle(){
				let arr=[];
				//循环配置项
				for (let configVo of this.config) {
					let text="";
					if(configVo.type=='text'){
						//文本类型
						text=configVo.text;
					}else if(configVo.type=='count'){
						//总数统计
						text=configVo.text+':'+this.model.length;
					}else if(configVo.type=='sum'){
						//总和统计
						let number = this.$calc.chain(0);
						this.model.map((modelVo)=>{
						   number.add(eval('modelVo.'+configVo.key));
						});
						text=configVo.text+':'+number.done();
					}else if(configVo.type=='avg'){
						//平均值统计
						let number = this.$calc.chain(0);
						this.model.map((modelVo)=>{
						   number.add(eval('modelVo.'+configVo.key));
						});
						number=this.$calc.chain(number.done()).divide(this.model.length).round(4).done();
						text=configVo.text+':'+number;
					}
					configVo.hasOwnProperty('fun')&&(text=configVo.fun(text));
					arr.push(text);
				}
				this.text=arr.join(" | ");
			}
		}
    }
</script>

<style scoped>
	.pageStatus{
		height: 24px !important;
		margin: 0 6px;
		padding: 0 6px;
		color: #606266;
		line-height: 24px !important;
		border-radius: 3px;
		border: 1px solid #dcdfe6;
		font-weight: normal;
	}
</style>
