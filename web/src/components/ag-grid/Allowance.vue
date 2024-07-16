<template>
	<div class="allowance">
		<div class="title">
			<span>{{title}} </span>
			<el-popover popper-class="allowancePopper" ref="popover"  @show="showPopper">
				<i class="el-icon-sort" slot="reference"></i>
				<el-input ref="taxInput" v-model="value" @keydown.enter.native="save" placeholder="折扣率" size="mini" clearable>
					<template slot="append">
						<span class="btn" @click="save">保存</span>
					</template>
				</el-input>
			</el-popover>
		</div>
	</div>
</template>
<script>
	//头组件|批量折扣率
	import Vue from 'vue';
    export default Vue.extend({
        name: 'Allowance',
        data() {
            return {
				title:'',
				value:''
            }
        },
        created(){
			this.title=this.params.dispose.title;
        },
        methods:{
			save(){
				if(this.$lib.validate('percentage',this.value)){
					this.params.api.forEachNode((node) => {
						if(node.data.key!=null){
							this.$grid.updateGridData(this.params,node.data.uniqid,this.params.dispose.key,this.value);
						}
					});
					this.params.context.runHandleGrid();
					this.$refs.popover.showPopper=false;
					this.$message({
						type: "success",
						message: "批量设置完成!"
					});
				}else{
					this.$message({
						type: "warning",
						message: "折扣率不正确!"
					});
				}
			},
			showPopper(){
				this.$nextTick(()=>{
					this.$refs.taxInput.focus();
				});
			}
        }
    })
</script>
<style>
	.allowancePopper{
		width: 160px;
	}
	.allowancePopper .el-input-group__append{
		padding: 0 12px;
	}
	.allowancePopper .el-input-group__append span{
		cursor: pointer;
	}
</style>
<style scoped>
	.allowance{
		width: 100%;
	}
	.title{
		text-align: center;
	}
</style>