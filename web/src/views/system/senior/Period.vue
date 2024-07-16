<template>
	<div class="period area">
		<div class="layout">
			<el-date-picker v-model="date" placeholder="结账日期" value-format="yyyy-MM-dd" type="date" class="date"></el-date-picker>
			<el-button-group>
				<el-button @click="save">结账</el-button>
				<el-button @click="back">反结账</el-button>
				<el-button @click="reload">刷新</el-button>
			</el-button-group>
		</div>
		<el-divider></el-divider>
		<el-table :data="tableData" height="calc(100% - 90px)" border>
			<el-table-column prop="date" label="结账日期" align="center" width="200px"></el-table-column>
			<el-table-column prop="time" label="操作日期" align="center" width="160px"></el-table-column>
			<el-table-column prop="userData.name" label="操作人" align="center" width="160px"></el-table-column>
		</el-table>
		<el-pagination class="tablePagination" :current-page.sync="page.current" :total="page.total" :page-size.sync="page.size"
		 :page-sizes="page.sizes" :pager-count="page.count" @size-change="record(1)" @current-change="record(0)" layout="prev,pager,next,jumper,sizes,total">
		</el-pagination>
	</div>
</template>
<script>
	export default {
		name: "Period",
		data() {
			return {
				date:"",
				tableData: [],
				page: {
					current: 1,
					total: 0,
					size: 30,
					sizes: [30, 60, 90, 150, 300],
					count: 5
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
				this.date=this.$moment().format('YYYY-MM-DD');
			},
			//获取数据
			record(page) {
				page==0||(this.page.current=page);
				let parm = {
					page: this.page.current,
					limit: this.page.size
				};
				this.$axios.post("period/record", parm).then(result => {
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
			//结账
			save(){
				let date=this.date;
				if(this.$lib.validate('time',date)){
					this.$axios.post("period/save", {date:this.date}).then(result => {
						if (result.state == "success") {
							this.record(1);
							this.$message({
								type: "success",
								message: "结账成功!"
							});
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
				}else{
					this.$message({
						type: "warning",
						message: "结账日期不正确!"
					});
				}
				
			},
			//反结账
			back() {
				this.$confirm("您确定要反结账吗?", "提示", {
					confirmButtonText: "确定",
					cancelButtonText: "取消",
					type: "warning"
				}).then(() => {
					this.$axios.post("period/back").then(result => {
						if (result.state == "success") {
							this.record(1);
							this.$message({
								type: "success",
								message: "反结账成功!"
							});
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
				}).catch(()=>{});
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
		justify-content: space-between;
	}
	.date{
		width: 150px;
	}
</style>
