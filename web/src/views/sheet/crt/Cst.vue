<template>
	<div class="cst area">
		<div class="layout">
			<el-popover ref="searchPopover" popper-class="searchPopover" placement="bottom-start">
				<el-form class="searchFrom" ref="searchFrom" inline>
					<el-form-item>
						<nodList v-model="searchFrom.supplier" placeholder="请选择供应商" action="service/supplierRecord" scene="supplier"></nodList>
					</el-form-item>
					<el-form-item>
						<el-select v-model="searchFrom.type" placeholder="显示明细">
							<el-option label="隐藏明细" :value="0"></el-option>
							<el-option label="显示明细" :value="1"></el-option> 
						</el-select>
					</el-form-item>
					<el-form-item>
						<el-date-picker v-model="searchFrom.startTime" placeholder="单据开始日期" value-format="yyyy-MM-dd" type="date"></el-date-picker>
					</el-form-item>
					<el-form-item>
						<el-date-picker v-model="searchFrom.endTime" placeholder="单据结束日期" value-format="yyyy-MM-dd" type="date"></el-date-picker>
					</el-form-item>
					<el-divider></el-divider>
					<el-button class="searchBtn" icon="el-icon-search" @click="record()" ></el-button>
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
			height="calc(100% - 60px)"
			border
			v-madeTable
		>
			<template v-if="tableType==1">
				<el-table-column align="center" width="44px"></el-table-column>
			</template>
			<el-table-column prop="bill" label="单据类型" align="center" width="120px"></el-table-column>
			<el-table-column prop="frameData.name" label="所属组织" align="center" width="120px"></el-table-column>
			<el-table-column prop="time" label="单据时间" align="center" width="120px"></el-table-column>
			<el-table-column prop="number" label="单据编号" align="center" width="180px"></el-table-column>
			<template v-if="tableType==1">
				<el-table-column prop="detail.name" label="名称" align="center" width="120px"></el-table-column>
				<el-table-column prop="detail.attr" label="属性" align="center" width="120px"></el-table-column>
				<el-table-column prop="detail.unit" label="单位" align="center" width="100px"></el-table-column>
				<el-table-column prop="detail.price" label="单价" align="center" width="100px"></el-table-column>
				<el-table-column prop="detail.nums" label="数量" align="center" width="100px"></el-table-column>
				<el-table-column prop="detail.dsc" label="折扣额" align="center" width="100px"></el-table-column>
				<el-table-column prop="detail.total" label="金额" align="center" width="100px"></el-table-column>
				<el-table-column prop="detail.tat" label="税额" align="center" width="100px"></el-table-column>
				<el-table-column prop="detail.tpt" label="价税合计" align="center" width="100px"></el-table-column>
			</template>
			<el-table-column prop="total" label="单据金额" align="center" width="100px"></el-table-column>
			<el-table-column prop="discount" label="优惠金额" align="center" width="100px"></el-table-column>
			<el-table-column prop="actual" label="应付金额" align="center" width="100px"></el-table-column>
			<el-table-column prop="money" label="实付金额" align="center" width="100px"></el-table-column>
			<el-table-column prop="balance" label="应付款余额" align="center" width="100px"></el-table-column>
			<el-table-column prop="data" label="备注" align="center" width="200px"></el-table-column>
		</el-table>
	</div>
</template>
<script>
	import NodList from "@/components/lib/NodList";
	export default {
		name: "Cst",
		components: {
			NodList
		},
		data() {
			return {
				searchFrom: {
					supplier : null,
					type: 0,
					startTime : "",
					endTime : ""
				},
				tableData: [],
				tableType:0
			};
		},
		created() {
			this.init();
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
			record() {
				if(this.searchFrom.supplier==null){
					this.$message({type: "warning",message: '请选择供应商后搜索!'});
				}else{
					let parm = this.searchFrom;
					this.$axios.post("crt/cst", parm).then(result => {
						if (result.state == "success") {
							this.tableData = result.info;
							this.$refs["searchPopover"].showPopper = false;
							//ELEMENT更新错位.暂延时处理
							this.tableType=-1;
							this.$nextTick(()=>{
								this.tableType=[0,1][this.searchFrom.type];
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
					window.open(this.$base.web+'crt/cstExports?'+parm)
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
