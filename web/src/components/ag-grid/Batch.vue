<template>
	<div class="inputGroup">
		<input class="batchInput ag-cell-edit-input" type="text" v-model="value" ref="input">
		<span class="bp" @click="showDialog">BP</span>
		<Lot v-if="dialog" :condition="condition" @rowClick="rowClick" @destroy="dialog=false"></Lot>
	</div>
</template>
<script>
	//内容组件|批次录入
	import Vue from 'vue';
	import Lot from "@/components/dialog/Lot";
    export default Vue.extend({
        name: 'Batch',
		components: {Lot},
        data() {
            return {
                value: '',
				dialog:false,
				condition:{}
            }
        },
        created(){
            this.value = this.params.value;
        },
		computed: {
			hasBatch(){
				let data=this.params.node.data;
				if(data.hasOwnProperty('batchType') && data.batchType){
					return true;
				}else{
					return false;
				}
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
					this.condition={
						goods:data.key,
						attr:data.attr,
						warehouse:data.warehouseId
					}
					this.dialog=true;
				}
			},
			//选中数据
			rowClick(row){
				this.value=row.number;
				if(row.time!=null){
					this.params.node.data.mfd=row.time;
				}
				this.$el.parentNode.focus();
				this.params.api.stopEditing();
			},
			//返回内容
            getValue(){
				return this.value;
            },
			//编辑状态
			isCancelBeforeStart() {
				return !this.hasBatch;
			},
        },
		mounted() {
			if(this.hasBatch){
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
	.bp{
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
	.batchInput{
		padding-right: 32px !important;
		box-sizing: border-box;
	}
</style>
