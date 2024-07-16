<template>
	<div class="code area">
		<div class="layout">
			<el-popover ref="searchPopover" popper-class="searchPopover" placement="bottom-start">
				<el-form class="searchFrom" ref="searchFrom"  inline>
					<el-form-item>
						<el-input placeholder="请输入条码名称" v-model="searchFrom.name" clearable></el-input>
					</el-form-item>
					<el-form-item>
						<el-input placeholder="请输入条码内容" v-model="searchFrom.info" clearable></el-input>
					</el-form-item>
					<el-form-item>
						<el-select v-model="searchFrom.type" placeholder="请选择条码类型" clearable>
							<el-option label="条形码" :value="1"></el-option>
							<el-option label="二维码" :value="2"></el-option>
						  </el-select>
					</el-form-item>
					<el-form-item>
						<el-input placeholder="请输入备注信息" v-model="searchFrom.data" clearable></el-input>
					</el-form-item>
					<el-divider></el-divider>
					<el-button class="searchBtn" icon="el-icon-search" @click="record(1)" ></el-button>
				</el-form>
				<el-button slot="reference" icon="el-icon-more" ></el-button>
			</el-popover>
			<el-button-group>
				<template v-if="tableSelection.length!=0">
					<template v-if="$lib.getUserRoot(store.root,'assist','del')">
						<el-button @click="del(tableSelection)"  type="info">删除</el-button>
					</template>
				</template>
				<template v-if="$lib.getUserRoot(store.root,'assist','add')">
					<el-button @click="set(0)" >新增</el-button>
				</template>
				<template v-if="$lib.getUserRoot(store.root,'assist','batch')">
					<el-button @click="batch.dialog=true" >批量</el-button>
				</template>
				<el-button @click="reload" >刷新</el-button>
			</el-button-group>
		</div>
		<el-divider></el-divider>
		<el-table :data="tableData" height="calc(100% - 90px)" @selection-change="selectionChange" border v-madeTable>
			<el-table-column type="selection" align="center" width="39px" fixed="left"></el-table-column>
			<el-table-column prop="name" label="条码名称" align="center" width="200px"></el-table-column>
			<el-table-column prop="info" label="条码内容" align="center" width="200px"></el-table-column>
			<el-table-column prop="extension.type" label="条码类型" align="center" width="200px"></el-table-column>
			<el-table-column prop="data" label="备注信息 " align="center" width="200px"></el-table-column>
			<el-table-column prop="set" label="相关操作" align="center" width="200px">
				<template slot-scope="scope">
					<el-button-group>
						<el-button @click="see(scope.row)" size="mini">查看</el-button>
						<el-button @click="set(scope.row.id)" size="mini">详情</el-button>
						<template v-if="$lib.getUserRoot(store.root,'assist','del')">
							<el-button @click="del([scope.row.id])" size="mini">删除</el-button>
						</template>
					</el-button-group>
				</template>
			</el-table-column>
		</el-table>
		<el-pagination class="tablePagination" :current-page.sync="page.current" :total="page.total" :page-size.sync="page.size"
		 :page-sizes="page.sizes" :pager-count="page.count" @size-change="record(1)" @current-change="record(0)" layout="prev,pager,next,jumper,sizes,total">
		</el-pagination>
		<el-dialog :visible.sync="dialog" title="详情" width="420px" v-madeDialog>
			<transition name="el-fade-in">
				<template v-if="dialog">
					<el-form :model="form" :rules="rules" ref="form" label-width="80px" >
						<el-form-item label="条码名称">
							<el-input placeholder="请输入条码名称" v-model="form.name" clearable></el-input>
						</el-form-item>
						<el-form-item label="条码内容">
							<el-input placeholder="请输入条码内容" v-model="form.info" clearable></el-input>
						</el-form-item>
						<el-form-item label="条码类型">
							<el-select v-model="form.type" placeholder="请选择条码类型">
								<el-option label="条形码" :value="0"></el-option>
								<el-option label="二维码" :value="1"></el-option>
							  </el-select>
						</el-form-item>
						<el-form-item label="备注信息">
							<el-input placeholder="请输入备注信息" v-model="form.data" clearable></el-input>
						</el-form-item>
					</el-form>
				</template>
			</transition>
			<span slot="footer" class="dialog-footer">
				<el-button @click="dialog = false" >取消</el-button>
				<template v-if="form.id==0 || $lib.getUserRoot(store.root,'assist','edit')">
					<el-button @click="save" type="primary" >保存</el-button>
				</template>
			</span>
		</el-dialog>
		<el-dialog class="tabsDialog" :visible.sync="batch.dialog" title="批量" width="420px" v-madeDialog>
			<transition name="el-fade-in">
				<template v-if="batch.dialog">
					<el-tabs v-model="batch.active">
						<el-tab-pane label="导入数据" name="import">
							<ul class="importTip">
								<li>1.该功能适用于批量导入数据。</li>
								<li>2.您需要下载数据模板后使用Excel录入数据。</li>
								<li>3.录入数据时，请勿修改首行数据标题以及排序。</li>
								<li>4.请查阅使用文档获取字段格式内容以及相关导入须知。</li>
								<li>5.点击下方上传模板，选择您编辑好的模板文件即可。</li>
							</ul>
							<el-divider></el-divider>
							<el-row style="text-align:center;">
								<el-col :span="12">
									<el-button @click="downTemplate" type="info" >下载模板</el-button>
								</el-col>
								<el-col :span="12">
									<el-upload
										:action="$base.web+'code/import'"
										:headers="{Token:$store.state.token}"
										:show-file-list="false"
										:on-success="importCall"
									>
										<el-button type="primary" >上传模板</el-button>
									</el-upload>
								</el-col>
							</el-row>
						</el-tab-pane>
						<el-tab-pane label="导出数据" name="export">
							<div class="exportItem" @click="exports">
								<i class="el-icon-download"></i>	
								<p>导出数据</p>
							</div>
						</el-tab-pane>
					</el-tabs>
				</template>
			</transition>
		</el-dialog>
		<el-dialog :visible.sync="view.dialog" title="查看" width="360px" v-madeDialog>
			<transition name="el-fade-in">
				<template v-if="view.dialog">
					<img class="codeImg" :src="view.src">
				</template>
			</transition>
		</el-dialog>
	</div>
</template>
<script>
	export default {
		name: "Code",
		data() {
			return {
				searchFrom: {
					name: "",
					info:"",
					type:"",
					data: ""
				},
				tableData: [],
				tableSelection:[],
				page: {
					current: 1,
					total: 0,
					size: 30,
					sizes: [30, 60, 90, 150, 300],
					count: 5
				},
				dialog: false,
				form: {
					id: 0,
					name: "",
					info:"",
					type:0,
					data: ""
				},
				rules: {
					name: {
						required: true,
						message: "请输入条码名称",
						trigger: "blur"
					},
					info: {
						required: true,
						message: "请输入条码内容",
						trigger: "blur"
					}
				},
				batch:{
					dialog:false,
					active:"import"
				},
				view:{
					dialog:false,
					src:''
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
				this.$axios.post("code/record", parm).then(result => {
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
			//设置数据
			set(id) {
				//1.初始化数据
				this.form = Object.assign({}, this.$options.data().form);
				//2.请求数据
				if (id > 0) {
					this.$axios.post("code/get", {
						id: id
					}).then(result => {
						if (result.state == "success") {
							this.form = result.info;
							this.dialog = true; //显示弹层
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
				} else {
					this.dialog = true; //显示弹层
				}
			},
			//保存数据
			save() {
				this.$refs["form"].validate(valid => {
					if (valid) {
						this.$axios.post("code/save", this.form).then(result => {
							if (result.state == "success") {
								this.record(0);
								this.dialog = false;
								this.$message({
									type: "success",
									message: "详情保存成功!"
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
			//删除数据
			del(parm) {
				this.$confirm("您确定要删除选中数据吗?", "提示", {
					confirmButtonText: "确定",
					cancelButtonText: "取消",
					type: "warning"
				}).then(() => {
					this.$axios.post("code/del", {
						parm: parm
					}).then(result => {
						if (result.state == "success") {
							this.record(0);
							this.$message({
								type: "success",
								message: "删除成功!"
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
				}).catch(()=>{});
			},
			//下载模板
			downTemplate(){
				this.$message({
					type: "success",
					message: "[ 数据模板 ] 下载请求中..."
				});
				setTimeout(() => {
					window.open(this.store.base.cdnSite+"/erp/batch/条码导入模板.xlsx")
				}, 1000);
			},
			//上传模板回调
			importCall(result, file, fileList){
				if (result.state == 'success') {
					this.$bus.emit('homeReload',this.$options.name);
					this.$message({
						type: "success",
						message: result.info
					});
				} else if (result.state == "error") {
					this.$message({
						type: "warning",
						message: "[ " + file.name + " ]" + result.info
					});
				} else {
					this.$message({
						type: "error",
						message: "[ ERROR ] 服务器响应超时!"
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
					let parm=this.$lib.objToParm({parm:this.tableSelection},true);
					setTimeout(() => {
						window.open(this.$base.web+'code/exports?'+parm)
					}, 1000);
				}
			},
			//查看条码
			see(parm){
				this.view.dialog=true;
				this.view.src=this.$base.web+'code/view?text='+parm.info+'&type='+(parm.type==0?'txm':'ewm')+'&token='+this.store.token;
			},
			//表格选中数据改变
			selectionChange(parm) {
				let data = [];
				for (let parmVo of parm) {
					data.push(parmVo.id);
				}
				this.tableSelection = data;
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
	.codeImg{
		display: block;
		margin: 0 auto;
		max-height: 150px;
	}
</style>
