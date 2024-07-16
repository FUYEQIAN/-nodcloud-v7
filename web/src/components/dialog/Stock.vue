<template>
	<div class="stock">
		<el-dialog :visible.sync="dialog" title="库存详情" width="320px" @closed="close" :append-to-body="true" v-madeDialog>
			<el-table :data="tableData" @row-click="rowClick" height="220px" size="mini" border>
				<el-table-column prop="name" label="仓库名称" align="center" min-width="160px"></el-table-column>
				<el-table-column prop="nums" label="库存数量" align="center" min-width="120px"></el-table-column>
			</el-table>
			<el-pagination class="tablePagination" :current-page.sync="page.current" :total="page.total" :page-size.sync="page.size" :page-sizes="page.sizes" :pager-count="page.count" @current-change="record" layout="prev,pager,next"></el-pagination>
		</el-dialog>
	</div>
</template>
<script>
	export default {
		name: "Stock",
		props: {
			condition:{
				required: true,
				type: Object
			}
		},
		data() {
			return {
				dialog:true,
				tableData: [],
				page: {
					current: 1,
					total: 0,
					size: 5,
					sizes: [5, 10, 15, 20, 30],
					count: 5
				}
			}
		},
		created() {
			this.record();
		},
		methods: {
			record(){
				let parm = Object.assign({
					page: this.page.current,
					limit: this.page.size
				},this.condition);
				this.$axios.post("service/goodsDepot", parm).then(result => {
					if (result.state == "success") {
						this.tableData = result.info;
						this.page.total = result.count;
					} else if (result.state == "error") {
						this.$message({
							type: "warning",
							message: result.info
						});
					} else {
						this.$message({
							type: "error",
							message: "[ ERROR ] 服务器响应超时!"
						});
					}
				});
			},
			rowClick(row){
				this.dialog=false;
				this.$emit('rowClick',row);
			},
			//弹层关闭事件
			close(){
				this.$emit('destroy',true);
			}
		},
	}
</script>
