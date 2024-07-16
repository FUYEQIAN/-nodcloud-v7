<template>
	<div class="cds area">
		<div class="layout">
			<el-popover ref="searchPopover" popper-class="searchPopover" placement="bottom-start">
				<el-form class="searchFrom" ref="searchFrom" inline>
					<el-form-item>
						<el-input placeholder="请输入单位名称" v-model="searchFrom.name" clearable></el-input>
					</el-form-item>
					<el-form-item>
						<el-input placeholder="请输入单位编号" v-model="searchFrom.number" clearable></el-input>
					</el-form-item>
					<el-form-item>
						<el-select v-model="searchFrom.mold" placeholder="单位类型" multiple collapse-tags>
							<el-option label="客户" value="customer"></el-option>
							<el-option label="供应商" value="supplier"></el-option> 
						</el-select>
					</el-form-item>
					<el-form-item>
						<el-input placeholder="请输入备注信息" v-model="searchFrom.data" clearable></el-input>
					</el-form-item>
					<el-divider></el-divider>
					<el-button class="searchBtn" icon="el-icon-search" @click="record(1)" ></el-button>
				</el-form>
				<el-button slot="reference" icon="el-icon-more"></el-button>
			</el-popover>
			<el-button-group>
				<el-button @click="exports">导出</el-button>
				<el-button @click="reload">刷新</el-button>
			</el-button-group>
		</div>
		<el-divider></el-divider>
		<el-table
			:data="tableData"
			height="calc(100% - 90px)"
			border
			v-madeTable
		>
			<el-table-column prop="mold" label="单位类型" align="center" width="120px"></el-table-column>
			<el-table-column prop="name" label="单位名称" align="center" width="160px"></el-table-column>
			<el-table-column prop="number" label="单位编号" align="center" width="160px"></el-table-column>
			<el-table-column prop="collection" label="应收款余额" align="center" width="120px"></el-table-column>
			<el-table-column prop="payment" label="应付款余额" align="center" width="120px"></el-table-column>
			<el-table-column prop="data" label="备注信息" align="center" width="160px"></el-table-column>
		</el-table>
		<el-pagination class="tablePagination" :current-page.sync="page.current" :total="page.total" :page-size.sync="page.size"
		 :page-sizes="page.sizes" :pager-count="page.count" @size-change="record(1)" @current-change="record(0)" layout="prev,pager,next,jumper,sizes,total,slot">
			<PageStatus :config="page.status" :model="tableData"></PageStatus>
		</el-pagination>
	</div>
</template>
<script>
	import PageStatus from "@/components/lib/PageStatus";
	export default {
		name: "Cds",
		components: {
			PageStatus
		},
		data() {
			return {
				searchFrom: {
					name : "",
					number : "",
					mold : [],
					data: ""
				},
				tableData: [],
				page: {
					current: 1,
					total: 0,
					size: 30,
					sizes: [30, 60, 90, 150, 300],
					count: 5,
					status:[
						{text:'总应收款余额',type:'sum',key:'collection'},
						{text:'总应付款余额',type:'sum',key:'payment'}
					]
				}
			};
		},
		created() {
			this.record(1); //获取数据
		},
		computed: {
			//读取数据中心
			store() {
				return this.$store.state;
			}
		},
		methods: {
			//获取数据
			record(page) {
				page==0||(this.page.current=page);
				let parm = Object.assign({
					page: this.page.current,
					limit: this.page.size
				}, this.searchFrom);
				this.$axios.post("crt/cds", parm).then(result => {
					if (result.state == "success") {
						this.tableData = result.info;
						this.page.total = result.count;
						this.$refs["searchPopover"].showPopper = false;
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
			//导出数据
			exports(){
				this.$message({
					type: "success",
					message: "[ 导出数据 ] 请求中..."
				});
				let parm=this.$lib.objToParm(this.searchFrom,true);
				setTimeout(() => {
					window.open(this.$base.web+'crt/cdsExports?'+parm)
				}, 1000);
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
