<template>
	<div class="stock">
		<el-dialog :visible.sync="dialog" title="序列号" width="460px" @closed="close" :append-to-body="true" v-madeDialog>
			<el-tabs v-model="active">
				<el-tab-pane label="常规录入" name="base">
					<el-input v-model="source.serial" placeholder="扫码录入或手动录入"  @keydown.enter.native="addSerial" clearable>
						<template slot="append">
							<span @click="addSerial">添加</span>
						</template>
					</el-input>
				</el-tab-pane>
				<el-tab-pane label="选择录入" name="choice" v-if="config.record.show">
					<el-table
						:data="source.record.data"
						ref="recordTable"
						height="150px"
						@row-click="rowClick"
						@selection-change="selectionChange"
						size="mini"
						border
						v-madeTable
					>
						<el-table-column type="selection" align="center" width="39px"></el-table-column>
						<el-table-column prop="number" label="序列号" align="center" min-width="160px"></el-table-column>
						<el-table-column prop="extension.state" label="状态" align="center" min-width="90px"></el-table-column>
					</el-table>
					<el-pagination
						class="tablePagination"
						:current-page.sync="source.record.page.current"
						:total="source.record.page.total"
						:page-size.sync="source.record.page.size"
						:page-sizes="source.record.page.sizes"
						:pager-count="source.record.page.count"
						@current-change="getSerial"
						layout="prev,pager,next,total,slot"
					>
						<el-button class="choiceBtn" type="info" @click="addChoice">添加选中</el-button>
					</el-pagination>
				</el-tab-pane>
				<el-tab-pane label="批量录入" name="batch">
					<el-input type="textarea" :rows="3" placeholder="扫码录入或手动录入|每行一条" v-model="source.serials"></el-input>
					<el-button class="rowBtn" @click="addSerials" type="info">批量添加</el-button>
				</el-tab-pane>
				<el-tab-pane label="自动生成" name="auto">
					<el-form :inline="true">
						<el-row :gutter="9">
							<el-col :span="9">
								<el-form-item label="前缀文本">
									<el-input v-model="source.auto.text" size="mini" clearable></el-input>
								</el-form-item>
							</el-col>
							<el-col :span="5">
								<el-form-item label="起始号码">
									<el-input v-model.number="source.auto.start" size="mini" clearable></el-input>
								</el-form-item>
							</el-col>
							<el-col :span="5">
								<el-form-item label="递增数量">
									<el-input v-model.number="source.auto.plus" size="mini" clearable></el-input>
								</el-form-item>
							</el-col>
							<el-col :span="5">
								<el-form-item label="生成数量">
									<el-input v-model.number="source.auto.count" size="mini" clearable></el-input>
								</el-form-item>
							</el-col>
						</el-row>
					</el-form>
					<el-button class="rowBtn" @click="addAuto" type="info">批量生成</el-button>
				</el-tab-pane>
			</el-tabs>
			<el-divider></el-divider>
			<el-table :data="tableData" height="150px" size="mini" border>
				<el-table-column prop="index" label="#" type="index" align="center" width="50px"></el-table-column>
				<el-table-column prop="serial" label="序列号" align="center" min-width="120px"></el-table-column>
				<el-table-column prop="set" label="操作" align="center" width="50px">
					<template slot="header">
						<i class="el-icon-delete" @click="clearSerial"></i>
					</template>
					<template slot-scope="scope">
						<i class="el-icon-delete" @click="delSerial(scope.$index)"></i>
					</template>
				</el-table-column>
			</el-table>
			<span slot="footer" class="dialog-footer">
				<el-button @click="dialog = false">取消</el-button>
				<el-button @click="save" type="primary">保存</el-button>
			</span>
		</el-dialog>
	</div>
</template>
<script>
	export default {
		name: "Snu",
		props: {
			config:{
				required: true,
				type: Object
			}
		},
		data() {
			return {
				dialog:true,
				active:'base',
				source:{
					serial:'',
					serials:'',
					record:{
						data:[],
						selection:[],
						page:{
							current: 1,
							total: 0,
							size: 30,
							sizes: [30, 60, 90, 150, 300],
							count: 5
						}
					},
					auto:{
						text:'SN',
						start:1,
						plus:1,
						count:1,
					},
				},
				tableData:[],
			}
		},
		created() {
			this.config.record.show&&this.getSerial();
			this.tableData=this.config.source.map(item=>{
				return {serial:item};
			});
		},
		methods: {
			//添加数据
			addSerial(){
				if(this.$lib.validate('empty',this.source.serial)){
					this.$message({
						type: "warning",
						message: "录入内容不可为空!"
					});
				}else{
					this.tableData.push({
						serial:this.source.serial
					});
					this.source.serial="";
				}
			},
			//批量添加数据
			addSerials(){
				let arr=this.source.serials.split(/[(\r\n)\r\n]+/).filter(item=>!this.$lib.validate("empty",item));
				if(arr.length>0){
					for (let item of arr) {
						this.tableData.push({
							serial:item
						});
					}
					this.source.serials="";
				}else{
					this.$message({
						type: "warning",
						message: "批量录入内容不可为空!"
					});
				}
			},
			//获取数据
			getSerial(){
				let parm = Object.assign({
					page: this.source.record.page.current,
					limit: this.source.record.page.size
				},this.config.condition,this.config.record.parm);
				this.$axios.post("service/getSerial",parm).then(result => {
					if (result.state == "success") {
						this.source.record.data = result.info;
						this.source.record.page.total = result.count;
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
			//行单击
			rowClick(row){
				this.$refs.recordTable.toggleRowSelection(row);
			},
			//选中数据
			selectionChange(parm) {
				let data = [];
				for (let parmVo of parm) {
					data.push(parmVo.number);
				}
				this.source.record.selection=data;
			},
			//添加选中
			addChoice(){
				let selection = this.source.record.selection;
				if(selection.length>0){
					for (let item of selection) {
						this.tableData.push({
							serial:item
						});
					}
					this.$refs.recordTable.clearSelection();
				}else{
					this.$message({
						type: "warning",
						message: "您还未选择数据!"
					});
				}
			},
			//批量生成数据
			addAuto(){
				if(!this.$lib.validate('integer',this.source.auto.start)){
					this.$message({
						type: "warning",
						message: "起始号码不正确!"
					});
				}else if(!this.$lib.validate('integer',this.source.auto.plus)){
					this.$message({
						type: "warning",
						message: "递增数量不正确!"
					});
				}else if(!this.$lib.validate('integer',this.source.auto.count)){
					this.$message({
						type: "warning",
						message: "生成数量不正确!"
					});
				}else{
					for (var i = 0; i < this.source.auto.count; i++) {
						let mul=this.$calc.chain(this.source.auto.plus).multiply(i).done();
						let number=this.$calc.chain(this.source.auto.start).add(mul).done();
						this.tableData.push({
							serial:this.source.auto.text+number
						});
					}
				}
			},
			//删除表格数据
			delSerial(index){
				this.tableData.splice(index,1);
			},
			//清空序列号
			clearSerial(){
				this.tableData=[];
			},
			//保存序列号
			save(){
				if(this.tableData.length>0){
					let serial=this.tableData.map(item=>item.serial);
					for (var i = 0; i < serial.length; i++) {
						if(!this.$lib.validate('serial',serial[i])){
							this.$message({
								type: "warning",
								message: "序列号第"+(i+1)+"条不符合规则!"
							});
							return false;
						}
					}
					if(this.$lib.distinct(serial).length==serial.length){
						this.$emit('save',serial);
						this.dialog=false;
					}else{
						this.$message({
							type: "warning",
							message: "序列号重复,请核实!"
						});
					}
				}else{
					this.$message({
						type: "warning",
						message: "序列号不可为空!"
					});
				}
			},
			//弹层关闭事件
			close(){
				this.$emit('destroy',true);
			}
		},
	}
</script>
<style scoped>
	.el-tabs{
		margin-top: -10px;
	}
	.el-form{
		border-radius: 4px;
		border: 1px solid #DCDFE6;
		padding: 4px 6px;
	}
	.el-form >>> .el-form-item{
		width: 100%;
		margin-right: 0;
		margin-bottom: 0;
	}
	.el-form >>> .el-form-item__label{
		font-size: 12px;
		line-height: 24px;
	}
	.el-form >>> .el-form-item__content{
		display: block;
	}
	.choiceBtn{
		float: right;
		height: 24px;
		line-height: 24px;
		margin-right: 10px;
		background-color: #909399;
	}
	.choiceBtn:hover{
		color: #ffffff;
		background: #a6a9ad;
	}
	.choiceBtn >>> span{
		height: 24px;
		line-height: 24px;
	}
	.rowBtn{
		width: 100%;
		margin-top: 10px;
	}
</style>