<template>
	<div class="btt area">
		<div class="layout">
			<el-popover ref="searchPopover" popper-class="searchPopover" placement="bottom-start">
				<el-form class="searchFrom" ref="searchFrom" inline>
					<el-form-item>
						<nodList v-model="searchFrom.supplier" placeholder="请选择供应商" action="service/supplierRecord" scene="supplier"></nodList>
					</el-form-item>
					<el-form-item>
						<el-input placeholder="请输入商品名称" v-model="searchFrom.goods" clearable></el-input>
					</el-form-item>
					<el-form-item>
						<el-input placeholder="请输入订单编号" v-model="searchFrom.number" clearable></el-input>
					</el-form-item>
					<el-form-item>
						<el-select v-model="searchFrom.state" placeholder="入库状态" multiple collapse-tags>
							<el-option label="未入库" :value="0"></el-option>
							<el-option label="部分入库" :value="1"></el-option>
							<el-option label="已入库" :value="2"></el-option> 
						</el-select>
					</el-form-item>
					<el-form-item>
						<el-date-picker v-model="searchFrom.startTime" placeholder="单据开始日期" value-format="yyyy-MM-dd" type="date"></el-date-picker>
					</el-form-item>
					<el-form-item>
						<el-date-picker v-model="searchFrom.endTime" placeholder="单据结束日期" value-format="yyyy-MM-dd" type="date"></el-date-picker>
					</el-form-item>
					<el-form-item>
						<el-date-picker v-model="searchFrom.startArrival" placeholder="到货开始日期" value-format="yyyy-MM-dd" type="date"></el-date-picker>
					</el-form-item>
					<el-form-item>
						<el-date-picker v-model="searchFrom.endArrival" placeholder="到货结束日期" value-format="yyyy-MM-dd" type="date"></el-date-picker>
					</el-form-item>
					<el-form-item>
						<el-select v-model="searchFrom.warehouse" placeholder="请选择仓库信息" multiple collapse-tags>
							<template v-for="warehouse in store.warehouse">
								<el-option :label="warehouse.name" :value="warehouse.id"></el-option>
							</template>
						</el-select>
					</el-form-item>
					<el-form-item>
						<el-select v-model="searchFrom.type" placeholder="排序类型">
							<el-option label="单据排序" :value="0"></el-option>
							<el-option label="商品排序" :value="1"></el-option>
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
			<template v-if="tableType==0">
				<el-table-column prop="frameData.name" label="所属组织" align="center" width="90px"></el-table-column>
				<el-table-column prop="supplierData.name" label="供应商" align="center" width="160px"></el-table-column>
				<el-table-column prop="time" label="单据时间" align="center" width="120px"></el-table-column>
				<el-table-column prop="number" label="单据编号" align="center" width="180px"></el-table-column>
				<el-table-column prop="goodsData.name" label="商品名称" align="center" width="160px"></el-table-column>
				<el-table-column prop="attr" label="辅助属性" align="center" width="120px"></el-table-column>
				<el-table-column prop="warehouseData.name" label="仓库" align="center" width="120px"></el-table-column>
			</template>
			<template v-if="tableType==1">
				<el-table-column prop="goodsData.name" label="商品名称" align="center" width="160px"></el-table-column>
				<el-table-column prop="attr" label="辅助属性" align="center" width="120px"></el-table-column>
				<el-table-column prop="warehouseData.name" label="仓库" align="center" width="120px"></el-table-column>
				<el-table-column prop="frameData.name" label="所属组织" align="center" width="90px"></el-table-column>
				<el-table-column prop="supplierData.name" label="供应商" align="center" width="160px"></el-table-column>
				<el-table-column prop="time" label="单据时间" align="center" width="120px"></el-table-column>
				<el-table-column prop="number" label="单据编号" align="center" width="180px"></el-table-column>
			</template>
			<el-table-column prop="unit" label="单位" align="center" width="90px"></el-table-column>
			<el-table-column prop="price" label="单价" align="center" width="90px"></el-table-column>
			<el-table-column prop="nums" label="数量" align="center" width="90px"></el-table-column>
			<el-table-column prop="money" label="金额" align="center" width="90px"></el-table-column>
			<el-table-column prop="extension.state" label="入库状态" align="center" width="90px"></el-table-column>
			<el-table-column prop="nns" label="未入库数量" align="center" width="90px"></el-table-column>
			<el-table-column prop="nmy" label="未入库金额" align="center" width="90px"></el-table-column>
			<el-table-column prop="arrival" label="到货日期" align="center" width="120px"></el-table-column>
			<el-table-column prop="data" label="备注信息" align="center" width="200px"></el-table-column>
		</el-table>
		<el-pagination class="tablePagination" :current-page.sync="page.current" :total="page.total" :page-size.sync="page.size"
		 :page-sizes="page.sizes" :pager-count="page.count" @size-change="record(1)" @current-change="record(0)" layout="prev,pager,next,jumper,sizes,total">
		</el-pagination>
	</div>
</template>
<script>
	import NodList from "@/components/lib/NodList";
	export default {
		name: "Btt",
		components: {
			NodList
		},
		data() {
			return {
				searchFrom: {
					supplier: null,
					goods:"",
					number:"",
					state:[],
					startTime:"",
					endTime:"",
					startArrival:"",
					endArrival:"",
					warehouse:[],
					type:0
				},
				tableData: [],
				tableType:0,
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
				this.$axios.post("brt/btt", parm).then(result => {
					if (result.state == "success") {
						this.tableData = result.info;
						this.page.total = result.count;
						this.$refs["searchPopover"].showPopper = false;
						//ELEMENT更新错位.暂延时处理
						this.tableType=-1;
						this.$nextTick(()=>{
							this.tableType=this.searchFrom.type==0?0:1;
							setTimeout(()=>{this.$refs.table.doLayout()},0);
						})
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
					window.open(this.$base.web+'brt/bttExports?'+parm)
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
