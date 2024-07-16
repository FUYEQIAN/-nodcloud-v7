<template>
	<div class="cost area">
		<div class="layout">
			<el-popover ref="searchPopover" popper-class="searchPopover" placement="bottom-start">
				<el-form class="searchFrom" ref="searchFrom" inline>
					<el-form-item>
						<nodList v-model="searchFrom.supplier" placeholder="请选择供应商" action="service/supplierRecord" scene="supplier"></nodList>
					</el-form-item>
					<el-form-item>
						<nodList v-model="searchFrom.customer" placeholder="请选择客户" action="service/customerRecord" scene="customer"></nodList>
					</el-form-item>
					<el-form-item>
						<el-input placeholder="请输入单据编号" v-model="searchFrom.number" clearable></el-input>
					</el-form-item>
					<el-form-item>
						<el-select v-model="searchFrom.iet" placeholder="支出类别" multiple collapse-tags>
							<template v-for="item in store.iet.out">
								<el-option :label="item.name" :value="item.id"></el-option>
							</template>
						</el-select>
					</el-form-item>
					<el-form-item>
						<el-date-picker v-model="searchFrom.startTime" placeholder="单据开始日期" value-format="yyyy-MM-dd" type="date"></el-date-picker>
					</el-form-item>
					<el-form-item>
						<el-date-picker v-model="searchFrom.endTime" placeholder="单据结束日期" value-format="yyyy-MM-dd" type="date"></el-date-picker>
					</el-form-item>
					<el-form-item>
						<el-select v-model="searchFrom.state" placeholder="结算状态" multiple collapse-tags>
							<el-option label="未结算" :value="0"></el-option>
							<el-option label="部分结算" :value="1"></el-option> 
							<el-option label="已结算" :value="2"></el-option>
						</el-select>
					</el-form-item>
					<el-form-item>
						<el-select v-model="searchFrom.mold" placeholder="单据类型" multiple collapse-tags>
							<el-option label="采购单" value="buy"></el-option>
							<el-option label="采购退货单" value="bre"></el-option> 
							<el-option label="销售单" value="sell"></el-option> 
							<el-option label="销售退货单" value="sre"></el-option>
							<el-option label="调拨单" value="swap"></el-option>
							<el-option label="其它入库单" value="entry"></el-option>
							<el-option label="其它出库单" value="extry"></el-option>
						</el-select>
					</el-form-item>
					<el-divider></el-divider>
					<el-button class="searchBtn" icon="el-icon-search" @click="record(1)"></el-button>
				</el-form>
				<el-button slot="reference" icon="el-icon-more"></el-button>
			</el-popover>
			<el-button-group>
				<template v-if="$lib.getUserRoot(store.root,'cost','edit')">
					<el-button @click="handle">结算</el-button>
				</template>
				<el-button @click="exports">导出</el-button>
				<el-button @click="reload">刷新</el-button>
			</el-button-group>
		</div>
		<el-divider></el-divider>
		<el-table
			ref="dataTable"
			class="gridTable"
			:data="tableData"
			height="calc(100% - 90px)"
			@selection-change="selectionChange"
			border
			v-madeTable
		>
			<el-table-column type="selection" align="center" width="39px"></el-table-column>
			<el-table-column prop="extension.type" label="单据类型" align="center" width="90px"></el-table-column>
			<el-table-column prop="sourceData.frameData.name" label="所属组织" align="center" width="90px"></el-table-column>
			<el-table-column prop="current.name" label="往来单位" align="center" width="160px"></el-table-column>
			<el-table-column prop="sourceData.time" label="单据时间" align="center" width="120px"></el-table-column>
			<el-table-column prop="sourceData.number" label="单据编号" align="center" width="180px"></el-table-column>
			<el-table-column prop="ietData.name" label="支出类别" align="center" width="120px"></el-table-column>
			<el-table-column prop="extension.state" label="结算状态" align="center" width="120px"></el-table-column>
			<el-table-column prop="money" label="金额" align="center" width="90px"></el-table-column>
			<el-table-column prop="settle" label="已结算金额" align="center" width="90px"></el-table-column>
			<el-table-column prop="uat" label="未结算金额" align="center" width="90px"></el-table-column>
			<el-table-column prop="csa" label="结算金额" align="center" width="90px">
				<template slot-scope="scope">
					<input type="text" v-model="scope.row.csa" @click.stop @input="csaChange(scope.row)" :readonly="scope.row.state==2"></input>
				</template>
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
		name: "Cost",
		components: {
			NodList,
			PageStatus
		},
		data() {
			return {
				searchFrom: {
					supplier: null,
					customer:null,
					number:"",
					iet:[],
					startTime:"",
					endTime:"",
					state:[0,1],
					mold:[]
				},
				tableData: [],
				tableSelection:[],
				page: {
					current: 1,
					total: 0,
					size: 30,
					sizes: [30, 60, 90, 150, 300],
					count: 5,
					status:[
						{text:'单据总金额',type:'sum',key:'money'},
						{text:'已结算总金额',type:'sum',key:'settle'},
						{text:'未结算总金额',type:'sum',key:'uat'}
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
				this.$axios.post("cost/record", parm).then(result => {
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
			//处理结算
			handle(){
				let selection=this.tableSelection.filter(row=>row.state!=2);
				if(selection.length==0){
					this.$message({
						type: "warning",
						message: "未选中有效费用数据!"
					});
				}else{
					let source=[];
					for (let row of selection) {
						if(!this.$lib.validate('plus',row.csa)){
							this.$message({
								type: "warning",
								message: "单据编号[ "+row.sourceData.number+" ]结算金额不正确!"
							});
							return false;
						}else if(row.csa-0>row.uat-0){
							this.$message({
								type: "warning",
								message: "单据编号[ "+row.sourceData.number+" ]结算金额不可大于未结算金额!"
							});
							return false;
						}else{
							source.push({id:row.id,csa:row.csa});
						}
					}
					//请求单据数据
					this.$axios.post("cost/buildOce", {
						parm:source
					}).then(result => {
						if (result.state == "success") {
							this.$bus.emit('switchPage','oce',true);//切换页面
							//等待页面切换完成
							setTimeout(()=>{
								this.$bus.emit('buildOce',result.info);//生成单据
							},600);
						} else if (result.state == "warning") {
							this.$message({
								type: "warning",
								message: result.info
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
				}
			},
			//导出数据
			exports(){
				if(this.tableSelection.length==0){
					this.$message({
						type: "warning",
						message: "未选择导出数据内容!"
					});
				}else{
					this.$message({
						type: "success",
						message: "[ 导出数据 ] 请求中..."
					});
					let data=this.tableSelection.map(row=>row.id);
					let parm=this.$lib.objToParm({parm:data},true);
					setTimeout(() => {
						window.open(this.$base.web+'cost/exports?'+parm)
					}, 1000);
				}
			},
			//金额改变
			csaChange(row){
				if(row.csa=='' || row.csa==0){
					this.$refs.dataTable.toggleRowSelection(row,false);
				}else{
					this.$refs.dataTable.toggleRowSelection(row,true);
				}
			},
			//表格选中数据改变
			selectionChange(parm) {
				for (var i = 0; i < this.tableData.length; i++) {
					let row=this.tableData[i];
					let find=parm.findIndex(item=>(item.id==row.id));
					if(find==-1){
						row.csa='';
					}else{
						if(row.csa=='' && row.state!=2)row.csa=row.uat;
					}
				}
				//转存数据
				this.tableSelection = parm;
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
