<template>
	<div class="bsy area">
		<div class="layout">
			<el-popover ref="searchPopover" popper-class="searchPopover" placement="bottom-start">
				<el-form class="searchFrom" ref="searchFrom" inline>
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
						<nodList ref="supplier" v-model="searchFrom.supplier" placeholder="请选择供应商" action="service/supplierRecord" scene="supplier" :disabled='searchFrom.type!=1'></nodList>
					</el-form-item>
					<el-form-item>
						<nodList ref="user" v-model="searchFrom.user" placeholder="请选择用户" action="service/userRecord" scene="user" :disabled='searchFrom.type!=2'></nodList>
					</el-form-item>
					<el-form-item>
						<el-date-picker v-model="searchFrom.startTime" placeholder="单据开始日期" value-format="yyyy-MM-dd" type="date"></el-date-picker>
					</el-form-item>
					<el-form-item>
						<el-date-picker v-model="searchFrom.endTime" placeholder="单据结束日期" value-format="yyyy-MM-dd" type="date"></el-date-picker>
					</el-form-item>
					<el-form-item>
						<nodList ref="people" v-model="searchFrom.people" placeholder="请选择关联人员" action="service/peopleRecord" scene="people" :disabled='searchFrom.type!=3'></nodList>
					</el-form-item>
					<el-form-item>
						<el-select v-model="searchFrom.type" placeholder="查询类型" @change="typeChange">
							<el-option label="按商品" :value="0"></el-option>
							<el-option label="按供应商" :value="1"></el-option>
							<el-option label="按用户" :value="2"></el-option>
							<el-option label="按人员" :value="3"></el-option>
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
			height="calc(100% - 90px)"
			border
			v-madeTable
		>
			<template v-if="tableType==1">
				<el-table-column prop="supplier.name" label="供应商" align="center" width="160px"></el-table-column>
			</template>
			<template v-if="tableType==2">
				<el-table-column prop="user.name" label="用户" align="center" width="160px"></el-table-column>
			</template>
			<template v-if="tableType==3">
				<el-table-column prop="people.name" label="关联人员" align="center" width="160px"></el-table-column>
			</template>
			<el-table-column prop="goodsData.name" label="商品名称" align="center" width="160px"></el-table-column>
			<el-table-column prop="attr" label="辅助属性" align="center" width="120px"></el-table-column>
			<el-table-column prop="warehouseData.name" label="仓库" align="center" width="120px"></el-table-column>
			<el-table-column prop="unit" label="单位" align="center" width="90px"></el-table-column>
			<el-table-column label="采购单" align="center">
				<el-table-column prop="buy.price" label="单价" align="center" width="90px"></el-table-column>
				<el-table-column prop="buy.nums" label="数量" align="center" width="90px"></el-table-column>
				<el-table-column prop="buy.money" label="金额" align="center" width="90px"></el-table-column>
			</el-table-column>
			<el-table-column label="采购退货单" align="center">
				<el-table-column prop="bre.price" label="单价" align="center" width="90px"></el-table-column>
				<el-table-column prop="bre.nums" label="数量" align="center" width="90px"></el-table-column>
				<el-table-column prop="bre.money" label="金额" align="center" width="90px"></el-table-column>
			</el-table-column>
			<el-table-column label="汇总" align="center">
				<el-table-column prop="summary.nums" label="数量" align="center" width="90px"></el-table-column>
				<el-table-column prop="summary.money" label="金额" align="center" width="90px"></el-table-column>
			</el-table-column>
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
		name: "Bsy",
		components: {
			NodList,
			PageStatus
		},
		data() {
			return {
				searchFrom: {
					goods:"",
					warehouse:[],
					supplier: null,
					user: null,
					startTime:"",
					endTime:"",
					people:null,
					type:0
				},
				tableData: [],
				tableType:0,
				page: {
					current: 1,
					total: 0,
					size: 30,
					sizes: [30, 60, 90, 150, 300],
					count: 5,
					status:[
						{text:'采购总金额',type:'sum',key:'buy.money'},
						{text:'采购退货总金额',type:'sum',key:'bre.money'},
						{text:'汇总金额',type:'sum',key:'summary.money'},
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
				//构造数据
				page==0||(this.page.current=page);
				let parm = Object.assign({
					page: this.page.current,
					limit: this.page.size
				}, this.searchFrom);
				this.$axios.post("brt/bsy", parm).then(result => {
					if (result.state == "success") {
						this.tableData = result.info;
						this.page.total = result.count;
						this.$refs["searchPopover"].showPopper = false;
						//ELEMENT更新错位.暂延时处理
						this.tableType=-1;
						this.$nextTick(()=>{
							this.tableType=[0,1,2,3][this.searchFrom.type];
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
			//场景匹配
			typeChange(){
				if(this.searchFrom.type==0){
					this.$refs['supplier'].init();
					this.$refs['user'].init();
					this.$refs['people'].init();
				}else if(this.searchFrom.type==1){
					this.$refs['user'].init();
					this.$refs['people'].init();
				}else if(this.searchFrom.type==2){
					this.$refs['supplier'].init();
					this.$refs['people'].init();
				}else{
					this.$refs['supplier'].init();
					this.$refs['user'].init();
				}
			},
			//导出数据
			exports(){
				this.$message({
					type: "success",
					message: "[ 导出数据 ] 请求中..."
				});
				let parm=this.$lib.objToParm(this.searchFrom,true);
				setTimeout(() => {
					window.open(this.$base.web+'brt/bsyExports?'+parm)
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
