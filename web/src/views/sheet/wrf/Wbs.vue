<template>
	<div class="wbs area">
		<div class="layout">
			<el-popover ref="searchPopover" popper-class="searchPopover" placement="bottom-start">
				<el-form class="searchFrom" ref="searchFrom"  inline>
					<el-form-item>
						<el-input placeholder="请输入商品名称" v-model="searchFrom.goods" clearable></el-input>
					</el-form-item>
					<el-form-item>
						<el-select v-model="searchFrom.warehouse" placeholder="请选择仓库信息" multiple collapse-tags>
							<template v-for="warehouse in store.warehouse">
								<el-option :label="warehouse.name" :value="warehouse.id"></el-option>
							</template>
						</el-select>
					</el-form-item>
					<el-form-item>
						<el-date-picker v-model="searchFrom.time" placeholder="库存日期" value-format="yyyy-MM-dd" type="date"></el-date-picker>
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
			<el-table-column prop="goodsData.name" label="商品名称" align="center" width="160px" fixed="left"></el-table-column>
			<el-table-column prop="goodsData.number" label="商品编号" align="center" width="120px"></el-table-column>
			<el-table-column prop="goodsData.spce" label="规格型号" align="center" width="90px"></el-table-column>
			<el-table-column prop="unit" label="单位" align="center" width="90px"></el-table-column>
			<template v-for="column in tableColumn">
				<el-table-column :label="column.name" align="center">
					<el-table-column :prop="'wb_'+column.id+'.uct'" label="成本" align="center" width="90px"></el-table-column>
					<el-table-column :prop="'wb_'+column.id+'.uns'" label="数量" align="center" width="90px"></el-table-column>
					<el-table-column :prop="'wb_'+column.id+'.bct'" label="总成本" align="center" width="90px"></el-table-column>
				</el-table-column>
			</template>
			<el-table-column label="汇总" align="center">
				<el-table-column prop="balance.uct" label="成本" align="center" width="90px"></el-table-column>
				<el-table-column prop="balance.uns" label="数量" align="center" width="90px"></el-table-column>
				<el-table-column prop="balance.bct" label="总成本" align="center" width="90px"></el-table-column>
			</el-table-column>
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
		name: "Wbs",
		components: {
			PageStatus
		},
		data() {
			return {
				searchFrom: {
					goods: "",
					warehouse:[],
					time:""
				},
				tableData: [],
				tableColumn:[],
				page: {
					current: 1,
					total: 0,
					size: 30,
					sizes: [30, 60, 90, 150, 300],
					count: 5,
					status:[
						{text:'总成本',type:'sum',key:'balance.bct'}
					]
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
			}
		},
		methods: {
			//初始化
			init(){
				this.searchFrom.time=this.$moment().format('YYYY-MM-DD');
			},
			//获取数据
			record(page) {
				page==0||(this.page.current=page);
				let parm = Object.assign({
					page: this.page.current,
					limit: this.page.size
				}, this.searchFrom);
				this.$axios.post("wrf/wbs", parm).then(result => {
					if (result.state == "success") {
						this.tableData = result.info;
						this.tableColumn=result.column;
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
					window.open(this.$base.web+'wrf/wbsExports?'+parm)
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
