<template>
	<div class="backup area">
		<div class="layout">
			<el-button-group>
				<template v-if="$lib.getUserRoot(store.root,'senior','del')">
					<el-button v-if="tableSelection.length!=0" @click="del(tableSelection)"  type="info">删除</el-button>
				</template>
				<el-button @click="backup" >备份</el-button>
				<el-button @click="reload" >刷新</el-button>
			</el-button-group>
		</div>
		<el-divider></el-divider>
		<el-table :data="tableData" height="calc(100% - 90px)" @selection-change="selectionChange" border v-madeTable>
			<el-table-column type="selection" align="center" fixed="left" width="39px"></el-table-column>
			<el-table-column prop="time" label="备份时间" align="center" width="200px"></el-table-column>
			<el-table-column prop="name" label="文件名称" align="center" width="260px"></el-table-column>
			<el-table-column prop="size" label="文件大小" align="center" width="160px"></el-table-column>
			<el-table-column prop="set" label="相关操作" align="center"  width="160px">
				<template slot-scope="scope">
					<el-button-group>
						<template v-if="$lib.getUserRoot(store.root,'senior','edit')">
							<el-button @click="restore(scope.row.name)" size="mini">恢复</el-button>
						</template>
						<template v-if="$lib.getUserRoot(store.root,'senior','del')">
							<el-button @click="del([scope.row.name])" size="mini">删除</el-button>
						</template>
					</el-button-group>
				</template>
			</el-table-column>
		</el-table>
		<el-pagination
			class="tablePagination"
			:current-page.sync="page.current"
			:total="page.total"
			:page-size.sync="page.size"
			:page-sizes="page.sizes"
			:pager-count="page.count"
			@size-change="record(1)"
			@current-change="record(0)"
			layout="prev,pager,next,jumper,sizes,total"
		>
		</el-pagination>
	</div>
</template>
<script>
	export default {
		name: "Backup",
		data() {
			return {
				page:{current:1,total:0,size:30,sizes:[30,60,90,150,300],count:5},
				tableData: [], 
				tableSelection:[],
				searchFrom: {
					info:"",
					user: "",
					startTime: "",
					endTime: ""
				}
			};
		},
		created() {
			this.init();
			this.record(1); //获取数据
		},
		computed: {
			//读取数据中心
			store() {
				return this.$store.state;
			},
		},
		methods: {
			//初始化
			init(){
				this.searchFrom.startTime=this.$moment().subtract(this.store.sys.fun.days,'days').format('YYYY-MM-DD');
				this.searchFrom.endTime=this.$moment().format('YYYY-MM-DD');
			},
			//获取数据
			record(page) {
				page==0||(this.page.current=page);
				this.$axios.post("backup/record",{
					page:this.page.current,
					limit:this.page.size,
				}).then(result => {
					if (result.state == "success") {
						this.tableData = result.info;
						this.page.total = result.count;
					} else if (result.state == "error") {
						this.$message({type: "warning",message: result.info});
					} else {
						this.$message({type: "error",message: "[ ERROR ] 服务器响应超时!"});
					}
				});
			},
			//备份数据
			backup(){
				this.$axios.post("backup/backup").then(result => {
					if (result.state == "success") {
						this.record(0);
						this.$message({type: "success",message: "备份数据成功!"});
					} else if (result.state == "error") {
						this.$message({type: "warning",message: result.info});
					} else {
						this.$message({type: "error",message: "[ ERROR ] 服务器响应超时!"});
					}
				});
			},
			//恢复数据
			restore(name) {
				this.$confirm("您确定要恢复该备份数据吗?", "提示", {
					confirmButtonText: "确定",
					cancelButtonText: "取消",
					type: "warning"
				}).then(() => {
					this.$axios.post("backup/restore",{name:name}).then(result => {
						if (result.state == "success") {
							this.record(0);
							this.$message({type: "success",message: "恢复备份数据成功!"});
						} else if (result.state == "error") {
							this.$message({type: "warning",message: result.info});
						} else {
							this.$message({type: "error",message: "[ ERROR ] 服务器响应超时!"});
						}
					});
				}).catch(()=>{});
			},
			//删除数据
			del(parm) {
				this.$confirm("您确定要删除选中数据吗?", "提示", {
					confirmButtonText: "确定",
					cancelButtonText: "取消",
					type: "warning"
				}).then(() => {
					this.$axios.post("backup/del",{parm:parm}).then(result => {
						if (result.state == "success") {
							this.record(0);
							this.$message({type: "success",message: "删除成功!"});
						} else if (result.state == "error") {
							this.$message({type: "warning",message: result.info});
						} else {
							this.$message({type: "error",message: "[ ERROR ] 服务器响应超时!"});
						}
					});
				}).catch(()=>{});
			},
			//表格选中数据改变
			selectionChange(parm){
				let data=[];
				for (let parmVo of parm) {
					data.push(parmVo.name);
				}
				this.tableSelection=data;
			},
			//页面刷新
			reload() {
				this.$bus.emit('homeReload',this.$options.name);
				this.$message({
					type: "success",
					message: "页面刷新成功!"
				});
			}
		}
	};
</script>
<style scoped>
	.layout {
		display: flex;
		justify-content: flex-end;
	}
</style>
