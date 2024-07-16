<template>
	<div class="customer area">
		<div class="layout">
			<el-popover ref="searchPopover" popper-class="searchPopover" placement="bottom-start">
				<el-form class="searchFrom" ref="searchFrom"  inline>
					<el-form-item>
						<el-input placeholder="请输入客户名称" v-model="searchFrom.name" clearable></el-input>
					</el-form-item>
					<el-form-item>
						<el-input placeholder="请输入客户编号" v-model="searchFrom.number" clearable></el-input>
					</el-form-item>
					<el-form-item>
						<el-select v-model="searchFrom.category" placeholder="请选择客户类别" clearable>
							<template v-for="category in store.sys.crCategory">
								<el-option :label="category" :value="category"></el-option>
							</template> 
						  </el-select>
					</el-form-item>
					<el-form-item>
						<el-select v-model="searchFrom.grade" placeholder="请选择客户等级" clearable>
							<template v-for="grade in store.sys.crGrade">
								<el-option :label="grade" :value="grade"></el-option>
							</template>
						  </el-select>
					</el-form-item>
					<el-form-item>
						<el-input placeholder="请输入联系人员" v-model="searchFrom.contacts" clearable></el-input>
					</el-form-item>
					<el-form-item>
						<el-input placeholder="请输入联系电话" v-model="searchFrom.tel" clearable></el-input>
					</el-form-item>
					<el-form-item>
						<nodList v-model="searchFrom.user" placeholder="请选择所属用户" action="service/userRecord" scene="user"></nodList>
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
					<template v-if="$lib.getUserRoot(store.root,'base','del')">
						<el-button @click="del(tableSelection)"  type="info">删除</el-button>
					</template>
				</template>
				<template v-if="$lib.getUserRoot(store.root,'base','add')">
					<el-button @click="set(0)" >新增</el-button>
				</template>
				<template v-if="$lib.getUserRoot(store.root,'base','batch')">
					<el-button @click="batch.dialog=true" >批量</el-button>
				</template>
				<el-button @click="reload" >刷新</el-button>
			</el-button-group>
		</div>
		<el-divider></el-divider>
		<el-table :data="tableData" height="calc(100% - 90px)" @selection-change="selectionChange" border v-madeTable>
			<el-table-column type="selection" align="center" width="39px" fixed="left"></el-table-column>
			<el-table-column prop="name" label="客户名称" align="center" width="260px"></el-table-column>
			<el-table-column prop="number" label="客户编号" align="center" width="160px"></el-table-column>
			<el-table-column prop="category" label="客户类别" align="center" width="160px"></el-table-column>
			<el-table-column prop="grade" label="客户等级" align="center" width="160px"></el-table-column>
			<el-table-column prop="balance" label="应收款余额" align="center" width="120px"></el-table-column>
			<el-table-column prop="integral" label="客户积分" align="center" width="120px"></el-table-column>
			<el-table-column prop="frameData.name" label="所属组织" align="center" width="160px"></el-table-column>
			<el-table-column prop="userData.name" label="所属用户" align="center" width="160px"></el-table-column>
			<el-table-column prop="data" label="备注信息 " align="center" min-width="200px"></el-table-column>
			<el-table-column prop="set" label="相关操作" align="center" width="200px" fixed="right">
				<template slot-scope="scope">
					<el-button-group>
						<el-button @click="set(scope.row.id)" size="mini">详情</el-button>
						<template v-if="$lib.getUserRoot(store.root,'base','del')">
							<el-button @click="del([scope.row.id])" size="mini">删除</el-button>
						</template>
					</el-button-group>
				</template>
			</el-table-column>
		</el-table>
		<el-pagination class="tablePagination" :current-page.sync="page.current" :total="page.total" :page-size.sync="page.size"
		 :page-sizes="page.sizes" :pager-count="page.count" @size-change="record(1)" @current-change="record(0)" layout="prev,pager,next,jumper,sizes,total">
		</el-pagination>
		<el-dialog class="tabsDialog" :visible.sync="dialog.show" title="详情" width="700px" v-madeDialog>
			<transition name="el-fade-in">
				<template v-if="dialog.show">
					<el-form :model="form" :rules="rules" ref="form" label-width="96px"  :inline="true">
						<el-tabs v-model="dialog.active">
							<el-tab-pane label="基础资料" name="base">
								<el-form-item label="客户名称" prop="name">
									<el-input placeholder="请输入客户名称" v-model="form.name" clearable></el-input>
								</el-form-item>
								<el-form-item label="客户编号" prop="number">
									<el-input placeholder="请输入客户编号" v-model="form.number" clearable></el-input>
								</el-form-item>
								<el-form-item label="所属组织" prop="frame">
									<nodTree v-model="form.frame" :treeData="store.frame" placeholder="请选择所属组织"></nodTree>
								</el-form-item>
								<el-form-item label="所属用户" prop="user">
									<nodList v-model="form.user" placeholder="请选择所属用户" action="service/userRecord" scene="user"></nodList>
								</el-form-item>
								<el-form-item label="客户类别">
									<el-select v-model="form.category" placeholder="请选择客户类别">
										<template v-for="category in store.sys.crCategory">
											<el-option :label="category" :value="category"></el-option>
										</template>
									  </el-select>
								</el-form-item>
								<el-form-item label="客户等级">
									<el-select v-model="form.grade" placeholder="请选择客户等级">
										<template v-for="grade in store.sys.crGrade">
											<el-option :label="grade" :value="grade"></el-option>
										</template>
									  </el-select>
								</el-form-item>
								<el-form-item label="开户银行">
									<el-input placeholder="请输入开户银行" v-model="form.bank" clearable></el-input>
								</el-form-item>
								<el-form-item label="银行账号">
									<el-input placeholder="请输入银行账号" v-model="form.account" clearable></el-input>
								</el-form-item>
								<el-form-item label="纳税号码">
									<el-input placeholder="请输入纳税人识别号" v-model="form.tax" clearable></el-input>
								</el-form-item>
								<el-form-item label="备注信息">
									<el-input placeholder="请输入备注信息" v-model="form.data" clearable></el-input>
								</el-form-item>
							</el-tab-pane>
							<el-tab-pane label="联系资料" name="contacts">
								<el-table :data="form.contacts" height="200px"  border>
									<el-table-column label="主联系人" align="center" min-width="90px">
										<template slot-scope="scope">
											<span>{{scope.row.main?"是":"否"}}</span>
										</template>
									</el-table-column>
									<el-table-column prop="name" label="联系人员" align="center" width="120px"></el-table-column>
									<el-table-column prop="tel" label="联系号码" align="center" width="120px"></el-table-column>
									<el-table-column prop="add" label="联系地址" align="center" width="160px"></el-table-column>
									<el-table-column prop="data" label="备注信息" align="center" min-width="120px"></el-table-column>
									<el-table-column prop="set" align="center" width="160px" fixed="right">
										<template slot-scope="scope" slot="header">
											<span style="margin-right:3px;">相关操作</span>
											<i class="el-icon-circle-plus-outline" @click="setContacts(-1)"></i>
										</template>
										<template slot-scope="scope">
											<el-button-group>
												<el-button @click="setContacts(scope.$index)" size="mini">详情</el-button>
												<el-button @click="delContacts(scope.$index)" size="mini">删除</el-button>
											</el-button-group>
										</template>
									</el-table-column>
								</el-table>
								<el-dialog :visible.sync="contacts.dialog" title="联系资料" width="360px" :append-to-body="true" v-madeDialog>
									<transition name="el-fade-in">
										<template v-if="contacts.dialog">
											<el-form :model="contacts.form" :rules="contacts.rules" ref="contactsForm" label-width="80px" >
												<el-form-item label="主联系人">
													<el-switch v-model="contacts.form.main"></el-switch>
												</el-form-item>
												<el-form-item label="联系人员" prop="name">
													<el-input placeholder="请输入联系人员" v-model="contacts.form.name"></el-input>
												</el-form-item>
												<el-form-item label="联系号码" prop="tel">
													<el-input placeholder="请输入联系号码" v-model="contacts.form.tel"></el-input>
												</el-form-item>
												<el-form-item label="联系地址">
													<el-input placeholder="请输入联系地址" v-model="contacts.form.add"></el-input>
												</el-form-item>
												<el-form-item label="备注信息">
													<el-input placeholder="请输入备注信息" v-model="contacts.form.data"></el-input>
												</el-form-item>
											</el-form>
										</template>
									</transition>
									<span slot="footer" class="dialog-footer">
										<el-button @click="contacts.dialog = false" >取消</el-button>
										<el-button @click="saveContacts" type="primary" >保存</el-button>
									</span>
								</el-dialog>
							</el-tab-pane>
							<template v-if="store.fields.hasOwnProperty($route.name)">
								<el-tab-pane label="扩展信息" name="more">
									<FieldForm v-model="form.more" rule="customer" prefix="more."></FieldForm>
								</el-tab-pane>
							</template>
						</el-tabs>
					</el-form>
				</template>
			</transition>
			<span slot="footer" class="dialog-footer">
				<el-button @click="dialog.show = false" >取消</el-button>
				<template v-if="form.id==0 || $lib.getUserRoot(store.root,'base','edit')">
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
										:action="$base.web+'customer/import'"
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
	</div>
</template>
<script>
	import NodTree from "@/components/lib/NodTree";
	import NodList from "@/components/lib/NodList";
	import FieldForm from "@/components/lib/FieldForm";
	export default {
		name: "Customer",
		components: {
			NodTree,
			NodList,
			FieldForm
		},
		data() {
			return {
				searchFrom: {
					name: "",
					number:"",
					category:"",
					grade:"",
					contacts:"",
					tel:"",
					user:null,
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
				dialog:{
					show:false,
					active:'base'
				},
				form: {
					id: 0,
					name: "",
					number:"",
					frame:null,
					user:null,
					category:"常规类别",
					grade: "常规等级",
					bank: "",
					account: "",
					tax: "",
					data: "",
					contacts: [],
					more: {},
				},
				rules: {
					name: {
						required: true,
						message: "请输入客户名称",
						trigger: "blur"
					},
					number: {
						required: true,
						message: "请输入客户编号",
						trigger: "blur"
					},
					frame: {
						required: true,
						message: "请选择所属组织",
						trigger: "change"
					},
					user: {
						required: true,
						message: "请选择所属用户",
						trigger: "change"
					}
				},
				contacts:{
					index: -1,
					dialog:false,
					form:{
						main:true,
						name:"",
						tel:"",
						add:"",
						data:""
					},
					rules: {
						name: {
							required: true,
							message: "请输入联系人员",
							trigger: "blur"
						},
						tel: {
							required: true,
							message: "请输入联系号码",
							trigger: "blur"
						}
					}
				},
				batch:{
					dialog:false,
					active:"import"
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
				this.$axios.post("customer/record", parm).then(result => {
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
				this.form = this.$lib.extend(true, {}, this.$options.data().form);
				//2.请求数据
				if (id > 0) {
					this.$axios.post("customer/get", {
						id: id
					}).then(result => {
						if (result.state == "success") {
							this.form = result.info;
							this.dialog.show = true; //显示弹层
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
					this.form.frame=this.store.user.frame;//赋值所属组织
					this.form.user=this.store.user.id;//赋值所属用户
					this.dialog.show = true; //显示弹层
				}
			},
			//保存数据
			save() {
				this.$refs["form"].validate(valid => {
					if (valid) {
						this.$axios.post("customer/save", this.form).then(result => {
							if (result.state == "success") {
								this.record(0);
								this.dialog.show = false;
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
					} else {
						this.$message({
							type: "warning",
							message: '表单验证错误,请检查并修正!'
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
					this.$axios.post("customer/del", {
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
			//添加联系资料
			setContacts(index) {
				this.contacts.form=index==-1?Object.assign({},this.$options.data().contacts.form):Object.assign({},this.form.contacts[index]);
				this.contacts.index = index; //转存INDEX
				this.contacts.dialog = true; //显示弹层
			},
			//保存联系资料
			saveContacts() {
				this.$refs["contactsForm"].validate(valid => {
					if (valid) {
						//主联系人|首为为主|新数据为主时处理原数据为否
						if(this.form.contacts.length==0){
							this.contacts.form.main=true;
						}else{
							if(this.contacts.form.main==true){
								for (let key in this.form.contacts) {
									this.form.contacts[key].main=false;
								}
							}
						}
						if (this.contacts.index > -1) {
							this.form.contacts.splice(this.contacts.index, 1, this.contacts.form);
						} else {
							this.form.contacts.push(this.contacts.form);
						}
						this.$message({
							type: "success",
							message: "更新联系资料成功,保存后生效!"
						});
						this.contacts.dialog = false; //关闭弹层
					}
				});
			},
			//删除联系资料
			delContacts(index) {
				let source = this.form.contacts[index].main;
				this.form.contacts.splice(index, 1);
				if(source==true && this.form.contacts.length>0){
					this.form.contacts[0].main=true;
				}
				this.$message({
					type: "success",
					message: "删除联系资料成功,保存后生效!"
				});
			},
			//下载模板
			downTemplate(){
				this.$message({
					type: "success",
					message: "[ 数据模板 ] 下载请求中..."
				});
				setTimeout(() => {
					window.open(this.store.base.cdnSite+"/erp/batch/客户导入模板.xlsx")
				}, 1000);
			},
			//上传模板回调
			importCall(result, file, fileList){
				if (result.state == 'success') {
					this.$lib.updateStore(this);
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
						window.open(this.$base.web+'customer/exports?'+parm)
					}, 1000);
				}
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
</style>
