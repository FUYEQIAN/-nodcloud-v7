<template>
	<div class="attribute area">
		<div class="layout">
			<el-popover ref="searchPopover" popper-class="searchPopover" placement="bottom-start">
				<el-form class="searchFrom" ref="searchFrom"  inline>
					<el-form-item>
						<el-input placeholder="请输入属性名称" v-model="searchFrom.name" clearable></el-input>
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
				<template v-if="$lib.getUserRoot(store.root,'assist','add')">
					<el-button @click="set(0)" >新增</el-button>
				</template>
				<el-button @click="reload" >刷新</el-button>
			</el-button-group>
		</div>
		<el-divider></el-divider>
		<el-table :data="tableData" height="calc(100% - 90px)" border>
			<el-table-column prop="name" label="属性名称" align="center" width="200px"></el-table-column>
			<el-table-column prop="sort" label="属性排序" align="center" width="120px"></el-table-column>
			<el-table-column prop="data" label="备注信息 " align="center" width="200px"></el-table-column>
			<el-table-column prop="set" label="相关操作" align="center" width="200px">
				<template slot-scope="scope">
					<el-button-group>
						<el-button @click="set(scope.row.id)" size="mini">详情</el-button>
						<template v-if="$lib.getUserRoot(store.root,'assist','del')">
							<el-button @click="del(scope.row.id)" size="mini">删除</el-button>
						</template>
					</el-button-group>
				</template>
			</el-table-column>
		</el-table>
		<el-pagination class="tablePagination" :current-page.sync="page.current" :total="page.total" :page-size.sync="page.size"
		 :page-sizes="page.sizes" :pager-count="page.count" @size-change="record(1)" @current-change="record(0)" layout="prev,pager,next,jumper,sizes,total">
		</el-pagination>
		<el-dialog :visible.sync="dialog.show" title="详情" width="420px" v-madeDialog>
			<transition name="el-fade-in">
				<template v-if="dialog.show">
					<el-form :model="form" :rules="rules" ref="form" label-width="80px" >
						<el-form-item label="属性名称" prop="name">
							<el-input placeholder="请输入属性名称" v-model="form.name" clearable></el-input>
						</el-form-item>
						<el-form-item label="属性内容">
							<el-input placeholder="请输入属性内容" v-model="dialog.attr" clearable>
								<template slot="append">
									<span @click="addAttr">添加</span>
								</template>
							</el-input>
						</el-form-item>
						<el-table class="attrTable" :data="form.info" size="mini" border>
							<el-table-column prop="name" label="属性内容" align="center" min-width="200px"></el-table-column>
							<el-table-column label="操作" align="center" width="100px">
								<template slot-scope="scope">
									<el-button @click="delAttr(scope.$index)" size="mini">删除</el-button>
								</template>
							</el-table-column>
						</el-table>
						<el-form-item label="属性排序" prop="sort">
							<el-input placeholder="请输入属性排序" v-model="form.sort" clearable></el-input>
						</el-form-item>
						<el-form-item label="备注信息">
							<el-input placeholder="请输入备注信息" v-model="form.data" clearable></el-input>
						</el-form-item>
					</el-form>
				</template>
			</transition>
			<span slot="footer" class="dialog-footer">
				<el-button @click="dialog.show = false" >取消</el-button>
				<template v-if="form.id==0 || $lib.getUserRoot(store.root,'assist','edit')">
					<el-button @click="save" type="primary" >保存</el-button>
				</template>
			</span>
		</el-dialog>
	</div>
</template>
<script>
	export default {
		name: "Attribute",
		data() {
			return {
				searchFrom: {
					name: "",
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
					attr:''
				},
				form: {
					id: 0,
					name: "",
					info:[],
					sort:0,
					data: ""
				},
				rules: {
					name: {
						required: true,
						message: "请输入属性名称",
						trigger: "blur"
					},
					sort: [
						{
							required: true,
							message: "请输入属性排序",
							trigger: "blur"
						},
						{
							validator: (rule,value,callback)=>{
								this.$lib.validate('number',value)?callback():callback(new Error('属性排序不正确'));
							},
							trigger: "blur"
						}
					]
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
				this.$axios.post("attribute/record", parm).then(result => {
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
				this.dialog.attr='';
				this.form = this.$lib.extend(true, {}, this.$options.data().form);
				//2.请求数据
				if (id > 0) {
					this.$axios.post("attribute/get", {
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
					this.dialog.show = true; //显示弹层
				}
			},
			//保存数据
			save() {
				this.$refs["form"].validate(valid => {
					if (valid) {
						if(this.form.info.length==0){
							this.$message({
								type: "warning",
								message: "属性内容表格不可为空!"
							});
						}else{
							this.$axios.post("attribute/save", this.form).then(result => {
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
						}
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
					this.$axios.post("attribute/del", {id: id}).then(result => {
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
			//添加属性内容
			addAttr() {
				let attr=this.dialog.attr;
				if(this.$lib.validate("empty",attr)){
					this.$message({
						type: "warning",
						message: "属性内容不可为空!"
					});
				}else if(this.form.info.findIndex(item=>item.name==attr)>-1){
					this.$message({
						type: "warning",
						message: "属性内容不可重复!"
					});
				}else if(attr.indexOf("|") != -1){
					this.$message({
						type: "warning",
						message: "属性内容不可包含[ | ]保留字符!"
					});
				}else{
					this.form.info.push({name:attr});
					this.dialog.attr="";
				}
			},
			//删除属性内容
			delAttr(index) {
				this.form.info.splice(index,1);
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
	.attrTable {
		margin-bottom: 22px;
	}
</style>
