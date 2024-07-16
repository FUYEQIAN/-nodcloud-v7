<template>
	<div class="goodsList">
		<el-dialog :visible.sync="dialog" title="商品列表" width="720px" @closed="close" :append-to-body="true" v-madeDialog>
			<el-table
				class="dataTable gridTable"
				height="300px"
				ref="dataTable"
				:data="tableData"
				:indent="0"
				:tree-props="{children: 'attr'}"
				@row-click="rowClick"
				header-row-class-name="dataTableHeader"
				row-key="key"
				size="mini"
				border
				v-madeTable
			>
				<el-table-column type="selection" align="center" width="39px" fixed="left"></el-table-column>
				<el-table-column prop="name" label="商品名称" align="center" width="160px" fixed="left"></el-table-column>
				<el-table-column prop="number" label="商品编号" align="center" width="120px"></el-table-column>
				<el-table-column prop="spec" label="规格型号" align="center" width="120px"></el-table-column>
				<el-table-column prop="categoryData.name" label="商品分类" align="center" width="120px"></el-table-column>
				<el-table-column prop="brand" label="商品品牌" align="center" width="120px"></el-table-column>
				<el-table-column prop="extension.unit" label="商品单位" align="center" width="90px"></el-table-column>
				<el-table-column prop="buy" label="采购价格" align="center" width="90px"></el-table-column>
				<el-table-column prop="sell" label="销售价格" align="center" width="90px"></el-table-column>
				<el-table-column prop="code" label="商品条码" align="center" width="120px"></el-table-column>
				<el-table-column prop="data" label="备注信息 " align="center" min-width="120px"></el-table-column>
				<el-table-column prop="nums" align="center" width="50px" fixed="right">
					<template slot="header">
						<i class="el-icon-edit-outline"></i>
					</template>
					<template slot-scope="scope">
						<template v-if="scope.row.hasOwnProperty('pid') || scope.row.attr.length==0">
							<input type="text" v-model="scope.row.nums" @input="numsChange(scope.row)"></input>
						</template>
						<template v-else>
							<span>-</span>
						</template>
					</template>
				</el-table-column>
			</el-table>
			<el-pagination class="tablePagination" :current-page.sync="page.current" :total="page.total" :page-size.sync="page.size"
			 :page-sizes="page.sizes" :pager-count="page.count" @size-change="record(1)" @current-change="record(0)" layout="prev,pager,next,jumper,sizes,total">
			</el-pagination>
			<span slot="footer" class="dialog-footer">
				<el-popover ref="searchPopover" popper-class="searchPopover" placement="top-start">
					<el-form class="searchFrom" ref="searchFrom" inline>
						<el-form-item>
							<el-input placeholder="名称 | 编号 | 型号" v-model="searchFrom.mate" clearable></el-input>
						</el-form-item>
						<el-form-item>
							<el-input placeholder="请输入商品条码" v-model="searchFrom.code" clearable></el-input>
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
				<el-button @click="dialog = false">取消</el-button>
				<el-button @click="choice" type="primary">选择</el-button>
			</span>
		</el-dialog>
	</div>
</template>
<script>
	import NodTree from "@/components/lib/NodTree";
	export default {
		name: "GoodsList",
		components:{
			NodTree
		},
		props: {
			parm:{
				required: true,
				type: Object
			}
		},
		data() {
			return {
				dialog:true,
				tableData:[],
				page: {
					current: 1,
					total: 0,
					size: 30,
					sizes: [30, 60, 90, 150, 300],
					count: 5
				},
				searchFrom: {
					mate: "",
					code:"",
					category:null,
					brand:"",
					type:"",
					data: ""
				}
			}
		},
		created() {
			this.searchFrom=Object.assign({},this.searchFrom,this.parm);
			this.record(1); //获取数据
		},
		computed: {
			//读取数据中心
			store() {
				return this.$store.state;
			}
		},
		methods: {
			//搜索商品
			record(page){
				page==0||(this.page.current=page);
				let parm = Object.assign({
					page: this.page.current,
					limit: this.page.size
				}, this.searchFrom);
				this.$axios.post("service/goodsRecord",parm).then(result => {
					if (result.state == "success") {
						//树结构处理
						for (let item of result.info) {
							item.nums='';
							if(item.attr.length>0){
								for (let attr of item.attr) {
									attr.nums='';
								}
							}
						}
						this.tableData = result.info;
						this.page.total = result.count;
						this.$refs["searchPopover"].showPopper=false;
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
			//数量改变
			numsChange(row){
				if(this.$lib.validate('empty',row.nums)){
					this.$refs.dataTable.toggleRowSelection(row,false);
				}else{
					this.$refs.dataTable.toggleRowSelection(row,true);
				}
			},
			//选择商品
			choice(){
				//获取选中数据
				let selection=this.$refs.dataTable.selection;
				if(selection.length>0){
					//数据处理
					let source=[];
					for (let item of selection) {
						//构造节点数据
						let record;
						if(item.hasOwnProperty('pid')){
							record=this.$lib.extend(true,{},this.tableData.find(obj=>obj.id==item.pid),{
								attr:item.name,
								buy:item.buy,
								sell:item.sell,
								retail:item.retail,
								code:item.code,
								nums:item.nums
							});
						}else{
							record=this.$lib.extend(true,{},item,{attr:''});
						}
						//数量处理
						if(this.$lib.validate('empty',record.nums)){
							record.nums=1;
						}else{
							if(!this.$lib.synValidate('nums',record.nums)){
								this.$message({type: "warning",message: "商品[ "+record.name+(record.attr==''?'':' | '+record.attr)+" ]数量不正确!"});
								return false;
							}
						}
						source.push(record);
					}
					this.dialog = false;
					this.$emit('choice',source);
				}else{
					this.$message({
						type: "warning",
						message: "您还未选择商品数据!"
					});
				}
			},
			//行单击事件
			rowClick(row){
				if(row.hasOwnProperty('pid')){
					this.$refs.dataTable.toggleRowSelection(row);
				}else{
					if(row.attr.length>0){
						this.$refs.dataTable.toggleRowExpansion(row);
					}else{
						this.$refs.dataTable.toggleRowSelection(row);
					}
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
	/* 头组件隐藏图标 */
	.dataTable >>> .dataTableHeader .el-table-column--selection .cell{
		display: none;
	}
	/* 父节点隐藏图标 */
	.dataTable >>> .el-table__row--level-0 .el-table-column--selection .cell{
		display: none;
	}
	/* 默认折叠图标前移 */
	.dataTable >>> .el-table__expand-icon{
		position: absolute;
		left: -30px;
		top: 10px;
	}
	/* 子节点加图标 */
	.dataTable >>> .el-table__placeholder{
		font-style: normal;
	    font-family: element-icons!important;
	}
	.dataTable >>> .el-table__placeholder:before {
	    content: "\e791";
	}
	/* 子节点背景颜色 */
	.dataTable >>> .el-table__body .el-table__row--level-1{
		background-color: #fcfcfc;
	}
	/* 操作按钮浮动 */
	.dialog-footer>span{
		float: left;
	}
</style>
