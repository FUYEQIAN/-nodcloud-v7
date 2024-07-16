<template>
	<div class="status">
		<p>{{text}}</p>
		<slot></slot>
	</div>
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
        name: 'Status',
		props: {
			config:{
				default:()=>{
					return [{text:'HELLO NODCLOUD',type:'text'}];
				}
			},
			source:{
				default:()=>{
					return [];
				}
			}
		},
		data() {
			return {
				text:''
			}
		},
		created() {
			this.handle();
		},
		watch: {
			config:{
				handler(){
					this.handle();
				},
				deep: true
			},
			source(data){
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
						text=configVo.text+':'+this.source.length;
					}else if(configVo.type=='sum'){
						//总和统计
						let number = this.$calc.chain(0);
						this.source.forEach((item)=>{
						   number.add(item[configVo.key]);
						});
						text=configVo.text+':'+number.done();
					}else if(configVo.type=='avg'){
						//平均值统计
						let number = this.$calc.chain(0);
						this.source.forEach((item)=>{
						   number.add(item[configVo.key]);
						});
						number=this.$calc.chain(number.done()).divide(this.source.length).round(4).done();
						text=configVo.text+':'+(isNaN(number)?0:number);
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
	.status{
		position: relative;
		width: 100%;
		font-size: 12px;
		padding: 3px 6px;
		color: rgba(0, 0, 0, 0.8);
		border: 1px solid #dcdfe6;
		border-top: none;
		box-sizing: border-box;
		line-height: 26px;
		background: #f5f7f7;
	}
</style>
