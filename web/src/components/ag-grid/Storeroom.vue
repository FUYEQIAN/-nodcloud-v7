<template>
	<div class="storeroom">
		<div class="title">
			<span>{{title}} </span>
			<el-popover popper-class="agGridSelect" ref="popover">
				<i class="el-icon-sort" slot="reference"></i>
				<ul class="list" ref="list">
					<template v-for="(warehouse,index) in list">
						<li @click="choice(warehouse)">{{warehouse.name}}</li>
					</template>
				</ul>
			</el-popover>
		</div>
	</div>
</template>
<script>
	//头组件|批量仓库
	import Vue from 'vue';
    export default Vue.extend({
        name: 'Storeroom',
        data() {
            return {
				title:'',
				list:[]
            }
        },
        created(){
			this.title=this.params.dispose.title;
			this.list=this.store.warehouse;
        },
		computed: {
			//读取数据中心
			store() {
				return this.$store.state;
			}
		},
        methods:{
			choice(item){
				let dispose=this.params.dispose;
				this.params.api.forEachNode((node) => {
					if(node.data.key!=null && node.data.goodsType==0){
						node.data[dispose.key]=item.id;
						this.$grid.updateGridData(this.params,node.data.uniqid,dispose.text,item.name);
					}
				});
				this.params.context.runHandleGrid();
				this.$refs.popover.showPopper=false;
				this.$message({
					type: "success",
					message: "批量设置完成!"
				});
			}
        }
    })
</script>
<style scoped>
	.storeroom{
		width: 100%;
	}
	.title{
		text-align: center;
	}
</style>