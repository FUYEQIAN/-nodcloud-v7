<template>
	<div class="bbt area">
		<div class="layout">
			<el-popover ref="searchPopover" popper-class="searchPopover" placement="bottom-start">
				<el-form class="searchFrom" ref="searchFrom" inline>
					<el-form-item>
						<nodList v-model="searchFrom.supplier" placeholder="请选择供应商" action="service/supplierRecord" scene="supplier"></nodList>
					</el-form-item>
					<el-form-item>
						<el-input placeholder="请输入单据编号" v-model="searchFrom.number" clearable></el-input>
					</el-form-item>
					<el-form-item>
						<el-date-picker v-model="searchFrom.startTime" placeholder="单据开始日期" value-format="yyyy-MM-dd" type="date"></el-date-picker>
					</el-form-item>
					<el-form-item>
						<el-date-picker v-model="searchFrom.endTime" placeholder="单据结束日期" value-format="yyyy-MM-dd" type="date"></el-date-picker>
					</el-form-item>
					<el-form-item>
						<el-select v-model="searchFrom.nucleus" placeholder="核销状态" multiple collapse-tags>
							<el-option label="未核销" :value="0"></el-option>
							<el-option label="部分核销" :value="1"></el-option>
							<el-option label="已核销" :value="2"></el-option> 
						</el-select>
					</el-form-item>
					<el-form-item>
						<el-select v-model="searchFrom.mold" placeholder="单据类型" multiple collapse-tags>
							<el-option label="采购单" value="buy"></el-option>
							<el-option label="采购退货单" value="bre"></el-option>
						</el-select>
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
			ref="table"
			:data="tableData"
			:tree-props="{children: 'node'}"
			row-key="key"
			height="calc(100% - 90px)"
			border
			v-madeTable
		>
			<el-table-column align="center" width="44px"></el-table-column>
			<el-table-column prop="name" label="单据类型" align="center" width="120px"></el-table-column>
			<el-table-column prop="frameData.name" label="所属组织" align="center" width="90px"></el-table-column>
			<el-table-column prop="supplierData.name" label="供应商" align="center" width="160px"></el-table-column>
			<el-table-column prop="time" label="单据时间" align="center" width="120px"></el-table-column>
			<el-table-column prop="number" label="单据编号" align="center" width="180px"></el-table-column>
			<el-table-column prop="total" label="单据金额" align="center" width="90px"></el-table-column>
			<el-table-column prop="actual" label="实际金额" align="center" width="90px"></el-table-column>
			<el-table-column prop="money" label="单据付款" align="center" width="90px"></el-table-column>
			<el-table-column prop="balance" label="应付款余额" align="center" width="90px"></el-table-column>
			<el-table-column prop="rate" label="付款率" align="center" width="90px"></el-table-column>
			<el-table-column prop="extension.nucleus" label="核销状态" align="center" width="90px"></el-table-column>
			<el-table-column prop="data" label="备注信息" align="center" width="200px"></el-table-column>
		</el-table>
		<el-pagination class="tablePagination" :current-page.sync="page.current" :total="page.total" :page-size.sync="page.size"
		 :page-sizes="page.sizes" :pager-count="page.count" @size-change="record(1)" @current-change="record(0)" layout="prev,pager,next,jumper,sizes,total,slot">
			<PageStatus :config="page.status" :model="tableData"></PageStatus>
		</el-pagination>
	</div>
</template>
<script>
	import NodList from "@/components/lib/NodList";
	import PageStatus from "@/components/lib/PageStatus";
	export default {
		name: "Bbt",
		components: {
			NodList,
			PageStatus
		},
		data() {
			return {
				searchFrom: {
					supplier: null,
					number:"",
					startTime:"",
					endTime:"",
					nucleus:[],
					mold:[]
				},
				tableData: [],
				page: {
					current: 1,
					total: 0,
					size: 30,
					sizes: [30, 60, 90, 150, 300],
					count: 5,
					status:[
						{text:'应付款总余额',type:'sum',key:'balance'}
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
				this.$axios.post("brt/bbt", parm).then(result => {
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
					window.open(this.$base.web+'brt/bbtExports?'+parm)
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
	.el-table >>> .el-table__row--level-1{
		background: #fcfcfc;
	}
</style>
