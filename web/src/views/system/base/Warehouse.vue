<template>
	<div class="warehouse area">
		<div class="layout">
			<el-popover ref="searchPopover" popper-class="searchPopover" placement="bottom-start">
				<el-form class="searchFrom" ref="searchFrom"  inline>
					<el-form-item>
						<el-input placeholder="请输入仓库名称" v-model="searchFrom.name" clearable></el-input>
					</el-form-item>
					<el-form-item>
						<el-input placeholder="请输入仓库编号" v-model="searchFrom.number	" clearable></el-input>
					</el-form-item>
					<el-form-item>
						<el-input placeholder="请输入联系人员" v-model="searchFrom.contacts" clearable></el-input>
					</el-form-item>
					<el-form-item>
						<el-input placeholder="请输入联系电话" v-model="searchFrom.tel" clearable></el-input>
					</el-form-item>
					<el-form-item>
						<el-input placeholder="请输入仓库地址" v-model="searchFrom.add" clearable></el-input>
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
				<template v-if="$lib.getUserRoot(store.root,'base','add')">
					<el-button @click="set(0)" >新增</el-button>
				</template>
				<el-button @click="reload" >刷新</el-button>
			</el-button-group>
		</div>
		<el-divider></el-divider>
		<el-table :data="tableData" height="calc(100% - 90px)" border>
			<el-table-column prop="name" label="仓库名称" align="center" width="220px"></el-table-column>
			<el-table-column prop="number" label="仓库编号" align="center" width="160px"></el-table-column>
			<el-table-column prop="frameData.name" label="所属组织" align="center" width="160px"></el-table-column>
			<el-table-column prop="data" label="备注信息 " align="center" width="200px"></el-table-column>
			<el-table-column prop="set" label="相关操作" align="center"  width="190px">
				<template slot-scope="scope">
					<el-button-group>
						<el-button @click="set(scope.row.id)" size="mini">详情</el-button>
						<template v-if="$lib.getUserRoot(store.root,'base','del')">
							<el-button @click="del(scope.row.id)" size="mini">删除</el-button>
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
						<el-form-item label="仓库名称" prop="name">
							<el-input placeholder="请输入仓库名称" v-model="form.name" clearable></el-input>
						</el-form-item>
						<el-form-item label="仓库编号" prop="number">
							<el-input placeholder="请输入仓库编号" v-model="form.number" clearable></el-input>
						</el-form-item>
						<el-form-item label="所属组织" prop="frame">
							<nodTree v-model="form.frame" :treeData="store.frame" placeholder="请选择所属组织"></nodTree>
						</el-form-item>
						<el-form-item label="联系人员">
							<el-input placeholder="请输入联系人员" v-model="form.contacts" clearable></el-input>
						</el-form-item>
						<el-form-item label="联系电话">
							<el-input placeholder="请输入联系电话" v-model="form.tel" clearable></el-input>
						</el-form-item>
						<el-form-item label="仓库地址">
							<el-input placeholder="请输入仓库地址" v-model="form.add" clearable></el-input>
						</el-form-item>
						<el-form-item label="备注信息">
							<el-input placeholder="请输入备注信息" v-model="form.data" clearable></el-input>
						</el-form-item>
					</el-form>
				</template>
			</transition>
			<span slot="footer" class="dialog-footer">
				<el-button @click="dialog = false" >取消</el-button>
				<template v-if="form.id==0 || $lib.getUserRoot(store.root,'base','edit')">
					<el-button @click="save" type="primary" >保存</el-button>
				</template>
			</span>
		</el-dialog>
	</div>
</template>
<script>
	import NodTree from "@/components/lib/NodTree";
	export default {
		name: "Warehouse",
		components: {
			NodTree,
		},
		data() {
			return {
				searchFrom: {
					name: "",
					number: "",
					contacts: "",
					tel: "",
					add: "",
					data: ""
				},
				tableData: [],
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
					name:"",
					number: "",
					frame:null,
					contacts: "",
					tel: "",
					add: "",
					data: ""
				},
				rules: {
					name: {
						required: true,
						message: "请输入仓库名称",
						trigger: "blur"
					},
					number: {
						required: true,
						message: "请输入仓库编号",
						trigger: "blur"
					},
					frame: {
						required: true,
						message: "请选择所属组织",
						trigger: "change"
					}
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
				this.$axios.post("warehouse/record", parm).then(result => {
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
					this.$axios.post("warehouse/get", {
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
						this.$axios.post("warehouse/save", this.form).then(result => {
							if (result.state == "success") {
								this.record(0);
								this.dialog = false;
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
					this.$axios.post("warehouse/del", {
						id: id
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
