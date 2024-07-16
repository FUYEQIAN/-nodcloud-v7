<template>
	<div class="invoice area">
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
						<el-select v-model="searchFrom.invoice" placeholder="发票状态" multiple collapse-tags>
							<el-option label="未开票" :value="0"></el-option>
							<el-option label="部分开票" :value="1"></el-option> 
							<el-option label="已开票" :value="2"></el-option>
						</el-select>
					</el-form-item>
					<el-form-item>
						<el-date-picker v-model="searchFrom.startTime" placeholder="单据开始日期" value-format="yyyy-MM-dd" type="date"></el-date-picker>
					</el-form-item>
					<el-form-item>
						<el-date-picker v-model="searchFrom.endTime" placeholder="单据结束日期" value-format="yyyy-MM-dd" type="date"></el-date-picker>
					</el-form-item>
					<el-form-item>
						<el-select v-model="searchFrom.mold" placeholder="单据类型" multiple collapse-tags>
							<el-option label="采购单" value="buy"></el-option>
							<el-option label="采购退货单" value="bre"></el-option> 
							<el-option label="销售单" value="sell"></el-option> 
							<el-option label="销售退货单" value="sre"></el-option> 
						</el-select>
					</el-form-item>
					<el-divider></el-divider>
					<el-button class="searchBtn" icon="el-icon-search" @click="record(1)"></el-button>
				</el-form>
				<el-button slot="reference" icon="el-icon-more"></el-button>
			</el-popover>
			<el-button-group>
				<template v-if="$lib.getUserRoot(store.root,'invoice','edit')">
					<el-button @click="handle">开票</el-button>
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
			<el-table-column prop="name" label="单据类型" align="center" width="90px"></el-table-column>
			<el-table-column prop="frameData.name" label="所属组织" align="center" width="90px"></el-table-column>
			<el-table-column prop="current.name" label="往来单位" align="center" width="160px"></el-table-column>
			<el-table-column prop="time" label="单据时间" align="center" width="120px"></el-table-column>
			<el-table-column prop="number" label="单据编号" align="center" width="180px"></el-table-column>
			<el-table-column prop="extension.invoice" label="发票状态" align="center" width="120px"></el-table-column>
			<el-table-column prop="actual" label="单据金额" align="center" width="160px"></el-table-column>
			<el-table-column prop="iat" label="已开票金额" align="center" width="90px"></el-table-column>
			<el-table-column prop="ani" label="未开票金额" align="center" width="90px"></el-table-column>
			<el-table-column prop="money" label="开票金额" align="center" width="90px">
				<template slot-scope="scope">
					<input type="text" v-model="scope.row.money" @click.stop @input="moneyChange(scope.row)" :readonly="scope.row.invoice==2"></input>
				</template>
			</el-table-column>
		</el-table>
		<el-pagination class="tablePagination" :current-page.sync="page.current" :total="page.total" :page-size.sync="page.size"
		 :page-sizes="page.sizes" :pager-count="page.count" @size-change="record(1)" @current-change="record(0)" layout="prev,pager,next,jumper,sizes,total,slot">
			<PageStatus :config="page.status" :model="tableData"></PageStatus>
		</el-pagination>
		<el-dialog :visible.sync="dialog.show" title="发票详情" width="420px" v-madeDialog>
			<transition name="el-fade-in">
				<template v-if="dialog.show">
					<el-form :model="dialog.form" :rules="dialog.rules" ref="form" label-width="80px" >
						<el-form-item label="合计金额">
							<el-input v-model="summary" readonly></el-input>
						</el-form-item>
						<el-form-item label="开票时间" prop="time">
							<el-date-picker v-model="dialog.form.time" placeholder="开票时间" value-format="yyyy-MM-dd" type="date"></el-date-picker>
						</el-form-item>
						<el-form-item label="发票号码" prop="number">
							<el-input placeholder="请输入发票号码" v-model="dialog.form.number" clearable></el-input>
						</el-form-item>
						<el-form-item label="发票抬头" prop="title">
							<el-input placeholder="请输入发票号码" v-model="dialog.form.title" clearable></el-input>
						</el-form-item>
						<el-form-item label="发票附件">
							<NodUpload v-model="dialog.form.file" :action="$base.web+'invoice/upload'"></NodUpload>
						</el-form-item>
						<el-form-item label="备注信息">
							<el-input placeholder="请输入备注信息" v-model="dialog.form.data" clearable></el-input>
						</el-form-item>
					</el-form>
				</template>
			</transition>
			<span slot="footer" class="dialog-footer">
				<el-button @click="dialog.show = false">取消</el-button>
				<el-button @click="save" type="primary">开票</el-button>
			</span>
		</el-dialog>
	</div>
</template>
<script>
	import NodList from "@/components/lib/NodList";
	import NodUpload from "@/components/lib/NodUpload";
	import PageStatus from "@/components/lib/PageStatus";
	export default {
		name: "Invoice",
		components: {
			NodList,
			NodUpload,
			PageStatus
		},
		data() {
			return {
				searchFrom: {
					supplier: null,
					customer:null,
					number:"",
					invoice:[0,1],
					startTime:"",
					endTime:"",
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
						{text:'单据总金额',type:'sum',key:'actual'},
						{text:'已开票总金额',type:'sum',key:'iat'},
						{text:'未开票总金额',type:'sum',key:'ani'}
					]
				},
				source:[],
				dialog:{
					show:false,
					form:{
						time:"",
						number:"",
						title:"",
						file:[],
						data:""
					},
					rules: {
						time: {
							required: true,
							message: "请选择开票日期",
							trigger: "change"
						},
						number: {
							required: true,
							message: "请输入发票号码",
							trigger: "blur"
						},
						title: {
							required: true,
							message: "请输入发票抬头",
							trigger: "blur"
						}
					},
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
			//发票合计
			summary(){
				let total=this.$calc.chain(0);
				for (let row of this.source) {
					total.add(row.money)
				}
				return total.done();
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
				this.$axios.post("invoice/record", parm).then(result => {
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
			//处理开票
			handle(){
				let selection=this.tableSelection.filter(row=>row.invoice!=2);
				if(selection.length==0){
					this.$message({
						type: "warning",
						message: "未选中有效发票数据!"
					});
				}else{
					this.source=[];
					for (let row of selection) {
						if(!this.$lib.validate('invoice',row.money)){
							this.$message({
								type: "warning",
								message: "单据编号[ "+row.number+" ]开票金额不正确!"
							});
							return false;
						}else if(row.money-0>row.ani-0){
							this.$message({
								type: "warning",
								message: "单据编号[ "+row.number+" ]开票金额不可大于未开票金额!"
							});
							return false;
						}else{
							this.source.push({
								type:row.mold,
								class:row.id,
								money:row.money
							});
						}
					}
					this.dialog.form = Object.assign({}, this.$options.data().dialog.form);
					this.dialog.form.time=this.$moment().format('YYYY-MM-DD');
					this.dialog.show=true;
				}
			},
			//提交数据
			save(){
				this.$refs["form"].validate(valid => {
					if (valid) {
						let data=[];
						let form=this.dialog.form;
						for (let row of this.source) {
							data.push({
								type:row.type,
								class:row.class,
								time:form.time,
								number:form.number,
								title:form.title,
								money:row.money,
								file:form.file,
								data:form.data
							});
						}
						this.$axios.post("invoice/save", {data:data}).then(result => {
							if (result.state == "success") {
								this.record(0);
								this.dialog.show = false;
								this.$message({
									type: "success",
									message: "开具发票成功!"
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
				});
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
					let data=this.tableSelection.map(row=>({mold:row.mold,id:row.id}));
					let parm=this.$lib.objToParm({parm:data},true);
					setTimeout(() => {
						window.open(this.$base.web+'invoice/exports?'+parm)
					}, 1000);
				}
			},
			//金额改变
			moneyChange(row){
				if(row.money=='' || row.money==0){
					this.$refs.dataTable.toggleRowSelection(row,false);
				}else{
					this.$refs.dataTable.toggleRowSelection(row,true);
				}
			},
			//表格选中数据改变
			selectionChange(parm) {
				for (var i = 0; i < this.tableData.length; i++) {
					let row=this.tableData[i];
					let find=parm.findIndex(item=>(item.id==row.id && item.mold==row.mold));
					if(find==-1){
						row.money='';
					}else{
						if(row.money=='' && row.invoice!=2)row.money=row.ani;
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
