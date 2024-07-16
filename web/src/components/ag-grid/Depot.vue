<template>
    <div class="depot">
		<i v-if="hasData" class="search el-icon-search" @click.stop="dialog=true"></i>
		<span class="agGridPopup">{{value}}</span>
		<Stock v-if="dialog" :condition="condition" @rowClick="rowClick" @destroy="dialog=false"></Stock>
	</div>
</template>
<script>
	//内容组件|仓库组件
	import Vue from 'vue';
	import Stock from "@/components/dialog/Stock";
    export default Vue.extend({
        name: 'Depot',
		components: {Stock},
		data() {
		    return {
		        value: '',
				dialog:false,
				condition:{}
		    }
		},
		created(){
			if(this.hasData){
				this.value = this.params.value;
				let data=this.params.node.data;
				this.condition = {
					goods:data.key,
					attr:data.attr
				};
			}
		},
		computed: {
			hasData(){
				let data=this.params.node.data;
				if(data.key==null){
					return false;
				}else{
					if(data.goodsType==0){
						return true
					}else{
						return false;
					}
				}
			}
		},
		methods:{
			rowClick(row){
				let data=this.params.node.data;
				let dispose=this.params.dispose;
				let compId=this.$el.parentNode.getAttribute('comp-id');
				data[dispose.key]=row.warehouse;
				this.$grid.updateGridData(this.params,data.uniqid,dispose.text,row.name);
				document.querySelector(".ag-cell[comp-id='"+compId+"']").focus();
			}
		}
    })
</script>
<style scoped>
	.depot{
		position: relative;
		height: 100%;
	}
	.search{
		position: absolute;
		left: 0;
		top: 6px;
		width: 16px;
		height: 16px;
		line-height: 16px;
	}
</style>