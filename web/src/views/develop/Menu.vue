<template>
	<div class="menu area">
		<div class="layout">
			<el-button-group>
				<el-button @click="set(0)">新增</el-button>
				<el-button @click="reload">刷新</el-button>
			</el-button-group>
		</div>
		<el-divider></el-divider>
		<el-table :data="tableData" height="calc(100% - 60px)" :tree-props="{children:'sub'}" row-key="id" border>
			<el-table-column prop="name" label="菜单名称" width="220px"></el-table-column>
			<el-table-column prop="key" label="菜单标识" align="center" width="120px"></el-table-column>
			<el-table-column prop="extension.model" label="菜单类型" align="center" width="120px"></el-table-column>
			<el-table-column prop="extension.type" label="菜单类型" align="center" width="120px"></el-table-column>
			<el-table-column prop="resource" label="菜单地址" align="center" width="160px"></el-table-column>
			<el-table-column prop="sort" label="菜单排序" align="center" width="90px"></el-table-column>
			<el-table-column prop="data" label="备注信息" align="center" width="160px"></el-table-column>
			<el-table-column prop="set" label="相关操作" align="center" width="160px">
				<template slot-scope="scope">
					<el-button-group>
						<el-button @click="set(scope.row.id)" size="mini">详情</el-button>
						<el-button @click="del(scope.row.id)" size="mini">删除</el-button>
					</el-button-group>
				</template>
			</el-table-column>
		</el-table>
		<el-dialog :visible.sync="dialog" title="详情" width="420px" v-madeDialog>
			<transition name="el-fade-in">
				<template v-if="dialog">
					<el-form :model="form" :rules="rules" ref="form" label-width="80px" >
						<el-form-item label="菜单名称" prop="name">
							<el-input placeholder="请输入菜单名称" v-model="form.name"></el-input>
						</el-form-item>
						<el-form-item label="菜单标识" prop="key">
							<el-input placeholder="请输入菜单标识" v-model="form.key"></el-input>
						</el-form-item>
						<el-form-item label="所属菜单" prop="pid">
							<nodTree v-model="form.pid" :treeData="treeData" placeholder="请选择所属菜单"></nodTree>
						</el-form-item>
						<el-form-item label="菜单模式">
							<el-select placeholder="请选择菜单模式" style="width:100%" v-model="form.model">
								<el-option label="标签模式" :value="0"></el-option>
								<el-option label="新页模式" :value="1"></el-option>
							</el-select>
						</el-form-item>
						<el-form-item label="菜单类型">
							<el-select placeholder="请选择菜单类型" style="width:100%" v-model="form.type">
								<el-option label="独立菜单" :value="0"></el-option>
								<el-option label="附属菜单" :value="1"></el-option>
							</el-select>
						</el-form-item>
						<el-form-item label="菜单地址">
							<el-input placeholder="请输入菜单地址" v-model="form.resource"></el-input>
						</el-form-item>
						<el-form-item label="菜单排序" prop="sort">
							<el-input placeholder="请输入菜单排序" v-model="form.sort"></el-input>
						</el-form-item>
						<el-form-item label="菜单图标">
							<el-input placeholder="请输入菜单图标" v-model="form.ico"></el-input>
						</el-form-item>
						<el-form-item label="权限标识">
							<el-input placeholder="请输入权限标识" v-model="form.root"></el-input>
						</el-form-item>
						<el-form-item label="备注信息">
							<el-input placeholder="请输入备注信息" v-model="form.data"></el-input>
						</el-form-item>
					</el-form>
				</template>
			</transition>
			<span slot="footer" class="dialog-footer">
				<el-button @click="dialog = false" >取消</el-button>
				<el-button @click="save" type="primary" >保存</el-button>
			</span>
		</el-dialog>
	</div>
</template>
<script>
	import NodTree from "@/components/lib/NodTree";
	export default {
		name: "Menu",
		components: {
			NodTree
		},
		data() {
			return {
				tableData: [],
				dialog: false,
				form: {
					id: 0,
					name: "",
					key: "",
					pid: null,
					model:0,
					type: 0,
					resource: "",
					sort: 0,
					ico: "",
					root: "",
					data: ""
				},
				rules: {
					name: {
						required: true,
						message: "请输入菜单名称",
						trigger: "blur"
					},
					key: {
						required: true,
						message: "请输入菜单标识",
						trigger: "blur"
					},
					pid: {
						required: true,
						message: "请选择所属菜单",
						trigger: "change"
					},
					sort: [
						{
							required:true,
							message:'请输入菜单排序',
							trigger:'blur'	
						},
						{
							validator:(rule,value,callback)=>{
								this.$lib.validate('number',value)?callback():callback(new Error('菜单排序不正确'));
							},
							trigger: "blur"
						}
					]
				}
			};
		},
		created() {
			this.record(); //获取数据
		},
		computed: {
			//详情菜单数据
			treeData() {
				return [{
					id: 0,
					name: "默认菜单",
					sub: this.tableData
				}];
			}
		},
		methods: {
			//获取数据
			record() {
				this.$axios.post("menu/record").then(result => {
					if (result.state == "success") {
						this.tableData = result.info;
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
				//1.初始化操作
				this.form=Object.assign({},this.$options.data().form);
				//2.请求数据
				if (id > 0) {
					this.$axios.post("menu/get", {id: id}).then(result => {
						if (result.state == "success") {
							this.form = result.info;
							this.dialog = true;//显示弹层
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
				}else{
					this.dialog = true;//显示弹层
				}
			},
			//保存数据
			save() {
				this.$refs["form"].validate(valid => {
					if (valid) {
						this.$axios.post("menu/save", this.form).then(result => {
							if (result.state == "success") {
								this.record();
								this.dialog=false;
								this.$lib.updateStore(this);
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
			del(id) {
				this.$confirm("您确定要删除该数据吗?", "提示", {
					confirmButtonText: "确定",
					cancelButtonText: "取消",
					type: "warning"
				}).then(() => {
					this.$axios.post("menu/del", {id: id}).then(result => {
						if (result.state == "success") {
							this.record(); //获取数据
							this.$lib.updateStore(this);//更新数据中心
							this.$message({type: "success",message: "删除成功!"});
						} else if (result.state == "error") {
							this.$message({type: "warning",message: result.info});
						} else {
							this.$message({type: "error",message: "[ ERROR ] 服务器响应超时!"});
						}
					});
				}).catch(()=>{});
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
		justify-content: flex-end;
	}
</style>
