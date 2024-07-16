<template>
	<div class="cos area">
		<div class="layout">
			<el-popover ref="searchPopover" popper-class="searchPopover" placement="bottom-start">
				<el-form class="searchFrom" ref="searchFrom" inline>
					<el-form-item>
						<el-select v-model="searchFrom.mold" placeholder="单据类型" multiple collapse-tags>
							<el-option label="其它收入单" value="ice"></el-option>
							<el-option label="其它支出单" value="oce"></el-option> 
						</el-select>
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
						<nodList v-model="searchFrom.iet" placeholder="请选择收支类别" action="service/ietRecord" scene="iet"></nodList>
					</el-form-item>
					<el-form-item>
						<nodList v-model="searchFrom.account" placeholder="请选择资金账户" action="service/accountRecord" scene="account"></nodList>
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
			ref="table"
			:data="tableData"
			height="calc(100% - 90px)"
			border
			v-madeTable
		>
			<el-table-column prop="name" label="单据类型" align="center" width="120px"></el-table-column>
			<el-table-column prop="class.frameData.name" label="所属组织" align="center" width="120px"></el-table-column>
			<el-table-column prop="current.name" label="往来单位" align="center" width="160px"></el-table-column>
			<el-table-column prop="class.time" label="单据时间" align="center" width="120px"></el-table-column>
			<el-table-column prop="class.number" label="单据编号" align="center" width="180px"></el-table-column>
			<el-table-column prop="info.ietData.name" label="收支类别" align="center" width="120px"></el-table-column>
			<el-table-column prop="in" label="收入" align="center" width="90px"></el-table-column>
			<el-table-column prop="out" label="支出" align="center" width="90px"></el-table-column>
			<el-table-column prop="class.accountData.name" label="结算账户" align="center" width="120px"></el-table-column>
			<el-table-column prop="class.data" label="备注信息" align="center" width="200px"></el-table-column>
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
		name: "Cos",
		components: {
			NodList,
			PageStatus
		},
		data() {
			return {
				searchFrom: {
					mold:[],
					number:"",
					startTime:"",
					endTime:"",
					iet:null,
					account:null,
					data:""
				},
				tableData: [],
				page: {
					current: 1,
					total: 0,
					size: 30,
					sizes: [30, 60, 90, 150, 300],
					count: 5,
					status:[
						{text:'总收入',type:'sum',key:'in'},
						{text:'总支出',type:'sum',key:'out'}
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
				this.$axios.post("crt/cos", parm).then(result => {
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
					window.open(this.$base.web+'crt/cosExports?'+parm)
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
