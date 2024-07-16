<template>
	<el-popover ref="popover" placement="top-start" width="420" trigger="click" class="nodeIet" @show="check" readonly>
		<el-table :data="table" size="mini" class="gridTable" border>
			<el-table-column label="支出类别" align="center" min-width="120px">
				<template slot-scope="scope">
					<select v-model="scope.row.iet">
						<template v-for="item in source" @change="change">
							<option :value="item.id">{{item.name}}</option>
						</template>
					</select>
				</template>
			</el-table-column>
			<el-table-column label="金额" align="center" width="100px">
				<template slot-scope="scope">
					<input type="text" v-model="scope.row.money" placeholder="金额" @input="change"></input>
				</template>
			</el-table-column>
			<el-table-column label="备注" align="center" width="100px">
				<template slot-scope="scope">
					<input type="text" v-model="scope.row.data" placeholder="备注" @input="change"></input>
				</template>
			</el-table-column>
			<el-table-column align="center" width="80px">
				<template slot="header" slot-scope="scope">
					<span>操作</span> <i class="el-icon-circle-plus-outline" @click="addIet"></i>
				</template>
				<template slot-scope="scope">
					<i class="el-icon-delete" @click="delIet(scope.$index)"></i>
				</template>
			</el-table-column>
		</el-table>
		<el-input slot="reference" v-model="text" :disabled="true" suffix-icon="el-icon-tickets"></el-input>
	</el-popover>
</template>
<script>
	export default {
		name: "NodIet",
		props: {
			value:{
				required: true,
				type:Array
			},
			type:{
				required: true
			}
		},
		data() {
			return {
				text:0,
				table:[],
				source:[]
			}
		},
		created(){
			this.table=[].concat(this.value);
			this.source=this.store.iet[this.type];
		},
		computed: {
			//读取数据中心
			store() {
				return this.$store.state;
			}
		},
		watch: {
			value:{
				handler(val){
					if(!this.$lib.comparison(val,this.table)){
						this.table=[].concat(val);
						this.change();
					}
				},
				deep: true
			}
		},
		methods: {
			//显示验证
			check(){
				if(this.source.length==0){
					this.$refs["popover"].showPopper=false;
					this.$message({type: "warning",message:"未设置收支类别"});
				}
			},
			//添加类别
			addIet(){
				this.table.push({iet:this.source[0].id,money:'',data:''});
				this.change();
			},
			//删除类别
			delIet(index){
				this.table.splice(index,1);
				this.change();
			},
			//数据改变
			change(){
				let text=this.$calc.chain(0);
				for (let row of this.table) {
					if(this.$lib.synValidate('numerical',row.money)){
						text.add(row.money);
					}
				}
				this.text=text.done();
				this.$emit('input',this.table);
				this.$emit('change',this.text);
			}
		}
	};
</script>
<style scoped>
	.nodeIet >>> .el-input__inner{
		background: #fff;
	}
</style>