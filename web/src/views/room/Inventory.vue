<template>
	<div class="inventory area">
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
						<el-select v-model="searchFrom.warehouse" placeholder="请选择仓库信息" multiple collapse-tags>
							<template v-for="warehouse in store.warehouse">
								<el-option :label="warehouse.name" :value="warehouse.id"></el-option>
							</template>
						</el-select>
					</el-form-item>
					<el-form-item>
						<el-input placeholder="请输入商品备注" v-model="searchFrom.data" clearable></el-input>
					</el-form-item>
					<el-divider></el-divider>
					<el-button class="searchBtn" icon="el-icon-search" @click="record(1)" ></el-button>
				</el-form>
				<el-button slot="reference" icon="el-icon-more"></el-button>
			</el-popover>
			<el-button-group>
				<el-button @click="build">生成盘点单</el-button>
				<el-button @click="exports">导出</el-button>
				<el-button @click="reload">刷新</el-button>
			</el-button-group>
		</div>
		<el-divider></el-divider>
		<el-table
			class="gridTable"
			:data="tableData"
			:tree-props="{children: 'attr'}"
			row-key="key"
			height="calc(100% - 90px)"
			border
			v-madeTable
		>
			<el-table-column prop="name" label="商品名称" align="left" width="220px" fixed="left"></el-table-column>
			<el-table-column prop="summary" label="库存数量" align="center" width="120px"></el-table-column>
			<el-table-column label="仓库" align="center">
				<template v-for="column in tableColumn"  >
					<el-table-column :label="column.name" align="center" :key="column.key">
						<el-table-column :prop="'record.'+(column.key)+'.basisAlias'" label="库存数" align="center" width="90px"></el-table-column>
						<el-table-column label="盘点数" align="center" :width="columnWidth">
							<template slot-scope="scope">
								<template v-if="scope.row.hasOwnProperty('attr') && scope.row.attr.length>0">
									<span>-</span>
								</template>
								<template v-else>
									<template v-if="Array.isArray(scope.row.record[column.key].enter)">
										<div class="unitGroup">
											<template v-for="item in scope.row.record[column.key].enter">
												<div class="unitItem">
													<input type="text" v-model="item.nums" @input="unitHandle(false)"></input>
													<span @click="unitNameClick">{{item.name}}</span>
												</div>
											</template>
										</div>
									</template>
									<template v-else>
										<input type="text" v-model="scope.row.record[column.key].enter" @input="unitHandle(false)"></input>
									</template>
								</template>
							</template>
						</el-table-column>
						<el-table-column label="盘盈盘亏" align="center" :width="columnWidth">
							<template slot-scope="scope">
								<template v-if="scope.row.hasOwnProperty('attr') && scope.row.attr.length>0">
									<span>-</span>
								</template>
								<template v-else>
									{{scope.row.record[column.key].diffAlias}}
								</template>
							</template>
						</el-table-column>
					</el-table-column>
				</template>
			</el-table-column>
			<el-table-column prop="number" label="商品编号" align="center" width="160px"></el-table-column>
			<el-table-column prop="spec" label="规格型号" align="center" width="160px"></el-table-column>
			<el-table-column prop="categoryData.name" label="商品分类" align="center" width="120px"></el-table-column>
			<el-table-column prop="brand" label="商品品牌" align="center" width="120px"></el-table-column>
			<el-table-column prop="extension.unit" label="商品单位" align="center" width="120px"></el-table-column>
			<el-table-column prop="code" label="商品条码" align="center" width="160px"></el-table-column>
			<el-table-column prop="data" label="商品备注" align="center" min-width="160px"></el-table-column>
		</el-table>
		<el-pagination class="tablePagination" :current-page.sync="page.current" :total="page.total" :page-size.sync="page.size"
		 :page-sizes="page.sizes" :pager-count="page.count" @size-change="record(1)" @current-change="record(0)" layout="prev,pager,next,jumper,sizes,total">
		</el-pagination>
		<el-dialog :visible.sync="dialog" title="盘点库存" width="420px" v-madeDialog>
			<transition name="el-fade-in">
				<template v-if="dialog">
					<div class="exportGroup">
						<div class="item" @click="buildEntry">
							<i class="el-icon-document"></i>	
							<p>盘盈单</p>
						</div>
						<div class="item" @click="buildExtry">
							<i class="el-icon-document"></i>	
							<p>盘亏单</p>
						</div>
					</div>
				</template>
			</transition>
		</el-dialog>
	</div>
</template>
<script>
	import NodTree from "@/components/lib/NodTree";
	export default {
		name: "Inventory",
		components: {
			NodTree
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
					warehouse:[],
					data:''
				},
				tableData: [],
				tableColumn:[],
				page: {
					current: 1,
					total: 0,
					size: 30,
					sizes: [30, 60, 90, 150, 300],
					count: 5
				},
				dialog:false,
				unitLength:0,
				summary:[]
			};
		},
		created() {
			this.record(1); //获取数据
		},
		computed: {
			//读取数据中心
			store() {
				return this.$store.state;
			},
			columnWidth(){
				let width=this.unitLength==0?90:this.unitLength*50;
				return width+"px";
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
				this.$axios.post("inventory/record", parm).then(result => {
					if (result.state == "success") {
						//匹配单位长度
						this.unitLength=0;
						for (let item of result.info) {
							if(item.unit=='-1' && item.units.length+1>this.unitLength){
								this.unitLength=item.units.length+1;
							}
						}
						this.tableData = result.info;
						this.tableColumn=result.column;
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
			//导出数据
			exports(){
				this.$message({
					type: "success",
					message: "[ 导出数据 ] 请求中..."
				});
				let parm=this.$lib.objToParm(this.searchFrom,true);
				setTimeout(() => {
					window.open(this.$base.web+'inventory/exports?'+parm)
				}, 1000);
			},
			//单位处理
			unitHandle(){
				let effect=true;
				//结构数据
				let rows=[];
				for (let item of this.tableData) {
					if(item.hasOwnProperty('attr') && item.attr.length>0){
						for (let attr of item.attr) {
							rows.push(attr);
						}
					}else{
						rows.push(item);
					}
				}
				//数据处理
				this.summary=[];
				for (let row of rows) {
					let goods = row.hasOwnProperty('pid')?this.tableData.find(item=>item.id==row.pid):row;
					loop:for (let key in row.record) {
						let obj=row.record[key];
						//单位判断
						if(Array.isArray(obj.enter)){
							let nums=0;
							for (let node of obj.enter) {
								if(!this.$lib.validate('empty',node.nums)){
									if(this.$lib.validate('plus',node.nums)){
										if(node.nums!=0){
											let relation=this.$lib.unitRelation(node.name,goods.units,this);
											let compute=this.$calc.chain(node.nums).multiply(relation.multiple).done();
											nums=this.$calc.chain(nums).add(compute).done();
										}
									}else{
										obj.diff=0;
										obj.diffAlias='';
										effect==true&&(effect=goods.name+"盘点数不正确!");
										continue loop;
									}
								}
							}
							if(nums==0){
								obj.diff=0;
								obj.diffAlias='';
							}else{
								let diff=this.$calc.chain(nums).subtract(obj.basis).done();
								let diffAbs=this.$calc.chain(diff).abs().done();
								let diffAlias=this.$lib.unitSwitch(diffAbs,goods.units,this);
								obj.diff=diff;
								obj.diffAlias=diff>0?diffAlias:'-'+diffAlias;
								this.summary.push({
									goods:goods.id,
									attr:row.hasOwnProperty('pid')?row.name:'',
									warehouse:obj.warehouse,
									unit:goods.units[0].source,
									nums:diff
								});
							}
						}else{
							if(this.$lib.validate('empty',obj.enter)){
								obj.diff=0;
								obj.diffAlias='';
							}else{
								if(this.$lib.validate('plus',obj.enter)){
									let diff=this.$calc.chain(obj.enter).subtract(obj.basis).done();
									obj.diff=diff;
									obj.diffAlias=diff;
									this.summary.push({
										goods:goods.id,
										attr:row.hasOwnProperty('pid')?row.name:'',
										warehouse:obj.warehouse,
										unit:'',
										nums:diff
									});
									
								}else{
									obj.diff=0;
									obj.diffAlias='';
									effect==true&&(effect=goods.name+"盘点数不正确!");
									continue;
								}
							}
						}
					}
				}
				return effect;
			},
			//盘点
			build(){
				let effect=this.unitHandle();
				if(effect==true){
					this.dialog=true;
				}else{
					this.$message({
						type: "warning",
						message: effect
					});
				}
			},
			//盘盈单
			buildEntry(){
				let info=[];
				for (let item of this.summary) {
					if(item.nums>0){
						info.push(item);
					}
				}
				if(info.length==0){
					this.$message({
						type: "warning",
						message: "未匹配到盘盈单数据"
					});
				}else{
					this.$axios.post("inventory/buildEntry", {
						info:info
					}).then(result => {
						if (result.state == "success") {
							this.dialog=false;
							this.$bus.emit('switchPage','entry',true);//切换页面
							//等待页面切换完成
							setTimeout(()=>{
								this.$bus.emit('buildEntry',result.info);//生成单据
							},600);
						} else if (result.state == "warning") {
							this.$message({
								type: "warning",
								message: result.info
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
			},
			//盘亏单
			buildExtry(){
				let info=[];
				for (let item of this.summary) {
					if(item.nums<0){
						info.push(item);
					}
				}
				if(info.length==0){
					this.$message({
						type: "warning",
						message: "未匹配到盘亏单数据"
					});
				}else{
					this.$axios.post("inventory/buildExtry", {
						info:info
					}).then(result => {
						if (result.state == "success") {
							this.dialog=false;
							this.$bus.emit('switchPage','extry',true);//切换页面
							//等待页面切换完成
							setTimeout(()=>{
								this.$bus.emit('buildExtry',result.info);//生成单据
							},600);
						} else if (result.state == "warning") {
							this.$message({
								type: "warning",
								message: result.info
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
			},
			//单位名称点击
			unitNameClick(e){
				e.target.previousSibling.focus();
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
	.unitGroup{
		position: absolute;
		top: 0;
		left: 0;
		width: 100%;
		height: 100%;
		display: flex;
		justify-content: center;
	}
	.unitItem{
		position: relative;
		width: 100%;
	}
	.unitItem input{
		padding: 0 25px 0 0;
		font-size: 12px;
	}
	.unitItem span{
		position: absolute;
		right: 0;
		line-height: 40px;
		width: 25px;
		text-align: center;
	}
</style>
