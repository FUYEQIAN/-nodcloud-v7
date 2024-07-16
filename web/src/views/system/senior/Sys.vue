<template>
	<div class="sys area">
		<el-form :model="form" :rules="rules" ref="form" label-width="90px"  class="formAdapt">
			<el-tabs tab-position="left" width="200px">
				<el-tab-pane label="基础信息">
					<el-form-item label="系统名称" prop="name">
						<el-input placeholder="请输入系统名称" v-model="form.name"></el-input>
					</el-form-item>
					<el-form-item label="公司名称" prop="company">
						<el-input placeholder="请输入公司名称" v-model="form.company"></el-input>
					</el-form-item>
					<el-form-item label="备案信息">
						<el-input placeholder="请输入备案信息" v-model="form.icp"></el-input>
					</el-form-item>
					<el-form-item label="公告信息">
						<el-input type="textarea" :rows="3" placeholder="公告信息" v-model="form.notice"></el-input>
					</el-form-item>
				</el-tab-pane>
				<el-tab-pane label="功能参数">
					<table class="funTable">
						<colgroup>
							<col width="160">
							<col width="120">
							<col>
						</colgroup>
						<tr>
							<th>功能名称</th>
							<th>功能配置</th>
							<th>功能说明</th>
						</tr>
						<tr>
							<td>自动审核</td>
							<td>
								<el-switch v-model="form.fun.examine"></el-switch>
							</td>
							<td>启用后单据模块将自动审核</td>
						</tr>
						<tr>
							<td>启用税金</td>
							<td>
								<el-switch v-model="form.fun.tax" @change="taxChange"></el-switch>
							</td>
							<td>
								<span>增值税税率</span>
								<input type="text" v-model="form.fun.rate" class="funTabRate" :disabled="!form.fun.tax">
								<span>%</span>
							</td>
						</tr>
						<tr>
							<td>允许负库存</td>
							<td>
								<el-switch v-model="form.fun.overflow"></el-switch>
							</td>
							<td>启用后商品库存将允许为负数</td>
						</tr>
						<tr>
							<td>计价方法</td>
							<td>
								<select v-model="form.fun.valuation" class="fluid">
									<option value="base">基础计价</option>
									<option value="ma">移动平均</option>
									<option value="fifo">先进先出</option>
								</select>
							</td>
							<td>根据实际情况选择相应计价方法</td>
						</tr>
						<tr>
							<td>核算类型</td>
							<td>
								<select v-model="form.fun.branch" class="fluid">
									<option :value="0">总仓核算</option>
									<option :value="1">分仓核算</option>
								</select>
							</td>
							<td>根据实际情况选择相应核算类型</td>
						</tr>
						<tr>
							<td>成本规则</td>
							<td>
								<select v-model="form.fun.rule" class="fluid">
									<option value="def">结存结余</option>
									<option value="attr">辅助属性</option>
									<option value="batch">批次日期</option>
									<option value="aab">属性批次</option>
								</select>
							</td>
							<td>根据实际情况选择相应成本规则</td>
						</tr>
						<tr>
							<td>数量位数</td>
							<td>
								<select v-model="form.fun.digit.nums" class="fluid">
									<option :value="0">0位小数</option>
									<option :value="1">1位小数</option>
									<option :value="2">2位小数</option>
									<option :value="3">3位小数</option>
									<option :value="4">4位小数</option>
								</select>
							</td>
							<td>全局数量小数位数范围</td>
						</tr>
						<tr>
							<td>金额位数</td>
							<td>
								<select v-model="form.fun.digit.money" class="fluid">
									<option :value="2">2位小数</option>
									<option :value="3">3位小数</option>
									<option :value="4">4位小数</option>
								</select>
							</td>
							<td>全局金额小数位数范围</td>
						</tr>
						<tr>
							<td>报表天数</td>
							<td>
								<input v-model="form.fun.days" class="fluid" />
							</td>
							<td>全局报表统计天数范围</td>
						</tr>
					</table>
				</el-tab-pane>
				<el-tab-pane label="物流配置">
					<el-table :data="form.logistics" size="mini" class="gridTable" style="width:561px" border>
						<el-table-column label="物流标识" align="center" width="160px">
							<template slot-scope="scope">
								<input type="text" v-model="scope.row.key" placeholder="物流标识"></input>
							</template>
						</el-table-column>
						<el-table-column label="物流名称" align="center" width="160px">
							<template slot-scope="scope">
								<input type="text" v-model="scope.row.name" placeholder="物流名称"></input>
							</template>
						</el-table-column>
						<el-table-column label="启用状态" align="center" width="120px">
							<template slot-scope="scope">
								<el-switch v-model="scope.row.enable" :disabled="scope.row.key=='auto'"></el-switch>
							</template>
						</el-table-column>
						<el-table-column align="center" width="120px">
							<template slot="header" slot-scope="scope">
								<span>相关操作</span> <i class="el-icon-circle-plus-outline" @click="addLogistics"></i>
							</template>
							<template slot-scope="scope">
								<i v-if="scope.row.key!='auto'" class="el-icon-delete" @click="delLogistics(scope.$index)"></i>
							</template>
						</el-table-column>
					</el-table>
				</el-tab-pane>
				<el-tab-pane label="商品品牌">
					<el-form-item label="商品品牌" >
						<el-input placeholder="请输入品牌名称" v-model="text.brand" clearable>
							<template slot="append"><i class="el-icon-circle-plus-outline" @click="addBrand"></i></template>
						</el-input>	
						<ul v-if="form.brand.length>0" class="list">
							<li v-for="(brand,index) in form.brand" :key="index">{{brand}}<i class="el-icon-remove-outline" @click="delBrand(index)"></i></li>
						</ul>
					</el-form-item>
				</el-tab-pane>
				<el-tab-pane label="计量单位">
					<el-form-item label="计量单位">
						<el-input placeholder="请输入计量单位名称" v-model="text.unit" clearable>
							<template slot="append"><i class="el-icon-circle-plus-outline" @click="addUnit"></i></template>
						</el-input>	
						<ul v-if="form.unit.length>0" class="list">
							<li v-for="(unit,index) in form.unit" :key="index">{{unit}}<i class="el-icon-remove-outline" @click="delUnit(index)"></i></li>
						</ul>
					</el-form-item>
				</el-tab-pane>
				<el-tab-pane label="客户类别">
					<el-form-item label="客户类别">
						<el-input placeholder="请输入客户类别名称" v-model="text.crCategory" clearable>
							<template slot="append"><i class="el-icon-circle-plus-outline" @click="addCrCategory"></i></template>
						</el-input>	
						<ul v-if="form.crCategory.length>0" class="list">
							<li v-for="(category,index) in form.crCategory" :key="index">{{category}}<i v-if="category!='常规类别'" class="el-icon-remove-outline" @click="delCrCategory(index)"></i></li>
						</ul>
					</el-form-item>
				</el-tab-pane>
				<el-tab-pane label="客户等级">
					<el-form-item label="客户等级">
						<el-input placeholder="请输入客户等级名称" v-model="text.crGrade" clearable>
							<template slot="append"><i class="el-icon-circle-plus-outline" @click="addCrGrade"></i></template>
						</el-input>	
						<ul v-if="form.crGrade.length>0" class="list">
							<li v-for="(grade,index) in form.crGrade" :key="index">{{grade}}<i v-if="grade!='常规等级'" class="el-icon-remove-outline" @click="delCrGrade(index)"></i></li>
						</ul>
					</el-form-item>
				</el-tab-pane>
				<el-tab-pane label="供应商类别">
					<el-form-item label="供应商类别">
						<el-input placeholder="请输入供应商类别名称" v-model="text.srCategory" clearable>
							<template slot="append"><i class="el-icon-circle-plus-outline" @click="addSrCategory"></i></template>
						</el-input>	
						<ul v-if="form.srCategory.length>0" class="list">
							<li v-for="(category,index) in form.srCategory" :key="index">{{category}}<i v-if="category!='常规类别'" class="el-icon-remove-outline" @click="delSrCategory(index)"></i></li>
						</ul>
					</el-form-item>
				</el-tab-pane>
			</el-tabs>
		</el-form>
		<template v-if="$lib.getUserRoot(store.root,'senior','edit')">
			<div class="layerBtn">
				<el-button type="info"  @click="save">保存设置</el-button>
			</div>
		</template>
	</div>
</template>
<script>
	export default{
		name: "Sys",
		data() {
			return {
				text:{
					payment:{
						name:'',
						key:''
					},
					brand:'',
					unit:'',
					crCategory:'',
					crGrade:'',
					srCategory:''
				},
				form: {
					name:'',
					icp:'',
					notice:'',
					company:'',
					fun:{
						examine:true,
						tax:false,
						rate:'0',
						overflow:false,
						valuation:'base',
						branch:0,
						rule:'def',
						digit:{
							nums:0,
							money:2
						},
						days:30
					},
					logistics:[
						{key:'auto',name:'自动识别',enable:true},
					],
					serial:false,
					batch:false,
					brand:[],
					unit:[],
					crCategory:[],
					crGrade:[],
					srCategory:[]
				},
				rules: {
					name: {
						required: true,
						message: "请输入系统名称",
						trigger: "blur"
					},
					company: {
						required: true,
						message: "请输入公司名称",
						trigger: "blur"
					},
					"vend.ratio":[
						{
							required: true,
							message: "请输入积分比例",
							trigger: "blur"
						},
						{
							validator: (rule,value,callback)=>{
								this.$lib.validate('integer',value)?callback():callback(new Error('积分比例不正确'));
							},
							trigger: "blur"
						}
					],
					"vend.barter":[
						{
							required: true,
							message: "请输入兑换比例",
							trigger: "blur"
						},
						{
							validator: (rule,value,callback)=>{
								this.$lib.validate('integer',value)?callback():callback(new Error('兑换比例不正确'));
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
			//读取数据中心
			store() {
				return this.$store.state;
			} 
		},
		methods: {
			//获取数据
			record() {
				this.$axios.post("sys/record").then(result => {
					if (result.state == "success") {
						this.form = result.info;
					} else if (result.state == "error") {
						this.$message({type: "warning",message: result.info});
					} else {
						this.$message({type: "error",message: "[ ERROR ] 服务器响应超时!"});
					}
				});
			},
			//保存表单数据
			save(){
				this.$refs["form"].validate(valid => {
					if(valid){
						//功能参数
						if(!this.$lib.validate('percentage',this.form.fun.rate)){
							this.$message({type: "warning",message: "增值税税率不正确!"});
							return false;
						}
						if(!this.$lib.validate('integer',this.form.fun.days)){
							this.$message({type: "warning",message: "报表天数不正确!"});
							return false;
						}
						//物流配置
						for (let item of this.form.logistics) {
							if(this.$lib.validate('empty',item.key) || this.$lib.validate('empty',item.name)){
								this.$message({type: "warning",message: "物流配置不正确!"});
								return false;
							}
						}
						this.$axios.post("sys/save",this.form).then(result => {
							if (result.state == "success"){
								this.$lib.updateStore(this);
								this.$message({type: "success",message: "系统参数保存成功!"});
							} else if (result.state == "error") {
								this.$message({type: "warning",message: result.info});
							} else {
								this.$message({type: "error",message: "[ ERROR ] 服务器响应超时!"});
							}
						});
					}else{
						this.$message({type: "warning",message: '表单验证错误,请检查并修正!'});
					}
				});
			},
			//税金状态改变
			taxChange(val){
				if(!val){
					this.form.fun.rate=0;
				}
			},
			//新增物流信息
			addLogistics(){
				this.form.logistics.push({key:'',name:'',enable:false});
			},
			//删除物流信息
			delLogistics(index){
				this.form.logistics.splice(index,1);
			},
			//添加商品品牌
			addBrand() {
				if(this.$lib.validate('empty',this.text.brand)){
					this.$message({type:"warning",message:"商品品牌名称不可为空!"});
				}else{
					this.form.brand.push(this.text.brand);
					this.text.brand="";
				}
			},
			//删除商品品牌
			delBrand(index) {
				this.form.brand.splice(index,1);
			},
			//添加计量单位
			addUnit() {
				if(this.$lib.validate('empty',this.text.unit)){
					this.$message({type:"warning",message:"计量单位名称不可为空!"});
				}else if(this.text.unit=='多单位' || this.text.unit=='-1'){
					this.$message({type:"warning",message:"计量单位[ 多单位 ]为保留文本!"});
				}else{
					this.form.unit.push(this.text.unit);
					this.text.unit="";
				}
			},
			//删除计量单位
			delUnit(index) {
				this.form.unit.splice(index,1);
			},
			//添加客户类别
			addCrCategory() {
				if(this.$lib.validate('empty',this.text.crCategory)){
					this.$message({type:"warning",message:"客户类别名称不可为空!"});
				}else if(this.text.crCategory=='常规类别'){
					this.$message({type:"warning",message:"客户类别[ 常规类别 ]为保留文本!"});
				}else{
					this.form.crCategory.push(this.text.crCategory);
					this.text.crCategory="";
				}
			},
			//删除客户类别
			delCrCategory(index) {
				this.form.crCategory.splice(index,1);
			},
			//添加客户等级
			addCrGrade() {
				if(this.$lib.validate('empty',this.text.crGrade)){
					this.$message({type:"warning",message:"客户等级名称不可为空!"});
				}else if(this.text.crGrade=='常规等级'){
					this.$message({type:"warning",message:"客户等级[ 常规等级 ]为保留文本!"});
				}else{
					this.form.crGrade.push(this.text.crGrade);
					this.text.crGrade="";
				}
			},
			//删除客户等级
			delCrGrade(index) {
				this.form.crGrade.splice(index,1);
			},
			//添加供应商类别
			addSrCategory() {
				if(this.$lib.validate('empty',this.text.srCategory)){
					this.$message({type:"warning",message:"供应商类别名称不可为空!"});
				}else if(this.text.srCategory=='常规类别'){
					this.$message({type:"warning",message:"供应商类别[ 常规类别 ]为保留文本!"});
				}else{
					this.form.srCategory.push(this.text.srCategory);
					this.text.srCategory="";
				}
			},
			//删除供应商类别
			delSrCategory(index) {
				this.form.srCategory.splice(index,1);
			}
		}		
	}
</script>
<style>
	.funTabRate{
		padding:2px;
		margin:0 6px;
		width: 32px;
	}
</style>
<style scoped>
	.sys{
		position: relative;
	}
	.el-form,.el-tabs{
		height: 100%;
	}
	.el-tabs >>> .el-tabs__content{
		height: 100%;
		overflow: auto;
	}
	.funTable{
		color: #606266;
		min-width: 560px;
		border-collapse: collapse;
	}
	.funTable th,.funTable td{
		line-height: 36px;
		text-align: center;
		border: 1px solid #EBEEF5;
	}
	.funTable tr td:nth-child(3){
		text-align: left;
		padding-left: 12px;
	}
	.funTable .fluid{
		width: 100%;
		height: 24px;
		border: none;
		text-align:center;
		text-align-last:center
	}
	.funTable input,.funTable select{
		outline: none;
	}
	.list{
		width: 360px;
		margin-top: 6px;
		list-style:none;
		border-radius: 4px;
		border: 1px solid #dcdfe6;
	}
	.list li{
		color: #606266;
		position: relative;
		padding: 0 24px;
	}
	.list li:nth-child(2n+1){
		background:#f4f4f5;
	}
	.list li i{
		position: absolute;
		right: 6px;
		bottom: 12px;
	}
</style>