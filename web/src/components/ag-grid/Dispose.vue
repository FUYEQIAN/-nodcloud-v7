<template>
    <div class="dispose">
		<i class="ico el-icon-s-tools" @click="dialog=true"></i>
		<el-dialog :visible.sync="dialog" class="disposeTable" title="字段配置" width="390px" :append-to-body="true" v-madeDialog>
			<transition name="el-fade-in">
				<template v-if="dialog">
					<el-table :data="list" row-key="key" size="mini" border>
					    <el-table-column prop="name" label="名称" align="center"></el-table-column>
						<el-table-column label="显示" align="center" width="120px">
							<template slot-scope="scope">
								<el-switch v-model="scope.row.show" @change="(val)=>{hideChange(scope.row.key,val)}"></el-switch>
							</template>
						</el-table-column>
					</el-table>
				</template>
			</transition>
		</el-dialog>
	</div>
</template>
<script>
	//头组件|表格配置
	import Vue from 'vue';
	import sortable from 'sortablejs';
    export default Vue.extend({
        name: 'Dispose',
		data() {
		    return {
				dialog:false,
		        list: []
		    }
		},
		methods:{
			//初始化
			init(){
				//获取字段配置
				this.params.columnApi.columnController.columnDefs.forEach(item=>{
					//dispose[true:显示|false:显示]
					if(item.dispose===true){
						this.list.push({name:item.headerName,key:item.field,show:!item.hide});
					}
				})
				//获取用户配置
				let storage = localStorage.getItem(this.params.dispose.key);
				if(storage){
					storage=JSON.parse(storage);
					let distinct=this.$lib.distinct(this.$lib.arrayColumn(this.list,'key').concat(this.$lib.arrayColumn(storage,'key')));
					if(distinct.length==this.list.length){
						this.list=storage;
					}else{
						localStorage.removeItem(this.params.dispose.key);
						this.$message({
							type: "success",
							message: "数据表格配置改变,恢复默认配置!"
						});
					}
				}
				//处理表格配置
				this.list.forEach(item=>{
					this.params.columnApi.setColumnVisible(item.key,item.show);
				});
			},
			//写入配置
			writeStorage(){
				localStorage.setItem(this.params.dispose.key,JSON.stringify(this.list));
			},
			//显示切换
			hideChange(key,val){
				this.params.columnApi.setColumnVisible(key,val)
				this.writeStorage();
			}
		},
		mounted(){
			this.$nextTick(()=>{
				this.init();
			})
		},
    })
</script>
<style scoped>
	.dispose{
		width: 100%;
	}
	.ico{
		display: block;
		text-align: center;
	}
</style>