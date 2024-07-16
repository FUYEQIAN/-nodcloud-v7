<template>
	<div class="goods area">
		<div class="layout">
			<el-popover ref="searchPopover" popper-class="searchPopover" placement="bottom-start">
				<el-form class="searchFrom" ref="searchFrom"  inline>
					<el-form-item>
						<el-input placeholder="请输入商品名称" v-model="searchFrom.name" clearable></el-input>
					</el-form-item>
					<el-form-item>
						<el-input placeholder="请输入商品编号" v-model="searchFrom.number" clearable></el-input>
					</el-form-item>
					<el-form-item>
						<el-input placeholder="请输入商品型号" v-model="searchFrom.spec" clearable></el-input>
					</el-form-item>
					<el-form-item>
						<nodTree v-model="searchFrom.category" :treeData="store.category" placeholder="请选择商品类别"></nodTree>
					</el-form-item>
					<el-form-item>
						<el-select v-model="searchFrom.brand" placeholder="请选择商品品牌" clearable>
							<template v-for="brand in store.sys.brand">
								<el-option :label="brand" :value="brand"></el-option>
							</template>
						  </el-select>
					</el-form-item>
					<el-form-item>
						<el-input placeholder="请输入商品条码" v-model="searchFrom.code" clearable></el-input>
					</el-form-item>
					<el-form-item>
						<el-select v-model="searchFrom.type" placeholder="请选择商品类型" clearable>
							<el-option label="常规商品" :value="1"></el-option>
							<el-option label="服务商品" :value="2"></el-option>
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
			<el-table-column prop="name" label="商品名称" align="center" width="220px"></el-table-column>
			<el-table-column prop="number" label="商品编号" align="center" width="160px"></el-table-column>
			<el-table-column prop="spec" label="规格型号" align="center" width="160px"></el-table-column>
			<el-table-column prop="categoryData.name" label="商品分类" align="center" width="120px"></el-table-column>
			<el-table-column prop="brand" label="商品品牌" align="center" width="120px"></el-table-column>
			<el-table-column prop="extension.unit" label="商品单位" align="center" width="120px"></el-table-column>
			<el-table-column prop="code" label="商品条码" align="center" width="160px"></el-table-column>
			<el-table-column prop="extension.type" label="商品类型" align="center" width="120px"></el-table-column>
			<el-table-column prop="data" label="备注信息 " align="center" min-width="200px"></el-table-column>
			<el-table-column prop="set" label="相关操作" align="center" width="180px" fixed="right">
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
		<el-dialog class="tabsDialog" :visible.sync="dialog.show" title="详情" width="970px" v-madeDialog>
			<transition name="el-fade-in">
				<template v-if="dialog.show">
					<el-form :model="form" :rules="rules" ref="form" label-width="80px"  :inline="true">
						<el-tabs v-model="dialog.active">
							<el-tab-pane label="基础信息" name="base">
								<el-form-item label="商品名称" prop="name">
									<el-input placeholder="请输入商品名称" v-model="form.name" clearable></el-input>
								</el-form-item>
								<el-form-item label="商品编号" prop="number">
									<el-input placeholder="请输入商品编号" v-model="form.number" clearable></el-input>
								</el-form-item>
								<el-form-item label="商品型号">
									<el-input placeholder="请输入商品型号" v-model="form.spec" clearable></el-input>
								</el-form-item>
								<el-form-item label="商品类别" prop="category">
									<template slot="label">
									    <span @click="switchPage('category')">商品类别</span>
									</template>
									<nodTree v-model="form.category" :treeData="store.category" placeholder="请选择商品类别"></nodTree>
								</el-form-item>
								<el-form-item label="商品品牌">
									<template slot="label">
									    <span @click="switchPage('sys')">商品品牌</span>
									</template>
									<el-select v-model="form.brand" placeholder="请选择商品品牌" clearable filterable>
										<template v-for="brand in store.sys.brand">
											<el-option :label="brand" :value="brand"></el-option>
										</template>
									</el-select>
								</el-form-item>
								<el-form-item label="商品单位" prop="unit">
									<template slot="label">
									    <span @click="switchPage('sys')">商品单位</span>
									</template>
									<el-select v-model="form.unit" placeholder="请选择商品单位" @change="unitChange" clearable>
										<template v-for="unit in store.sys.unit">
											<el-option :label="unit" :value="unit"></el-option>
										</template>
										<el-option label="多单位" value="-1"></el-option>
									 </el-select>
								</el-form-item>
								<el-form-item label="采购价格" prop="buy">
									<el-input placeholder="请输入采购价格" v-model="form.buy" clearable></el-input>
								</el-form-item>
								<el-form-item label="销售价格" prop="sell">
									<el-input placeholder="请输入销售价格" v-model="form.sell" clearable></el-input>
								</el-form-item>
								<el-form-item label="商品条码">
									<template slot="label">
									    <span @click="switchPage('code')">商品条码</span>
									</template>
									<el-input placeholder="请输入商品条码" v-model="form.code" clearable></el-input>
								</el-form-item>
								<el-form-item label="商品货位">
									<el-input placeholder="请输入商品货位" v-model="form.location" clearable></el-input>
								</el-form-item>
								<el-form-item label="库存阈值" prop="stock">
									<el-input placeholder="请输入库存阈值" v-model="form.stock" clearable></el-input>
								</el-form-item>
								<el-form-item label="商品类型">
									<el-select v-model="form.type" placeholder="请选择商品类型">
										<el-option label="常规商品" :value="0"></el-option>
										<el-option label="服务商品" :value="1"></el-option>
									</el-select>
								</el-form-item>
								<el-form-item label="备注信息">
									<el-input placeholder="请输入备注信息" v-model="form.data" clearable></el-input>
								</el-form-item>
							</el-tab-pane>
							<el-tab-pane label="辅助属性" name="attr">
								<el-table :data="dialog.attribute" size="mini" border style="margin-bottom: 12px;" >
									<el-table-column prop="name" label="属性名称" width="90px"></el-table-column>
									<el-table-column label="属性内容" min-width="240px">
										<template slot-scope="scope">
											<template v-for="(row,index) in scope.row.info">
												<el-checkbox v-model="dialog.attrChecked['n'+$lib.charUnique(row.name)].checked" class="attrNode" @change="AttrChange">{{row.name}}</el-checkbox>
											</template>
										</template>
									</el-table-column>
								</el-table>
								<el-table :data="form.attr" size="mini" class="gridTable" border>
									<el-table-column prop="name" label="属性名称" align="center" min-width="100px"></el-table-column>
									<el-table-column align="center" width="100px">
										<template slot="header" slot-scope="scope">
											<span>采购价格 <i class="el-icon-sort" @click="attrFill('buy')"></i></span>
										</template>
										<template slot-scope="scope">
											<input type="text" v-model="scope.row.buy" placeholder="采购价格"></input>
										</template>
									</el-table-column>
									<el-table-column align="center" width="100px">
										<template slot="header" slot-scope="scope">
											<span>销售价格 <i class="el-icon-sort" @click="attrFill('sell')"></i></span>
										</template>
										<template slot-scope="scope">
											<input type="text" v-model="scope.row.sell" placeholder="销售价格"></input>
										</template>
									</el-table-column>
									<el-table-column align="center" width="120px">
										<template slot="header" slot-scope="scope">
											<span>条形码 <i class="el-icon-sort" @click="attrFill('code')"></i></span>
										</template>
										<template slot-scope="scope">
											<input type="text" v-model="scope.row.code" placeholder="条形码"></input>
										</template>
									</el-table-column>
								</el-table>
							</el-tab-pane>
							<el-tab-pane label="属性配置" name="dispose" class="dispose formItemInitial">
								<el-collapse v-model="dialog.disposeActive" @change="disposeChange">
									<template v-if="form.unit=='-1'">
										<el-collapse-item title="多单位配置" name="units">
											<el-table :data="form.units" size="mini" class="gridTable" border>
												<el-table-column label="单位名称" align="center" min-width="120px">
													<template slot-scope="scope">
														<input type="text" v-model="scope.row.name" @input="unitNameChange(scope.$index)" placeholder="单位名称"></input>
													</template>
												</el-table-column>
												<el-table-column label="单位数量" align="center" width="120px">
													<template slot-scope="scope">
														<input type="text" v-model="scope.row.nums" placeholder="单位数量"></input>
													</template>
												</el-table-column>
												<el-table-column label="关联单位" align="center" width="120px">
													<template slot-scope="scope">
														<template v-if="scope.$index==0">
															<input type="text" v-model="scope.row.source" placeholder="基本单位"></input>
														</template>
														<template v-else>
															<span>{{scope.row.source}}</span>
														</template>
													</template>
												</el-table-column>
												<el-table-column label="折扣率 (%)" align="center" width="120px">
													<template slot-scope="scope">
														<input type="text" v-model="scope.row.discount" placeholder="折扣率"></input>
													</template>
												</el-table-column>
												<el-table-column align="center" width="120px">
													<template slot="header" slot-scope="scope">
														<span>相关操作</span> <i class="el-icon-circle-plus-outline" @click="addUnit"></i>
													</template>
													<template slot-scope="scope">
														<i class="el-icon-delete" @click="delUnit(scope.$index)"></i>
													</template>
												</el-table-column>
											</el-table>
										</el-collapse-item>
									</template>
									<el-collapse-item title="折扣策略" name="strategy">
										<el-table :data="form.strategy" size="mini" class="gridTable" border>
											<el-table-column label="客户等级" align="center" width="160px">
												<template slot-scope="scope">
													<select v-model="scope.row.grade">
														<template v-for="gride in store.sys.crGrade">
															<option :value="gride">{{gride}}</option>
														</template>
													</select>
												</template>
											</el-table-column>
											<el-table-column label="折扣率 (%)" align="center" width="160px">
												<template slot-scope="scope">
													<input type="text" v-model="scope.row.discount" placeholder="折扣策略"></input>
												</template>
											</el-table-column>
											<el-table-column label="备注信息" align="center" min-width="120px">
												<template slot-scope="scope">
													<input type="text" v-model="scope.row.data" placeholder="备注信息"></input>
												</template>
											</el-table-column>
											<el-table-column align="center" width="120px">
												<template slot="header" slot-scope="scope">
													<span>相关操作</span> <i class="el-icon-circle-plus-outline" @click="addStrategy"></i>
												</template>
												<template slot-scope="scope">
													<i class="el-icon-delete" @click="delStrategy(scope.$index)"></i>
												</template>
											</el-table-column>
										</el-table>
									</el-collapse-item>
									<el-collapse-item name="serial" class="collapseSwitch collapseContentHide">
										<template slot="title">
											<span>序列商品</span><el-switch v-model="form.serial" :disabled="true"></el-switch>
										</template>
									</el-collapse-item>
									<el-collapse-item name="batch" class="collapseSwitch">
										<template slot="title">
											<span>批次商品</span> <el-switch v-model="form.batch" :disabled="true"></el-switch>
										</template>
										<el-form-item label="有效期">
											<el-switch v-model="form.validity" @change="validityChange"></el-switch>
										</el-form-item>
										<el-form-item label="保质期" prop="protect">
											<el-input placeholder="请输入保质期" v-model="form.protect" :disabled="!form.validity" clearable>
												<template slot="suffix">天</template>
											</el-input>
										</el-form-item>
										<el-form-item label="预警阀值" prop="threshold">
											<el-input placeholder="请输入预警阀值" v-model="form.threshold" :disabled="!form.validity" clearable>
												<template slot="suffix">天</template>
											</el-input>
										</el-form-item>
									</el-collapse-item>
								</el-collapse>
							</el-tab-pane>
							<el-tab-pane label="图文详情" name="details" class="formItemInitial">
								<el-form-item label="商品图像">
									<el-upload
										:action="$base.web+'goods/upload'"
										:headers="{Token:$store.state.token}"
										list-type="picture-card"
										:limit="3"
										:file-list="form.imgs"
										:on-success="uploadSuccess"
										:on-remove="uploadRemove"
										:on-preview="uploadPreview"
										:on-exceed="uploadExceed"
									>
										<i class="el-icon-plus"></i>
									</el-upload>
								</el-form-item>
								<el-form-item label="图文详情" class="editorBox" >
									<NodEditor v-model="form.details" ></NodEditor>
								</el-form-item>
							</el-tab-pane>
							<template v-if="store.fields.hasOwnProperty($route.name)">
								<el-tab-pane label="扩展信息" name="more" class="formItemInitial">
									<FieldForm v-model="form.more" rule="goods" prefix="more."></FieldForm>
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
										:action="$base.web+'goods/import'"
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
	import NodEditor from "@/components/lib/NodEditor.vue";
	import FieldForm from "@/components/lib/FieldForm";

	export default {
		name: "Goods",
		components: {
			NodTree,
			NodList,
			NodEditor,
			FieldForm,
		
		},
		data() {
			return {
				searchFrom: {
					name: "",
					number:"",
					spec:"",
					category:null,
					brand:"",
					code:"",
					type:"",
					data: "",
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
					active:'base',
					attribute:[],
					attrChecked:{},
					disposeActive:[],
				},
				form: {
					id: 0,
					name: "",
					number:"",
					spec:"",
					category:null,
					brand:"",
					unit:"",
					buy:"",
					sell:"",
					code:"",
					location:'',
					stock:30,
					type:0,
					data:"",
					imgs:[],
					details:"",
					units:[],
					strategy:[],
					serial:false,
					batch:false,
					validity:false,
					protect:0,
					threshold:0,
					more: {},
					attr:[]
				},
				rules: {
					name: {
						required: true,
						message: "请输入商品名称",
						trigger: "blur"
					},
					number: {
						required: true,
						message: "请输入商品编号",
						trigger: "blur"
					},
					category: {
						required: true,
						message: "请选择商品类别",
						trigger: "change"
					},
					unit: {
						required: true,
						message: "请选择商品单位",
						trigger: "blur"
					},
					buy: [
						{
							required: true,
							message: "请输入采购价格",
							trigger: "blur"
						},
						{
							validator: (rule,value,callback)=>{
								this.$lib.synValidate('money',value)?callback():callback(new Error('采购价格不正确'));
							},
							trigger: "blur"
						}
					],
					sell: [
						{
							required: true,
							message: "请输入销售价格",
							trigger: "blur"
						},
						{
							validator: (rule,value,callback)=>{
								this.$lib.synValidate('money',value)?callback():callback(new Error('销售价格不正确'));
							},
							trigger: "blur"
						}
					],
					
					stock: [
						{
							required: true,
							message: "请输入库存阈值",
							trigger: "blur"
						},
						{
							validator: (rule,value,callback)=>{
								this.$lib.synValidate('nums',value)?callback():callback(new Error('库存阈值不正确'));
							},
							trigger: "blur"
						}
					],
					protect: [
						{
							required: true,
							message: "请输入批次商品 - 保质期",
							trigger: "blur"
						},
						{
							validator: (rule,value,callback)=>{
								this.$lib.validate('number',value)?callback():callback(new Error('批次商品 - 保质期不正确'));
							},
							trigger: "blur"
						}
					],
					threshold: [
						{
							required: true,
							message: "请输入批次商品 - 预警阀值",
							trigger: "blur"
						},
						{
							validator: (rule,value,callback)=>{
								this.$lib.validate('number',value)?callback():callback(new Error('批次商品 - 预警阀值不正确'));
							},
							trigger: "blur"
						}
					]
				},
				batch:{
					dialog:false,
					active:"import"
				},
				
			};
		},
		created() {
			this.getBase();//获取基础数据
			this.record(1); //获取数据
		},
		computed: {
			//读取数据中心
			store() {
				return this.$store.state;
			}
		},
		methods: {
			getBase(){
				this.$axios.post("attribute/select").then(result => {
					if (result.state == "success") {
						let attribute=result.info;
						this.dialog.attribute = result.info;
						//辅助属性选中源ASCII编码处理
						let obj={};
						for (let key in attribute){
							for (let item of attribute[key].info) {
								obj['n'+this.$lib.charUnique(item['name'])]={name:item['name'],group:key,checked:false};
							}
						}
						this.$set(this.dialog,'attrChecked',obj);
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
			//获取数据
			record(page) {
				page==0||(this.page.current=page);
				let parm = Object.assign({
					page: this.page.current,
					limit: this.page.size
				}, this.searchFrom);
				this.$axios.post("goods/record", parm).then(result => {
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
				for (let key in this.dialog.attrChecked) {
					this.dialog.attrChecked[key].checked=false;
				}
				this.dialog.disposeActive=[];
				//2.请求数据
				if (id > 0) {
					this.$axios.post("goods/get", {
						id: id
					}).then(result => {
						if (result.state == "success") {
							this.form = result.info;
							//辅助属性选中处理
							result.info.attr.forEach(attr=>{
								attr.name.split("|").forEach(item=>{
									this.dialog.attrChecked['n'+this.$lib.charUnique(item)].checked=true;
								});
							})
							//属性配置面板展示
							result.info.unit=='-1'&&this.dialog.disposeActive.push('units');
							result.info.strategy.length>0&&this.dialog.disposeActive.push('strategy');
							result.info.serial==true&&this.dialog.disposeActive.push('serial');
							result.info.batch==true&&this.dialog.disposeActive.push('batch');
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
					if (valid){
						//辅助属性判断
						if(this.form.attr.length>0){
							for (let attr of this.form.attr) {
								if(!this.$lib.synValidate('money',attr.buy)){
									this.$message({
										type: "warning",
										message: "辅助属性[ "+attr.name+" ]采购价格不正确!"
									});
									return false;
								}else if(!this.$lib.synValidate('money',attr.sell)){
									this.$message({
										type: "warning",
										message: "辅助属性[ "+attr.name+" ]销售价格不正确!"
									});
									return false;
								}
							}
						}
						//商品类型判断
						if(this.form.type==0){
							//常规商品

							//多单位判断
							if(this.form.unit==-1){
								if(this.form.units.length==0){
									this.$message({
										type: "warning",
										message: "[ 属性配置 - 多单位配置 ]不可为空!"
									});
									return false;
								}else{
									let mate=[this.form.units[0].source];
									for (var i = 0; i < this.form.units.length; i++) {
										if(this.$lib.validate('empty',this.form.units[i].source)){
											this.$message({
												type: "warning",
												message: "多单位配置 - 第"+(i+1)+"行基本单位不可为空!"
											});
											return false;
										}else if(this.$lib.validate('empty',this.form.units[i].name)){
											this.$message({
												type: "warning",
												message: "多单位配置 - 第"+(i+1)+"行单位名称不可为空!"
											});
											return false;

										}else if(this.form.units[i].nums==0 || !this.$lib.synValidate('nums',this.form.units[i].nums)){
											this.$message({
												type: "warning",
												message: "多单位配置 - 第"+(i+1)+"行单位数量不正确!"
											});
											return false;
										}else if(!this.$lib.validate('percentage',this.form.units[i].discount)){
											this.$message({
												type: "warning",
												message: "多单位配置 - 第"+(i+1)+"行折扣率不正确!"
											});
											return false;
										}else{
											mate.push(this.form.units[i].name)
										}
									}
									if(this.$lib.distinct(mate).length!=this.form.units.length+1){
										this.$message({
											type: "warning",
											message: "多单位配置 - 单位名称不可重复!"
										});
										return false;
									}
								}
							}
							//折扣策略
							if(this.form.strategy.length>0){
								let mate=[];
								for (let i = 0; i < this.form.strategy.length; i++) {
									if(!this.$lib.validate('percentage',this.form.strategy[i].discount)){
										this.$message({
											type: "warning",
											message: "折扣策略 - 第"+(i+1)+"行折扣率不正确!"
										});
										return false;
									}else{
										mate.push(this.form.strategy[i].grade);
									}
								}
								if(this.form.strategy.length!=this.$lib.distinct(mate).length){
									this.$message({
										type: "warning",
										message: "折扣策略 - 客户等级不可重复!"
									});
									return false;
								}
							}
							//有效期判断
							if(this.form.validity){
								if(this.$lib.validate('empty',this.form.protect)){
									this.$message({
										type: "warning",
										message: "批次商品 - 保质期不可为空!"
									});
									return false;
								}else if(this.$lib.validate('empty',this.form.threshold)){
									this.$message({
										type: "warning",
										message: "批次商品 - 预警阀值不可为空!"
									});
									return false;
								}else if(this.form.threshold-0>this.form.protect-0){
									this.$message({
										type: "warning",
										message: "批次商品 - 预警阀值不可大于保质期!"
									});
									return false;
								}
							}
						}else{
							//服务商品

							//多单位判断
							if(this.form.unit=='-1'){
								this.$message({
									type: "warning",
									message: "服务商品不可启用多单位!"
								});
								return false;
							}
							//序列判断
							if(this.form.serial){
								this.$message({
									type: "warning",
									message: "服务商品不可启用序列商品!"
								});
								return false;
							}
							//批次判断
							if(this.form.batch){
								this.$message({
									type: "warning",
									message: "服务商品不可启用批次商品!"
								});
								return false;
							}
						}
						this.$axios.post("goods/save", this.form).then(result => {
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
					this.$axios.post("goods/del", {
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
			//单位切换事件
			unitChange(val){
				if(val!='-1'){
					this.form.units=[];
				}
			},
			//辅助属性选择事件
			AttrChange(){
				this.form.attr=[];//初始化属性组合信息
				//获取当前选中属性
				let data={};
				for (let key in this.dialog.attrChecked) {
					if(this.dialog.attrChecked[key].checked){
						let group=this.dialog.attrChecked[key].group;
						if(data.hasOwnProperty(group)){
							data[group].push(this.dialog.attrChecked[key].name);
						}else{
							data[group]=[this.dialog.attrChecked[key].name];
						}
					}
				}
				if(Object.values(data).length>0){
					//笛卡尔积算法组合
					var items = Object.values(data).reduce((last, current) => {
						const array = [];
						last.forEach(par1 => {
							current.forEach(par2 => {
								array.push(par1 + "|" + par2);
							});
						});
						return array;
					});
					//辅助属性组合
					for (let item of items) {
						this.form.attr.push({
							name:item,
							buy:'',
							sell:'',
							code:''
						});
					}
				}
			},
			//批量填充辅助属性内容
			attrFill(field){
				for (let key in this.form.attr) {
					this.form.attr[key][field]=this.form[field];
				}
			},
			//属性配置面板折叠
			disposeChange(){
				this.form.batch=this.dialog.disposeActive.indexOf("batch")==-1?false:true;
				this.form.serial=this.dialog.disposeActive.indexOf("serial")==-1?false:true;
				//批次状态改变事件
				if(!this.form.batch){
					this.form.validity=false;
					this.form.protect=0;
					this.form.threshold=0;
				}
			},
			//添加单位
			addUnit(){
				if(this.form.units.length==0){
					// 首条
					this.form.units.push({name:'',nums:'',source:"",discount:"0"});
				}else{
					// 非首条
					this.form.units.push({name:'',nums:'',source:this.form.units[this.form.units.length-1].name,discount:"0"});
				}
			},
			//删除单位
			delUnit(index){
				this.form.units.splice(index,this.form.units.length-index);
			},
			//单位名称改变事件
			unitNameChange(index){
				if(this.form.units.hasOwnProperty(index+1)){
					this.form.units[index+1].source=this.form.units[index].name;
				}
			},
			//新增价格策略
			addStrategy(){
				this.form.strategy.push({grade:"常规等级",discount:'0',data:''})
			},
			//删除价格策略
			delStrategy(index){
				this.form.strategy.splice(index,1);
			},
			//有效期状态改变事件
			validityChange(){
				if(!this.form.validity){
					this.form.protect=0;
					this.form.threshold=0;
				}
			},
			//商品图像上传成功
			uploadSuccess(result, file, fileList) {
				if (result.state == 'success') {
					this.form.imgs.push({
						name: file.name,
						url: result.info
					});
				} else if (result.state == "error") {
					fileList.splice(fileList.findIndex(obj => obj.uid == file.uid), 1);
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
			//商品图像删除
			uploadRemove(file, fileList) {
				this.form.imgs.splice(this.form.imgs.findIndex(obj => obj.uid == file.uid), 1);
			},
			//商品图像查看
			uploadPreview(file) {
				this.$message({
					type: "success",
					message: "[ " + file.name + " ] 下载请求中..."
				});
				setTimeout(() => {
					window.open(file.url)
				}, 1000);
			},
			//商品图像个数限制
			uploadExceed(files, fileList) {
				this.$message({
					type: "warning",
					message: "商品图像最多上传3张!"
				});
			},
			//下载模板
			downTemplate(){
				this.$message({
					type: "success",
					message: "[ 数据模板 ] 下载请求中..."
				});
				setTimeout(() => {
					window.open(this.store.base.cdnSite+"/erp/batch/商品导入模板.xlsx")
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
						window.open(this.$base.web+'goods/exports?'+parm)
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
			//切换页面
			switchPage(key) {
			    this.$bus.emit('switchPage', key, true); //切换页面
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
	.formItemInitial >>> .el-form-item{
		display: initial;
	}
	.editorBox >>> .el-form-item__content{
		width: calc(100% - 80px)
	}
	.unitsName{
		line-height: 35px;
	}
	.attrNode{
		width: 25%;
		margin-right:0;
	}
	.collapseSwitch >>> .el-collapse-item__arrow{
		display: none;
	}
	.collapseSwitch .el-switch{
		cursor: pointer;
		margin: 0 8px 0 auto;
	}
	.collapseSwitch .el-switch >>>.el-switch__core{
		cursor: pointer;
	}
	.collapseContentHide >>> .el-collapse-item__content{
		display: none;
	}
	.collapseContentHide >>> .el-collapse-item__wrap{
		border-bottom:none;
	}
	.collapseContentHide >>> .el-collapse-item__header.is-active{
		border-bottom-color:#EBEEF5;
	}
</style>
