<template>
	<div class="log area">
		<div class="layout">
			<el-popover ref="searchPopover" popper-class="searchPopover" placement="bottom-start">
				<el-form class="searchFrom" ref="searchFrom"  inline>
					<el-form-item>
						<el-input placeholder="请输入操作内容" v-model="searchFrom.info" clearable></el-input>
					</el-form-item>
					<el-form-item>
						<nodList v-model="searchFrom.user" placeholder="请选择操作用户" action="service/userRecord" scene="user"></nodList>
					</el-form-item>
					<el-form-item>
						<el-date-picker v-model="searchFrom.startTime" placeholder="请输入开始日期" value-format="yyyy-MM-dd" type="date"></el-date-picker>
					</el-form-item>
					<el-form-item>
						<el-date-picker v-model="searchFrom.endTime" placeholder="请输入结束日期" value-format="yyyy-MM-dd" type="date"></el-date-picker>
					</el-form-item>
					<el-divider></el-divider>
					<el-button class="searchBtn" icon="el-icon-search" @click="record(1)" ></el-button>
				</el-form>
				<el-button slot="reference" icon="el-icon-more" ></el-button>
			</el-popover>
			<el-button-group>
				<template v-if="$lib.getUserRoot(store.root,'senior','del')">
					<el-button @click="empty" >清空</el-button>
				</template>
				<el-button @click="reload" >刷新</el-button>
			</el-button-group>
		</div>
		<el-divider></el-divider>
		<el-table :data="tableData" height="calc(100% - 90px)" border>
			<el-table-column prop="time" label="操作时间" align="center" width="200px"></el-table-column>
			<el-table-column prop="userData.name" label="操作用户" align="center" width="160px"></el-table-column>
			<el-table-column prop="info" label="操作内容 " align="center" width="360px"></el-table-column>
		</el-table>
		<el-pagination class="tablePagination" :current-page.sync="page.current" :total="page.total" :page-size.sync="page.size"
		 :page-sizes="page.sizes" :pager-count="page.count" @size-change="record(1)" @current-change="record(0)" layout="prev,pager,next,jumper,sizes,total">
		</el-pagination>
	</div>
</template>
<script>
	import NodList from "@/components/lib/NodList";
	export default {
		name: "Log",
		components: {
			NodList
		},
		data() {
			return {
				page: {
					current: 1,
					total: 0,
					size: 30,
					sizes: [30, 60, 90, 150, 300],
					count: 5
				},
				tableData: [],
				searchFrom: {
					info: "",
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
				let parm = Object.assign({
					page: this.page.current,
					limit: this.page.size
				}, this.searchFrom);
				this.$axios.post("log/record", parm).then(result => {
					if (result.state == "success") {
						this.tableData = result.info;
						this.page.total = result.count;
						this.$refs["searchPopover"].showPopper=false;
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
			//清空数据
			empty() {
				this.$confirm("您确定要清空日志吗?", "提示", {
					confirmButtonText: "确定",
					cancelButtonText: "取消",
					type: "warning"
				}).then(() => {
					this.$axios.post("log/empty").then(result => {
						if (result.state == "success") {
							this.record(1);
							this.$message({
								type: "success",
								message: "清空日志成功!"
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
</style>
