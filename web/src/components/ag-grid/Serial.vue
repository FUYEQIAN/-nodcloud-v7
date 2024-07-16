<template>
	<div class="inputGroup">
		<input class="ag-cell-edit-input" type="text" v-model="value" ref="input" :disabled="hasSerial">
		<span class="sn" v-if="hasSerial" @click="showDialog">SN</span>
		<Snu v-if="dialog" :config="config" @save="saveSnu" @destroy="dialog=false"></Snu>
	</div>
</template>
<script>
	//内容组件|常规数量AND序列号
	import Vue from 'vue';
	import Snu from "@/components/dialog/Snu";
    export default Vue.extend({
        name: 'Serial',
		components: {Snu},
        data() {
            return {
                value: '',
				dialog:false,
				config:{}
            }
        },
        created(){
			this.value = this.params.value;
        },
		computed: {
			hasSerial(){
				let data=this.params.node.data;
				if(data.hasOwnProperty('serialType') && data.serialType){
					return true;
				}else{
					return false;
				}
			},
			hasRecord(){
				return this.params.dispose.record.show;
			}
		},
        methods:{
			//显示弹层
			showDialog(){
				let data=this.params.node.data;
				if(data.warehouseId==null){
					this.$message({
						type: "warning",
						message: "请先选择仓库信息"
					});
				}else{
					if(data.batchType && data.batch==''){
						this.$message({
							type: "warning",
							message: "请先选择批次信息"
						});
					}else if(data.validityType && data.mfd==''){
						this.$message({
							type: "warning",
							message: "请先选择生产日期"
						});
					}else{
						this.config={
							record:this.params.dispose.record,
							source:data.serial,
							condition:{
								warehouse:data.warehouseId,
								goods:data.key,
								attr:data.attr,
								batch:data.batch,
								mfd:data.mfd
							}
						}
						this.dialog=true;
					}
				}
			},
			//保存数据
			saveSnu(serial){
				let data=this.params.node.data;
				if(data.unitData.length>0){
					this.value=this.$calc.chain(serial.length).divide(data.unitRelation.multiple).done();
				}else{
					this.value=serial.length;
				}
				data.serial=serial;
				this.params.api.stopEditing();
			},
			//返回内容
            getValue(){
				return this.value;
            }
        },
		mounted() {
			if(!this.hasSerial){
				this.$nextTick(()=>{
					this.$refs.input.focus();
					this.$refs.input.select();
				});
			}
			
		}
    })
</script>

<style scoped>
	.inputGroup{
		position: relative;
		width: 100%;
		height: 100%;
	}
	.sn{
		position: absolute;
		right: 6px;
		top: 6px;
		width: 22px;
		height: 16px;
		color: #FFFFFF;
		font-size: 12px;
		line-height: 16px;
		border-radius: 2px;
		background: #909399;
	}
	input[disabled]{
		background: #FFFFFF;
	}
</style>