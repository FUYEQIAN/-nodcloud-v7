<template>
	<div class="inputGroup">
		<input class="ag-cell-edit-input" type="text" v-model="value" ref="input">
		<template v-if="hasSource">
			<el-popover class="popoverTag" popper-class="agCpePopover" trigger="click" @show="getCost">
				<div slot="reference">
					<i class="el-icon-collection-tag"></i>
				</div>
				<el-table :data="cost" height="80px" size="mini" border>
					<el-table-column prop="extension.type" label="单据类型" align="center" width="90px"></el-table-column>
					<el-table-column prop="sourceData.number" label="单据编号" align="center" width="180px"></el-table-column>
					<el-table-column prop="ietData.name" label="支出名称" align="center" width="120px"></el-table-column>
					<el-table-column prop="uat" label="未结算金额" align="center" width="90px"></el-table-column>
				</el-table>
			</el-popover>
		</template>
	</div>
</template>
<script>
	//内容组件|支出类别
	import Vue from 'vue';
    export default Vue.extend({
        name: 'Cpe',
        data() {
            return {
                value: '',
				cost:[]
            }
        },
        created(){
			this.value = this.params.value;
        },
		computed: {
			hasSource(){
				if(this.params.node.data.source==0){
					return false;
				}else{
					return true
				}
			}
		},
        methods:{
			//获取数据
			getCost(){
				let source=this.params.node.data.source;
				this.$axios.post("service/getCost",{
					cost:source
				}).then(result => {
					if (result.state == "success") {
						this.cost=[result.info];
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
			//返回内容
            getValue(){
				return this.value;
            }
        },
		mounted() {
			this.$nextTick(()=>{
				this.$refs.input.focus();
				this.$refs.input.select();
			});
		}
    })
</script>
<style>
	.agCpePopover{
		min-width: auto;
		padding: 6px;
	}
</style>
<style scoped>
	.inputGroup{
		position: relative;
		width: 100%;
		height: 100%;
	}
	.popoverTag{
		position: absolute;
		right: 6px;
		top: 6px;
		line-height: initial;
	}
	.costText{
		font-size: 12px;
		cursor: default;
	}
</style>