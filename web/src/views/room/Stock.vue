<template>
	<div class="stock area">
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
						<el-select v-model="searchFrom.state" placeholder="查询类型">
							<el-option label="常规库存" :value="0"></el-option>
							<el-option label="非零库存" :value="1"></el-option>
							<el-option label="预警库存" :value="2"></el-option>
						</el-select>
					</el-form-item>
					<el-divider></el-divider>
					<el-button class="searchBtn" icon="el-icon-search" @click="record(1)" ></el-button>
				</el-form>
				<el-button slot="reference" icon="el-icon-more"></el-button>
			</el-popover>
			<el-button-group>
				<el-button @click="exports">导出</el-button>
				<el-button @click="reload">刷新</el-button>
			</el-button-group>
		</div>
		<el-divider></el-divider>
		<el-table
			:data="tableData"
			:tree-props="{children: 'attr'}"
			row-key="key"
			height="calc(100% - 90px)"
			border
			v-madeTable
		>
			<el-table-column prop="name" label="商品名称" align="left" width="160px" fixed="left"></el-table-column>
			<el-table-column prop="summary" label="库存数量" align="center" width="120px"></el-table-column>
			<el-table-column label="仓库" align="center">
				<div v-for="column in tableColumn" :key="column.key">
					<el-table-column :prop="column.key" :label="column.name" align="center" width="90px"></el-table-column>
				</div>
			</el-table-column>
			<el-table-column prop="stock" label="预警阈值" align="center" width="120px"></el-table-column>
			<el-table-column prop="number" label="商品编号" align="center" width="160px"></el-table-column>
			<el-table-column prop="spec" label="规格型号" align="center" width="160px"></el-table-column>
			<el-table-column prop="categoryData.name" label="商品分类" align="center" width="120px"></el-table-column>
			<el-table-column prop="brand" label="商品品牌" align="center" width="120px"></el-table-column>
			<el-table-column prop="extension.unit" label="商品单位" align="center" width="120px"></el-table-column>
			<el-table-column prop="code" label="商品条码" align="center" width="160px"></el-table-column>
			<el-table-column prop="data" label="商品备注" align="center" width="160px"></el-table-column>
			<el-table-column prop="set" label="相关操作" align="center" min-width="100px" fixed="right">
				<template slot-scope="scope">
					<el-button @click="showStockDetail(scope.row)" size="mini">详情</el-button>
				</template>
			</el-table-column>
		</el-table>
		<el-pagination class="tablePagination" :current-page.sync="page.current" :total="page.total" :page-size.sync="page.size"
		 :page-sizes="page.sizes" :pager-count="page.count" @size-change="record(1)" @current-change="record(0)" layout="prev,pager,next,jumper,sizes,total">
		</el-pagination>
		<StockDetail v-if="stockDetailDialog.show" :condition="stockDetailDialog.condition" @destroy="stockDetailDialog.show=false"></StockDetail>
	</div>
</template>
<script>
	import NodTree from "@/components/lib/NodTree";
	import StockDetail from "@/components/dialog/StockDetail";
	export default {
		name: "Stock",
		components: {
			NodTree,
			StockDetail
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
					state:0
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
				stockDetailDialog:{
					show:false,
					condition:{}
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
				this.$axios.post("stock/record", parm).then(result => {
					if (result.state == "success") {
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
					window.open(this.$base.web+'stock/exports?'+parm)
				}, 1000);
			},
			//库存详情
			showStockDetail(row){
				let goods=row.hasOwnProperty('pid')?this.tableData.find(item=>item.id==row.pid):row;
				let obj=row.hasOwnProperty('pid')?{goods:row.pid,attr:row.name}:(row.attr.length>0?{goods:row.id}:{goods:row.id,attr:''});
				obj.warehouse=this.tableColumn.map(item=>item.id);
				this.stockDetailDialog.condition=obj;
				this.stockDetailDialog.show=true;
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
	.el-table >>> .el-table__row--level-1{
		background: #fcfcfc;
	}
</style>
