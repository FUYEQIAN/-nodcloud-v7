<template>
	<div class="frame area">
		<div class="layout">
			<el-button-group>
				<template v-if="$lib.getUserRoot(store.root,'senior','add')">
					<el-button @click="set(0)" >新增</el-button>
				</template>
				<el-button @click="reload" >刷新</el-button>
			</el-button-group>
		</div>
		<el-divider></el-divider>
		<el-table :data="tableData" height="calc(100% - 60px)" :tree-props="{children:'sub'}" row-key="id" border>
			<el-table-column prop="name" label="组织名称" width="200px"></el-table-column>
			<el-table-column prop="sort" label="组织排序" align="center" width="200px"></el-table-column>
			<el-table-column prop="data" label="备注信息" align="center" width="200px"></el-table-column>
			<el-table-column prop="set" label="相关操作" align="center" width="160px">
				<template slot-scope="scope">
					<el-button-group>
						<el-button @click="set(scope.row.id)" size="mini">详情</el-button>
						<template v-if="$lib.getUserRoot(store.root,'senior','del')">
							<el-button @click="del(scope.row.id)" size="mini">删除</el-button>
						</template>
					</el-button-group>
				</template>
			</el-table-column>
		</el-table>
		<el-dialog :visible.sync="dialog" title="详情" width="420px" v-madeDialog>
			<transition name="el-fade-in">
				<template v-if="dialog">
					<el-form :model="form" :rules="rules" ref="form" label-width="80px" >
						<el-form-item label="组织名称" prop="name">
							<el-input placeholder="请输入组织名称" v-model="form.name"></el-input>
						</el-form-item>
						<el-form-item label="所属组织" prop="pid">
							<nodTree v-model="form.pid" :treeData="treeData" placeholder="请选择所属组织"></nodTree>
						</el-form-item>
						<el-form-item label="组织排序" prop="sort">
							<el-input placeholder="请输入组织排序" v-model="form.sort"></el-input>
						</el-form-item>
						<el-form-item label="备注信息">
							<el-input placeholder="请输入备注信息" v-model="form.data"></el-input>
						</el-form-item>
					</el-form>
				</template>
			</transition>
			<span slot="footer" class="dialog-footer">
				<el-button @click="dialog = false" >取消</el-button>
				<template v-if="form.id==0 || $lib.getUserRoot(store.root,'senior','edit')">
					<el-button @click="save" type="primary" >保存</el-button>
				</template>
			</span>
		</el-dialog>
	</div>
</template>
<script>
	import NodTree from "@/components/lib/NodTree";
	export default {
		name: "Frame",
		components: {
			NodTree
		},
		data(){
			return {
				tableData:[],
				dialog: false,
				form:{
					id:0,
					pid:null,
					name:"",
					sort:0,
					data:""
				},
				rules:{
					name:{
						required: true,
						message: "请输入组织名称",
						trigger: "blur"
					},
					pid:{
						required: true,
						message: "请选择所属组织",
						trigger: "change"
					},
					sort: [
						{
							required: true,
							message: "请输入组织排序",
							trigger: "blur"
						},
						{
							validator: (rule,value,callback)=>{
								this.$lib.validate('number',value)?callback():callback(new Error('组织排序不正确'));
							},
							trigger: "blur"
						}
					]
				}
			}
		},
		created() {
			this.record(); //获取数据
		},
		computed: {
			//读取数据中心
			store() {
				return this.$store.state;
			},
			//详情菜单数据
			treeData() {
				return [{
					id: 0,
					name: "默认组织",
					sub: this.tableData
				}];
			}
		},
		methods:{
			//获取数据
			record(){
				this.$axios.post("frame/record").then(result => {
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
					this.$axios.post("frame/get", {id: id}).then(result => {
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
						this.$axios.post("frame/save", this.form).then(result => {
							if (result.state == "success") {
								this.dialog=false;
								this.record(); //获取数据
								this.$lib.updateStore(this);//更新数据中心
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
					this.$axios.post("frame/del", {id: id}).then(result => {
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
	}
	
</script>

<style scoped>
	.layout {
		display: flex;
		justify-content: flex-end;
	}
</style>
